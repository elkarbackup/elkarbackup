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

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Collection;

use JMS\DiExtraBundle\DependencyInjection\Collection\LazyServiceMap;
use JMS\DiExtraBundle\Tests\BaseTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyServiceMapTest extends BaseTestCase
{
    /**
     * @var LazyServiceMap
     */
    private $map;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    private $container;

    public function testGet()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('bar_service')
            ->will($this->returnValue($a = new \stdClass()));

        $this->assertSame($a, $this->map->get('foo')->get());
        $this->assertSame($a, $this->map->get('foo')->get());
    }

    public function testRemove()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('bar_service')
            ->will($this->returnValue($a = new \stdClass()));

        $this->assertSame($a, $this->map->remove('foo'));
        $this->assertFalse($this->map->contains($a));
        $this->assertFalse($this->map->containsKey('foo'));
    }

    public function testIterator()
    {
        $this->container->expects($this->at(0))
            ->method('get')
            ->with('bar_service')
            ->will($this->returnValue($a = new \stdClass()));

        $this->container->expects($this->at(1))
            ->method('get')
            ->with('baz_service')
            ->will($this->returnValue($b = new \stdClass()));

        $iterator = $this->map->getIterator();

        $this->assertSame($a, $iterator->current());

        $iterator->next();
        $this->assertSame($b, $iterator->current());
    }

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->map = new LazyServiceMap($this->container, array(
            'foo' => 'bar_service',
            'bar' => 'baz_service',
        ));
    }
}
