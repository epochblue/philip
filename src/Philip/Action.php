<?php

namespace Philip;

/**
 * A terribly-named class for storing a callback action and a pattern
 * that determines whether that callback should be executed.
 *
 * @author Bill Israel <bill.israel@gmail.com>
 */
class Action
{
    /** @var string $pattern A RegEx to compare a string against */
    private $pattern;

    /** @var callable $callback A function to call */
    private $callback;

    /** @var array $matches Matches from testing the $pattern */
    private $matches = array();

    /**
     * Constructor.
     *
     * @param string   $pattern  A RegEx
     * @param callable $callback A callable
     */ 
    public function __construct($pattern, $callback)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
    }
    
    /**
     * Tests the pattern against the given string.
     *
     * @param string $str The string to test.
     * @return boolean True if the pattern matched anything, false otherwise.
     */
    public function isExecutable($str)
    {
        if ($this->pattern) {
            return (bool) preg_match($this->pattern, $str, $this->matches);
        }

        return true;
    }

    /**
     * Executes the given callback, returns the callback's return value.
     *
     * @param array $params An array of params to pass to the callback
     */
    public function executeCallback($params)
    {
        return call_user_func_array($this->callback, $params);
    }

    /**
     * Get the array of matches from the pattern.
     *
     * @return array The matches
     */
    public function getMatches()
    {
        return array_slice($this->matches, 1);
    }
}

