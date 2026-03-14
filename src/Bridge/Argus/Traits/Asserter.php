<?php
    /**
     * Project Name:    Wingman — Locator — Asserter Trait
     * Created by:      Angel Politis
     * Creation Date:   Mar 13 2026
     * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Bridge.Argus.Traits namespace.
    namespace Wingman\Locator\Bridge\Argus\Traits;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the trait is already in place there is nothing to do.
    if (trait_exists(__NAMESPACE__ . '\Asserter', false)) return;

    # Import the following classes to the current scope.
    use Throwable;
    use Wingman\Locator\Locator;

    /**
     * Provides assertion methods for verifying path resolution, filesystem existence, path types, namespace
     * resolvability, and manifest discovery state through the Locator singleton.
     * This trait is intended for use in test classes that need to assert Locator-specific state. It follows
     * the same pattern as the Helix and Verix Asserter traits, delegating result recording to the abstract
     * `recordAssertion` method that the consuming test class must supply.
     * @package Wingman\Locator\Bridge\Argus\Traits
     * @author Angel Politis <info@angelpolitis.com>
     * @since 0.1
     */
    trait Asserter {
        /**
         * Checks whether a manifest is or is not loaded for the given namespace and records the result.
         * Retrieves the manifest repository from the Locator singleton and counts manifests registered
         * under the supplied namespace name.
         * @param string $namespace The canonical namespace name to check.
         * @param bool $shouldExist Whether a loaded manifest is expected (true) or not expected (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runManifestAssertion (string $namespace, bool $shouldExist, string $message) : void {
            try {
                $manifests = Locator::get()->getManifestRepository()->getByNamespace($namespace);
                $status = count($manifests) > 0;

                $this->recordAssertion(
                    $shouldExist ? $status : !$status,
                    ($shouldExist ? "Manifest loaded for namespace: " : "No manifest loaded for namespace: ") . $namespace,
                    $status ? "Found " . count($manifests) . " manifest(s)" : "No manifests found for namespace",
                    $message ?: "Locator manifest assertion failed."
                );
            }
            catch (Throwable $e) {
                $this->recordAssertion(
                    false,
                    ($shouldExist ? "Manifest loaded for namespace: " : "No manifest loaded for namespace: ") . $namespace,
                    "Error: " . $e->getMessage(),
                    $message ?: "Locator manifest assertion failed."
                );
            }
        }

        /**
         * Checks whether a namespace is or is not resolvable to a root directory and records the result.
         * Calls `getPathToNamespace()` on the Locator singleton and treats a non-null result as resolvable.
         * @param string $namespace The namespace name to test.
         * @param bool $shouldExist Whether the namespace is expected to be resolvable (true) or not (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runNamespaceAssertion (string $namespace, bool $shouldExist, string $message) : void {
            try {
                $path = Locator::get()->getPathToNamespace($namespace);
                $status = $path !== null;

                $this->recordAssertion(
                    $shouldExist ? $status : !$status,
                    ($shouldExist ? "Namespace resolvable: " : "Namespace not resolvable: ") . $namespace,
                    $status ? "Resolved to: " . $path : "Namespace could not be resolved",
                    $message ?: "Locator namespace assertion failed."
                );
            }
            catch (Throwable $e) {
                $this->recordAssertion(
                    false,
                    ($shouldExist ? "Namespace resolvable: " : "Namespace not resolvable: ") . $namespace,
                    "Error: " . $e->getMessage(),
                    $message ?: "Locator namespace assertion failed."
                );
            }
        }

        /**
         * Checks whether a path expression resolves to an existing filesystem entry and records the result.
         * Calls `getPathTo()` on the Locator singleton, which returns `null` when the resolved path does not exist.
         * @param string $expression The path expression to resolve.
         * @param bool $shouldExist Whether the path is expected to exist (true) or not (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runPathExistenceAssertion (string $expression, bool $shouldExist, string $message) : void {
            try {
                $path = Locator::get()->getPathTo($expression);
                $status = $path !== null;

                $this->recordAssertion(
                    $shouldExist ? $status : !$status,
                    ($shouldExist ? "Path exists: " : "Path does not exist: ") . $expression,
                    $status ? "Found at: " . $path : "Path does not exist on the filesystem",
                    $message ?: "Locator path existence assertion failed."
                );
            }
            catch (Throwable $e) {
                $this->recordAssertion(
                    false,
                    ($shouldExist ? "Path exists: " : "Path does not exist: ") . $expression,
                    "Error: " . $e->getMessage(),
                    $message ?: "Locator path existence assertion failed."
                );
            }
        }

        /**
         * Checks whether a path expression resolves to or does not resolve to a given absolute path and records the result.
         * Calls `getPathFor()` on the Locator singleton, which performs resolution without an existence check.
         * @param string $expression The path expression to resolve.
         * @param string $expected The expected absolute path.
         * @param bool $shouldMatch Whether the expression is expected to resolve to `$expected` (true) or not (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runPathResolutionAssertion (string $expression, string $expected, bool $shouldMatch, string $message) : void {
            try {
                $resolved = Locator::get()->getPathFor($expression);
                $status = $resolved === $expected;

                $this->recordAssertion(
                    $shouldMatch ? $status : !$status,
                    ($shouldMatch ? "Resolves to: " : "Does not resolve to: ") . $expected,
                    "Resolved to: " . $resolved,
                    $message ?: "Locator path resolution assertion failed."
                );
            }
            catch (Throwable $e) {
                $this->recordAssertion(
                    false,
                    ($shouldMatch ? "Resolves to: " : "Does not resolve to: ") . $expected,
                    "Error: " . $e->getMessage(),
                    $message ?: "Locator path resolution assertion failed."
                );
            }
        }

        /**
         * Checks whether a path expression resolves to a specific filesystem type (file or directory) and records the result.
         * Calls either `getPathToFile()` or `getPathToDirectory()` depending on the supplied `$type`, treating a non-null
         * result as confirmation that the path exists and is of the expected type.
         * @param string $expression The path expression to resolve.
         * @param string $type The expected type; one of `"file"` or `"directory"`.
         * @param bool $shouldMatch Whether the path is expected to be of the given type (true) or not (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runPathTypeAssertion (string $expression, string $type, bool $shouldMatch, string $message) : void {
            try {
                $locator = Locator::get();

                $path = match ($type) {
                    "file"      => $locator->getPathToFile($expression),
                    "directory" => $locator->getPathToDirectory($expression),
                };

                $status = $path !== null;

                $this->recordAssertion(
                    $shouldMatch ? $status : !$status,
                    ($shouldMatch ? "Path is a $type: " : "Path is not a $type: ") . $expression,
                    $status ? "Confirmed $type at: " . $path : "Not a $type or does not exist",
                    $message ?: "Locator path type assertion failed."
                );
            }
            catch (Throwable $e) {
                $this->recordAssertion(
                    false,
                    ($shouldMatch ? "Path is a $type: " : "Path is not a $type: ") . $expression,
                    "Error: " . $e->getMessage(),
                    $message ?: "Locator path type assertion failed."
                );
            }
        }

        /**
         * Records the result of an assertion, including its status, expected and actual values, and an optional message.
         * This method is intended to be implemented by the consuming class to handle assertion recording in a way that fits its architecture.
         * @param bool $status The result of the assertion (true for pass, false for fail).
         * @param mixed $expected The expected value in the assertion.
         * @param mixed $actual The actual value obtained during the test.
         * @param string $message An optional message providing additional context about the assertion.
         */
        abstract protected function recordAssertion (bool $status, mixed $expected, mixed $actual, string $message) : void;

        /**
         * Asserts that at least one manifest has been loaded for the given namespace.
         * @param string $namespace The canonical namespace name to check.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertManifestLoaded (string $namespace, string $message = "") : void {
            $this->runManifestAssertion($namespace, true, $message);
        }

        /**
         * Asserts that no manifest has been loaded for the given namespace.
         * @param string $namespace The canonical namespace name to check.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertManifestNotLoaded (string $namespace, string $message = "") : void {
            $this->runManifestAssertion($namespace, false, $message);
        }

        /**
         * Asserts that the given namespace cannot be resolved to a root directory.
         * @param string $namespace The namespace name to test.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertNamespaceNotResolvable (string $namespace, string $message = "") : void {
            $this->runNamespaceAssertion($namespace, false, $message);
        }

        /**
         * Asserts that the given namespace can be resolved to a root directory.
         * @param string $namespace The namespace name to test.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertNamespaceResolvable (string $namespace, string $message = "") : void {
            $this->runNamespaceAssertion($namespace, true, $message);
        }

        /**
         * Asserts that the given path expression resolves to an existing filesystem entry.
         * @param string $expression The path expression to resolve.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathExists (string $expression, string $message = "") : void {
            $this->runPathExistenceAssertion($expression, true, $message);
        }

        /**
         * Asserts that the given path expression resolves to an existing directory.
         * @param string $expression The path expression to resolve.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathIsDirectory (string $expression, string $message = "") : void {
            $this->runPathTypeAssertion($expression, "directory", true, $message);
        }

        /**
         * Asserts that the given path expression resolves to an existing file.
         * @param string $expression The path expression to resolve.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathIsFile (string $expression, string $message = "") : void {
            $this->runPathTypeAssertion($expression, "file", true, $message);
        }

        /**
         * Asserts that the given path expression does not resolve to an existing filesystem entry.
         * @param string $expression The path expression to resolve.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathNotExists (string $expression, string $message = "") : void {
            $this->runPathExistenceAssertion($expression, false, $message);
        }

        /**
         * Asserts that the given path expression does not resolve to an existing directory.
         * Passes when the path does not exist or exists but is not a directory.
         * @param string $expression The path expression to resolve.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathNotIsDirectory (string $expression, string $message = "") : void {
            $this->runPathTypeAssertion($expression, "directory", false, $message);
        }

        /**
         * Asserts that the given path expression does not resolve to an existing file.
         * Passes when the path does not exist or exists but is not a file.
         * @param string $expression The path expression to resolve.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathNotIsFile (string $expression, string $message = "") : void {
            $this->runPathTypeAssertion($expression, "file", false, $message);
        }

        /**
         * Asserts that the given path expression does not resolve to the specified absolute path.
         * @param string $expression The path expression to resolve.
         * @param string $expected The absolute path that the expression should not resolve to.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathNotResolvesTo (string $expression, string $expected, string $message = "") : void {
            $this->runPathResolutionAssertion($expression, $expected, false, $message);
        }

        /**
         * Asserts that the given path expression resolves to the specified absolute path.
         * @param string $expression The path expression to resolve.
         * @param string $expected The expected absolute path.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPathResolvesTo (string $expression, string $expected, string $message = "") : void {
            $this->runPathResolutionAssertion($expression, $expected, true, $message);
        }
    }
?>