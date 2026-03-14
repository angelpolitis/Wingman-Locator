<?php
    /**
	 * Project Name:    Wingman — Locator — Namespace Registry
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 16 2025
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use RuntimeException;

    /**
     * Represents a namespace registry.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class NamespaceRegistry {
        /**
         * The namespaces of a registry.
         * @var array<string, NamespaceObject>
         */
        private array $namespaces = [];

        /**
         * The namespace alias map (`alias => `namespace`) of a registry.
         * @var array<string, string>
         */
        private array $namespaceAliasMap = [];

        /**
         * The names of all registered namespaces of a registry.
         * @var string[]
         */
        private array $namespaceNames = [];

        /**
         * Creates a new namespace registry.
         * @param NamespaceObject[] $namespaces The namespaces of the registry.
         */
        public function __construct (array $namespaces = []) {
            foreach ($namespaces as $namespaceObject) {
                $this->add($namespaceObject);
            }
        }

        /**
         * Registers a namespace.
         * @param NamespaceObject $namespace The namespace to register.
         * @return static The registry.
         * @throws RuntimeException If the namespace is already registered.
         */
        public function add (NamespaceObject $namespace) : static {
            $name = strtolower($namespace->getName());
            if (isset($this->namespaces[$name])) {
                throw new RuntimeException("Namespace '{$name}' is already registered.");
            }
            $this->namespaceNames[] = $name;
            $this->namespaces[$name] = $namespace;
            foreach ($namespace->getAliases() as $alias) {
                $this->namespaceAliasMap[strtolower($alias)] = $name;
            }
            return $this;
        }

        /**
         * Creates a namespace registry from an array of namespaces.
         * @param NamespaceObject[] $namespaces The namespaces.
         * @return static The registry.
         */
        public static function from (array $namespaces) : static {
            return new static($namespaces);
        }

        /**
         * Gets the canonical name of a namespace.
         * @param string $namespace A namespace.
         * @return string|null The canonical name of the namespace, if it exists.
         */
        public function getCanonicalNamespace (string $namespace) : ?string {
            $namespace = strtolower($namespace);
            if (isset($this->namespaces[$namespace])) {
                return $namespace;
            }
            if (isset($this->namespaceAliasMap[$namespace])) {
                return $this->namespaceAliasMap[$namespace];
            }
            return null;
        }

        /**
         * Gets a registered namespace by its name or alias.
         * @param string $namespace The namespace.
         * @return NamespaceObject|null The namespace object, or `null` if it doesn't exist.
         */
        public function getNamespace (string $namespace) : ?NamespaceObject {
            $namespace = $this->getCanonicalNamespace($namespace);
            if (!$namespace) return null;
            return $this->namespaces[$namespace];
        }

        /**
         * Gets all registered namespace names.
         * @return NamespaceObject[] The registered namespaces.
         */
        public function getNamespaces () : array {
            return $this->namespaces;
        }
        
        /**
         * Gets all variants of a namespace (self + aliases).
         * @param string $namespace The namespace.
         * @return string[] The variants of the namespace.
         */
        public function getNamespaceVariants (string $namespace) : array {
            $namespace = strtolower($namespace);
            if (isset($this->namespaceAliasMap[$namespace])) {
                $namespace = $this->namespaceAliasMap[$namespace];
            }
            return [$namespace, ...$this->namespaces[$namespace]->getAliases()];
        }
        
        /**
         * Checks whether a namespace exists.
         * @param string $namespace The namespace.
         * @return bool Whether the namespace exists.
         */
        public function hasNamespace (string $namespace) : bool {
            $namespace = strtolower($namespace);
            return isset($this->namespaces[$namespace]) || isset($this->namespaceAliasMap[$namespace]);
        }

        /**
         * Refreshes the namespace alias map to reflect any changes made to the registered namespaces.
         * @return static The registry.
         */
        public function refreshAliasMap () : static {
            $this->namespaceAliasMap = [];
            foreach ($this->namespaces as $name => $namespace) {
                foreach ($namespace->getAliases() as $alias) {
                    $this->namespaceAliasMap[strtolower($alias)] = $name;
                }
            }
            return $this;
        }

        /**
         * Removes a registered namespace.
         * @param string $namespace The namespace to remove.
         * @return static The registry.
         */
        public function remove (string $namespace) : static {
            $canonicalName = $this->getCanonicalNamespace($namespace);
            if (!$canonicalName) return $this;
            unset($this->namespaces[$canonicalName]);
            $this->namespaceNames = array_filter($this->namespaceNames, fn ($name) => $name !== $canonicalName);
            $this->refreshAliasMap();
            return $this;
        }
    }
?>