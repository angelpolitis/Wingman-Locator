<?php
    /**
	 * Project Name:    Wingman — Locator — Resolution Context
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
    use RuntimeException;
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Objects\Symbol;

    /**
     * Represents the context of a resolution.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class ResolutionContext {
        /**
         * The implicit namespace of a context.
         * @var string
         */
        private string $implicitNamespace;

        /**
         * The namespace of a context.
         * @var string|null
         */
        private ?string $namespace;
        
        /**
         * The manifest of a context.
         * @var string|null
         */
        private ?string $manifest;

        /**
         * Whether relative roots use the default namespace.
         * @var bool
         */
        private bool $relativeUsesDefaultNamespace = false;

        /**
         * The root used by relative paths of a context.
         * @var PathRootVariable
         */
        private PathRootVariable $relativeRoot = PathRootVariable::SERVER;

        /**
         * The root paths of a context.
         * @var array<string, string>
         */
        private array $roots = [];

        /**
         * The symbol used to scope a context.
         * @var Symbol|null
         */
        private ?Symbol $symbol = null;
    
        /**
         * Creates a new context.
         * @param string $namespace The namespace.
         * @param string|null $manifest The manifest (optional).
         */
        public function __construct (string $namespace, ?string $manifest = null) {
            $this->namespace = $namespace;
            $this->manifest = $manifest;
        }

        /**
         * Gets the current working directory root path of a context.
         * @return string The CWD root path.
         */
        private function getCwdRoot () : string {
            return $this->roots["cwd"] ?? getcwd();
        }

        /**
         * Gets the manifest root path of a context.
         * @return string The manifest root path.
         * @throws RuntimeException If the manifest is not defined.
         */
        private function getManifestRoot () : string {
            $manifest = $this->manifest;

            if (!$manifest) {
                if ($this->symbol) {
                    $symbolName = $this->symbol->getName();
                    throw new RuntimeException("The symbol '$symbolName' has not been registered through a manifest.");
                }
                else throw new RuntimeException("The variable 'manifest' can only be used inside manifest-registered symbols/virtuals.");
            }

            return dirname($manifest);
        }

        /**
         * Gets the OS root path of a context.
         * @return string The OS root path.
         */
        private function getOsRoot () : string {
            if (isset($this->roots["os"])) {
                return $this->roots["os"];
            }
            if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
                $cwd = getcwd();
                if (!$cwd) return "C:\\";
                return substr($cwd, 0, 3);
            }
            return '/';
        }

        /**
         * Gets the relative root path of a context.
         * @return string The relative root path.
         */
        private function getRelativeRoot () : string {
            if ($this->symbol) {
                return "@{$this->namespace}";
            }
            
            return $this->relativeUsesDefaultNamespace ? "@{$this->namespace}" : $this->relativeRoot->render();
        }

        /**
         * Gets the server root path of a context.
         * @return string The server root path.
         */
        private function getServerRoot () : string {
            if (isset($this->roots["server"])) return $this->roots["server"];
            return $_SERVER["DOCUMENT_ROOT"] ?? "";
        }

        /**
         * Gets the root paths of a context.
         * @return array<string, string> The root paths.
         */
        public function getRoots () : array {
            return $this->roots;
        }

        /**
         * Gets the implicit namespace of a context.
         * @return string The implicit namespace.
         */
        public function getImplicitNamespace () : string {
            return $this->implicitNamespace;
        }

        /**
         * Gets the manifest of a context.
         * @return string|null The manifest.
         */
        public function getManifest () : ?string {
            return $this->manifest;
        }

        /**
         * Gets the namespace a context.
         * @return string The namespace.
         */
        public function getNamespace () : string {
            return $this->namespace ?? $this->implicitNamespace;
        }

        /**
         * Gets the root path based on the given root variable.
         * @param PathRootType|PathRootVariable $typeOrvariable The root type or variable.
         * @return string The root path.
         */
        public function getRoot (PathRootType|PathRootVariable $typeOrvariable) : string {
            return match ($typeOrvariable) {
                PathRootType::ABSOLUTE => $this->getServerRoot(),
                PathRootType::RELATIVE_EXPLICIT => $this->symbol ? $this->getManifestRoot() : $this->getRelativeRoot(),
                PathRootType::RELATIVE_IMPLICIT => $this->getRelativeRoot(),
                PathRootVariable::SERVER => $this->getServerRoot(),
                PathRootVariable::CWD => $this->getCwdRoot(),
                PathRootVariable::OS => $this->getOsRoot(),
                PathRootVariable::MANIFEST => $this->getManifestRoot(),
                PathRootVariable::PACKAGE => throw new RuntimeException("Package root resolution is not implemented yet."),
                default => throw new RuntimeException("Unhandled root type '" . $typeOrvariable->name . "'.")
            };
        }

        /**
         * Gets the symbol of a context.
         * @return Symbol|null The symbol.
         */
        public function getSymbol () : ?Symbol {
            return $this->symbol;
        }

        /**
         * Creates a new context.
         * @return static The new context.
         */
        public static function create (string $namespace, ?string $manifest = null) : static {
            return new static($namespace, $manifest);
        }

        /**
         * Sets the implicit namespace of a context.
         * @param string $namespace The implicit namespace.
         * @return static The context.
         */
        public function setImplicitNamespace (string $namespace) : static {
            $this->implicitNamespace = $namespace;
            return $this;
        }

        /**
         * Sets the manifest of a context.
         * @param string|null $manifest The manifest.
         * @return static The context.
         */
        public function setManifest (?string $manifest) : static {
            $this->manifest = $manifest;
            return $this;
        }

        /**
         * Sets the namespace of a context.
         * @param string $namespace The namespace.
         * @return static The context.
         */
        public function setNamespace (string $namespace) : static {
            $this->namespace = $namespace;
            return $this;
        }

        /**
         * Sets the relative root variable of a context.
         * @param PathRootVariable $variable The relative root variable.
         * @return static The context.
         */
        public function setRelativeRoot (PathRootVariable $variable) : static {
            $this->relativeRoot = $variable;
            return $this;
        }

        /**
         * Sets the relative root behavior of a context.
         * @param bool $usesDefaultNamespace Whether relative roots use the default namespace.
         * @return static The context.
         */
        public function setRelativeUsesDefaultNamespace (bool $usesDefaultNamespace) : static {
            $this->relativeUsesDefaultNamespace = $usesDefaultNamespace;
            return $this;
        }

        /**
         * Sets the roots of a context.
         * @param array{cwd: string, os: string, server: string} $roots The roots.
         * @return static The context.
         */
        public function setRoots (array $roots) : static {
            $this->roots = $roots;
            return $this;
        }

        /**
         * Sets the symbol of a context.
         * @param Symbol|null $symbol The symbol.
         * @return static The context.
         */
        public function setSymbol (?Symbol $symbol) : static {
            $this->symbol = $symbol;
            return $this;
        }
    }
?>