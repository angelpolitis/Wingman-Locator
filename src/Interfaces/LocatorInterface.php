<?php
    /**
     * Project Name:    Wingman — Locator — Locator Interface
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Interfaces namespace.
    namespace Wingman\Locator\Interfaces;

    # Import the following classes to the current scope.
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Defines the contract for a path locator.
     * Implementing this interface allows the locator to be swapped out for testing, alternative implementations, or proxy/decorator patterns
     * without coupling consumers to the concrete `Locator` class.
     * @package Wingman\Locator\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    interface LocatorInterface {
        /**
         * Discovers manifests in the specified root directory and applies their configurations to the namespace manager.
         * @param string|null $rootDirectory The root directory to search for manifests. Defaults to the document root in web contexts or the current working directory in CLI contexts.
         * @param DiscoveryProfile|null $profile The discovery profile to use.
         */
        public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void;

        /**
         * Gets the manifest repository.
         * @return ManifestRepository The manifest repository.
         */
        public function getManifestRepository () : ManifestRepository;

        /**
         * Gets the absolute path to a path expression without checking whether the path exists.
         * @param string $pathExpression The path expression.
         * @return string The absolute path.
         */
        public function getPathFor (string $pathExpression) : string;

        /**
         * Gets the absolute path to a path expression, returning `null` if the path does not exist on the filesystem.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path, or `null` if it does not exist.
         */
        public function getPathTo (string $pathExpression) : ?string;

        /**
         * Gets the absolute path to a directory path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the directory, or `null` if it does not exist.
         */
        public function getPathToDirectory (string $pathExpression) : ?string;

        /**
         * Gets the absolute path to a file path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the file, or `null` if it does not exist.
         */
        public function getPathToFile (string $pathExpression) : ?string;

        /**
         * Gets the absolute path to a namespace.
         * @param string $namespace The namespace.
         * @return string|null The absolute path to the namespace, or `null` if the namespace does not exist.
         */
        public function getPathToNamespace (string $namespace) : ?string;

        /**
         * Gets the absolute path to a root variable.
         * @param PathRootVariable|string $root The root variable.
         * @return string|null The absolute path, or `null` if it does not exist.
         */
        public function getPathToRoot (PathRootVariable|string $root) : ?string;
    }
?>