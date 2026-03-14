<?php
    /**
	 * Project Name:    Wingman — Locator — Symbol
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 14 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    /**
     * Represents a symbol.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class Symbol {
        /**
         * The name of a symbol.
         * @var string
         */
        private string $name;

        /**
         * The target of a symbol.
         * @var string
         */
        private string $target;

        /**
         * The manifest path of a symbol.
         * @var string|null
         */
        private ?string $manifest;

        /**
         * Creates a new symbol.
         * @param string $name The name of the symbol.
         * @param string $target The target of the symbol.
         * @param string|null $manifest The manifest path of the symbol (optional).
         */
        public function __construct (string $name, string $target, ?string $manifest = null) {
            $this->name = $name;
            $this->target = $target;
            $this->manifest = $manifest;
        }

        /**
         * Gets the manifest path of a symbol.
         * @return string|null The manifest path.
         */
        public function getManifest () : ?string {
            return $this->manifest;
        }

        /**
         * Gets the name of a symbol.
         * @return string The name.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the target of a symbol.
         * @return string The target.
         */
        public function getTarget () : string {
            return $this->target;
        }
    }
?>