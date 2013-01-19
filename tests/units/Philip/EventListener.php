<?php
namespace tests\units\Philip;

use atoum;
use Philip\EventListener as TestedClass;

require_once __DIR__ . '/../bootstrap.php';

class EventListener extends atoum
{
    public function test__construct()
    {
        $this
            ->if($pattern = uniqid())
            ->and($callback = function() {})
            ->then
                ->object(new TestedClass($pattern, $callback))
        ;
    }

    public function testTestAndExecute()
    {
        $this
            ->if($pattern = '/^$/')
            ->and($result = uniqid())
            ->and($callback = function() use($result) { return $result; })
            ->and($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request(uniqid()))
            ->and($this->mockGenerator->unshuntParentClassCalls())
            ->and($event = new \mock\Philip\IRC\Event($request))
            ->and($this->calling($request)->getMessage = $message = uniqid())
            ->and($object = new TestedClass($pattern, $callback))
            ->then
                ->boolean($object->testAndExecute($event))->isFalse()
            ->if($pattern = '/.*/')
            ->and($object = new TestedClass($pattern, $callback))
            ->then
                ->variable($object->testAndExecute($event))->isIdenticalTo($result)
                ->mock($event)
                    ->call('setMatches')->withArguments(array())->once()
        ;
    }
}
