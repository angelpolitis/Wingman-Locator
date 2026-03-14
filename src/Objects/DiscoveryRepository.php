<?php
    /**
     * Project Name:    Wingman — Locator — Discovery Repository
     * Created by:      Angel Politis
     * Creation Date:   Feb 25 2026
     * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    # Import the following classes to the current scope.
    use Wingman\Locator\PathUtils;

    /**
     * Represents a discovery repository.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class DiscoveryRepository {
        /**
         * The entries of a repository.
         * @var array<int, array{path: string, profile: DiscoveryProfile}>
         */
        protected array $entries = [];

        /**
         * The unique path-profile signatures of a repository.
         * @var array<string, true>
         */
        protected array $signatures = [];

        /**
         * Creates a new discovery repository.
         */
        public function __construct () {}

        /**
         * Creates a unique key for a path-profile pair.
         * @param string $path The path to create a key for.
         * @param DiscoveryProfile $profile The discovery profile to create a key for.
         * @return string The unique key for the path-profile pair.
         */
        private static function createKey (string $path, DiscoveryProfile $profile) : string {
            return md5($path . "\x00" . $profile);
        }

        /**
         * Adds a discovery profile to a repository.
         * @param string $path The path associated with the discovery profile.
         * @param DiscoveryProfile $profile The discovery profile to add.
         * @return static The repository.
         */
        public function add (string $path, DiscoveryProfile $profile) : static {
            $this->entries[] = ["path" => $path, "profile" => $profile];
            $this->signatures[self::createKey($path, $profile)] = true;
            return $this;
        }

        /**
         * Exports the content of the repository in a dehydrated format.
         * @return array<int, array{path: string, profile: DiscoveryProfile}> The dehydrated content of the repository.
         */
        public function exportContent () : array {
            $dehydrated = [];
            foreach ($this->entries as $entry) {
                $dehydrated[] = [
                    "path"    => PathUtils::getRelativePath(dirname(dirname(__DIR__)), $entry["path"]),
                    "profile" => $entry["profile"]->dehydrate(),
                ];
            }
            return $dehydrated;
        }

        /**
         * Gets all entries in the repository.
         * @return array<int, array{path: string, profile: DiscoveryProfile}> The entries of the repository.
         */
        public function getAll () : array {
            return $this->entries;
        }

        /**
         * Checks if a repository has a discovery profile for a path.
         * @param string $path The path to check.
         * @param DiscoveryProfile $profile The discovery profile to check for.
         * @return bool True if the repository has the discovery profile for the path, false otherwise.
         */
        public function has (string $path, DiscoveryProfile $profile) : bool {
            return isset($this->signatures[self::createKey($path, $profile)]);
        }
    }
?>