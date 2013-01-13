<?php
namespace tests\units\Philip\IRC;

use atoum;
use Philip\IRC\Request as TestedClass;

require_once __DIR__ . '/../../bootstrap.php';

class Request extends atoum
{
    public function testClass()
    {
        $this
            ->string(TestedClass::RE_MSG)->isNotEmpty()
        ;
    }

    public function testConstruct()
    {
        $this
            ->if($command = uniqid())
            ->then
                ->exception(function() use($command) {
                    new TestedClass($command);
                })
                    ->isInstanceOf('\\InvalidArgumentException')
                    ->hasMessage(sprintf('Invalid command: %s', $command))
            ->if($command = ':username!user@host COMMAND #channel param otherParam :Message')
            ->then
                ->object($object = new TestedClass($command))->isInstanceOf('\\Philip\\IRC\\Request')
                ->boolean($object->isFromServer())->isEqualTo(false)
                ->boolean($object->isFromUser())->isEqualTo(true)
                ->boolean($object->getServer())->isEqualTo(false)
                ->string($object->getSendingUser())->isEqualTo('username')
                ->string($object->getHost())->isEqualTo('host')
                ->string($object->getCommand())->isIdenticalTo('COMMAND')
                ->string($object->getSource())->isEqualTo('#channel')
                ->array($object->getParams())->isEqualTo(array('param', 'otherParam'))
                ->string($object->getMessage())->isEqualTo('Message')
            ->if($command = ':server.name COMMAND param otherParam :Message')
            ->then
                ->object($object = new TestedClass($command))->isInstanceOf('\\Philip\\IRC\\Request')
                ->boolean($object->isFromServer())->isEqualTo(true)
                ->boolean($object->isFromUser())->isEqualTo(false)
                ->boolean($object->getSendingUser())->isEqualTo(false)
                ->boolean($object->getSource())->isEqualTo(false)
                ->boolean($object->getSource())->isEqualTo(false)
                ->string($object->getServer())->isEqualTo('server.name')
                ->string($object->getHost())->isEmpty()
                ->string($object->getCommand())->isIdenticalTo('COMMAND')
                ->array($object->getParams())->isEqualTo(array('param', 'otherParam'))
                ->string($object->getMessage())->isEqualTo('Message')
            ->if($command = ':username!user@host COMMAND #channel :Message')
            ->then
                ->object($object = new TestedClass($command))->isInstanceOf('\\Philip\\IRC\\Request')
                ->array($object->getParams())->isEmpty()
            ->if($command = ':server.name COMMAND :Message')
            ->then
                ->object($object = new TestedClass($command))->isInstanceOf('\\Philip\\IRC\\Request')
                ->array($object->getParams())->isEmpty()
        ;
    }
}
