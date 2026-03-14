<?php
    /**
     * Project Name:    Wingman — Locator — Discovery Profile
     * Created by:      Angel Politis
     * Creation Date:   Feb 24 2026
     * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use Wingman\Locator\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Locator\Bridge\Cortex\Configuration;

    /**
     * Governs the rules for manifest discovery, including depth and path filtering.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class DiscoveryProfile {
        /**
         * The maximum depth to scan for manifests. Set to -1 for unlimited.
         * @var int
         */
        #[Configurable("locator.discovery.depth", "The maximum depth to scan for manifests. Set to -1 for unlimited.")]
        private int $maxDepth = 1;

        /**
         * Whether to only scan the root directory (i.e., no subdirectories).
         * @var bool
         */
        #[Configurable("locator.discovery.onlyRoot", "Whether to only scan the root directory.")]
        private bool $onlyRoot = false;

        /**
         * Patterns of paths to include in the scan. If not empty, a path must match at least one pattern to be included.
         * @var array
         */
        #[Configurable("locator.discovery.include", "Patterns of paths to include in the scan.")]
        private array $included = [];

        /**
         * Patterns of paths to exclude from the scan. If a path matches any of these patterns, it will be excluded.
         * @var array
         */
        #[Configurable("locator.discovery.exclude", "Patterns of paths to exclude from the scan.")]
        private array $excluded = [];

        /**
         * Whether to automatically omit hidden files and directories (those starting with a dot). This is enabled by default.
         * @var bool
         */
        #[Configurable("locator.discovery.omitHidden", "Whether to automatically omit hidden files and directories (those starting with a dot). This is enabled by default.")]
        private bool $omitHidden = true;

        /**
         * Creates a new discovery profile with the specified settings.
         * @param array|Configuration $config An associative array or Configuration object of settings to override the defaults. Array keys must be fully‑qualified dot-notation keys (e.g. `"locator.discovery.depth"`); use `from()` when passing short-form keys.
         */
        public function __construct (array|Configuration $config = []) {
            $config = $config instanceof Configuration ? $config : Configuration::fromIterable($config);
            Configuration::hydrate($this, $config);
            $this->included = $this->preparePatterns($this->included);
            $this->excluded = $this->preparePatterns($this->excluded);
        }

        /**
         * Creates a new discovery profile from an array of properties.
         * @param array $properties An associative array of properties to set on the profile.
         * @return static The discovery profile with the specified properties.
         */
        public static function __set_state (array $properties) : static {
            $profile = new static();
            foreach ($properties as $key => $value) {
                if (property_exists($profile, $key)) {
                    $profile->$key = $value;
                }
            }
            return $profile;
        }

        /**
         * Converts the discovery profile to a string representation (JSON format).
         * @return string The string representation of the discovery profile.
         */
        public function __toString () : string {
            return json_encode([
                "depth" => $this->maxDepth,
                "onlyRoot" => $this->onlyRoot,
                "include" => $this->included,
                "exclude" => $this->excluded,
                "omitHidden" => $this->omitHidden
            ]);
        }

        /**
         * Translates glob-style patterns into equivalent regex patterns.
         * Supports:
         *  - `**` — matches any number of path segments at any depth.
         *  - `*` — matches any characters within a single path segment (no `/`).
         *  - Literal `.` and other regex-special characters are escaped.
         * @param array $patterns An array of glob-style patterns.
         * @return array An array of compiled regex patterns.
         */
        private function preparePatterns (array $patterns) : array {
            return array_map(function (string $pattern) : string {
                # Normalise separators and trim trailing slashes.
                $pattern = str_replace('\\', '/', rtrim($pattern, '/'));

                # Split on `**` to isolate double-star wildcards before any escaping.
                $doubleStar = explode('**', $pattern);

                $parts = [];
                foreach ($doubleStar as $i => $segment) {
                    # Within each segment, escape regex specials then convert single `*`.
                    $escaped = preg_quote($segment, '#');
                    $escaped = str_replace('\\*', '[^/]*', $escaped);
                    $parts[] = $escaped;

                    # Re-insert `**` as a regex that matches any path (including separators).
                    if ($i < count($doubleStar) - 1) {
                        $parts[] = '.*';
                    }
                }

                $regex = implode('', $parts);

                # Anchor the pattern so it matches the full relative path or any sub-path component.
                return '#(^|/)' . $regex . '(/|$)#';
            }, $patterns);
        }

        /**
         * Converts the discovery profile to a plain array of its internal property values.
         * This is used by the cache manager to persist profiles without relying on `var_export`'s
         * class-dependent `__set_state` serialisation, which causes `__PHP_Incomplete_Class` errors
         * when the class is not yet loaded during cache file inclusion.
         * @return array The dehydrated profile data.
         */
        public function dehydrate () : array {
            return [
                "excluded"  => $this->excluded,
                "included"  => $this->included,
                "maxDepth"  => $this->maxDepth,
                "omitHidden" => $this->omitHidden,
                "onlyRoot"  => $this->onlyRoot,
            ];
        }

        /**
         * Compares a profile with another profile or an array of settings.
         * @param array|self $profile The profile to compare with, either as a `DiscoveryProfile` instance or an associative array of settings.
         * @return bool Whether the profiles are equivalent.
         */
        public function equals (array|self $profile) : bool {
            if ($profile instanceof static) {
                $profile = [
                    "depth" => $profile->maxDepth,
                    "onlyRoot" => $profile->onlyRoot,
                    "include" => $profile->included,
                    "exclude" => $profile->excluded,
                    "omitHidden" => $profile->omitHidden
                ];
            }
            return $profile["depth"] === $this->maxDepth
                && $profile["onlyRoot"] === $this->onlyRoot
                && $profile["omitHidden"] === $this->omitHidden
                && $profile["include"] === $this->included
                && $profile["exclude"] === $this->excluded;
        }

        /**
         * Creates a discovery profile from an array of settings.
         * Supports caller-facing short keys (`"depth"`, `"onlyRoot"`, `"omitHidden"`, `"include"`, `"exclude"`)
         * and maps them directly to the corresponding internal properties, bypassing the Configuration pipeline
         * so that array-valued settings (e.g. include/exclude lists) are never unintentionally expanded into
         * nested namespaces by `Configuration::fromIterable()`.
         * @param array $settings An associative array of settings to override the defaults. Supported keys: "depth", "onlyRoot", "include", "exclude", "omitHidden".
         * @return static The discovery profile.
         */
        public static function from (array $settings) : static {
            $keyMap = [
                "depth"      => "maxDepth",
                "onlyRoot"   => "onlyRoot",
                "omitHidden" => "omitHidden",
                "include"    => "included",
                "exclude"    => "excluded",
            ];

            $profile = new static();

            $profile->maxDepth = -1;

            foreach ($settings as $key => $value) {
                $propName = $keyMap[$key] ?? $key;

                if (property_exists($profile, $propName)) {
                    $profile->$propName = $value;
                }
            }

            $profile->included = $profile->preparePatterns($profile->included);
            $profile->excluded = $profile->preparePatterns($profile->excluded);

            return $profile;
        }

        /**
         * Determines if a path should be processed based on legacy rules.
         * @param string $relativePath The path relative to the discovery root.
         * @param int $depth The depth of the path from the discovery root.
         * @return bool Whether the path should be processed.
         */
        public function validate (string $relativePath, int $depth) : bool {
            # Depth check.
            if ($this->maxDepth !== -1 && $depth > $this->maxDepth) return false;
            if ($this->onlyRoot && $depth > 0) return false;

            # Hidden files and directories filter.
            if ($this->omitHidden && preg_match('#(^|/)\.[^/\.]#', $relativePath)) return false;

            # Include filter.
            if (!empty($this->included)) {
                $matched = false;
                foreach ($this->included as $regex) {
                    if (preg_match($regex, $relativePath)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) return false;
            }

            # Exclude filter.
            foreach ($this->excluded as $regex) {
                if (preg_match($regex, $relativePath)) return false;
            }

            return true;
        }
    }
?>