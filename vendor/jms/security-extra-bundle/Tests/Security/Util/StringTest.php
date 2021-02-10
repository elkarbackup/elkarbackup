<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Util;

use JMS\SecurityExtraBundle\Security\Util\String as StringUtil;

class StringTest extends \PHPUnit_Framework_TestCase
{
    public function testEquals()
    {
        if(PHP_VERSION_ID >= 70000) {
            return $this->markTestSkipped('String class name can\'t be used on php 7.');
        }
        $this->assertTrue(StringUtil::equals('password', 'password'));
        $this->assertFalse(StringUtil::equals('password', 'foo'));
    }
}
