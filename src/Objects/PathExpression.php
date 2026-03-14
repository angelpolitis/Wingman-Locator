<?php
    /**
	 * Project Name:    Wingman — Locator — Path Expression
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 15 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Enums\PathRootVariable;

    /**
     * Represents a path expression.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PathExpression {
        /**
         * The relative part of the path.
         * @var string
         */
        protected string $relativePath;

        /**
         * The root type of the path.
         * @var PathRootType
         */
        protected PathRootType $rootType;

        /**
         * The argument of the root, if applicable.
         * @var string|null
         */
        protected ?string $rootArg = null;

        /**
         * The variable if rootType == VARIABLE.
         * @var PathRootVariable|null
         */
        protected ?PathRootVariable $variable = null;
    
        /**
         * The raw string representation of a path expression.
         * @var string
         */
        protected string $raw;
        
        /**
         * Creates a new path.
         * @param string $relativePath The relative part of the path.
         * @param PathRootType $root The root type of the path.
         * @param string|null $rootArg The argument of the root, if applicable.
         */
        public function __construct (string $relativePath, PathRootType $rootType, ?string $rootArg = null, ?PathRootVariable $variable = null, ?string $raw = null) {
            $this->relativePath = $relativePath;
            $this->rootType = $rootType;
            $this->rootArg = $rootArg;
            $this->variable = $variable;
            $this->raw = $raw ?? $this->__toString();
        }

        /**
         * Converts a path to a string.
         * @return string The string representation of the path.
         */
        public function __toString () : string {
            $r = ltrim($this->relativePath, '/\\');
            return match ($this->rootType) {
                PathRootType::VARIABLE => "@{" . (strtolower($this->variable->name) ?? $this->rootArg) . "}" . ($r !== '' ? '/' . $r : ''),
                PathRootType::NAMESPACE => "@{$this->rootArg}" . ($r !== '' ? '/' . $r : ''),
                PathRootType::ABSOLUTE => "/$r",
                PathRootType::DRIVE => $this->rootArg . ":/$r",
                PathRootType::RELATIVE_EXPLICIT => "./$r",
                PathRootType::RELATIVE_IMPLICIT => $r,
            };
        }

        /**
         * Analyses a raw path string and creates a new path expression.
         * @param string $raw The raw path string.
         * @return static The analysed path.
         */
        public static function from (string $raw) : static {
            [$rootType, $rootArg, $relative] = PathRootType::detect(trim($raw));

            $variable = null;
            if ($rootType === PathRootType::VARIABLE && isset($rootArg)) {
                $variable = PathRootVariable::try($rootArg) ?? PathRootVariable::UNKNOWN;
            }
            return new static($relative, $rootType, $rootArg, $variable, $raw);
        }

        /**
         * Gets the relative part of a path expression.
         * @return string The relative part of the path expression.
         */
        public function getRelativePath () : string {
            return $this->relativePath;
        }

        /**
         * Gets the argument of a path expression's root, if applicable.
         * @return string|null The argument of the path expression's root, or `null` if there is none.
         */
        public function getRootArg () : ?string {
            return $this->rootArg;
        }

        /**
         * Gets the root of a path expression.
         * @return PathRootType The root of the path expression.
         */
        public function getRootType () : PathRootType {
            return $this->rootType;
        }

        /**
         * Gets the variable of a path expression, if applicable.
         * @return PathRootVariable|null The variable of the path expression, or `null` if there is none.
         */
        public function getVariable (): ?PathRootVariable {
            return $this->variable;
        }

        /**
         * Normalises a path expression.
         * @param self|string $path The path expression or raw path string.
         * @return static The normalised path expression.
         */
        public static function normalise (self|string $path) : static {
            return $path instanceof static ? $path : static::from($path);
        }

        /**
         * Creates a new path expression with the provided relative path.
         * @param string $path The path.
         * @return static The path expression.
         */
        public function withRelativePath (string $path) : static {
            return new static($path, $this->rootType, $this->rootArg, $this->variable);
        }
    }
?>