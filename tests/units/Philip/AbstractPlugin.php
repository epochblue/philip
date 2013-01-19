<?php
namespace tests\units\Philip;

use atoum;

require_once __DIR__ . '/../bootstrap.php';

class AbstractPlugin extends atoum
{
    public function test__construct()
    {
        $this
            ->if($bot = new \Philip\Philip())
            ->then
                ->object($object = new \mock\Philip\AbstractPlugin($bot))->isInstanceOf('\\Philip\\AbstractPlugin')
                ->object($object->getBot())->isIdenticalTo($bot)
                ->array($object->getConfig())->isEqualTo(array())
        ;
    }

    public function testBoot()
    {
        $this
            ->if($bot = new \Philip\Philip())
            ->and($object = new \mock\Philip\AbstractPlugin($bot))
            ->then
                ->object($object->boot())->isIdenticalTo($object)
                ->array($object->getConfig())->isEqualTo(array())
            ->if($config = array(uniqid() => uniqid()))
            ->then
                ->object($object->boot($config))->isIdenticalTo($object)
                ->array($object->getConfig())->isEqualTo($config)
        ;
    }

    public function testGetConfig()
    {
        $this
            ->if($bot = new \Philip\Philip())
            ->and($object = new \mock\Philip\AbstractPlugin($bot))
            ->then
                ->array($object->getConfig())->isEqualTo(array())
            ->if($config = array(uniqid() => uniqid()))
            ->and($object->boot($config))
            ->then
                ->array($object->getConfig())->isEqualTo($config)
        ;
    }
}
