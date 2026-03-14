<?php
    /**
	 * Project Name:    Wingman — Locator — Resolution Result
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 17 2025
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    
    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use Wingman\Locator\Objects\PathExpression;

    /**
     * Represents the result of a resolution.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class ResolutionResult {
        /**
         * The value of a result.
         * @var mixed
         */
        private mixed $value;

        /**
         * Whether the result is terminal.
         * @var bool
         */
        private bool $terminal;

        /**
         * The resolution context of a result.
         * @var ResolutionContext
         */
        private ResolutionContext $context;

        /**
         * Creates a new resolution result.
         * @param mixed $value The value.
         * @param bool $terminal Whether the result is terminal.
         */
        private function __construct (mixed $value, ResolutionContext $context, bool $terminal) {
            $this->value = $value;
            $this->context = $context;
            $this->terminal = $terminal;
        }
    
        /**
         * Creates a continuation result.
         * @param PathExpression|string $next The next path to resolve.
         * @return static The continuation result.
         */
        public static function continue (PathExpression|string $next, ResolutionContext $context) : static {
            return new static($next, $context, false);
        }
    
        /**
         * Creates a terminal result.
         * @param mixed $resource The resolved resource.
         * @return static The terminal result.
         */
        public static function terminal (mixed $resource, ResolutionContext $context) : static {
            return new static($resource, $context, true);
        }

        /**
         * Gets the resolution context of a result.
         * @return ResolutionContext The resolution context.
         */
        public function getContext () : ResolutionContext {
            return $this->context;
        }

        /**
         * Gets the path of a result.
         * @return PathExpression|string The path exoression.
         */
        public function getPath () : PathExpression|string {
            return $this->value;
        }
    
        /**
         * Gets the resource of a result.
         * @return mixed The resource.
         */
        public function getResource () : mixed {
            return $this->value;
        }
    
        /**
         * Checks whether a result is terminal.
         * @return bool Whether the result is terminal.
         */
        public function isTerminal () : bool {
            return $this->terminal;
        }
    }
?>