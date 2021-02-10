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

namespace JMS\DiExtraBundle\Tests\Metadata;

use JMS\DiExtraBundle\Metadata\ClassMetadata;
use PHPUnit\Framework\TestCase;

class ClassMetadataTest extends TestCase
{
    public function testSerializeUnserialize()
    {
        $classMetadata = new ClassMetadata('JMS\DiExtraBundle\Tests\Fixture\LoginController');
        $classMetadata->arguments = array('foo', 'bar');
        $classMetadata->abstract = true;
        $classMetadata->public = false;
        $classMetadata->id = 'foo';
        $classMetadata->deprecated = true;
        $classMetadata->decorates = 'test.service';
        $classMetadata->decoration_inner_name = 'old.test.service';
        $classMetadata->autowire = true;
        $classMetadata->autowiringTypes = array('JMS\DiExtraBundle\Tests\Fixture\LoginController');

        $this->assertEquals($classMetadata, unserialize(serialize($classMetadata)));
    }
}
