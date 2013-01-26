<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource;

use Alchemy\Zippy\Resource\ResourceTeleporter;
use Alchemy\Zippy\Resource\TeleporterFactory;

/**
 * This class is responsible of looping throught a set of provided
 * resource, separate the resources that belong to the reference context from them
 * that do not and getting the appropriate ResourceTeleporter object for each
 * resource
 */
class ResourceMapper
{
    /**
     * Maps resources to their appropriate ResourceTeleporter object
     *
     * @return An array of ResourceTeleporter objects
     */
    public function map($context, array $resources)
    {
        // A set of resource that live in the context
        $contextFiles = array();
        // A set of resource that do not live in the context
        $temporaryFiles = array();


        foreach ($resources as $location => $resource) {
            $location = ltrim($location, '/');

            // case resource is stream
            // example fopen('http:// ...);
            if (is_resource($resource)) {
                $meta = stream_get_meta_data($resource);
                $url = parse_url($meta['uri']);

                if ($this->isLocalFilesystem($meta['wrapper_type'])
                        && $this->isFileInContext($url['path'], $context)
                            && !$this->isLocationCustomized($location)) {
                    $target = $this->getRelativePathFromContext($url['path'], $context);

                    $contextFiles[$target] = $this->getResourceTeleporter(
                        $meta['wrapper_type'],
                        $meta['uri'],
                        $target
                    );

                    continue;
                }

                if (!$this->isLocationCustomized($location)) {
                    $target = $this->getBaseName($meta['uri']);
                } else {
                    $target = $this->getCustomPath($location, $meta['uri']);
                }

                $temporaryFiles[$target] = $this->getResourceTeleporter(
                    $meta['wrapper_type'],
                    $meta['uri'],
                    $target
                );

                continue;
            }

            // case resource is a resource URI or local path
            // example  file:///path/to/local/file or http://path.to.remote/file
            // or /path/to/local/file
            if (is_string($resource)) {
                $url = parse_url($resource);

                // resource is a resource URI
                if (isset($url['scheme'])) {
                    if ($this->isLocalFilesystem($url['scheme'])
                            && $this->isFileInContext($url['path'], $context)
                                && !$this->isLocationCustomized($location)) {
                        $target = $this->getRelativePathFromContext($url['path'], $context);

                        $contextFiles[$target] = $this->getResourceTeleporter(
                            $url['scheme'],
                            $resource,
                            $target
                        );

                        continue;
                    }

                    if (!$this->isLocationCustomized($location)) {
                        $target = $this->getBaseName($resource);
                    } else {
                        $target = $this->getCustomPath($location, $resource);
                    }

                    $temporaryFiles[$target] = $this->getResourceTeleporter(
                        $url['scheme'],
                        $resource,
                        $target
                    );

                    continue;
                }

                // resource is a local path
                if ($this->isFileInContext($resource, $context)) {
                    $target = $this->getRelativePathFromContext($resource, $context);
                    $contextFiles[$target] = $this->getResourceTeleporter(
                        'local',
                        $resource,
                        $target
                    );
                } else {
                    $target = $this->getBaseName($resource);
                    $temporaryFiles[$target] = $this->getResourceTeleporter(
                        'local',
                        $resource,
                        $target
                    );
                }
            }
        }

        return array_merge($contextFiles, $temporaryFiles);
    }

    /**
     * Get an instance of ResourceTeleporter
     *
     * @param String $protocole A protocole scheme
     * @param String $uri       A resource URI
     * @param String $target    A reosurce target location
     *
     * @return ResourceTeleporter
     */
    private function getResourceTeleporter($protocole, $uri, $target)
    {
        return new ResourceTeleporter(
            TeleporterFactory::create($protocole),
            $uri,
            $target
        );
    }

    /**
     * Checks wheteher the path belong to the context
     *
     * @param String $path A resource path
     *
     * @return Boolean
     */
    private function isFileInContext($path, $context)
    {
        return false !== stripos($path, $context);
    }

    /**
     * Gets the relative path from the context for the given path
     *
     * @param String $path A resource path
     *
     * @return String
     */
    private function getRelativePathFromContext($path, $context)
    {
        return ltrim(str_replace($context, '', $path), '/');
    }

    /**
     * Gets the basename of a given path
     *
     * @param String $path A resource path
     *
     * @return String
     */
    private function getBaseName($path)
    {
        return basename($path);
    }

    /**
     * Gets the full path for a given location and a given resource path
     *
     * @param String $location A path to the desired resource destination
     * @param String $path     A path to a resource
     *
     * @return String
     */
    private function getCustomPath($location, $path)
    {
        return sprintf('%s/%s', ltrim(rtrim($location, '/'), '/'), $this->getBaseName($path));
    }

    /**
     * Checks whether the given scheme can access the local filesystem
     *
     * @param String $scheme
     *
     * @return Boolean
     */
    private function isLocalFilesystem($scheme)
    {
        return 'plainfile' === $scheme || 'file' === $scheme;
    }

    /**
     * Checks whether the given location is customized location
     *
     * @param String $location A desired location
     *
     * @return Boolean
     */
    private function isLocationCustomized($location)
    {
        return false === is_numeric($location);
    }
}