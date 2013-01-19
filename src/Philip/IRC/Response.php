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
 * A simplified representation of an IRC response object.
 *
 * @author Bill Israel <bill.israel@gmail.com>
 */
class Response
{
    /** @var array $args The IRC command response arguments */
    private $args;

    /**
     * Constructor.
     *
     * @param string $cmd  The IRC command to return
     * @param mixed  $args The arguments to send with it
     */
    public function __construct($cmd, $args = '')
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $args = array_map(function($x) { return trim($x); }, $args);
        $end = count($args) - 1;
        $args[$end] = ':' . $args[$end];

        $this->args = $args;
        array_unshift($this->args, strtoupper($cmd));
    }

    /**
     * Creates a PONG response.
     *
     * @param  string   $host The host string to send back
     * @return Response An IRC Response object
     */
    public static function pong($host)
    {
        return new self('PONG', $host);
    }

    /**
     * Creates a QUIT response.
     *
     * @param  string   $msg The quitting message
     * @return Response An IRC Response object
     */
    public static function quit($msg)
    {
        return new self('QUIT', $msg);
    }

    /**
     * Creates a JOIN response.
     *
     * @param  string   $channels The channels to join
     * @return Response An IRC Response object
     */
    public static function join($channels)
    {
        return new self('JOIN', $channels);
    }

    /**
     * Creates a PART response.
     *
     * @param  string   $channels The channels to leave
     * @return Response An IRC Response object
     */
    public static function leave($channels)
    {
        return new self('PART', $channels);
    }

    /**
     * Creates a PRIVMSG response.
     *
     * @param  string   $who  The channel/nick to send this msg to
     * @param  string   $what The messages to send
     * @return Response An IRC Response object
     */
    public static function msg($who, $what)
    {
        return new self('PRIVMSG', array($who, $what));
    }

    /**
     * Creates a NOTICE response.
     *
     * @param  string   $channel The channel to send the notice to.
     * @return Response An IRC Response object
     */
    public static function notice($channel, $msg)
    {
        return new self('NOTICE', array($channel, $msg));
    }

    /**
     * Creates a ACTION response.
     *
     * @param  string   $channel The channel to send the action to.
     * @return Response An IRC Response object
     */
    public static function action($channel, $msg)
    {
        // ACTION isn't really part of the IRC spec, it's kind of an agreement between client devs.
        // An ACTION is just a standard PRIVMSG whose message starts with HEX 01 byte, followed by
        // "ACTION", then the message, and ends with a HEX 01 byte.
        //
        // See also:
        //	http://www.dreamincode.net/forums/topic/85216-irc-action/page__p__535748&#entry535748
        $msg = "\x01ACTION $msg\x01";

        return new self('PRIVMSG', array($channel, $msg));
    }

    /**
     * Creates a USER response.
     *
     * @param  string   $nick     The bot's nickname
     * @param  string   $host     The IRC host to connect to
     * @param  string   $server   The server name
     * @param  string   $realname The bot's "real name"
     * @return Response An IRC Response object
     */
    public static function user($nick, $host, $server, $realname)
    {
        return new self('USER', array($nick, $host, $server, $realname));
    }

    /**
     * Creates a NICK response.
     *
     * @param  string   $nick The nickname to set
     * @return Response An IRC Response object
     */
    public static function nick($nick)
    {
        return new self('NICK', $nick);
    }

    /**
     * Stringify this object.
     *
     * @return string The string representation of the response
     */
    public function __toString()
    {
        return implode(' ', $this->args);
    }
}
