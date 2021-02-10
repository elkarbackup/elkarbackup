<?php

namespace CG\Generator;

abstract class AbstractBuilder
{
    private $attributes;

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function removeAttribute($key)
    {
        unset($this->attributes[$key]);
    }

    public function getAttribute($key)
    {
        if ( ! isset($this->attributes[$key])) {
            throw new \InvalidArgumentException(sprintf('There is no attribute named "%s".', $key));
        }

        return $this->attributes[$key];
    }

    /**
     * @param string $key
     */
    public function getAttributeOrElse($key, $default)
    {
        if ( ! isset($this->attributes[$key])) {
            return $default;
        }

        return $this->attributes[$key];
    }

    public function hasAttribute($key)
    {
        return isset($this->attributes[$key]);
    }

    public function setAttributes(array $attrs)
    {
        $this->attributes = $attrs;

        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}