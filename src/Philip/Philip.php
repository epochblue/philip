<?php

namespace Philip;

use Philip\Action,
    Philip\IRC\Request,
    Philip\IRC\Response;

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

    /** @var array $events Events and their handlers */
    private $events;

    /**
     * Constructor.
     *
     * @param array $config The configuration for the bot
     */
    public function __construct($config = array())
    {
        $this->config = $config;
        $this->events = array(
            'privmsg.channel' => array(),
            'privmsg.private' => array(),
            'ping'     => array(),
            'join'     => array(),
            'part'     => array(),
            'error'    => array(),
            'notice'   => array(),
        );

        $this->addDefaultHandlers();
    }

    /**
     * Destructor; ensure the socket gets closed.
     */
    public function __destruct()
    {
        if (isset($this->socket)) {
            fclose($this->socket);
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
        $this->onEvent('privmsg.channel', new Action($pattern, $callback));
    }

    /**
     * Adds an event handler to the list when private messages come in.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onPrivateMessage($pattern, $callback)
    {
        $this->onEvent('privmsg.private', new Action($pattern, $callback));
    }

    /**
     * Adds event handlers to the list for both channel messages and private messages.
     *
     * @param string   $pattern  The RegEx to test the message against
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onMessages($pattern, $callback)
    {
        $this->onEvent('privmsg.channel', new Action($pattern, $callback));
        $this->onEvent('privmsg.private', new Action($pattern, $callback));
    }

    /**
     * Adds event handlers to the list for JOIN messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onJoin($callback)
    {
        $this->onEvent('join', new Action(null, $callback));
    }

    /**
     * Adds event handlers to the list for PART messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onPart($callback)
    {
        $this->onEvent('part', new Action(null, $callback));
    }

    /**
     * Adds event handlers to the list for ERROR messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onError($callback)
    {
        $this->onEvent('error', new Action(null, $callback));
    }

    /**
     * Adds event handlers to the list for NOTICE messages.
     *
     * @param callable $callback The callback to run if the pattern matches
     */
    public function onNotice($callback)
    {
        $this->onEvent('notice', new Action(null, $callback));
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
     * Loads a plugin. See the README for plugin documentation.
     *
     * @param string $name The name of the plugin to load
     */
    public function loadPlugin($name)
    {
        $plugin_class = $name . 'Plugin';
        $path = 'plugins/' . $plugin_class . '.php';
        if (file_exists($path)) {
            require($path);
        }

        $n = "\\" . $plugin_class;
        $plugin = new $n($this);
        $plugin->init();
    }

    /**
     * Loads multiple plugins in a single call.
     *
     * @param array $names The names of the plugins to load.
     */
    public function loadPlugins($names)
    {
        foreach ($names as $name) {
            $this->loadPlugin($name);
        }
    }

    /**
     * Determins if the given user is an admin.
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
     * Adds an action to the list of possible actions when an event is fired.
     *
     * @param string $event  The Event to listen for
     * @param Action $action The action to run when the event is fired
     */
    private function onEvent($event, $action)
    {
        $this->events[$event][] = $action;
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
                $req   = $this->receive($data);
                $event = strtolower($req->getCommand());
                $msg   = $req->getMessage();

                if ($event === 'privmsg') {
                    $event .= $req->isPrivateMessage() ? '.private' : '.channel';
                }

                // Skip processing anything if the event is unknown or the user sending
                // the message is actually the bot
                if (!isset($this->events[$event]) || ($req->getSendingUser() === $this->config['nick'])) {
                    continue;
                }

                $responses = array();
                foreach($this->events[$event] as $action) {
                    if ($action->isExecutable($msg)) {
                        if ($response = $action->executeCallback(array($req, $action->getMatches()))) {
                            $responses[] = $response;
                        }
                    }
                }

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
        fwrite(STDOUT, '--> ' . $raw);
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
            fwrite($this->socket, ($response . "\r\n"));
            fwrite(STDOUT, '<-- ' . $response . PHP_EOL);
        }
    }

    /**
     * Loads default event handlers for basic IRC commands.
     */
    private function addDefaultHandlers()
    {
        // When the server PINGs us, just respond with PONG and the server's host
        $pingAction = new Action(null, function($request, $params) {
            return Response::pong($request->getMessage());
        });

        // If an Error message is encountered, just log it for now.
        $errorAction = new Action(null, function($request, $params) {
            fwrite(STDOUT, "ERROR: {$request->getMessage()}" . PHP_EOL);
        });

        $this->onEvent('ping', $pingAction);
        $this->onEvent('error', $errorAction);
    }
}
