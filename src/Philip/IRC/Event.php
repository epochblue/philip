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

use Symfony\Component\EventDispatcher\Event as BaseEvent;

/**
 * A wrapper object for a Philip IRC event.
 *
 * @author Bill Israel <bill.israel@gmail.com>
 */
class Event extends BaseEvent
{
    /** @var \Philip\IRC\Request $request The request object for this event */
    private $request;

    /** @var array $responses Array of responses for the event */
    private $responses = array();

    /** @var array $matches Array of matches for the pattern */
    private $matches = array();

    /**
     * Constructor.
     *
     * @param $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Set the matches.
     *
     * @param array $matches
     */
    public function setMatches($matches)
    {
        $this->matches = $matches;
    }

    /**
     * Get the matches from the tested pattern.
     *
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * Get the request.
     *
     * @return \Philip\IRC\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Add a response to the list of responses.
     *
     * @param $response
     */
    public function addResponse($response)
    {
        array_push($this->responses, $response);
    }

    /**
     * Get the responses.
     *
     * @return array
     */
    public function getResponses()
    {
        return $this->responses;
    }
}