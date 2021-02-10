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

namespace JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture;

use JMS\DiExtraBundle\Annotation as DI; // Use this alias in order to not have this class picked up by the finder

/**
 * @DI\Service(
 *     id="test.service",
 *     environments={"dev", "test"},
 *     decorates="test.service",
 *     decorationInnerName="original.test.service",
 *     deprecated="use new.test.service instead",
 *     public=false,
 *     autowire=false,
 *     autowiringTypes={"JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture\Service"}
 * )
 *
 * @author wodka
 */
class Service
{
}
