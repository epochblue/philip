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

use Philip\IRC\Event;

/**
 * Philip Plugin Abstract
 *
 * @package    philip
 * @author     Doug Hurst <dalan.hurst@gmail.com>
 * @since      2012-10-12
 */
abstract class AbstractPlugin
{
    /** @var \Philip\Philip */
    protected $bot;

    /** @var array */
    protected $config = array();

    /**
     * Constructor
     *
     * @param \Philip\Philip $bot
     * @param array $config Any plugin-specific configuration
     */
    public function __construct(Philip $bot, array $config = array())
    {
        $this->bot = $bot;
        $this->config = $config;
    }

    /**
     * Returns a string name version of the plugin.
     *
     * @return string The name of the plugin
     */
    abstract public function getName();

    /**
     * Init the plugin and start listening to messages.
     */
    abstract public function init();

    /**
     * Returns a help message for the plugin.
     *
     * @param Event $event
     *
     * @return string A simple help message.
     */
    public function displayHelp(Event $event)
    {
        // By default, don't print a help message
    }

    /**
     * @return \Philip\Philip
     */
    public function getBot()
    {
        return $this->bot;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
