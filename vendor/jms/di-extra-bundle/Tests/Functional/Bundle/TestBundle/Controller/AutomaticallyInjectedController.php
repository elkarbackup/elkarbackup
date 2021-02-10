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

namespace JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Templating\EngineInterface;

class AutomaticallyInjectedController
{
    private $context;
    private $templating;
    private $router;
    private $foo;

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/automatic-controller-injection-test")
     */
    public function testAction()
    {
        $content = '';

        $content .= sprintf("\$context injection: %s\n", $this->context instanceof SecurityContextInterface || $this->context instanceof AuthorizationCheckerInterface ? 'OK' : 'FAILED');
        $content .= sprintf("\$templating injection: %s\n", $this->templating instanceof EngineInterface ? 'OK' : 'FAILED');
        $content .= sprintf("\$router injection: %s\n", $this->router instanceof RouterInterface ? 'OK' : 'FAILED');
        $content .= sprintf("\$foo injection: %s\n", 'bar' === $this->foo ? 'OK' : 'FAILED');

        return new Response($content);
    }
}
