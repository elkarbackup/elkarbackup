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

use PhpCollection\Map;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A map of services which may be initialized lazily.
 *
 * This is useful if you have a list of services which implement a common interface, and where you only need selected
 * services during a request. The map then automatically lazily initializes these services upon first access.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LazyServiceMap extends Map
{
    private $container;
    private $serviceIds;

    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container = $container;
        $this->serviceIds = $serviceIds;
    }

    public function get($key)
    {
        $this->initialize($key);

        return parent::get($key);
    }

    public function containsKey($key)
    {
        return isset($this->serviceIds[$key]) || parent::containsKey($key);
    }

    public function remove($key)
    {
        $this->initialize($key);

        return parent::remove($key);
    }

    public function getIterator()
    {
        foreach ($this->serviceIds as $key => $id) {
            $this->set($key, $this->container->get($id));
            unset($this->serviceIds[$key]);
        }

        return parent::getIterator();
    }

    private function initialize($key)
    {
        if (!isset($this->serviceIds[$key])) {
            return;
        }

        $this->set($key, $this->container->get($this->serviceIds[$key]));
        unset($this->serviceIds[$key]);
    }
}
