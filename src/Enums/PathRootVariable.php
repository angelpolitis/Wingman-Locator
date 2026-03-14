<?php
    /**
	 * Project Name:    Wingman — Locator — Path Root Variable
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 15 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Enums namespace.
    namespace Wingman\Locator\Enums;
    
    /**
     * Represents a path root variable.
     * @package Wingman\Locator\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum PathRootVariable {
        /**
         * The operating system root variable (constant).
         * Example: `@{os}`
         */
        case OS;

        /**
         * The current working directory root variable (constant).
         * Example: `@{cwd}`
         */
        case CWD;

        /**
         * The server root variable (constant).
         * Example: `@{server}`
         */
        case SERVER;

        /**
         * The namespace root variable (contextual).
         * Example: `@{namespace}`
         */
        case NAMESPACE;

        /**
         * The manifest root variable (contextual).
         * Example: `@{manifest}`
         */
        case MANIFEST;

        /**
         * The package root variable (contextual).
         * Example: `@{package}`
         */
        case PACKAGE;
        
        /**
         * An unknown variable.
         * Example: `@{...}`
         */
        case UNKNOWN;

        /**
         * Checks whether a variable is contextual.
         * @return bool Whether the variable is contextual.
         */
        public function isContextual () : bool {
            return match ($this) {
                self::NAMESPACE, self::MANIFEST, self::PACKAGE => true,
                default => false
            };
        }

        /**
         * Checks whether a variable is constant.
         * @return bool Whether the variable is constant.
         */
        public function isConstant () : bool {
            return match ($this) {
                self::OS, self::CWD, self::SERVER => true,
                default => false
            };
        }

        /**
         * Checks whether a variable is unknown.
         * @return bool Whether the variable is unknown.
         */
        public function isUnknown () : bool {
            return $this === self::UNKNOWN;
        }

        /**
         * Renders a variable placeholder as a string.
         * @return string The string representation of the variable.
         */
        public function render () : string {
            return sprintf("@{%s}", strtolower($this->name));
        }

        /**
         * Checks whether a variable supports relative traversal.
         * @return bool Whether the variable supports relative traversal.
         */
        public function supportRelativeTraversal () : bool {
            return match ($this) {
                self::OS, self::UNKNOWN => false,
                default => true
            };
        }

        /**
         * Performs a case-insensitive lookup for enum values.
         * @param string $value The value to look up.
         * @return self The corresponding enum case or `UNKNOWN` if the value is invalid.
         */
        public static function try (string $value) : self {
            $normalised = strtolower($value);
            foreach (self::cases() as $case) {
                if (strtolower($case->name) === $normalised) {
                    return $case;
                }
            }
            return self::UNKNOWN;
        }
    }
?>