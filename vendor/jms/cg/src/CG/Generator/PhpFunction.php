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

namespace CG\Generator;

/**
 * Represents a PHP function.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
use CG\Core\ReflectionUtils;

class PhpFunction extends AbstractBuilder
{
    private $name;
    private $namespace;
    private $parameters = array();
    private $body = '';
    private $referenceReturned = false;
    private $docblock;
    private $returnType;
    private $returnTypeBuiltin = false;

    public static function fromReflection(\ReflectionFunction $ref)
    {
        $function = new static();

        if (false === $pos = strrpos($ref->name, '\\')) {
            $function->setName(substr($ref->name, $pos + 1));
            $function->setNamespace(substr($ref->name, $pos));
        } else {
            $function->setName($ref->name);
        }

        if (method_exists($ref, 'getReturnType')) {
            if ($type = $ref->getReturnType()) {
                $function->setReturnType((string)$type);
            }
        }
        $function->referenceReturned = $ref->returnsReference();
        $function->docblock = ReflectionUtils::getUnindentedDocComment($ref->getDocComment());

        foreach ($ref->getParameters() as $refParam) {
            assert($refParam instanceof \ReflectionParameter);

            $param = PhpParameter::fromReflection($refParam);
            $function->addParameter($param);
        }

        return $function;
    }

    public static function create($name = null)
    {
        return new static($name);
    }

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * In contrast to getName(), this method accepts the fully qualified name
     * including the namespace.
     *
     * @param string $name
     */
    public function setQualifiedName($name)
    {
        if (false !== $pos = strrpos($name, '\\')) {
            $this->namespace = substr($name, 0, $pos);
            $this->name = substr($name, $pos + 1);

            return $this;
        }

        $this->namespace = null;
        $this->name = $name;

        return $this;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param boolean $bool
     */
    public function setReferenceReturned($bool)
    {
        $this->referenceReturned = (Boolean) $bool;

        return $this;
    }

    public function setReturnType($type)
    {
        $this->returnType = $type;
        $this->returnTypeBuiltin = BuiltinType::isBuiltIn($type);
        return $this;
    }

    /**
     * @param integer $position
     */
    public function replaceParameter($position, PhpParameter $parameter)
    {
        if ($position < 0 || $position > count($this->parameters)) {
            throw new \InvalidArgumentException(sprintf('$position must be in the range [0, %d].', count($this->parameters)));
        }

        $this->parameters[$position] = $parameter;

        return $this;
    }

    public function addParameter(PhpParameter $parameter)
    {
        $this->parameters[] = $parameter;

        return $this;
    }

    /**
     * @param string|integer $nameOrIndex
     *
     * @return PhpParameter
     */
    public function getParameter($nameOrIndex)
    {
        if (is_int($nameOrIndex)) {
            if ( ! isset($this->parameters[$nameOrIndex])) {
                throw new \InvalidArgumentException(sprintf('There is no parameter at position %d (0-based).', $nameOrIndex));
            }

            return $this->parameters[$nameOrIndex];
        }

        foreach ($this->parameters as $param) {
            assert($param instanceof PhpParameter);

            if ($param->getName() === $nameOrIndex) {
                return $param;
            }
        }

        throw new \InvalidArgumentException(sprintf('There is no parameter named "%s".', $nameOrIndex));
    }

    /**
     * @param integer $position
     */
    public function removeParameter($position)
    {
        if (!isset($this->parameters[$position])) {
            throw new \InvalidArgumentException(sprintf('There is not parameter at position %d.', $position));
        }

        unset($this->parameters[$position]);
        $this->parameters = array_values($this->parameters);

        return $this;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param string $docBlock
     */
    public function setDocblock($docBlock)
    {
        $this->docblock = $docBlock;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getQualifiedName()
    {
        if ($this->namespace) {
            return $this->namespace.'\\'.$this->name;
        }

        return $this->name;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getDocblock()
    {
        return $this->docblock;
    }

    public function isReferenceReturned()
    {
        return $this->referenceReturned;
    }

    public function getReturnType()
    {
        return $this->returnType;
    }

    public function hasReturnType()
    {
        return null !== $this->getReturnType();
    }

    public function hasBuiltinReturnType()
    {
        return $this->returnTypeBuiltin;
    }

}
