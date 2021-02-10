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

namespace JMS\DiExtraBundle\DependencyInjection\Compiler;

use JMS\DiExtraBundle\Config\ServiceFilesResource;
use JMS\DiExtraBundle\Exception\RuntimeException;
use JMS\DiExtraBundle\Finder\PatternFinder;
use JMS\DiExtraBundle\Metadata\MetadataConverter;
use Metadata\AdvancedMetadataFactoryInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Search for annotation usage.
 */
class AnnotationConfigurationPass implements CompilerPassInterface
{
    /**
     * @var string[]
     */
    private $patterns;

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        /** @var AdvancedMetadataFactoryInterface $factory */
        $factory = $container->get('jms_di_extra.metadata.metadata_factory');
        /** @var MetadataConverter $converter */
        $converter = $container->get('jms_di_extra.metadata.converter');
        $disableGrep = $container->getParameter('jms_di_extra.disable_grep');

        $directories = $this->getScanDirectories($container);
        if (!$directories) {
            if (method_exists($container, 'log')) {
                $container->log($this, 'No directories configured for AnnotationConfigurationPass.');
            } else {
                $container->getCompiler()->addLogMessage('No directories configured for AnnotationConfigurationPass.');
            }

            return;
        }

        /*
         * process all available patterns
         */
        foreach ($this->getPatterns($container) as $pattern) {
            $this->handlePattern($container, $directories, $pattern, $factory, $converter, $disableGrep);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return \string[]
     */
    private function getPatterns(ContainerBuilder $container)
    {
        if (null === $this->patterns) {
            $this->patterns = $container->getParameter('jms_di_extra.annotation_patterns');
            $container->getParameterBag()->remove('jms_di_extra.annotation_patterns');
        }

        return $this->patterns;
    }

    /**
     * @param ContainerBuilder                 $container
     * @param string[]                         $directories
     * @param string                           $pattern
     * @param AdvancedMetadataFactoryInterface $factory
     * @param MetadataConverter                $converter
     * @param bool                             $disableGrep
     */
    private function handlePattern(ContainerBuilder $container, $directories, $pattern, $factory, $converter, $disableGrep)
    {
        $finder = new PatternFinder($pattern, '*.php', $disableGrep);
        $files = $finder->findFiles($directories);
        $container->addResource(new ServiceFilesResource($files, $directories, $disableGrep, $pattern));
        foreach ($files as $file) {
            $container->addResource(new FileResource($file));
            require_once $file;

            $className = $this->getClassName($file);

            if (null === $metadata = $factory->getMetadataForClass($className)) {
                continue;
            }
            if (null === $metadata->getOutsideClassMetadata()->id) {
                continue;
            }
            if (!$metadata->getOutsideClassMetadata()->isLoadedInEnvironment($container->getParameter('kernel.environment'))) {
                continue;
            }

            foreach ($converter->convert($metadata) as $id => $definition) {
                $container->setDefinition($id, $definition);
            }
        }
    }

    /**
     * Figure out where to search for usages.
     *
     * @param ContainerBuilder $c
     *
     * @return string[]
     */
    private function getScanDirectories(ContainerBuilder $c)
    {
        $bundles = $c->getParameter('kernel.bundles');
        $scanBundles = $c->getParameter('jms_di_extra.bundles');
        $scanAllBundles = $c->getParameter('jms_di_extra.all_bundles');

        $directories = $c->getParameter('jms_di_extra.directories');
        foreach ($bundles as $name => $bundle) {
            if (!$scanAllBundles && !in_array($name, $scanBundles, true)) {
                continue;
            }

            if ('JMSDiExtraBundle' === $name) {
                continue;
            }

            $reflected = new \ReflectionClass($bundle);
            $directories[] = dirname($reflected->getFileName());
        }

        return $directories;
    }

    /**
     * Only supports one namespaced class per file.
     *
     *
     * @param string $filename
     *
     * @throws \RuntimeException if the class name cannot be extracted
     *
     * @return string the fully qualified class name
     */
    private function getClassName($filename)
    {
        $src = file_get_contents($filename);

        if (!preg_match('/\bnamespace\s+([^;\{\s]+)\s*?[;\{]/s', $src, $match)) {
            throw new RuntimeException(sprintf('Namespace could not be determined for file "%s".', $filename));
        }
        $namespace = $match[1];

        if (!preg_match('/\b(?:class|trait)\s+([^\s]+)\s+(?:extends|implements|{)/is', $src, $match)) {
            throw new RuntimeException(sprintf('Could not extract class name from file "%s".', $filename));
        }

        return $namespace.'\\'.$match[1];
    }
}
