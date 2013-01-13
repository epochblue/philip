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
            ->castToString(TestedClass::pong($host = uniqid()))
                ->isEqualTo('PONG :' . $host)
            ->castToString(TestedClass::quit($message = uniqid()))
                ->isEqualTo('QUIT :' . $message)
            ->castToString(TestedClass::join($channel = '#' . uniqid()))
                ->isEqualTo('JOIN :' . $channel)
            ->castToString(TestedClass::leave($channel))
                ->isEqualTo('PART :' . $channel)
            ->castToString(TestedClass::msg($who = uniqid(), $message))
                ->isEqualTo('PRIVMSG ' . $who . ' :' . $message)
            ->castToString(TestedClass::notice($channel, $message))
                ->isEqualTo('NOTICE ' . $channel . ' :' . $message)
            ->castToString(TestedClass::action($channel, $message))
                ->isEqualTo('PRIVMSG ' . $channel . ' :' . "\x01ACTION " . $message . "\x01")
            ->castToString(TestedClass::user($nick = uniqid(), $host = uniqid(), $server = uniqid(), $name = uniqid()))
                ->isEqualTo('USER ' . $nick . ' ' . $host . ' ' . $server . ' :' . $name)
            ->castToString(TestedClass::nick($nick))
                ->isEqualTo('NICK :' . $nick)
        ;
    }
}
