<?php
    /**
	 * Project Name:    Wingman — Locator — Namespace Manager
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 15 2025
	 * Last Modified:   Mar 12 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator namespace.
    namespace Wingman\Locator;

    # Import the following classes to the current scope.
    use Wingman\Locator\Enums\NamespaceNotation;
    use Wingman\Locator\Exceptions\UnknownNamespaceException;
    use Wingman\Locator\Objects\NamespaceObject;
    use Wingman\Locator\Objects\NamespaceRegistry;

    /**
     * Represents a namespace manager.
     * @package Wingman\Locator
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NamespaceManager {
        /**
         * The default namespace.
         * @var string
         */
        public const DEFAULT_NAMESPACE = "default";

        /**
         * The namespace used to locate resources when one isn't explicitly provided.
         * @var string
         */
        protected string $implicitNamespace = self::DEFAULT_NAMESPACE;

        /**
         * The namespace registry of a manager.
         * @var NamespaceRegistry
         */
        protected NamespaceRegistry $registry;

        /**
         * Creates a new namespace manager.
         * @param NamespaceObject[] $namespaces The namespaces of the namespace manager.
         */
        public function __construct (array $namespaces = []) {
            $this->registry = NamespaceRegistry::from($namespaces);
        }

        /**
         * Gets the namespace alias of a namespace.
         * @param string $namespace The namespace.
         * @return string[] The aliases of the namespace.
         */
        public function getAliases (string $namespace) : array {
            return $this->registry->getNamespace($namespace)?->getAliases() ?? [];
        }

        /**
         * Gets the canonical name of a namespace.
         * @param string $namespace A namespace.
         * @return string|null The canonical name of the namespace, if it exists.
         */
        public function getCanonicalNamespace (string $namespace) : ?string {
            return $this->registry->getCanonicalNamespace($namespace);
        }

        /**
         * Gets the namespace to be used when one isn't explicitly provided.
         * When `$static` is `true` (the default), returns the statically assigned implicit namespace — the fast path suitable for production.
         * When `$static` is `false`, inspects the call stack via `debug_backtrace()` to infer the namespace from the caller's file location.
         * This dynamic mode is useful for transparent namespace-scoped resolution but should be used deliberately due to its performance cost.
         * @param bool $static Whether to use the static namespace rather than inferring it dynamically from the call stack [default: `true`].
         * @return string The namespace.
         */
        public function getImplicitNamespace (bool $static = true) : string {
            if ($static) return $this->implicitNamespace;

            $origin = null;
            
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $entry) {
                $file = $entry["file"] ?? null;

                # Skip the current iteration if the file of the entry is this file.
                if ($file == __FILE__) continue;

                # Skip the current iteration if the file of the entry belongs to the same namespace as this file.
                if ($file && $this->getPathNamespace($file) == $this->getPathNamespace(__FILE__)) continue;

                $origin = $entry;
                break;
            }

            # Return the default namespace if there's no origin.
            if (!$origin || !$file) return self::DEFAULT_NAMESPACE;

            # Find the namespace the file of the origin is located in.
            return $this->getPathNamespace($origin["file"]);
        }

        /**
         * Gets a namespace object by name.
         * @param string $name The name of the namespace.
         * @return NamespaceObject|null The namespace object, or `null` if it doesn't exist.
         */
        public function getNamespace (string $name) : ?NamespaceObject {
            return $this->registry->getNamespace($name);
        }

        /**
         * Gets the path to a specified namespace.
         * @param string $namespace The namespace.
         * @param bool $relativeFromRoot Whether the returned path should be relative from the root.
         * @return string|null The path to the namespace, or `null` if it doesn't exist.
         */
        public function getNamespacePath (string $namespace) : ?string {
            if (strtolower($namespace) === static::DEFAULT_NAMESPACE) {
                return "@{server}";
            }
            return $this->registry->getNamespace($namespace)?->getPath();
        }

        /**
         * Gets all variants of a namespace (self + aliases).
         * @param string $namespace The namespace.
         * @return string[] The variants of the namespace.
         */
        public function getNamespaceVariants (string $namespace) : array {
            return $this->registry->getNamespaceVariants($namespace);
        }

        /**
         * Determines which namespace a fully-resolved absolute path belongs to.
         * Rules:
         * - Longest matching namespace root wins
         * - Matching is segment-safe
         * - If none match, DEFAULT_NAMESPACE is returned
         * @param string $absolutePath Fully resolved absolute filesystem path
         * @return string Namespace name
         */
        public function getPathNamespace (string $absolutePath) : string {
            $absolutePath = PathUtils::fix($absolutePath);

            $bestNamespace = self::DEFAULT_NAMESPACE;
            $bestLength = -1;

            foreach ($this->registry->getNamespaces() as $name => $namespaceObject) {
                $namespaceRoot = $namespaceObject->getPath();

                # Exact match OR path starts with root + separator.
                if ($absolutePath === $namespaceRoot || str_starts_with($absolutePath, $namespaceRoot . DIRECTORY_SEPARATOR)) {
                    $length = strlen($namespaceRoot);

                    if ($length > $bestLength) {
                        $bestLength = $length;
                        $bestNamespace = $namespaceObject->getName();
                    }
                }
            }

            return $bestNamespace;
        }

        /**
         * Gets the namespace registry of a manager.
         * @return NamespaceRegistry The underlying registry holding all registered namespace objects.
         */
        public function getRegistry () : NamespaceRegistry {
            return $this->registry;
        }

        /**
         * Checks whether a namespace exists.
         * @param string $namespace The namespace.
         * @return bool Whether the namespace exists.
         */
        public function hasNamespace (string $namespace) : bool {
            if (strtolower($namespace) === static::DEFAULT_NAMESPACE) {
                return true;
            }
            return $this->registry->hasNamespace($namespace);
        }

        /**
         * Makes a namespace path out of a namespace and a path.
         * @param string|null $namespace The namespace.
         * @param string $path The path.
         * @param NamespaceNotation $notation The notation to use.
         * @return string The namespace path.
         */
        public static function makeNamespacePath (string $path, ?string $namespace = null, NamespaceNotation $notation = NamespaceNotation::PATH) : string {
            $path = PathUtils::fix($path);

            ["namespace" => $ns, "path" => $path] = PathUtils::analyse($path);

            $namespace = $namespace ?: $ns;

            if (empty($namespace)) return $path;

            $path = ltrim($path, DIRECTORY_SEPARATOR);

            return match ($notation) {
                NamespaceNotation::COLON => "$namespace:$path",
                NamespaceNotation::PATH => "@$namespace" . DIRECTORY_SEPARATOR . "$path",
            };
        }

        /**
         * Refreshes the namespace registry to reflect any changes made to the registered namespaces.
         * @return static The namespace manager.
         */
        public function refreshRegistry () : static {
            $this->registry->refreshAliasMap();
            return $this;
        }
        
        /**
         * Registers a namespace.
         * @param NamespaceObject $namespace The namespace to register.
         * @return static The namespace manager.
         */
        public function registerNamespace (NamespaceObject $namespace) : static {
            $this->registry->add($namespace);
            return $this;
        }

        /**
         * Sets the namespace to be used when one isn't explicitly provided.
         * @param string $namespace The namespace.
         * @return static The namespace manager.
         * @throws UnknownNamespaceException If the given namespace isn't defined in the map.
         */
        public function setImplicitNamespace (string $namespace) : static {
            if (!$this->hasNamespace($namespace)) {
                throw new UnknownNamespaceException("The namespace '$namespace' isn't recognised.");
            }

            $this->implicitNamespace = $this->getCanonicalNamespace($namespace);
            return $this;
        }
    }
?>