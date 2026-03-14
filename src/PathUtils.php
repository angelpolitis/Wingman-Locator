<?php
    /**
	 * Project Name:    Wingman — Locator — Path Utilities
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
    use Wingman\Locator\Exceptions\PathTraversalException;
    use Wingman\Locator\Objects\URI;

    /**
     * A static class that groups together various pure path-related operations.
     * @package Wingman\Locator
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class PathUtils {
        /**
         * Ensures the static class cannot be instantiated.
         */
        private function __construct () {}

        /**
         * Normalises a path component by:
         *  - Replacing multiple slashes with a single slash.
         *  - Resolving `.` and `..` segments.
         *  - Standardising directory separators.
         * @param string $path The path to normalise.
         * @param string $separator The directory separator to use in the normalised path.
         * @return string The normalised path component.
         */
        private static function normalisePathComponent (string $path, string $separator) : string {
            # Detect Windows drive prefix (C:, D:, etc.).
            # Preserve UNC prefix (// or \\)
            $uncPrefix = "";
            if (str_starts_with($path, '//') || str_starts_with($path, '\\\\')) {
                $uncPrefix = $separator . $separator;
                $path = substr($path, 2);
            }

            # Detect Windows drive prefix (C:, D:, etc.)
            $drive = "";
            if (preg_match('#^[a-zA-Z]:#', $path, $m)) {
                $drive = $m[0];
                $path = substr($path, 2);
            }
        
            # Standardise separators.
            $path = str_replace(['/', '\\'], $separator, $path);
        
            # Collapse duplicate separators.
            $path = preg_replace('#' . preg_quote($separator) . "+#", $separator, $path);
        
            $isAbsolute = str_starts_with($path, $separator);
        
            $parts = [];
            foreach (explode($separator, $path) as $part) {
                if ($part === "" || $part === '.') {
                    continue;
                }
                if ($part === '..') {
                    array_pop($parts);
                    continue;
                }
                $parts[] = $part;
            }
        
            $normalised = implode($separator, $parts);
        
            if ($isAbsolute) {
                $normalised = $separator . $normalised;
            }
        
            # When returning, prepend the UNC prefix instead of just the drive.
            $result = $drive . ($normalised === "" ? ($isAbsolute ? $separator : "") : $normalised);
            
            return $uncPrefix . $result;
        }

        /**
         * Analyses a given path to its components (*namespace* and *path*).
         * @param string $path The path.
         * @return array The *namespace* and *path*.
         */
        public static function analyse (string $path) : array {
            $fixedPath = self::fix($path);
            $ds = preg_quote(DIRECTORY_SEPARATOR);
            $dn = preg_quote(self::fix(NamespaceManager::DEFAULT_NAMESPACE));

            # Check whether the path matches the path notation of selecting a namespace.
            if (preg_match('#^' . $ds . '?@('. $dn .'|[^' . $ds . ']+)' . $ds .'#', $fixedPath, $matches)) {
                return [
                    "namespace" => $matches[1] == DIRECTORY_SEPARATOR ? NamespaceManager::DEFAULT_NAMESPACE : $matches[1],
                    "path" => substr($fixedPath, strlen($matches[0]))
                ];
            }

            # Check whether the path is absolute or a URL (no namespace extraction possible).
            if (self::isAbsolutePath($fixedPath) || self::isURL($fixedPath)) {
                return ["namespace" => null, "path" => $path];
            }

            # Split the path at the first colon.
            $path = preg_split("#(?<!\\\\):#", $path, 2);
            
            if (sizeof($path) > 1) {
                return [
                    "namespace" => $path[0],
                    "path" => $path[1]
                ];
            }

            return ["namespace" => null, "path" => $path[0]];
        }

        /**
         * Resolves a path against a root, ensuring it doesn't escape the root.
         * If the resolved path attempts to escape the root, a `PathTraversalException` is thrown if `$strict` is `true`, otherwise the root is returned.
         * @param string $root The root directory.
         * @param string $path The path to resolve.
         * @param bool $strict Whether to throw an exception on path traversal [default: `true`].
         * @return string The resolved path.
         * @throws PathTraversalException If the resolved path attempts to escape the root and `$strict` is `true`.
         */
        public static function clamp (string $root, string $path, bool $strict = true) : string {
            $root = self::normalise($root);
            
            $absolute = self::isAbsolutePath($path) 
                ? self::normalise($path) 
                : self::normalise(self::join($root, $path));

            $rootWithSeparator = self::forceTrailingSeparator($root);
            $absoluteWithSeparator = self::forceTrailingSeparator($absolute);

            if (!str_starts_with($absoluteWithSeparator, $rootWithSeparator)) {
                if ($strict) {
                    throw new PathTraversalException("Path traversal detected: '$path' attempts to escape root '$root'");
                }
                return $root;
            }

            return $absolute;
        }

        /**
         * Gets a path having replaced with a custom separator all defined separators.
         * @param string|null $path The path to fix.
         * @param string $new The separator to replace the old separators [default: `DIRECTORY_SEPARATOR`].
         * @param string[] $old The separators to be replaced [default: `\`, `/`].
         * @return string|null The fixed path, or `null` if no path was given.
         */
        public static function fix (?string $path, string $new = DIRECTORY_SEPARATOR, array $old = ['\\', '/']) : ?string {
            return is_null($path) ? null : str_replace($old, $new, $path);
		}

        /**
         * Gets a path with the defined trailing separator.
         * @param string $path The path to fix.
         * @param string $new The separator to replace the old separators [default: `DIRECTORY_SEPARATOR`].
         * @param string[] $old The separators, if any, to be replaced [default: `\`, `/`].
         * @return string The fixed path.
         */
        public static function forceTrailingSeparator (string $path, string $new = DIRECTORY_SEPARATOR, array $old = ['\\', '/']) : string {
            return empty($path) ? $new : rtrim(self::fix($path, $new, $old), $new) . $new;
        }

        /**
         * Gets the absolute path of a given path.
         * @param string $path The path.
         * @param string|null $base The base path to resolve relative paths against [default: caller's directory].
         * @return string The absolute path.
         */
        public static function getAbsolutePath (string $path, ?string $base = null) : string {
            $path = self::normalise($path);

            if (PathUtils::isURL($path) || PathUtils::isAbsolutePath($path)) return $path;

            # Relative to the web root.
            if (str_starts_with($path, '/')) {
                return PathUtils::join($_SERVER["DOCUMENT_ROOT"], ltrim($path, '/'));
            }

            # Relative to the caller or an explicit base.
            if ($base === null) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                $base = isset($trace[1]["file"]) ? dirname($trace[1]["file"]) : getcwd();
            }

            return PathUtils::join($base, $path);
        }
        
        /**
         * Calculates the relative path from a source directory to a target path.
         * @param string $from Absolute source path (the package).
         * @param string $to Absolute target path (the cache root).
         * @return string The relative path (e.g., "../../src/apps/www").
         */
        public static function getRelativePath (string $from, string $to) : string {
            if (!self::isAbsolutePath($to)) {
                return self::fix($to);
            }

            $from = self::fix(self::normalise($from) ?: $from);
            $to = self::fix(self::normalise($to) ?: $to);

            $fromParts = explode(DIRECTORY_SEPARATOR, rtrim($from, DIRECTORY_SEPARATOR));
            $toParts = explode(DIRECTORY_SEPARATOR, rtrim($to, DIRECTORY_SEPARATOR));

            while (count($fromParts) && count($toParts) && ($fromParts[0] === $toParts[0])) {
                array_shift($fromParts);
                array_shift($toParts);
            }

            return str_repeat(".." . DIRECTORY_SEPARATOR, count($fromParts)) . implode(DIRECTORY_SEPARATOR, $toParts);
        }

        /**
         * Checks whether the path is absolute on any supported platform.
         * @param string $path The path to check.
         * @return bool Whether the path is absolute.
         */
        public static function isAbsolutePath (string $path) : bool {
            return self::isUnixAbsolutePath($path) || self::isWindowsAbsolutePath($path);
        }

        /**
         * Checks whether a path is a data URL.
         * @param string $path The path to check.
         * @return bool Whether the path is a data URL.
         */
        public static function isDataURL (string $path) : bool {
            return str_starts_with($path, "data:");
        }

        /**
         * Checks whether a path is a file URL.
         * @param string $path The path to check.
         * @return bool Whether the path is a file URL.
         */
        public static function isFileUrl (string $path) : bool {
            return str_starts_with($path, "file:///");
        }

        /**
         * Checks whether a path is a local file path (not a URL).
         * @param string $path The path to check.
         * @return bool Whether the path is a local file path.
         */
        public static function isLocal (string $path) : bool {
            return !self::isURL($path);
        }

        /**
         * Checks whether a path is a PHP stream.
         * @param string $path The path to check.
         * @return bool Whether the path is a PHP stream.
         */
        public static function isPHPStream (string $path) : bool {
            return preg_match("#^php://[a-zA-Z0-9/._\-,=]+(?:\?.*)?$#", $path) === 1;
        }

        /**
         * Checks whether a path is a relative path.
         * @param string $path The path to check.
         * @return bool Whether the path is a relative path.
         */
        public static function isRelativePath (string $path) : bool {
            return !self::isURL($path) && !self::isAbsolutePath($path);
        }

        /**
         * Checks whether a path is an absolute Unix/Linux path.
         * @param string $path The path to check.
         * @return bool Whether the path is an absolute Unix/Linux path.
         */
        public static function isUnixAbsolutePath (string $path) : bool {
            return str_starts_with($path, '/');
        }

        /**
         * Checks whether a path is a URL.
         * @param string $path The path to check.
         * @return bool Whether the path is a URL.
         */
        public static function isURL (string $path) : bool {
            if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path) === 1) {
                return true;
            }
            return str_starts_with($path, '//') && !str_contains(substr($path, 2), '\\');
        }

        /**
         * Checks whether the path is an absolute Windows path.
         * Supports:
         *  - Drive letter paths: C:\Windows
         *  - UNC paths: \\Server\Share
         *  - Windows absolute paths with a leading slash: /C:/Windows)
         * @param string $path The path to check.
         * @return bool Whether the path is an absolute Windows path.
         */
        public static function isWindowsAbsolutePath (string $path) : bool {
            return preg_match('#^(?:/?[a-zA-Z]:[\\\\/]|\\\\\\\\)#', $path);
        }

        /**
         * Joins the fragments of a path into a complete path.
         * @param string ...$pathFraments The fragments of a path.
         * @return string The path.
         */
        public static function join (string ...$pathFragments) : string {
            $fragments = [];
            foreach ($pathFragments as $index => $fragment) {
                $trim = $index > 0 ? "trim" : "rtrim";
                $fragment = $trim($fragment, "\\/");
                if (empty($fragment)) continue;
                $fragments[] = $fragment;
            }
            return self::fix(implode(DIRECTORY_SEPARATOR, $fragments));
        }
        
        /**
         * Normalises a given path by:
         *  - Replacing multiple slashes with a single slash.
         *  - Resolving `.` and `..` segments.
         *  - Standardising directory separators.
         * @param string $path The path to normalise.
         * @return string The normalised path.
         */
        public static function normalise (string $path) : string {
            if (self::isURL($path)) {
                $uri = URI::from($path);
                $normalisedPath = self::normalisePathComponent($uri->getPath(), '/');
                return (string) $uri->withPath($normalisedPath);
            }
            return self::normalisePathComponent($path, DIRECTORY_SEPARATOR);
        }
    }
?>