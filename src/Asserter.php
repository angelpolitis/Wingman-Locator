<?php
    /**
	 * Project Name:    Wingman — Locator — Asserter
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 13 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator namespace.
    namespace Wingman\Locator;

    # Import the following classes to the current scope.
    use FilesystemIterator;
    use Wingman\Locator\Exceptions\FileNotWritableException;
    use Wingman\Locator\Exceptions\NonexistentDirectoryException;
    use Wingman\Locator\Exceptions\NonexistentFileException;
    use Wingman\Locator\Exceptions\NotADirectoryException;
    use Wingman\Locator\Exceptions\NotAFileException;

    /**
     * Represents an asserter.
     * @package Wingman\Locator
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Asserter {
        /**
         * Ensures the static class cannot be instantiated.
         */
        private function __construct () {}

        /**
         * Asserts whether a directory is empty.
         * @param string $directory The directory.
         * @return bool `true` if the directory is empty; `false` otherwise.
         */
        public static function isDirectoryEmpty (string $directory) : bool {
            static::requireDirectoryAt($directory);
            $files = new FilesystemIterator($directory);
            return !$files->valid();
        }

        /**
         * Requires that a path points to an existent directory.
         * @param string $path The path.
         * @throws NonexistentDirectoryException If the given path doesn't point to a directory.
         * @throws NotADirectoryException If the given path points to a type of resource other than a directory.
         */
        public static function requireDirectoryAt (string $path) : void {
            # Check whether the given path doesn't exist.
            if (!file_exists($path)) {
                throw new NonexistentDirectoryException("The directory '$path' couldn't be found.");
            }

            # Check whether the given path doesn't point to a directory.
            if (!is_dir($path)) {
                throw new NotADirectoryException("The resource '$path' isn't a directory.");
            }
        }

        /**
         * Requires that a path points to an existent file.
         * @param string $path The path to the file.
         * @param bool $requireWritable Whether the file must be writable.
         * @throws NonexistentFileException If the given path doesn't point to a file.
         * @throws NotAFileException If the given path points to a type of resource other than a file.
         */
        public static function requireFileAt (string $path, bool $requireWritable = false) : void {
            # Terminate the function if the path is a URL or a PHP stream.
            if (PathUtils::isURL($path) || preg_match("#^php://\\w+$#", $path)) return;

            # Throw an exception if the given path doesn't exist.
            if (!file_exists($path)) {
                throw new NonexistentFileException("The file at '$path' couldn't be found.");
            }

            # Throw an exception if the given path doesn't point to a file.
            if (!is_file($path)) {
                throw new NotAFileException("The resource at '$path' isn't a file.");
            }

            # Throw an exception if the file isn't writable.
            if ($requireWritable && !is_writable($path)) {
                throw new FileNotWritableException("The file at '$path' isn't writable.");
            }
        }
    }
?>