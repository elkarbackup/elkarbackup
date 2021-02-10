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

namespace JMS\DiExtraBundle\HttpKernel;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ControllerInjectorsWarmer implements CacheWarmerInterface
{
    private $kernel;
    private $controllerResolver;
    private $blackListedControllerFiles;
    private $scanAllBundles;
    private $scanBundles;

    public function __construct(
        KernelInterface $kernel,
        ControllerResolver $resolver,
        array $blackListedControllerFiles,
        $scanAllBundles = true,
        array $scanBundles = array()
    ) {
        $this->kernel = $kernel;
        $this->controllerResolver = $resolver;
        $this->blackListedControllerFiles = $blackListedControllerFiles;
        $this->scanAllBundles = $scanAllBundles;
        $this->scanBundles = $scanBundles;
    }

    public function warmUp($cacheDir)
    {
        // This avoids class-being-declared twice errors when the cache:clear
        // command is called. The controllers are not pre-generated in that case.
        $suffix = defined('Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate::NEW_CACHE_FOLDER_SUFFIX')
            ? CacheWarmerAggregate::NEW_CACHE_FOLDER_SUFFIX
            : '_new';

        if (basename($cacheDir) === $this->kernel->getEnvironment().$suffix) {
            return;
        }

        $classes = $this->findControllerClasses();
        foreach ($classes as $class) {
            $this->controllerResolver->createInjector($class);
        }
    }

    public function isOptional()
    {
        return false;
    }

    private function findControllerClasses()
    {
        $dirs = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            if (!$this->scanAllBundles && !in_array($bundle->getName(), $this->scanBundles, true)) {
                continue;
            }

            if (!is_dir($controllerDir = $bundle->getPath().'/Controller')) {
                continue;
            }

            $dirs[] = $controllerDir;
        }

        // Combination of scanAllBundles/scanBundles can lead to empty dirs.
        // Only search for controllers if we have at least one directory,
        // otherwise the finder will throw an exception.
        if (!empty($dirs)) {
            foreach (Finder::create()->name('*Controller.php')->in($dirs)->files() as $file) {
                $filename = $file->getRealPath();
                if (!in_array($filename, $this->blackListedControllerFiles)) {
                    require_once $filename;
                }
            }
        }

        // It is not so important if these controllers never can be reached with
        // the current configuration nor whether they are actually controllers.
        // Important is only that we do not miss any classes.
        return array_filter(get_declared_classes(), function ($name) {
            return preg_match('/Controller\\\(.+)Controller$/', $name) > 0;
        });
    }
}
