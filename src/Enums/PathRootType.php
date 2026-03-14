<?php
    /**
	 * Project Name:    Wingman — Locator — Path Root
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 18 2025
	 * Last Modified:   Feb 25 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Enums namespace.
    namespace Wingman\Locator\Enums;

    # Import the following classes to the current scope.
    use Wingman\Locator\PathUtils;

    /**
     * Represents a path root type.
     * @package Wingman\Locator\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum PathRootType {
        /**
         * Absolute path root type.
         * Example: `/path/to/resource`
         */
        case ABSOLUTE;

        /**
         * Drive path root type.
         * Example: `C:\path\to\resource` (Windows only)
         */
        case DRIVE;

        /**
         * Namespace path root type.
         * Example: `@namespace/path/to/resource` or `namespace:path/to/resource`
         */
        case NAMESPACE;

        /**
         * Explicit relative path root type.
         * Example: `./path/to/resource`
         */
        case RELATIVE_EXPLICIT;

        /**
         * Relative path root type.
         * Example: `path/to/resource`
         */
        case RELATIVE_IMPLICIT;

        /**
         * Variable path root type.
         * Example: `@{variable}/path/to/resource`
         */
        case VARIABLE;

        /**
         * Detects the root type of a given path.
         * @param string $path The path to analyze.
         * @return array An array [rootType, rootArg|null, relativePath].
         */
        public static function detect (string $path) : array {
            $types = [
                self::DRIVE,
                self::VARIABLE,
                self::ABSOLUTE,
                self::NAMESPACE,
                self::RELATIVE_EXPLICIT,
                self::RELATIVE_IMPLICIT,
            ];
            foreach ($types as $type) {
                $match = $type->test($path);
                if ($match !== null) return $match;
            }
            
            return [self::RELATIVE_IMPLICIT, null, PathUtils::fix($path)];
        }

        /**
         * Checks whether a root type is relative.
         * @return bool Whether the root type is relative.
         */
        public function isRelative () : bool {
            return match ($this) {
                self::RELATIVE_EXPLICIT, self::RELATIVE_IMPLICIT => true,
                default => false
            };
        }

        /**
         * Attempt to match a path against this root type.
         * Returns an array [rootType, rootArg|null, relativePath]
         * or null if not matched.
         * @param string $path The path to test.
         * @return array|null The match result or null if not matched.
         */
        public function test (string $path) : ?array {
            if ($this === self::NAMESPACE && PathUtils::isURL($path)) {
                return null;
            }
            $path = PathUtils::fix($path);
            $sep = preg_quote(DIRECTORY_SEPARATOR, '#');

            return match ($this) {
                self::DRIVE => preg_match("#^([A-Za-z]):[\\\\/]?(.*)$#", $path, $m)
                    ? [self::DRIVE, strtoupper($m[1]), $m[2] ?? ""]
                    : null,
                self::VARIABLE => preg_match("#^@\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}(?:{$sep}(.*))?$#", $path, $m)
                    ? [self::VARIABLE, $m[1], $m[2] ?? ""]
                    : null,
                self::ABSOLUTE => str_starts_with($path, DIRECTORY_SEPARATOR)
                    ? [self::ABSOLUTE, null, ltrim($path, DIRECTORY_SEPARATOR)]
                    : null,
                self::NAMESPACE => preg_match("#^@([^{$sep}]+)(?:{$sep}(.*))?$#", $path, $m)
                    ? [self::NAMESPACE, $m[1], $m[2] ?? ""]
                    : (
                        preg_match("#^([^:]+): *(.*)$#", $path, $m)
                            ? [self::NAMESPACE, $m[1], $m[2] ?? ""]
                            : null
                    ),
                self::RELATIVE_EXPLICIT => str_starts_with($path, '.' . DIRECTORY_SEPARATOR)
                    ? [self::RELATIVE_EXPLICIT, null, substr($path, 2)]
                    : null,
                self::RELATIVE_IMPLICIT => [self::RELATIVE_IMPLICIT, null, $path]
            };
        }
    }
?>