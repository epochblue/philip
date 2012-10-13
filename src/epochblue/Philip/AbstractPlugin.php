<?php
/**
 * Philip
 *
 * PHP Version 5.3
 *
 * @package    philip
 * @copyright  2012 iostudio. LLC
 */
namespace Epochblue\Philip;

/**
 * Philip Plugin Abstract
 *
 * @package    philip
 * @author     Doug Hurst <dalan.hurst@gmail.com>
 * @since      2012-10-12
 */
abstract class AbstractPlugin
{
    /**
     * @var Philip
     */
    protected $bot;

    /**
     * Constructor
     *
     * @param Philip $bot
     */
    public function __construct(Philip $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Init the plugin and start listening to messages
     */
    abstract public function init();
}
