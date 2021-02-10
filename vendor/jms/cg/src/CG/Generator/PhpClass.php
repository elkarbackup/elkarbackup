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

use Doctrine\Common\Annotations\PhpParser;
use CG\Core\ReflectionUtils;

/**
 * Represents a PHP class.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PhpClass extends AbstractBuilder
{
    private static $phpParser;

    private $name;
    private $parentClassName;
    private $interfaceNames = array();
    private $useStatements = array();
    private $constants = array();
    private $properties = array();
    private $requiredFiles = array();
    private $methods = array();
    private $abstract = false;
    private $final = false;
    private $docblock;

    public static function create($name = null)
    {
        return new self($name);
    }

    public static function fromReflection(\ReflectionClass $ref)
    {
        $class = new static();
        $class
            ->setName($ref->name)
            ->setAbstract($ref->isAbstract())
            ->setFinal($ref->isFinal())
            ->setConstants($ref->getConstants())
        ;

        if (null === self::$phpParser) {
            if (!class_exists('Doctrine\Common\Annotations\PhpParser')) {
                self::$phpParser = false;
            } else {
                self::$phpParser = new PhpParser();
            }
        }

        if (false !== self::$phpParser) {
            $class->setUseStatements(self::$phpParser->parseClass($ref));
        }

        if ($docComment = $ref->getDocComment()) {
            $class->setDocblock(ReflectionUtils::getUnindentedDocComment($docComment));
        }

        foreach ($ref->getMethods() as $method) {
            $class->setMethod(static::createMethod($method));
        }

        foreach ($ref->getProperties() as $property) {
            $class->setProperty(static::createProperty($property));
        }

        return $class;
    }

    /**
     * @return PhpMethod
     */
    protected static function createMethod(\ReflectionMethod $method)
    {
        return PhpMethod::fromReflection($method);
    }

    /**
     * @return PhpProperty
     */
    protected static function createProperty(\ReflectionProperty $property)
    {
        return PhpProperty::fromReflection($property);
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
     * @param string|null $name
     */
    public function setParentClassName($name)
    {
        $this->parentClassName = $name;

        return $this;
    }

    public function setInterfaceNames(array $names)
    {
        $this->interfaceNames = $names;

        return $this;
    }

    /**
     * @param string $name
     */
    public function addInterfaceName($name)
    {
        $this->interfaceNames[] = $name;

        return $this;
    }

    public function setRequiredFiles(array $files)
    {
        $this->requiredFiles = $files;

        return $this;
    }

    /**
     * @param string $file
     */
    public function addRequiredFile($file)
    {
        $this->requiredFiles[] = $file;

        return $this;
    }

    public function setUseStatements(array $useStatements)
    {
        foreach ($useStatements as $alias => $namespace) {
            if (!is_string($alias)) {
                $alias = null;
            }
            $this->addUseStatement($namespace, $alias);
        }

        return $this;
    }

    /**
     * @param string      $namespace
     * @param string|null $alias
     */
    public function addUseStatement($namespace, $alias = null)
    {
        if (null === $alias) {
            $alias = substr($namespace, strrpos($namespace, '\\') + 1);
        }

        $this->useStatements[$alias] = $namespace;

        return $this;
    }

    public function setConstants(array $constants)
    {
        $normalizedConstants = array();
        foreach ($constants as $name => $value) {
            if ( ! $value instanceof PhpConstant) {
                $constValue = $value;
                $value = new PhpConstant($name);
                $value->setValue($constValue);
            }

            $normalizedConstants[$name] = $value;
        }

        $this->constants = $normalizedConstants;

        return $this;
    }

    /**
     * @param string|PhpConstant $name
     * @param string $value
     */
    public function setConstant($nameOrConstant, $value = null)
    {
        if ($nameOrConstant instanceof PhpConstant) {
            if (null !== $value) {
                throw new \InvalidArgumentException('If a PhpConstant object is passed, $value must be null.');
            }

            $name = $nameOrConstant->getName();
            $constant = $nameOrConstant;
        } else {
            $name = $nameOrConstant;
            $constant = new PhpConstant($nameOrConstant);
            $constant->setValue($value);
        }

        $this->constants[$name] = $constant;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasConstant($name)
    {
        return array_key_exists($name, $this->constants);
    }

    /**
     * Returns a constant.
     *
     * @param string $name
     *
     * @return PhpConstant
     */
    public function getConstant($name)
    {
        if ( ! isset($this->constants[$name])) {
            throw new \InvalidArgumentException(sprintf('The constant "%s" does not exist.'));
        }

        return $this->constants[$name];
    }

    /**
     * @param string $name
     */
    public function removeConstant($name)
    {
        if (!array_key_exists($name, $this->constants)) {
            throw new \InvalidArgumentException(sprintf('The constant "%s" does not exist.', $name));
        }

        unset($this->constants[$name]);

        return $this;
    }

    public function setProperties(array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    public function setProperty(PhpProperty $property)
    {
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    /**
     * @param string $property
     */
    public function hasProperty($property)
    {
        if ($property instanceof PhpProperty) {
            $property = $property->getName();
        }

        return isset($this->properties[$property]);
    }

    /**
     * @param string $property
     */
    public function removeProperty($property)
    {
        if ($property instanceof PhpProperty) {
            $property = $property->getName();
        }

        if (!array_key_exists($property, $this->properties)) {
            throw new \InvalidArgumentException(sprintf('The property "%s" does not exist.', $property));
        }
        unset($this->properties[$property]);

        return $this;
    }

    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    public function setMethod(PhpMethod $method)
    {
        $this->methods[$method->getName()] = $method;

        return $this;
    }

    public function getMethod($method)
    {
        if ( ! isset($this->methods[$method])) {
            throw new \InvalidArgumentException(sprintf('The method "%s" does not exist.', $method));
        }

        return $this->methods[$method];
    }

    /**
     * @param string|PhpMethod $method
     */
    public function hasMethod($method)
    {
        if ($method instanceof PhpMethod) {
            $method = $method->getName();
        }

        return isset($this->methods[$method]);
    }

    /**
     * @param string|PhpMethod $method
     */
    public function removeMethod($method)
    {
        if ($method instanceof PhpMethod) {
            $method = $method->getName();
        }

        if (!array_key_exists($method, $this->methods)) {
            throw new \InvalidArgumentException(sprintf('The method "%s" does not exist.', $method));
        }
        unset($this->methods[$method]);

        return $this;
    }

    /**
     * @param boolean $bool
     */
    public function setAbstract($bool)
    {
        $this->abstract = (Boolean) $bool;

        return $this;
    }

    /**
     * @param boolean $bool
     */
    public function setFinal($bool)
    {
        $this->final = (Boolean) $bool;

        return $this;
    }

    /**
     * @param string $block
     */
    public function setDocblock($block)
    {
        $this->docblock = $block;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParentClassName()
    {
        return $this->parentClassName;
    }

    public function getInterfaceNames()
    {
        return $this->interfaceNames;
    }

    public function getRequiredFiles()
    {
        return $this->requiredFiles;
    }

    public function getUseStatements()
    {
        return $this->useStatements;
    }

    public function getNamespace()
    {
        if (false === $pos = strrpos($this->name, '\\')) {
            return null;
        }

        return substr($this->name, 0, $pos);
    }

    public function getShortName()
    {
        if (false === $pos = strrpos($this->name, '\\')) {
            return $this->name;
        }

        return substr($this->name, $pos+1);
    }

    public function getConstants($asObjects = false)
    {
        if ($asObjects) {
            return $this->constants;
        }

        return array_map(function(PhpConstant $constant) {
            return $constant->getValue();
        }, $this->constants);
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function isAbstract()
    {
        return $this->abstract;
    }

    public function isFinal()
    {
        return $this->final;
    }

    public function getDocblock()
    {
        return $this->docblock;
    }

    public function hasUseStatements()
    {
        return count($this->getUseStatements()) > 0;
    }

    public function uses($typeDef)
    {
        if (empty($typeDef)) {
            throw new \InvalidArgumentException("Empty type definition name given in " . __METHOD__);
        }

        if (!$this->hasUseStatements()) {
            return false;
        }

        if ('\\' === $typeDef[0]) {
            return false; // typedef references the root
        }

        $parts = explode('\\', $typeDef);
        $typeDef = array_shift($parts);
        return isset($this->useStatements[$typeDef]);
    }
}
