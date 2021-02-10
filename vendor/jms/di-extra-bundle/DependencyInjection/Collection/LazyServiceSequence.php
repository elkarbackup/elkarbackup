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

namespace JMS\DiExtraBundle\DependencyInjection\Collection;

use PhpCollection\Sequence;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyServiceSequence extends Sequence
{
    private $container;

    public function __construct(ContainerInterface $container, array $serviceIds = array())
    {
        parent::__construct($serviceIds);

        $this->container = $container;
    }

    public function get($index)
    {
        $this->initialize($index);

        return parent::get($index);
    }

    public function getIterator()
    {
        return new LazySequenceIterator($this->container, $this, $this->elements);
    }

    private function initialize($index)
    {
        if (!isset($this->elements[$index]) || !is_string($this->elements[$index])) {
            return;
        }

        $this->elements[$index] = $this->container->get($this->elements[$index]);
    }
}
