<?php

/**
 * Philip
 *
 * PHP Version 5.3
 *
 * @package    philip
 * @copyright  2012, Bill Israel <bill.israel@gmail.com>
 */
namespace Philip\IRC;

/**
 * A representation of an IRC request message.
 *
 * @author Bill Israel <bill.israel@gmail.com>
 */
class Request
{
    const RE_MSG = '/^
        (?:
            \:(?P<prefix>
                (?P<server>[^\s!]*)
                (?:!~?(?P<user>[^\s]+)@(?P<host>[^\s]+))?
            )\s+
        )?
        (?P<command>[a-zA-Z]+|[0-9]{3})
        (?:\s+(?P<channel>[#&!+]+[^\x07\x2C\s]{0,200}))?
        (?:(?P<params>(?:\s+[^:][^\s]*)*))?
        (?:\s+\:(?P<message>[^\r\n]*))?
        \r?\n?
    $/x';

    // Member Vars
    private $raw;
    private $prefix;
    private $server;
    private $user;
    private $host;
    private $cmd;
    private $channel;
    private $params;
    private $message;

    /**
     * Constructor.
     *
     * @param string $raw The raw IRC Request to parse
     */
    public function __construct($raw)
    {
		$this->raw = $raw;
        $matches = array();

        if (preg_match(self::RE_MSG, $raw, $matches)) {
            $this->prefix   = $matches['prefix'];
            $this->server   = $matches['server'];
            $this->user     = $matches['user'];
            $this->host     = $matches['host'];
            $this->cmd      = $matches['command'];
            $this->channel  = $matches['channel'];

            if (!empty($matches['params'])) {
                $this->params = explode(' ', trim($matches['params']));
            } else {
                $this->params = array();
            }

            if (isset($matches['message'])) {
                $this->message  = $matches['message'];
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid command: %s', $raw));
        }
    }

    /**
     * Returns the sent command.
     *
     * @return string The IRC command in the request
     */
    public function getCommand()
    {
        return $this->cmd;
    }

    /**
     * Returns the parameters from the request.
     *
     * @return array The parameters in the request (minus the trailing param)
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns the message portion of the request.
     *
     * @return string The message/trailing part of the request
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the source of the message. If it was a PM, the source
     * will be a user's nick. If it was a message in a channel, it'll
     * be the channel name.
     *
     * @return string The sending user's nick, or the channel name
     */
    public function getSource()
    {
        if ($this->isPrivateMessage()) {
            return $this->getSendingUser();
        }

        return $this->channel;
    }

    /**
     * Returns the sending user's nick, false otherwise.
     *
     * @return mixed The sending user's nick, or false if it wasn't sent by a user
     */
    public function getSendingUser()
    {
        if ($this->isFromUser()) {
            return $this->server;
        }

        return false;
    }

    /**
     * Return the sending server if it was sent by a server, false otherwise.
     *
     * @return mixed The sending server, or false if it wasn't sent by a server
     */
    public function getServer()
    {
        if ($this->isFromServer()) {
            return $this->prefix;
        }

        return false;
    }

    /**
     * Returns true if the message is a private message.
     *
     * @return bool True if the message is a private one
     */
    public function isPrivateMessage()
    {
        return empty($this->channel);
    }

    /**
     * Returns true if the message was sent by a user.
     *
     * @return bool True if the request was from a user, false otherwise
     */
    public function isFromUser()
    {
        return !empty($this->user);
    }

    /**
     * Returns true if the message was sent from a server.
     *
     * @return bool True if the request was from a server, false otherwise
     */
    public function isFromServer()
    {
        return !$this->isFromUser();
    }

    public function getHost()
    {
        return $this->host;
    }
}
