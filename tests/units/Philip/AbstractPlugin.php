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

    public function testGetEmptyConfig()
    {
        $this
            ->if($bot = new \Philip\Philip())
            ->and($object = new \mock\Philip\AbstractPlugin($bot))
            ->then
                ->array($object->getConfig())->isEqualTo(array())
        ;
    }

    public function testGetConfig()
    {
        $this
            ->if($bot = new \Philip\Philip())
            ->if($config = array(uniqid() => uniqid()))
            ->and($object = new \mock\Philip\AbstractPlugin($bot, $config))
            ->then
            ->array($object->getConfig())->isEqualTo($config)
        ;
    }
}
