<?php
    /**
	 * Project Name:    Wingman — Locator — Namespace Notation
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
     * Represents a namespace notation.
     * @package Wingman\Locator\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum NamespaceNotation : string {
        /**
         * Colon notation (e.g., `App:path`).
         * @var string
         */
        case COLON = "colon";

        /**
         * Path notation (e.g., `@App/path`).
         * @var string
         */
        case PATH = "path";

        /**
         * Resolves a namespace notation from a string or returns the existing instance.
         * @param static|string $notation The namespace notation to resolve.
         * @return static The resolved namespace notation.
         */
        public static function resolve (self|string $notation) : static {
            return $notation instanceof static ? $notation : static::from(strtolower($notation));
        }
    }
?>