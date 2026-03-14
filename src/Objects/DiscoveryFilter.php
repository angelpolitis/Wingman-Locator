<?php
    /**
     * Project Name:    Wingman — Locator — Discovery Filter
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
    use RecursiveDirectoryIterator;
    use RecursiveFilterIterator;
    use RecursiveIterator;
    use Wingman\Locator\PathUtils;

    /**
     * A filter iterator that applies the rules defined in a DiscoveryProfile to determine which files and directories should be included in the discovery process.
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class DiscoveryFilter extends RecursiveFilterIterator {
        /**
         * The discovery profile containing the rules to apply.
         * @var DiscoveryProfile
         */
        private DiscoveryProfile $profile;

        /**
         * The root directory from which the discovery process starts. This is used to calculate relative paths for filtering.
         * @var string
         */
        private string $rootDirectory;

        /**
         * Creates a new discovery filter.
         * @param RecursiveIterator $iterator The inner iterator to wrap, which should be a RecursiveDirectoryIterator.
         * @param DiscoveryProfile $profile The discovery profile containing the rules to apply.
         * @param string $rootDirectory The root directory from which the discovery process starts. This is used to calculate relative paths for filtering.
         */
        public function __construct (RecursiveIterator $iterator, DiscoveryProfile $profile, string $rootDirectory) {
            parent::__construct($iterator);
            $this->profile = $profile;
            $this->rootDirectory = $rootDirectory;
        }

        /**
         * Determines whether the current file or directory should be included in the discovery process based on the rules defined in the discovery profile.
         * This involves checking the depth, applying include and exclude patterns, and optionally omitting hidden files and directories.
         * @return bool Whether the current file or directory should be included in the discovery process.
         */
        public function accept () : bool {
            $file = $this->current();
            $fullPath = $file->getRealPath();
            
            $relativePath = PathUtils::fix(str_replace($this->rootDirectory, "", $fullPath));
            $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

            if ($relativePath === "") return true;

            $depth = substr_count($relativePath, DIRECTORY_SEPARATOR);

            return $this->profile->validate($relativePath, $depth);
        }

        /**
         * Gets the children of the current iterator.
         * @return RecursiveFilterIterator|null The children iterator, or `null` if there are no children.
         */
        public function getChildren () : ?RecursiveFilterIterator {
            /** @var RecursiveDirectoryIterator $directoryIterator */
            $directoryIterator = $this->getInnerIterator();
            return new self($directoryIterator->getChildren(), $this->profile, $this->rootDirectory);
        }
    }
?>