<?php
namespace tests\units\Philip\IRC;

use atoum;
use Philip\IRC\Response as TestedClass;

require_once __DIR__ . '/../../bootstrap.php';

class Response extends atoum
{
    public function testClass()
    {
        $this
            ->string(TestedClass::pong($host = uniqid()))
                ->isEqualTo('PONG :' . $host)
            ->string(TestedClass::quit($message = uniqid()))
                ->isEqualTo('QUIT :' . $message)
            ->string(TestedClass::join($channel = '#' . uniqid()))
                ->isEqualTo('JOIN ' . $channel)
            ->string(TestedClass::leave($channel))
                ->isEqualTo('PART ' . $channel)
            ->string(TestedClass::msg($who = uniqid(), $message))
                ->isEqualTo('PRIVMSG ' . $who . ' :' . $message)
            ->string(TestedClass::notice($channel, $message))
                ->isEqualTo('NOTICE ' . $channel . ' :' . $message)
            ->string(TestedClass::action($channel, $message))
                ->isEqualTo('PRIVMSG ' . $channel . ' :' . "\x01ACTION " . $message . "\x01")
            ->string(TestedClass::user($nick = uniqid(), $name = uniqid()))
                ->isEqualTo('USER ' . $nick . ' 8 * :' . $name)
            ->string(TestedClass::nick($nick))
                ->isEqualTo('NICK :' . $nick)
            ->string(TestedClass::pass($pass = uniqid()))
                ->isEqualTo('PASS ' . $pass)
        ;
    }
}
