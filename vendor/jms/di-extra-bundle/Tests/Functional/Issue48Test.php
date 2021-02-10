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

namespace JMS\DiExtraBundle\Tests\Functional;

class Issue48Test extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testCreatingMultipleKernelsInATest()
    {
        $kernelA = static::createKernel(array('debug' => false, 'config' => 'doctrine.yml'));
        $kernelA->boot();

        $kernelB = static::createKernel(array('debug' => true, 'config' => 'doctrine.yml'));
        $kernelB->boot();

        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $kernelA->getContainer()->get('doctrine.orm.default_entity_manager'));
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $kernelB->getContainer()->get('doctrine.orm.default_entity_manager'));
    }
}
