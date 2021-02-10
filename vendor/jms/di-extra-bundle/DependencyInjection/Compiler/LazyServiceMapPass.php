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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This pass allows you to easily create lazy-loading service maps.
 *
 * ```php
 *    $container->addCompilerPass(new LazyServiceMapPass(
 *        'jms_serializer.visitor',
 *        'format',
 *        function(ContainerBuilder $container, Definition $def) {
 *            $container->getDefinition('jms_serializer')
 *                ->addArgument($def);
 *        }
 *    ));
 * ```
 *
 * In the example above, we make the definition of visitors lazy-loading.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LazyServiceMapPass implements CompilerPassInterface, \Serializable
{
    private $tagName;
    private $keyAttributeName;
    private $callable;

    public function __construct($tagName, $keyAttributeName, $callable)
    {
        $this->tagName = $tagName;
        $this->keyAttributeName = $keyAttributeName;
        $this->callable = $callable;
    }

    public function process(ContainerBuilder $container)
    {
        if (!is_callable($this->callable)) {
            throw new \RuntimeException('The callable is invalid. If you had serialized this pass, the original callable might not be available anymore.');
        }

        $serviceMap = array();
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag[$this->keyAttributeName])) {
                    throw new \RuntimeException(sprintf('The attribute "%s" must be set for service "%s" and tag "%s".', $this->keyAttributeName, $id, $this->tagName));
                }

                $serviceMap[$tag[$this->keyAttributeName]] = $id;
            }
        }

        $def = new Definition('JMS\DiExtraBundle\DependencyInjection\Collection\LazyServiceMap');
        $def->addArgument(new Reference('service_container'));
        $def->addArgument($serviceMap);

        call_user_func($this->callable, $container, $def);
    }

    public function serialize()
    {
        return serialize(array($this->tagName, $this->keyAttributeName));
    }

    public function unserialize($str)
    {
        list($this->tagName, $this->keyAttributeName) = unserialize($str);
    }
}
