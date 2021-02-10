<?php

namespace JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Factory;

use JMS\DiExtraBundle\Annotation as DI;

/** @DI\Service("my_factory") */
class MyFactory
{
    /** @DI\Service("factory_generic_service") */
    public function createGenericService(): \stdClass
    {
        return new \stdClass();
    }
}
