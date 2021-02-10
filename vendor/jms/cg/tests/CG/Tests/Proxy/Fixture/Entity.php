<?php

namespace CG\Tests\Proxy\Fixture;

class Entity
{
    public function getName()
    {
        return 'foo';
    }

    final public function getBaz()
    {
    }

    protected function getFoo()
    {
    }

    private function getBar()
    {
    }
}