<?php

namespace CG\Generator;

use CG\Generator\AbstractBuilder;

class PhpConstant extends AbstractBuilder
{
    private $name;
    private $value;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }
}