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

namespace JMS\DiExtraBundle\Tests\Functional;

class AutomaticControllerInjectionsTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testInjections()
    {
        $client = $this->createClient(array(
            'config' => class_exists('Symfony\Component\Security\Core\Authorization\AuthorizationChecker') ? 'automatic_controller_injections.yml' : 'bc_automatic_controller_injections.yml',
        ));
        $client->request('GET', '/automatic-controller-injection-test');

        $expected = '';
        $expected .= "\$context injection: OK\n";
        $expected .= "\$templating injection: OK\n";
        $expected .= "\$router injection: OK\n";
        $expected .= "\$foo injection: OK\n";

        $this->assertEquals($expected, $client->getResponse()->getContent());
    }
}
