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

namespace JMS\DiExtraBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
final class Service
{
    /** @var string */
    public $id;

    /** @var string */
    public $parent;

    /** @var bool */
    public $public;

    /** @var string */
    public $scope;

    /** @var bool */
    public $shared;

    /** @var string */
    public $deprecated;

    /** @var string */
    public $decorates;

    /**
     * @var string
     *
     * @deprecated since version 1.8, to be removed in 2.0. Use $decorationInnerName instead.
     */
    public $decoration_inner_name;

    /** @var string */
    public $decorationInnerName;

    /** @var bool */
    public $abstract;

    /** @var array<string> */
    public $environments = array();

    /** @var bool */
    public $autowire;

    /** @var array<string> */
    public $autowiringTypes;
}
