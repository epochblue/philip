<?php

/**
 * Philip
 *
 * PHP Version 5.3
 *
 * @package    philip
 * @copyright  2012, Bill Israel <bill.israel@gmail.com>
 */
namespace Philip;

use Philip\EventListener;
use Philip\IRC\Event;
use Philip\IRC\Request;
use Philip\IRC\Response;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * A Slim-inspired IRC bot.
 *
 * @author Bill Israel <bill.israel@gmail.com>
 */
class Philip
{
    /** @var array $config The bot's configuration */
    private $config;

    /** @var resource $socket The socket for communicating with the IRC server */
    private $socket;

    /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher The event mediator */
    private $dispatcher;

    /** @var \Monolog\Logger $log The log to write to, if debug is enabled */
    private $log;

    /** @var string $pidfile The location to write to, if write_pidfile is enabled */
    private $pidfile;

    /** @var \Philip\AbstractPlugin[] */
    private $plugins = array();

    /** @var bool */
    private $askStop = false;

    /**
     * Constructor.
     *
     * @param array $config The configuration for the bot
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
     */
    public function __construct($config = array(), EventDispatcher $dispatcher = null)
    {
        $this->config = $config;
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->initialize();
    }

    /**
     * Destructor; ensure the socket gets closed.
     * Destroys pid file if set in config.
     */
    public function __destruct()
    {
        if (isset($this->socket)) {
            fclose($this->socket);
        }

        if (isset($this->config['write_pidfile']) && $this->config['write_pidfile']) {
            unlink($this->pidfile);
        }
    }


    /**
     * Adds an event handler to the list for when someone talks in a channel.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onChannel($pattern, $callback)
    {
        $handler = new EventListener($pattern, $callback);
        $this->dispatcher->addListener('message.channel', array($handler, 'testAndExecute'));

        return $this;
    }

    /**
     * Adds an event handler to the list when private messages come in.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onPrivateMessage($pattern, $callback)
    {
        $handler = new EventListener($pattern, $callback);
        $this->dispatcher->addListener('message.private', array($handler, 'testAndExecute'));

        return $this;
    }

    /**
     * Adds event handlers to the list for both channel messages and private messages.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onMessages($pattern, $callback)
    {
        return $this
            ->onChannel($pattern, $callback)
            ->onPrivateMessage($pattern, $callback)
        ;
    }

    /**
     * Adds event handlers to the list for JOIN messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onJoin($callback)
    {
        return $this->onServer('join', $callback);
    }

    /**
     * Adds event handlers to the list for PART messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onPart($callback)
    {
        return $this->onServer('part', $callback);
    }

    /**
     * Adds event handlers to the list for ERROR messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onError($callback)
    {
        return $this->onServer('error', $callback);
    }

    /**
     * Adds event handlers to the list for NOTICE messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     *
     * @return \Philip\Philip
     */
    public function onNotice($callback)
    {
        return $this->onServer('notice', $callback);
    }

    public function onServer($command, $callback)
    {
        $handler = new EventListener(null, $callback);
        $this->dispatcher->addListener('server.' . $command, array($handler, 'testAndExecute'));

        return $this;
    }

    /**
     * Return the configuration so plugins and external things can use it.
     *
     * @return array The bot's current configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns the logger, in case any handlers need to log.
     *
     * @return Logger An instance of a Monolog logger
     */
    public function getLogger()
    {
        return $this->log;
    }

    /**
     * Returns the location of the pid file.
     *
     * @return mixed Read-only file resource, or null if there was an error opening the file
     */
    public function getPidfile()
    {
        $resource = false;

        if (isset($this->pidfile) && is_readable($this->pidfile)) {
            $resource = fopen($this->pidfile, 'r');
        }

        return $resource ? $resource : null;
    }

    /**
     * Loads a plugin. See the README for plugin documentation.
     *
     * @param string $name The fully-qualified classname of the plugin to load
     *
     * @return \Philip\Philip
     */
    public function loadPlugin(AbstractPlugin $plugin)
    {
        $name = $plugin->getName();
        $this->log->addDebug('--- Loading plugin ' . $name . PHP_EOL);
        $plugin->init();
        $this->plugins[$name] = $plugin;

        return $this;
    }

