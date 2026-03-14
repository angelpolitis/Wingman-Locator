<?php
    /**
	 * Project Name:    Wingman — Locator — Manifest Repository
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 13 2025
	 * Last Modified:   Feb 25 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use Wingman\Locator\Exceptions\ManifestOverwriteException;

    /**
     * Represents a manifest repository.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class ManifestRepository {
        /**
         * The manifests (paths) of the repository.
         * @var string[]
         */
        protected array $manifests = [];
        
        /**
         * The manifests (paths) of the repository keyed by their namespaces.
         * @var array<string, string[]>
         */
        protected array $manifestsByNamespace = [];
        
        /**
         * The manifests of the repository keyed by their paths.
         * @var array<string, Manifest>
         */
        protected array $manifestsByPath = [];

        /**
         * The namespace of each manifest (path) of the repository.
         * @var array<string, string>
         */
        protected array $namespacePerManifest = [];

        /**
         * Creates a new manifest repository.
         */
        public function __construct () {}

        /**
         * Adds a manifest to the repository.
         * @param Manifest $manifest The manifest to add.
         * @return static The repository.
         * @throws ManifestOverwriteException If a manifest with the same path is already registered.
         */
        public function add (Manifest $manifest) : static {
            $path = $manifest->getSourcePath();
            $namespace = $manifest->getNamespace();
            
            if (isset($this->manifestsByPath[$path])) {
                throw new ManifestOverwriteException("Manifest with path '{$path}' is already registered.");
            }
            $this->manifestsByPath[$path] = $manifest;
            $this->manifestsByNamespace[$namespace][] = $path;
            $this->namespacePerManifest[$path] = $namespace;

            $this->manifests[] = $path;
            return $this;
        }

        /**
         * Dehydrates a repository into an array of manifest data.
         * @return array The dehydrated repository data.
         */
        public function dehydrate () : array {
            $data = [];
            foreach ($this->manifestsByPath as $path => $manifest) {
                $data[] = $manifest->dehydrate();
            }
            return $data;
        }
        
        /**
         * Gets a manifest of a repository by its path or index.
         * @param string|int $pathOrIndex The path or index of the manifest to get.
         * @return Manifest|null The manifest, if it exists.
         */
        public function get (string|int $pathOrIndex) : ?Manifest {
            if (is_string($pathOrIndex)) {
                return $this->manifestsByPath[$pathOrIndex] ?? null;
            }
            else {
                $path = $this->manifests[$pathOrIndex] ?? null;
                return $path ? ($this->manifestsByPath[$path] ?? null) : null;
            }
        }

        /**
         * Gets the manifests of a repository.
         * @return Manifest[] The manifests.
         */
        public function getAll () : array {
            return array_map(fn ($path) => $this->manifestsByPath[$path], $this->manifests);
        }

        /**
         * Gets the paths of the manifests of a repository.
         * @return string[] The manifest paths.
         */
        public function getAllPaths () : array {
            return $this->manifests;
        }

        /**
         * Gets all manifests belonging to a given namespace.
         * Uses the internal namespace index for O(1) lookup rather than iterating over all manifests.
         * @param string $namespace The canonical namespace name.
         * @return Manifest[] The manifests registered under the namespace, or an empty array if none exist.
         */
        public function getByNamespace (string $namespace) : array {
            $paths = $this->manifestsByNamespace[$namespace] ?? [];
            return array_map(fn ($path) => $this->manifestsByPath[$path], $paths);
        }

        /**
         * Hydrates a repository from an array of manifest data.
         * @param array $data The array of manifest data.
         */
        public static function hydrate (array $data) : static {
            $repo = new static();
            foreach ($data as $manifestData) {
                $manifest = Manifest::from($manifestData, $manifestData["sourcePath"]);
                $repo->add($manifest);
            }
            return $repo;
        }
    }
?>