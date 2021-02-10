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

namespace JMS\DiExtraBundle\DependencyInjection\Compiler;

use Serializable;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LazyServiceSequencePass implements CompilerPassInterface, Serializable
{
    private $tagName;
    private $callable;

    public function __construct($tagName, $callable)
    {
        $this->tagName = $tagName;
        $this->callable = $callable;
    }

    public function process(ContainerBuilder $container)
    {
        if (!is_callable($this->callable)) {
            throw new \RuntimeException('The callable is invalid. If you had serialized this pass, the original callable might not be available anymore.');
        }

        $serviceIds = array();
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $attrs) {
            $serviceIds[] = $id;
        }

        $seqDef = new Definition('JMS\DiExtraBundle\DependencyInjection\Collection\LazyServiceSequence');
        $seqDef->addArgument(new Reference('service_container'));
        $seqDef->addArgument($serviceIds);

        call_user_func($this->callable, $container, $seqDef);
    }

    public function serialize()
    {
        return $this->tagName;
    }

    public function unserialize($tagName)
    {
        $this->tagName = $tagName;
    }
}
