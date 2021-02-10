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

namespace JMS\DiExtraBundle\Generator;

use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;
use CG\Generator\Writer;
use CG\Proxy\GeneratorInterface;

class RepositoryInjectionGenerator implements GeneratorInterface
{
    public function generate(\ReflectionClass $original, PhpClass $proxy)
    {
        $writer = new Writer();

        // copy over all public methods
        foreach ($original->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }

            $writer->reset()->write('return $this->delegate->')->write($method->name)->write('(');
            $first = true;
            foreach ($method->getParameters() as $param) {
                if (!$first) {
                    $writer->write(', ');
                }
                $first = false;

                $writer->write('$')->write($param->name);
            }
            $writer->write(');');

            $proxyMethod = PhpMethod::fromReflection($method)
                ->setBody($writer->getContent());
            $proxy->setMethod($proxyMethod);
        }

        $proxy->setProperty(PhpProperty::create('delegate')->setVisibility('private'));
        $proxy->setProperty(PhpProperty::create('container')->setVisibility('private'));

        $proxy->setMethod(PhpMethod::create('__construct')
            ->setVisibility('public')
            ->addParameter(PhpParameter::create('objectManager'))
            ->addParameter(PhpParameter::create('container')->setType('Symfony\\Component\\DependencyInjection\\ContainerInterface'))
            ->setBody($writer->reset()->writeln('$this->delegate = $objectManager;')->writeln('$this->container = $container;')->getContent())
        );

        $proxy->setMethod(PhpMethod::fromReflection($original->getMethod('getRepository'))
            ->setParameters(array(PhpParameter::create('className')))
            ->setBody($writer->reset()->writeln('$repository = $this->delegate->getRepository($className);'."\n")
                ->writeln('if ($repository instanceof \Symfony\Component\DependencyInjection\ContainerAwareInterface) {')
                ->indent()
                    ->writeln('$repository->setContainer($this->container);'."\n")
                    ->writeln('return $repository;')
                ->outdent()
                ->writeln("}\n")
                ->writeln('if (null !== $metadata = $this->container->get("jms_di_extra.metadata.metadata_factory")->getMetadataForClass(get_class($repository))) {')
                ->indent()
                    ->writeln('foreach ($metadata->classMetadata as $classMetadata) {')
                    ->indent()
                        ->writeln('foreach ($classMetadata->methodCalls as $call) {')
                        ->indent()
                            ->writeln('list($method, $arguments) = $call;')
                            ->writeln('call_user_func_array(array($repository, $method), $this->prepareArguments($arguments));')
                        ->outdent()
                        ->writeln('}')
                    ->outdent()
                    ->writeln('}')
                ->outdent()
                ->writeln('}'."\n")
                ->writeln('return $repository;')
                ->getContent()
            )
        );

        $proxy->setMethod(PhpMethod::create('prepareArguments')
            ->setVisibility('private')
            ->addParameter(PhpParameter::create('arguments')->setType('array'))
            ->setBody($writer->reset()->writeln('$processed = array();')
                 ->writeln('foreach ($arguments as $arg) {')
                 ->indent()
                     ->writeln('if ($arg instanceof \Symfony\Component\DependencyInjection\Reference) {')
                     ->indent()
                         ->writeln('$processed[] = $this->container->get((string) $arg, $arg->getInvalidBehavior());')
                     ->outdent()
                     ->writeln('} else if ($arg instanceof \Symfony\Component\DependencyInjection\Parameter) {')
                     ->indent()
                         ->writeln('$processed[] = $this->container->getParameter((string) $arg);')
                     ->outdent()
                     ->writeln('} else {')
                     ->indent()
                         ->writeln('$processed[] = $arg;')
                     ->outdent()
                     ->writeln('}')
                 ->outdent()
                 ->writeln('}'."\n")
                 ->writeln('return $processed;')
                 ->getContent()
            )
        );
    }
}
