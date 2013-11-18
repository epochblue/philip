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

        return sprintf('%s %s', strtoupper($cmd), implode(' ', $args));
    }

    /**
     * Creates a ACTION response.
     *
     * @param string $who The channel to send the action to.
     * @param string $msg The message to send
     *
     * @return string An IRC response message
     */
    public static function action($who, $msg)
    {
        // ACTION isn't really part of the IRC spec, it's kind of an agreement between client devs.
        // An ACTION is just a standard PRIVMSG whose message starts with HEX 01 byte, followed by
        // "ACTION", then the message, and ends with a HEX 01 byte.
        //
        // See also:
        //	http://www.dreamincode.net/forums/topic/85216-irc-action/page__p__535748&#entry535748
        $msg = "\x01ACTION $msg\x01";

        return self::msg($who, $msg);
    }

    /**
     * Creates a PRIVMSG response.
     *
     * @param string $who     The nick to send invite to
     * @param string $channel The channel to invite nick to
     *
     * @return string An IRC response message
     */
    public static function invite($who, $channel)
    {
        return sprintf('%s %s %s', 'INVITE', $who, $channel);
    }

    /**
     * Creates a JOIN response.
     *
     * @param string $channels The channels to join
     *
     * @return string An IRC response message
     */
    public static function join($channels)
    {
        return sprintf('%s %s', 'JOIN', $channels);
    }

    /**
     * Creates a KICK response.
     *
     * @param string $who     The nick to kick
     * @param string $channel The channel to kick nick from
     * @param string $reason  Message with the reason
     *
     * @return string An IRC response message
     */
    public static function kick($who, $channel, $reason = '')
    {
        return sprintf('%s %s %s :%s', 'KICK', $channel, $who, $reason);
    }

    /**
     * Creates a PART response.
     *
     * This function is deprecated, on IRC you part a channel, not leave.
     * This leave function is now an alias of the part() function and will
     * remain here for backwards compatibility.
     *
     * @param string $channels The channels to leave
     *
     * @return string An IRC response message
     *
     * @deprecated
     */
    public static function leave($channels)
    {
        return self::part($channels);
    }

    /**
     * @param string $channel The channel change to mode for
     * @param string $mode    The mode to set
     * @param string $args    Extra arguments (e.g. nickname or other mode options)
     *
     * @return string An IRC response message
     */
    public static function mode($channel, $mode, $args = '')
    {
        return sprintf('%s %s %s %s', 'MODE', $channel, $mode, $args);
    }

    /**
     * Creates a PRIVMSG response.
     *
     * @param string $who The channel/nick to send this msg to
     * @param string $msg The message to send
     *
     * @return string An IRC response message
     */
    public static function msg($who, $msg)
    {
        return sprintf('%s %s :%s', 'PRIVMSG', $who, $msg);
    }

    /**
     * Creates a NICK response.
     *
     * @param string $nick The nickname to set
     *
     * @return string An IRC response message
     */
    public static function nick($nick)
    {
        return sprintf('%s :%s', 'NICK', $nick);
    }

    /**
     * Creates a NOTICE response.
     *
     * @param string $channel The channel to send the notice to.
     * @param string $msg     The message to send
     *
     * @return string An IRC response message
     */
    public static function notice($channel, $msg)
    {
        return sprintf('%s %s :%s', 'NOTICE', $channel, $msg);
    }

    /**
     * Creates a PART response.
     *
     * @param string $channels The channels to leave
     *
     * @return string An IRC response message
     */
    public static function part($channels)
    {
        return sprintf('%s %s', 'PART', $channels);
    }

    /**
     * Creates a PASS response.
     *
     * @param string $password The user's password
     *
     * @return string An IRC response message
     */
    public static function pass($password)
    {
        return sprintf('%s %s', 'PASS', $password);
    }

    /**
     * Creates a PONG response.
     *
     * @param string $host The host string to send back
     *
     * @return string An IRC response message
     */
    public static function pong($host)
    {
        return sprintf('%s :%s', 'PONG', $host);
    }

    /**
     * Creates a QUIT response.
     *
     * @param string $msg The quitting message
     *
     * @return string An IRC response message
     */
    public static function quit($msg)
    {
        return sprintf('%s :%s', 'QUIT', $msg);
    }

    /**
     * Creates a TOPIC response.
     *
     * @param string $channel The channel to set the topic
     * @param string $topic   The topic to set
     *
     * @return string An IRC response message
     */
    public static function topic($channel, $topic)
    {
        return sprintf('%s %s :%s', 'TOPIC', $channel, $topic);
    }

    /**
     * Creates a USER response.
     *
     * @param string $username The bot's username
     * @param string $realname The bot's "real name"
     *
     * @return string An IRC response message
     */
    public static function user($username, $realname)
    {
        return sprintf('%s %s %s %s :%s', 'USER', $username, '8', '*', $realname);
    }
}
