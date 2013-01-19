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
     */
    public function __construct(Philip $bot)
    {
        $this->bot = $bot;
    }

    abstract public function getName();

    /**
     * Init the plugin and start listening to messages
     */
    abstract public function init();

    public function boot(array $config = array())
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param Event $help
     */
    public function displayHelp(Event $help)
    {
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
