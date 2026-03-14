<?php
    /**
	 * Project Name:    Wingman — Locator — Namespace Object
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
    use Wingman\Locator\PathUtils;
    use Wingman\Locator\Objects\Symbol;

    /**
     * Represents a namespace.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class NamespaceObject {
        /**
         * The aliases of a namespace.
         * @var string[]
         */
        private array $aliases = [];

        /**
         * The name of a namespace.
         * @var string
         */
        private string $name;

        /**
         * The path of a namespace.
         * @var string
         */
        private string $path;

        /**
         * The settings of a namespace.
         * @var array<string, *>
         */
        private array $settings = [];

        /**
         * The symbols of a namespace.
         * @var array<string, Symbol>
         */
        private array $symbols = [];

        /**
         * The virtual resources of a namespace.
         * @var array<string, array>
         */
        private array $virtuals = [];

        /**
         * Creates a new namespace.
         * @param string $name The name of the namespace.
         * @param string $path The path of the namespace.
         * @param string[]|null $aliases The aliases of the namespace.
         * @param array<string, *>|null $settings The settings of the namespace.
         * @param array<string, string>|null $symbols The symbols of the namespace.
         * @param array<string, array>|null $virtuals The virtual resources of the namespace.
         */
        public function __construct (string $name, string $path, ?array $aliases = null, ?array $settings = null, ?array $symbols = null, ?array $virtuals = null) {
            $this->name = $name;
            $this->path = PathUtils::fix($path);
            $this->setAliases($aliases ?? []);
            $this->setSettings($settings ?? []);
            $this->setSymbols($symbols ?? []);
            $this->setVirtuals($virtuals ?? []);
        }

        /**
         * Recursively processes a virtual entry, normalising all embedded path strings with the current OS separator.
         * Preserves the full nested structure expected by the virtual resolver (strings, associative arrays, nested content maps).
         * @param mixed $entry The virtual entry to process.
         * @return mixed The processed entry with all path strings normalised.
         */
        private function processVirtual (mixed $entry) : mixed {
            if (is_string($entry)) {
                return PathUtils::fix($entry);
            }

            if (is_array($entry)) {
                $result = [];
                foreach ($entry as $key => $value) {
                    $result[$key] = $this->processVirtual($value);
                }
                return $result;
            }

            return $entry;
        }

        /**
         * Adds an alias to a namespace.
         * @param string $alias The alias to add.
         * @return static The namespace.
         */
        public function addAlias (string $alias) : static {
            $this->aliases[] = $alias;
            return $this;
        }

        /**
         * Adds aliases to a namespace.
         * @param string[] $aliases The aliases to add.
         * @return static The namespace.
         */
        public function addAliases (array $aliases) : static {
            $this->aliases = array_unique(array_merge($this->aliases, $aliases));
            return $this;
        }

        /**
         * Adds a setting to a namespace.
         * @param string $key The key of the setting to add.
         * @param mixed $value The value of the setting to add.
         * @return static The namespace.
         */
        public function addSetting (string $key, mixed $value) : static {
            $this->settings[$key] = $value;
            return $this;
        }

        /**
         * Adds settings to a namespace.
         * @param array<string, *> $settings The settings to add.
         * @return static The namespace.
         */
        public function addSettings (array $settings) : static {
            $this->settings = array_replace_recursive($this->settings, $settings);
            return $this;
        }

        /**
         * Adds a symbol to a namespace.
         * @param string $name The name of the symbol.
         * @param string $target The target of the symbol.
         * @param string|null $manifest The manifest of the symbol.
         * @return static The namespace.
         */
        public function addSymbol (string $name, string $target, ?string $manifest = null) : static {
            $name = PathUtils::fix($name);
            $target = PathUtils::fix($target);
            $this->symbols[$name] = new Symbol($name, $target, $manifest);
            return $this;
        }

        /**
         * Adds symbols to a namespace.
         * @param array<string, string> $symbols The symbols to add.
         * @param string|null $manifest The manifest of the symbols.
         * @return static The namespace.
         */
        public function addSymbols (array $symbols, ?string $manifest = null) : static {
            foreach ($symbols as $name => $target) {
                $this->addSymbol($name, $target, $manifest);
            }
            return $this;
        }

        /**
         * Adds a virtual resource to a namespace.
         * @param string $name The name of the virtual resource.
         * @param array|string $virtual The virtual resource to add. A bare string is treated as a direct path target.
         * @return static The namespace.
         */
        public function addVirtual (string $name, array|string $virtual) : static {
            $this->virtuals[$name] = $this->processVirtual($virtual);
            return $this;
        }

        /**
         * Adds virtual resources to a namespace.
         * @param array<string, *> $virtuals The virtual resources to add.
         * @return static The namespace.
         */
        public function addVirtuals (array $virtuals) : static {
            $virtuals = array_map([$this, "processVirtual"], $virtuals ?? []);
            $this->virtuals = array_replace_recursive($this->virtuals, $virtuals);
            return $this;
        }

        /**
         * Creates a new namespace.
         * @param array $data The data of the namespace.
         * @return static The created namespace.
         */
        public static function from (array $data) : static {
            return new static(
                $data["name"],
                $data["path"],
                $data["aliases"] ?? [],
                $data["settings"] ?? [],
                $data["symbols"] ?? [],
                $data["virtuals"] ?? []
            );
        }

        /**
         * Gets the aliases of a namespace.
         * @return string[] The aliases of the namespace.
         */
        public function getAliases () : array {
            return $this->aliases;
        }

        /**
         * Gets the name of a namespace.
         * @return string The name of the namespace.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the path of a namespace.
         * @return string The path of the namespace.
         */
        public function getPath () : string {
            return $this->path;
        }

        /**
         * Gets the settings of a namespace.
         * @return array<string, *> The settings of the namespace.
         */
        public function getSettings () : array {
            return $this->settings;
        }

        /**
         * Gets a symbol of a namespace.
         * @param string $symbol The symbol to get.
         * @return Symbol|null The symbol, or `null` if it doesn't exist.
         */
        public function getSymbol (string $symbol) : ?Symbol {
            return $this->symbols[PathUtils::fix($symbol)] ?? null;
        }

        /**
         * Gets the symbols of a namespace.
         * @return array<string, Symbol> The symbols of the namespace.
         */
        public function getSymbols () : array {
            return $this->symbols;
        }

        /**
         * Gets the virtual resources of a namespace.
         * @return array<string, *> The virtual resources of the namespace.
         */
        public function getVirtuals () : array {
            return $this->virtuals;
        }

        /**
         * Imports data into a namespace.
         * @param array $data The data to import.
         * @param string|null $manifest The manifest of the data.
         * @return static The namespace.
         */
        public function import (array $data, ?string $manifest = null) : static {
            $this->addAliases($data["aliases"] ?? []);
            $this->addSettings($data["settings"] ?? []);
            $this->addSymbols($data["symbols"] ?? [], $manifest);
            $this->addVirtuals($data["virtuals"] ?? []);
            return $this;
        }

        /**
         * Sets the aliases of a namespace.
         * @param string[] $aliases The aliases to set.
         * @return static The namespace.
         */
        public function setAliases (array $aliases) : static {
            $this->aliases = $aliases;
            return $this;
        }

        /**
         * Sets the settings of a namespace.
         * @param array<string, *> $settings The settings to set.
         * @return static The namespace.
         */
        public function setSettings (array $settings) : static {
            $this->settings = $settings;
            return $this;
        }

        /**
         * Sets the symbols of a namespace.
         * @param bool $overwrite Whether to overwrite existing symbols.
         * @return static The namespace.
         */
        public function setSymbols (array $symbols) : static {
            $this->symbols = [];
            return $this->addSymbols($symbols);
        }

        /**
         * Sets the virtual resources of a namespace.
         * @param array<string, *> $virtuals The virtual resources to set.
         * @return static The namespace.
         */
        public function setVirtuals (array $virtuals) : static {
            $this->virtuals = array_map([$this, "processVirtual"], $virtuals ?? []);
            return $this;
        }
    }
?>