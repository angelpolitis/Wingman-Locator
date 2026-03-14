<?php
    /**
	 * Project Name:    Wingman — Locator — Path Facade
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 18 2025
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Facades namespace.
    namespace Wingman\Locator\Facades;

    # Import the following classes to the current scope.
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;

    /**
     * A static facade that delegates path-resolution calls to the active Locator instance.
     * By default the facade resolves paths through the global singleton returned by `Locator::get()`.
     * An alternative `LocatorInterface` implementation can be injected via `setLocator()`, which is
     * particularly useful in test environments where the real filesystem should not be accessed.
     * @package Wingman\Locator\Facades
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Path {
        /**
         * An optionally overridden locator instance used in place of the global singleton.
         * @var LocatorInterface|null
         */
        private static ?LocatorInterface $locator = null;

        /**
         * Returns the active locator, falling back to the global singleton when none has been injected.
         * @return LocatorInterface The active locator.
         */
        private static function getLocator () : LocatorInterface {
            return static::$locator ?? Locator::get();
        }

        /**
         * Gets the absolute path to a path expression.
         * @param string $pathExpression The path expression.
         * @return string The absolute path (no validation).
         */
        public static function for (string $pathExpression) : string {
            return static::getLocator()->getPathFor($pathExpression);
        }

        /**
         * Overrides the locator instance used by the facade.
         * Pass `null` to restore the default singleton behaviour.
         * @param LocatorInterface|null $locator The locator to use, or `null` to reset.
         */
        public static function setLocator (?LocatorInterface $locator) : void {
            static::$locator = $locator;
        }

        /**
         * Gets the absolute path to a path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the resource, or `null` if it doesn't exist.
         */
        public static function to (string $pathExpression) : ?string {
            return static::getLocator()->getPathTo($pathExpression);
        }

        /**
         * Gets the absolute path to a directory path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the directory, or `null` if it doesn't exist.
         */
        public static function toDirectory (string $pathExpression) : ?string {
            return static::getLocator()->getPathToDirectory($pathExpression);
        }

        /**
         * Gets the absolute path to a file path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the file, or `null` if it doesn't exist.
         */
        public static function toFile (string $pathExpression) : ?string {
            return static::getLocator()->getPathToFile($pathExpression);
        }

        /**
         * Gets the absolute path to a namespace.
         * @param string $namespace The namespace.
         * @return string|null The absolute path to the namespace, or `null` if it doesn't exist.
         */
        public static function toNamespace (string $namespace) : ?string {
            return static::getLocator()->getPathToNamespace($namespace);
        }

        /**
         * Gets the absolute path to a root variable.
         * @param PathRootVariable|string $root The root variable.
         * @return string|null The absolute path to the root variable, or `null` if it doesn't exist.
         */
        public static function toRoot (PathRootVariable|string $root) : ?string {
            return static::getLocator()->getPathToRoot($root);
        }
    }
?>