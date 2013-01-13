<?php
namespace tests\units\Philip\IRC;

use atoum;
use Philip\IRC\Event as TestedClass;

require_once __DIR__ . '/../../bootstrap.php';

class Event extends atoum
{
    public function testConstruct()
    {
        $this
            ->if($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request($raw = uniqid()))
            ->then
                ->object($object = new TestedClass($request))->isInstanceOf('\\Philip\\IRC\\Event')
                ->array($object->getMatches())->isEmpty()
                ->array($object->getResponses())->isEmpty()
        ;
    }

    public function testSetMatches()
    {
        $this
            ->if($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request($raw = uniqid()))
            ->and($object = new TestedClass($request))
            ->and($matches = array(uniqid(), uniqid()))
            ->then
                ->object($object->setMatches($matches))->isIdenticalTo($object)
                ->array($object->getMatches())->isEqualTo($matches)
        ;
    }

    public function testGetMatches()
    {
        $this
            ->if($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request($raw = uniqid()))
            ->and($object = new TestedClass($request))
            ->and($matches = array(uniqid(), uniqid()))
            ->then
                ->array($object->getMatches())->isEqualTo(array())
            ->if($object->setMatches($matches))
            ->then
                ->array($object->getMatches())->isEqualTo($matches)
        ;
    }

    public function testGetRequest()
    {
        $this
            ->if($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request($raw = uniqid()))
            ->and($object = new TestedClass($request))
            ->then
                ->object($object->getRequest())->isIdenticalTo($request)
        ;
    }

    public function testAddResponse()
    {
        $this
            ->if($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request($raw = uniqid()))
            ->and($response = new \Philip\IRC\Response(uniqid()))
            ->and($object = new TestedClass($request))
            ->then
                ->object($object->addResponse($response))->isIdenticalTo($object)
                ->array($object->getResponses())->isEqualTo(array($response))
            ->if($otherResponse = new \Philip\IRC\Response(uniqid()))
            ->and($object->addResponse($otherResponse))
            ->then
                ->array($object->getResponses())->isEqualTo(array($response, $otherResponse))
        ;
    }

    public function testGetResponses()
    {
        $this
            ->if($this->mockGenerator->shuntParentClassCalls())
            ->and($request = new \mock\Philip\IRC\Request($raw = uniqid()))
            ->and($object = new TestedClass($request))
            ->and($matches = array(uniqid(), uniqid()))
            ->then
                ->array($object->getResponses())->isEmpty()
            ->if($response = new \Philip\IRC\Response(uniqid()))
            ->and($object->addResponse($response))
            ->then
                ->array($object->getResponses())->isEqualTo(array($response))
            ->if($otherResponse = new \Philip\IRC\Response(uniqid()))
            ->and($object->addResponse($otherResponse))
            ->then
                ->array($object->getResponses())->isEqualTo(array($response, $otherResponse))
        ;
    }
}
