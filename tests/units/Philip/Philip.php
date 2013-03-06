<?php
namespace tests\units\Philip;

use atoum;
use Philip\Philip as TestedClass;

require_once __DIR__ . '/../bootstrap.php';

class Philip extends atoum
{
    public function test__construct()
    {
        $this
            ->if($object = new TestedClass())
            ->then
                ->object($object)->isInstanceOf('\\Philip\\Philip')
                ->array($object->getConfig())->isEqualTo(array())
                ->object($object->getLogger())->isInstanceOf('\\Monolog\\Logger')
            ->if($config = array(uniqid() => uniqid()))
            ->and($object = new TestedClass($config))
            ->then
                ->array($object->getConfig())->isIdenticalTo($config)
        ;
    }

    public function testOnChannel()
    {
        $this
            ->if($dispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcher())
            ->and($object = new TestedClass(array(), $dispatcher))
            ->and($pattern = uniqid())
            ->and($callback = function() {})
            ->and($listener = new \Philip\EventListener($pattern, $callback))
            ->then
                ->object($object->onChannel($pattern, $callback))->isIdenticalTo($object)
                ->mock($dispatcher)
                    ->call('addListener')->withArguments('message.channel', array($listener, 'testAndExecute'), 0)->once()
        ;
    }

    public function testOnPrivate()
    {
        $this
            ->if($dispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcher())
            ->and($object = new TestedClass(array(), $dispatcher))
            ->and($pattern = uniqid())
            ->and($callback = function() {})
            ->and($listener = new \Philip\EventListener($pattern, $callback))
            ->then
                ->object($object->onPrivateMessage($pattern, $callback))->isIdenticalTo($object)
                ->mock($dispatcher)
                    ->call('addListener')->withArguments('message.private', array($listener, 'testAndExecute'), 0)->once()
        ;
    }

    public function testOnMessages()
    {
        $this
            ->if($object = new \mock\Philip\Philip(array()))
            ->and($pattern = uniqid())
            ->and($callback = function() {})
            ->then
                ->object($object->onMessages($pattern, $callback))->isIdenticalTo($object)
                ->mock($object)
                    ->call('onChannel')->withArguments($pattern, $callback)->once()
                    ->call('onPrivateMessage')->withArguments($pattern, $callback)->once()
        ;
    }

    public function testOnServer()
    {
        $this
            ->if($dispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcher())
            ->and($object = new TestedClass(array(), $dispatcher))
            ->and($command = uniqid())
            ->and($callback = function() {})
            ->and($listener = new \Philip\EventListener(null, $callback))
            ->then
                ->object($object->onServer($command, $callback))->isIdenticalTo($object)
                ->mock($dispatcher)
                    ->call('addListener')->withArguments('server.' . $command, array($listener, 'testAndExecute'), 0)->once()
            ->if($command = rand(0, PHP_INT_MAX))
            ->then
                ->object($object->onServer($command, $callback))->isIdenticalTo($object)
                ->mock($dispatcher)
                    ->call('addListener')->withArguments('server.' . $command, array($listener, 'testAndExecute'), 0)->once()
        ;
    }

    public function testOnJoin()
    {
        $this
            ->if($object = new \mock\Philip\Philip(array()))
            ->and($callback = function() {})
            ->then
                ->object($object->onJoin($callback))->isIdenticalTo($object)
                ->mock($object)
                    ->call('onServer')->withArguments('join', $callback)->once()
        ;
    }

    public function testOnPart()
    {
        $this
            ->if($object = new \mock\Philip\Philip(array()))
            ->and($callback = function() {})
            ->then
                ->object($object->onPart($callback))->isIdenticalTo($object)
                ->mock($object)
                    ->call('onServer')->withArguments('part', $callback)->once()
        ;
    }

    public function testOnError()
    {
        $this
            ->if($object = new \mock\Philip\Philip(array()))
            ->and($callback = function() {})
            ->then
                ->object($object->onError($callback))->isIdenticalTo($object)
                ->mock($object)
                    ->call('onServer')->withArguments('error', $callback)->once()
        ;
    }

    public function testOnNotice()
    {
        $this
            ->if($object = new \mock\Philip\Philip())
            ->and($callback = function() {})
            ->then
                ->object($object->onNotice($callback))->isIdenticalTo($object)
                ->mock($object)
                    ->call('onServer')->withArguments('notice', $callback)->once()
        ;
    }

    public function testLoadPlugin()
    {
        $this
            ->if($object = new TestedClass())
            ->and($plugin = new \mock\Philip\AbstractPlugin($object))
            ->and($this->calling($plugin)->getName = $name = uniqid())
            ->then
                ->object($object->loadPlugin($plugin))->isIdenticalTo($object)
                ->mock($plugin)
                    ->call('getName')->once()
                ->object($object->getPlugin($name))->isIdenticalTo($plugin)
        ;
    }

    public function testLoadPlugins()
    {
        $this
            ->if($object = new TestedClass())
            ->and($plugin = new \mock\Philip\AbstractPlugin($object))
            ->and($this->calling($plugin)->getName = $name = uniqid())
            ->and($otherPlugin = new \mock\Philip\AbstractPlugin($object))
            ->and($this->calling($otherPlugin)->getName = $otherName = uniqid())
            ->then
                ->object($object->loadPlugins(array($plugin, $otherPlugin)))->isIdenticalTo($object)
                ->mock($plugin)
                    ->call('getName')->once()
                ->mock($otherPlugin)
                    ->call('getName')->once()
                ->object($object->getPlugin($name))->isIdenticalTo($plugin)
                ->object($object->getPlugin($otherName))->isIdenticalTo($otherPlugin)
        ;
    }
}
