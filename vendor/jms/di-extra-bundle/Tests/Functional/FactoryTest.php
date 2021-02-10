<?php

namespace JMS\DiExtraBundle\Tests\Functional;

use JMS\DiExtraBundle\Tests\Functional\BaseTestCase;

class FactoryTest extends BaseTestCase
{
    public function testGetFactoryService()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->markTestSkipped('Requires PHP 7.0');
        }

        $this->createClient();
        $container = self::$kernel->getContainer();

        $service = $container->get('factory_generic_service');
        $this->assertInstanceOf('stdClass', $service);
    }
}