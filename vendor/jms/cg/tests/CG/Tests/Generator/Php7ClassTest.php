<?php

namespace CG\Tests\Generator;

use CG\Generator\PhpProperty;
use CG\Generator\PhpParameter;
use CG\Generator\PhpMethod;
use CG\Generator\PhpClass;

class Php7ClassTest extends \PHPUnit_Framework_TestCase
{
    public function testFromReflection()
    {
        if (PHP_VERSION_ID < 70000) {
           $this->markTestSkipped("Test is only valid for PHP >=7");
        }
        $class = new PhpClass();
        $class
            ->setName('CG\Tests\Generator\Fixture\EntityPhp7')
            ->setDocblock('/**
 * Doc Comment.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */')
             ->setProperty(PhpProperty::create('id')
                 ->setVisibility('private')
                 ->setDefaultValue(0)
                 ->setDocblock('/**
 * @var integer
 */')
             );

        $class->setMethod(PhpMethod::create()
            ->setName('getId')
            ->setDocblock('/**
 * @return int
 */')
            ->setVisibility('public')
            ->setReturnType('int')
        );

        $class->setMethod(PhpMethod::create()
            ->setName('setId')
            ->setVisibility('public')
            ->setDocBlock('/**
 * @param int $id
 * @return EntityPhp7
 */')
            ->addParameter(PhpParameter::create()
                    ->setName('id')
                    ->setType('int')
                    ->setDefaultValue(null)
            )
            ->setReturnType('self')
        );

        $class->setMethod(PhpMethod::create()
            ->setName('getTime')
            ->setVisibility('public')
            ->setReturnType('DateTime')
        );

        $class->setMethod(PhpMethod::create()
            ->setName('getTimeZone')
            ->setVisibility('public')
            ->setReturnType('DateTimeZone')
        );

        $class->setMethod(PhpMethod::create()
            ->setName('setTime')
            ->setVisibility('public')
            ->addParameter(PhpParameter::create()
                ->setName('time')
                ->setType('DateTime')
            )
        );

        $class->setMethod(PhpMethod::create()
            ->setName('setTimeZone')
            ->setVisibility('public')
            ->addParameter(PhpParameter::create()
                ->setName('timezone')
                ->setType('DateTimeZone')
            )
        );

        $class->setMethod(PhpMethod::create()
            ->setName('setArray')
            ->setVisibility('public')
            ->setReturnType('array')
            ->addParameter(PhpParameter::create()
                ->setName('array')
                ->setDefaultValue(null)
                ->setPassedByReference(true)
                ->setType('array')
            )
        );

        $class->setMethod(PhpMethod::create()
            ->setName('getFoo')
            ->setReturnType('CG\Tests\Generator\Fixture\SubFixture\Foo')
        );

        $class->setMethod(PhpMethod::create()
            ->setName('getBar')
            ->setReturnType('CG\Tests\Generator\Fixture\SubFixture\Bar')
        );

        $class->setMethod(PhpMethod::create()
            ->setName('getBaz')
            ->setReturnType('CG\Tests\Generator\Fixture\SubFixture\Baz')
        );

        $this->assertEquals($class, PhpClass::fromReflection(new \ReflectionClass('CG\Tests\Generator\Fixture\EntityPhp7')));
    }
}
