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

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\DiExtraBundle\Metadata\DefaultNamingStrategy;
use JMS\DiExtraBundle\Metadata\Driver\AnnotationDriver;
use PHPUnit\Framework\TestCase;

class AnnotationDriverTest extends TestCase
{
    public function testFormType()
    {
        $metadata = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\LoginType'));

        $this->assertEquals('j_m_s.di_extra_bundle.tests.metadata.driver.fixture.login_type', $metadata->id);
        $this->assertEquals(array(
            'form.type' => array(
                array('alias' => 'login'),
            ),
        ), $metadata->tags);
    }

    public function testFormTypeWithExplicitAlias()
    {
        $metadata = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\SignUpType'));

        $this->assertEquals(array(
            'form.type' => array(
                array('alias' => 'foo'),
            ),
        ), $metadata->tags);
    }

    public function testCustomAnnotationOnClass()
    {
        $metadata = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\ClassMetaProcessor'));
        $this->assertEquals('works', @$metadata->tags['custom'], 'check value of custom annotation');
    }

    public function testServiceAnnotations()
    {
        $metadata = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\Service'));
        $this->assertEquals('test.service', $metadata->id);
        $this->assertEquals(array('dev', 'test'), $metadata->environments);
        $this->assertEquals('test.service', $metadata->decorates);
        $this->assertEquals('original.test.service', $metadata->decorationInnerName);
        $this->assertEquals('use new.test.service instead', $metadata->deprecated);
        $this->assertEquals(false, $metadata->public);
        $this->assertEquals(array('JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\Service'), $metadata->autowiringTypes);
    }

    public function testCustomAnnotationOnMethod()
    {
        $metadata = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\MethodMetaProcessor'));
        $this->assertEquals('fancy', @$metadata->tags['omg'], 'check key and value of custom annotation');
    }

    private function getDriver()
    {
        return new AnnotationDriver(new AnnotationReader(), new DefaultNamingStrategy());
    }
}
