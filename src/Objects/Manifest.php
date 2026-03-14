<?php
    /**
	 * Project Name:    Wingman — Locator — Manifest
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 13 2025
	 * Last Modified:   Mar 12 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\PathUtils;

    /**
     * Represents a manifest.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class Manifest {
        /**
         * The namespace of a manifest.
         * @var string
         */
        protected string $namespace;

        /**
         * The source path of a manifest. This is the path of the manifest file from which the manifest was loaded.
         * @var string
         */
        protected string $sourcePath;

        /**
         * The aliases of a manifest.
         * @var array
         */
        protected array $aliases = [];

        /**
         * The symbols of a manifest.
         * @var array
         */
        protected array $symbols = [];

        /**
         * The virtual resources of a manifest.
         * @var array
         */
        protected array $virtuals = [];
        
        /**
         * The settings of a manifest.
         * @var array
         */
        protected array $settings = [];
        
        /**
         * The namespace aliases of a manifest.
         * @var array
         */
        protected array $namespaceAliases = [];

        /**
         * Creates a new manifest.
         * @param array{namespace : string, aliases: array<string, string>, symbols: array<string, string>, virtuals: array, settings: array, namespaceAliases: array<string, string>} $data The data.
         * @param string $sourcePath The source path of the manifest. This is the path of the manifest file from which the manifest was loaded.
         */
        public function __construct (array $data, string $sourcePath) {
            $this->sourcePath = $sourcePath;
            $this->namespace = $data["namespace"] ?? NamespaceManager::DEFAULT_NAMESPACE;
            $this->aliases = $data["aliases"] ?? [];
            if (isset($data["symbols"])) $this->setSymbols($data["symbols"]);
            $this->virtuals = $data["virtuals"] ?? [];
            $this->settings = $data["settings"] ?? [];
            $this->namespaceAliases = $data["namespaceAliases"] ?? [];
        }

        /**
         * Allows var_export to reconstruct a manifest automatically.
         * @param array $properties The properties.
         * @return static The reconstructed object.
         */
        public static function __set_state (array $properties): static {
            return new static($properties, $properties["sourcePath"]);
        }

        /**
         * Dehydrates a manifest for caching by converting absolute paths to relative paths based on a given base path.
         * This allows the manifest to be stored in a cache file without hardcoding absolute paths, making it more portable and adaptable to different environments when rehydrated.
         * @return array The dehydrated manifest data.
         */
        public function dehydrate () : array {
            return [
                "namespace" => $this->namespace,
                "sourcePath" => PathUtils::getRelativePath(dirname(dirname(__DIR__)), $this->sourcePath),
                "aliases" => $this->aliases,
                "symbols" => $this->symbols,
                "virtuals" => $this->virtuals,
                "settings" => $this->settings,
                "namespaceAliases" => $this->namespaceAliases
            ];
        }

        /**
         * Creates a new manifest out of given data.
         * @param array{namespace : string, aliases: array<string, string>, symbols: array<string, string>, virtuals: array, settings: array, namespaceAliases: array<string, string>} $data The data.
         * @param string $sourcePath The source path of the manifest. This is the path of the manifest file from which the manifest was loaded.
         * @return static The created manifest.
         */
        public static function from (array $data, string $sourcePath) : static {
            $manifest = new static($data, $sourcePath);
            return $manifest;
        }

        /**
         * Gets the aliases of a manifest.
         * @return array The aliases.
         */
        public function getAliases () : array {
            return $this->aliases;
        }

        /**
         * Gets the namespace of a manifest.
         * @return string The namespace of the manifest.
         */
        public function getNamespace () : string {
            return $this->namespace;
        }

        /**
         * Gets the namespace aliases of a manifest.
         * @return array The namespace aliases.
         */
        public function getNamespaceAliases () : array {
            return $this->namespaceAliases;
        }

        /**
         * Gets the settings of a manifest.
         * @return array The settings.
         */
        public function getSettings () : array {
            return $this->settings;
        }

        /**
         * Gets the source path of a manifest. This is the path of the manifest file from which the manifest was loaded.
         * @return string The source path.
         */
        public function getSourcePath () : string {
            return $this->sourcePath;
        }

        /**
         * Gets a symbol of a manifest.
         * @param string $symbol The symbol.
         * @return string|null The symbol path, or `null` if it doesn't exist.
         */
        public function getSymbol (string $symbol) : ?string {
            return $this->symbols[PathUtils::fix($symbol)] ?? null;
        }

        /**
         * Gets the symbols of a manifest.
         * @return array The symbols.
         */
        public function getSymbols () : array {
            return $this->symbols;
        }

        /**
         * Gets the virtual folders of a manifest.
         * @return array The virtual folders.
         */
        public function getVirtuals () : array {
            return $this->virtuals;
        }

        /**
         * Checks whether a manifest belongs to the given namespace, either directly or via a namespace alias.
         * @param string $namespace The namespace to check.
         * @return bool Whether the manifest is associated with the given namespace.
         */
        public function hasNamespace (string $namespace) : bool {
            return $this->namespace === $namespace || in_array($namespace, $this->namespaceAliases, true);
        }

        /**
         * Rehydrates a manifest from cached data by restoring absolute paths.
         * @param array $data The cached manifest data.
         * @return static The rehydrated manifest.
         */
        public static function hydrate (array $data) : static {
            $absolutePath = PathUtils::normalise(PathUtils::join(dirname(dirname(__DIR__)), $data["sourcePath"]));
            return new static($data, $absolutePath);
        }

        /**
         * Sets the aliases of a manifest.
         * @param array $aliases The aliases.
         * @return static The manifest.
         */
        public function setAliases (array $aliases) : static {
            $this->aliases = $aliases;
            return $this;
        }

        /**
         * Sets the namespace of a manifest.
         * @param string $namespace The namespace.
         * @return static The manifest.
         */
        public function setNamespace (string $namespace) : static {
            $this->namespace = $namespace;
            return $this;
        }

        /**
         * Sets the namespace aliases of a manifest.
         * @param array $namespaceAliases The namespace aliases.
         * @return static The manifest.
         */
        public function setNamespaceAliases (array $namespaceAliases) : static {
            $this->namespaceAliases = $namespaceAliases;
            return $this;
        }

        /**
         * Sets the settings of a manifest.
         * @param array $settings The settings.
         * @return static The manifest.
         */
        public function setSettings (array $settings) : static {
            $this->settings = array_replace_recursive($this->settings, $settings);
            return $this;
        }

        /**
         * Sets a symbol of a manifest.
         * @param string $symbol The symbol.
         * @param string $path The path of the symbol.
         * @return static The manifest.
         */
        public function setSymbol (string $symbol, string $path) : static {
            $this->symbols[PathUtils::fix($symbol)] = PathUtils::fix($path);
            return $this;
        }

        /**
         * Sets the symbols of a manifest.
         * @param array $symbols The symbols.
         * @return static The manifest.
         */
        public function setSymbols (array $symbols) : static {
            foreach ($symbols as $key => $value) {
                $this->symbols[PathUtils::fix($key)] = PathUtils::fix($value);
            }
            return $this;
        }

        /**
         * Sets the virtual resources of a manifest.
         * @param array $virtuals The virtual resources.
         * @return static The manifest.
         */
        public function setVirtuals (array $virtuals) : static {
            $this->virtuals = $virtuals;
            return $this;
        }
    }
?>