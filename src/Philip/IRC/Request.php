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
    // IRC Message Constants
    private static $PREFIX   = 1;
    private static $COMMAND  = 2;
    private static $MIDDLE   = 3;
    private static $TRAILING = 4;

    // IRC User Prefix Constants
    private static $NICK = 1;
    private static $USER = 2;
    private static $HOST = 3;

    // Saves 4 parts: <prefix> <command> <middle params> <trailing param>
    private static $RE_MSG = '/^(?:[:@]([^\\s]+) )?([^\\s]+)(?: ((?:[^:\\s][^\\s]* ?)*))?(?: ?:(.*))?$/';

    // Saves 3 parts: <nick> <username> <hostname>
    private static $RE_SENDER = '/^([^!@]+)!(?:[ni]=)?([^@]+)@([^ ]+)$/';

    // Member Vars
    private $raw;
    private $prefix;
    private $cmd;
    private $middle;
    private $trailing;


    /**
     * Constructor.
     *
     * @param string $raw The raw IRC Request to parse
     */
    public function __construct($raw)
    {
		$this->raw = $raw;

        $matches = array();
        preg_match(self::$RE_MSG, $raw, $matches);

		// Remove newlines and carriage returns
		$count = count($matches);
		for($i = $count - 1; $i >= 0; $i--) {
			$matches[$i] = str_replace(array(chr(10), chr(13)), '', $matches[$i]); 
		}

		if ($count) {
			$this->prefix   = $matches[self::$PREFIX];
			$this->cmd      = $matches[self::$COMMAND];
			$this->middle   = $matches[self::$MIDDLE] ? explode(' ', $matches[self::$MIDDLE]) : null;
			$this->trailing = $matches[self::$TRAILING] ?: null;
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
		if (is_array($this->middle)) {
			return $this->middle;
		}

		return array();
	}

	/**
	 * Returns the message portion of the request.
	 *
	 * @return string The message/trailing part of the request
	 */
    public function getMessage()
    {
		if ($this->trailing) {
			return $this->trailing;
		}

		return '';
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

		return $this->middle[0];
	}

	/**
	 * Returns the sending user's nick, false otherwise.
	 *
	 * @return mixed The sending user's nick, or false if it wasn't sent by a user
	 */
    public function getSendingUser()
    {
		if ($this->isFromUser()) {
			$matches = array();
			preg_match(self::$RE_SENDER, $this->prefix, $matches);

			return $matches[self::$NICK];
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
		return isset($this->middle[0]) && !$this->isChannel($this->middle[0]);	
	}

	/**
	 * Returns true if the message was sent by a user.
	 *
	 * @return bool True if the request was from a user, false otherwise
	 */
    public function isFromUser()
    {
		return (bool) preg_match(self::$RE_SENDER, $this->prefix);
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

	/**
	 * Determines whether the given string is a channel name.
	 *
	 * @param string $str The string to test
	 * @return bool True if the string is a channel name, false otherwise
	 */
    private function isChannel($str)
    {
		// Channels can start with #, &, !, or + (and have more than 1 of them)
		return strspn($str, '#&!+', 0, 1) >= 1;
	}
}