    /**
     * Loads multiple plugins in a single call.
     *
     * @param \Philip\AbstractPlugin[] $plugins The fully-qualified classnames of the plugins to load.
     *
     * @return \Philip\Philip
     */
    public function loadPlugins(array $plugins)
    {
        foreach ($plugins as $plugin) {
            $this->loadPlugin($plugin);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return AbstractPlugin
     */
    public function getPlugin($name)
    {
        if (false === isset($this->plugins[$name])) {
            throw new \InvalidArgumentException(sprintf('Plugin %s is not registered'));
        }

        return $this->plugins[$name];
    }

    /**
     * Determines if the given user is an admin.
     *
     * @param  string  $user The username to test
     * @return boolean True if the user is an admin, false otherwise
     */
    public function isAdmin($user)
    {
        return in_array($user, $this->config['admins']);
    }

    /**
     * Starts the IRC bot.
     */
    public function run()
    {
        if ($this->connect()) {
            $this->login();
            $this->join();

            foreach ($this->plugins as $plugin) {
                $name = $plugin->getName();
                $this->log->addDebug('--- Booting plugin ' . $name . PHP_EOL);
                $plugin->boot(isset($this->config[$name]) ? $this->config[$name] : array());
            }

            $this->listen();
        }
    }

    /**
     * Connects to the IRC server.
     *
     * @return boolean True if the socket was created successfully
     */
    private function connect()
    {
        stream_set_blocking(STDIN, 0);
        $this->socket = fsockopen($this->config['hostname'], $this->config['port']);

        return (bool) $this->socket;
    }

    /**
     * Logs in to the IRC server with the user info in the config.
     */
    private function login()
    {
        $this->send(Response::nick($this->config['nick']));
        $this->send(Response::user(
            $this->config['nick'],
            $this->config['hostname'],
            $this->config['servername'],
            $this->config['realname']
        ));

        if (isset($this->config['password'])) {
            $this->send(Response::msg(
                'NickServ',
                'identify ' . $this->config['password']
            ));
        }
    }

    /**
     * Joins the channels specified in the config.
     */
    private function join()
    {
        if (!is_array($this->config['channels'])) {
            $this->config['channels'] = array($this->config['channels']);
        }

        foreach ($this->config['channels'] as $channel) {
            $this->send(Response::join($channel));
        }
    }

    /**
     * Driver of the bot; listens for messages, responds to them accordingly.
     */
    private function listen()
    {
        do {
            $data = fgets($this->socket, 512);
            if (!empty($data)) {
                $request   = $this->receive($data);
                $cmd       = strtolower($request->getCommand());

                if ($cmd === 'privmsg') {
                    $event_name = 'message.' . ($request->isPrivateMessage() ? 'private' : 'channel');
                } else {
                    $event_name = 'server.' . $cmd;
                }

                // Skip processing if the incoming message is from the bot
                if ($request->getSendingUser() === $this->config['nick']) {
                    continue;
                }

                $event = new Event($request);
                $this->dispatcher->dispatch($event_name, $event);
                $responses = $event->getResponses();

                if (!empty($responses)) {
                    $this->send($responses);
                }
            }
        } while (!feof($this->socket) && false === $this->askStop);
    }

    public function askStop()
    {
        $this->askStop = true;

        return $this;
    }

    /**
     * Convert the raw incoming IRC message into a Request object
     *
     * @param  string  $raw The unparsed incoming IRC message
     * @return Request The parsed message
     */
    private function receive($raw)
    {
        $this->log->debug('--> ' . $raw);

        return new Request($raw);
    }

    /**
     * Actually push data back into the socket (giggity).
     *
     * @param string|array $responses The responses to send back to the server
     */
    public function send($responses)
    {
        if (!is_array($responses)) {
            $responses = array($responses);
        }

        foreach ($responses as $response) {
            $response .= "\r\n";
            fwrite($this->socket, $response);
            $this->log->debug('<-- ' . $response);

            if (isset($this->config['unflood']['interval'])) {
                usleep($this->config['unflood']['interval']);
            }
        }

        if (isset($this->config['unflood']['delay'])) {
            usleep($this->config['unflood']['delay']);
        }
    }

    /**
     * Do some minor initialization work before construction is complete.
     */
    private function initialize()
    {
        $this->setupLogger();
        $this->writePidfile();
        $this->addDefaultHandlers();
    }

    /**
     * Sets up the logger, but only if debug is enabled.
     */
    private function setupLogger()
    {
        $this->log = new Logger('philip');
        if (isset($this->config['debug']) && $this->config['debug'] == true) {
            $log_path = isset($this->config['log']) ? $this->config['log'] : false;

            if (!$log_path) {
                throw new \Exception("If debug is enabled, you must supply a log file location.");
            }

            try {
                $format = "[%datetime% - %level_name%]: %message%";
                $handler = new StreamHandler($log_path, Logger::DEBUG);
                $handler->setFormatter(new LineFormatter($format));
                $this->log->pushHandler($handler);
            } catch (\Exception $e) {
                throw \Exception("Unable to open/read log file.");
            }
        } else {
            $this->log->pushHandler(new NullHandler());
        }
    }

    /**
     * If Philip is configured to write a pid file, open it, and write the pid into it.
     *
     * @throws \Exception If Philip is configured to write a pidfile, and
     *                    there's no 'pidfile' location in the configuration
     * @throws \Exception If Philip is unable to open the pidfile for writing
     */
    private function writePidfile()
    {
        if (isset($this->config['write_pidfile']) && $this->config['write_pidfile']) {
            if (!isset($this->config['pidfile'])) {
                throw new \Exception('Please supply a pidfile location.');
            }

            $this->pidfile = $this->config['pidfile'];

            if ($pidfile = fopen($this->pidfile, 'w')) {
                fwrite($pidfile, getmypid());
                fclose($pidfile);
            } else {
                throw new \Exception('Unable to open pidfile for writing.');
            }
        }
    }

    /**
     * Loads default event handlers for basic IRC commands.
     */
    private function addDefaultHandlers()
    {
        $log = $this->log;

        // When the server PINGs us, just respond with PONG and the server's host
        $this->onServer(
            'ping',
            function($event) {
                $event->addResponse(Response::pong($event->getRequest()->getMessage()));
            }
        );

        // If an Error message is encountered, just log it for now.
        $this->onError(
            function($event) use ($log) {
                $log->debug("ERROR: {$event->getRequest()->getMessage()}");
            }
        );

        $plugins = & $this->plugins;
        $help = function(Event $event) use (& $plugins) {
            foreach ($plugins as $plugin) {
                $plugin->displayHelp($event);
            }
        };

        $this->onMessages('/^!help$/', $help);
    }
}
