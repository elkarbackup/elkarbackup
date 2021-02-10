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

namespace JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects the security context or token storage/authorization checker,
 * depending on the Symfony version.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class SecurityCompatibilityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->processReferences($container);
        $this->processTags($container);
    }

    private function processReferences(ContainerBuilder $container) {
        if (class_exists('Symfony\Component\Security\Core\Authorization\AuthorizationChecker')) {
            // using Symfony 2.6+, the current configuration can be used.
            return;
        }

        if ($container->hasDefinition('security.access.method_interceptor')) {
            $container->getDefinition('security.access.method_interceptor')
                ->replaceArgument(0, new Reference('security.context'));
        }

        if ($container->hasDefinition('security.extra.twig_extension')) {
            $container->getDefinition('security.extra.twig_extension')
                ->replaceArgument(0, new Reference('security.context'));
        }
    }

    private function processTags(ContainerBuilder $container) {
        if (!$container->hasDefinition('security.acl.has_class_permission_compiler')) {
            return;
        }

        $definition = $container->getDefinition('security.acl.has_class_permission_compiler');

        if ($container->hasDefinition('security.context')) {
            $definition->addTag('security.expressions.variable', array('variable' => 'security_context', 'service' => 'security.context'));
        }
        if ($container->hasDefinition('security.token_storage')) {
            $definition->addTag('security.expressions.variable', array('variable' => 'token_storage', 'service' => 'security.token_storage'));
            $definition->addTag('security.expressions.variable', array('variable' => 'authorization_checker', 'service' => 'security.authorization_checker'));
        }
    }
}
