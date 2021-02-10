<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\Tests\Metadata\Driver;

use JMS\DiExtraBundle\Metadata\ClassMetadata;
use JMS\DiExtraBundle\Metadata\Driver\ConfiguredControllerInjectionsDriver;
use JMS\DiExtraBundle\Tests\BaseTestCase;
use Symfony\Component\DependencyInjection\Reference;

class ConfiguredControllerInjectionsDriverTest extends BaseTestCase
{
    public function testIgnoresNonControllers()
    {
        $class = new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\NonControllerClass');
        $this->delegateReturnsEmptyMetadata();
        $metadata = $this->getDriver(array('foo' => new Reference('foo')))->loadMetadataForClass($class);

        $this->assertArrayNotHasKey('foo', $metadata->properties);
    }

    public function testLoadMetadataForClass()
    {
        $class = new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Controller\MyTestController');
        $this->delegateReturnsEmptyMetadata();
        $metadata = $this->getDriver(array('foo' => $ref = new Reference('foo')),
            array('setFoo' => array('foo')))->loadMetadataForClass($class);

        $this->assertArrayHasKey('foo', $metadata->properties);
        $this->assertSame($ref, $metadata->properties['foo']);

        $this->assertSame('setFoo', $metadata->methodCalls[0][0]);
        $this->assertSame(array('foo'), $metadata->methodCalls[0][1]);
    }

    public function testExplicitConfigurationWins()
    {
        $class = new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Controller\MyTestController');
        $this->delegate->expects($this->once())
            ->method('loadMetadataForClass')
            ->with($class)
            ->will($this->returnCallback(function () use ($class) {
                $metadata = new ClassMetadata($class->name);
                $metadata->properties['foo'] = new Reference('bar');
                $metadata->methodCalls[] = array('setFoo', array('foo'));

                return $metadata;
            }))
        ;

        $metadata = $this->getDriver(array('foo' => new Reference('baz'), array('setFoo' => array('bar'), 'setBar' => array('bar'))))->loadMetadataForClass($class);
        $this->assertArrayHasKey('foo', $metadata->properties);
        $this->assertEquals('bar', (string) $metadata->properties['foo']);

        $this->assertSame('setFoo', $metadata->methodCalls[0][0]);
        $this->assertEquals(1, count($metadata->methodCalls));
        $this->assertSame(array('foo'), $metadata->methodCalls[0][1]);
    }

    protected function setUp()
    {
        $this->delegate = $this->createMock('Metadata\Driver\DriverInterface');
    }

    private function delegateReturnsEmptyMetadata()
    {
        $this->delegate
            ->expects($this->any())
            ->method('loadMetadataForClass')
            ->will($this->returnCallback(function ($v) {
                return new ClassMetadata($v->name);
            }))
        ;
    }

    private function getDriver(array $propertyInjections = array(), array $methodInjections = array())
    {
        return new ConfiguredControllerInjectionsDriver($this->delegate, $propertyInjections, $methodInjections);
    }
}

class NonControllerClass
{
    private $foo;
}

namespace JMS\DiExtraBundle\Tests\Metadata\Driver\Controller;

class MyTestController
{
    private $foo;

    public function setFoo()
    {
    }

    private function setBar()
    {
    }
}
