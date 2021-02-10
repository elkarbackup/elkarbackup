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

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Compiler;

use JMS\DiExtraBundle\DependencyInjection\Compiler\LazyServiceMapPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LazyServiceMapPassTest extends TestCase
{
    public function testProcess()
    {
        $called = false;
        $self = $this;

        $pass = new LazyServiceMapPass('tag', 'key', function (ContainerBuilder $container, Definition $def) use (&$called, $self) {
            $self->assertFalse($called);
            $called = true;

            $self->assertEquals(new Reference('service_container'), $def->getArgument(0));
            $self->assertEquals(array('json' => 'foo', 'xml' => 'bar', 'atom' => 'bar'), $def->getArgument(1));
        });

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('findTaggedServiceIds'))
            ->getMock();

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('tag')
            ->will($this->returnValue(array('foo' => array(array('key' => 'json')), 'bar' => array(array('key' => 'xml'), array('key' => 'atom')))));

        $pass->process($container);
        $this->assertTrue($called);
    }
}
