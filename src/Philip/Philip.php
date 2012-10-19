<?php

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

    /** @var EventDispatcher $dispatcher The event mediator */
    private $dispatcher;

    /** @var Logger $log The log to write to, if debug is enabled */
    private $log;

    /** @var string $pidfile The location to write to, if write_pidfile is enabled */
    private $pidfile;

    /**
     * Constructor.
     *
     * @param array $config The configuration for the bot
     */
    public function __construct($config = array())
    {
        $this->config = $config;
        $this->dispatcher = new EventDispatcher();

        $this->setupLogger();
        $this->setupPidfile();
        $this->addDefaultHandlers();
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

        if ( isset($this->config['write_pidfile']) ) {
            if ( $this->config['write_pidfile'] === true ) {
                unlink( $this->pidfile );
            }
        }
    }


    /**
     * Creates a pid file if 'pid' is set in configuration
     */
    public function setupPidfile()
    {
        if(isset($this->config['write_pidfile']) && $this->config['write_pidfile'] === true ) {
            if( isset($this->config['pidfile'])) {
                $this->pidfile = $this->config['pidfile'];
            } else {
                $this->pidfile = sprintf("%s/philip.pid", __DIR__);
            }

        $pidfile = fopen($this->pidfile, 'w') or die("can't open file");
        fwrite($pidfile, getmypid());
        fclose($pidfile);
        }
    }

    /**
     * Adds an event handler to the list for when someone talks in a channel.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onChannel($pattern, $callback)
    {
        $handler = new EventListener($pattern, $callback);
        $this->dispatcher->addListener('message.channel', array($handler, 'testAndExecute'));
    }

    /**
     * Adds an event handler to the list when private messages come in.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onPrivateMessage($pattern, $callback)
    {
        $handler = new EventListener($pattern, $callback);
        $this->dispatcher->addListener('message.private', array($handler, 'testAndExecute'));
    }

    /**
     * Adds event handlers to the list for both channel messages and private messages.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onMessages($pattern, $callback)
    {
        $handler = new EventListener($pattern, $callback);
        $this->dispatcher->addListener('message.channel', array($handler, 'testAndExecute'));
        $this->dispatcher->addListener('message.private', array($handler, 'testAndExecute'));
    }

    /**
     * Adds event handlers to the list for JOIN messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onJoin($callback)
    {
        $handler = new EventListener(null, $callback);
        $this->dispatcher->addListener('server.join', array($handler, 'testAndExecute'));
    }

    /**
     * Adds event handlers to the list for PART messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onPart($callback)
    {
        $handler = new EventListener(null, $callback);
        $this->dispatcher->addListener('server.part', array($handler, 'testAndExecute'));
    }

    /**
     * Adds event handlers to the list for ERROR messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onError($callback)
    {
        $handler = new EventListener(null, $callback);
        $this->dispatcher->addListener('server.error', array($handler, 'testAndExecute'));
    }

    /**
     * Adds event handlers to the list for NOTICE messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onNotice($callback)
    {
        $handler = new EventListener(null, $callback);
        $this->dispatcher->addListener('server.notice', array($handler, 'testAndExecute'));
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
     * Loads a plugin. See the README for plugin documentation.
     *
     * @param string $name The fully-qualified classname of the plugin to load
     *
     * @throws \InvalidArgumentException
     */
    public function loadPlugin($classname)
    {
        if (class_exists($classname) && $plugin = new $classname($this)) {
            if (!$plugin instanceof AbstractPlugin) {
                throw new \InvalidArgumentException('Class must be an instance of \Philip\AbstractPlugin');
            }

            $plugin->init();
        }
    }

    /**
     * Loads multiple plugins in a single call.
     *
     * @param array $names The fully-qualified classnames of the plugins to load.
     */
    public function loadPlugins($classnames)
    {
        foreach ($classnames as $classname) {
            $this->loadPlugin($classname);
        }
    }

    /**
     * Determines if the given user is an admin.
     *
     * @param string $user The username to test
     * @return boolean True if the user is an admin, false otherwise
     */
    public function isAdmin($user) {
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
        } while (!feof($this->socket));
    }

    /**
     * Convert the raw incoming IRC message into a Request object
     *
     * @param string $raw The unparsed incoming IRC message
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
     * @param array $responses The responses to send back to the server
     */
    private function send($responses)
    {
        if (!is_array($responses)) {
            $responses = array($responses);
        }

        foreach ($responses as $response) {
            $response .= "\r\n";
            fwrite($this->socket, $response);
            $this->log->debug('<-- ' . $response);
        }
    }

    /**
     * Loads default event handlers for basic IRC commands.
     */
    private function addDefaultHandlers()
    {
        // When the server PINGs us, just respond with PONG and the server's host
        $pingHandler = new EventListener(null, function($event) {
            $event->addResponse(Response::pong($event->getRequest()->getMessage()));
        });

        // If an Error message is encountered, just log it for now.
        $log = $this->log;
        $errorHandler = new EventListener(null, function($event) use ($log) {
            $log->debug("ERROR: {$event->getRequest()->getMessage()}");
        });

        $this->dispatcher->addListener('server.ping', array($pingHandler, 'testAndExecute'));
        $this->dispatcher->addListener('server.error', array($errorHandler, 'testAndExecute'));
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
}
