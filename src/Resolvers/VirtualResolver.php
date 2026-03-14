<?php
    /**
	 * Project Name:    Wingman — Locator — Virtual Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 18 2025
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Resolvers namespace.
    namespace Wingman\Locator\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\PathUtils;

    /**
     * Resolves virtual paths defined in a namespace's manifest to their concrete counterparts.
     * A virtual path is an entry in the `virtuals` map that creates a logical name pointing to
     * either a real file, a real directory, or a nested tree of such entries — decoupling the
     * public path surface from the underlying filesystem layout.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class VirtualResolver implements ResolverInterface {
        /**
         * The manager of a namespace resolver.
         * @var NamespaceManager
         */
        protected NamespaceManager $manager;

        /**
         * Creates a new virtual resolver.
         * @param NamespaceManager $manager The namespace manager.
         */
        public function __construct (NamespaceManager $manager) {
            $this->manager = $manager;
        }

        /**
         * Resolves the given absolute path against the virtual tree of the owning namespace.
         * Returns a continuation result pointing to the concrete path when a virtual entry is matched,
         * or `null` when the path does not correspond to any registered virtual.
         * @param PathExpression|string $input The input path.
         * @param ResolutionContext|null $context The resolution context.
         * @return ResolutionResult|null The result, or `null` if the input is not inside a known virtual tree.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $raw = $input instanceof PathExpression ? $input->__toString() : (string) $input;
            $path = PathUtils::fix($raw);

            if (!PathUtils::isAbsolutePath($path)) return null;

            $namespace = $this->manager->getPathNamespace($path);
            $namespaceRoot = $this->manager->getNamespacePath($namespace);
            if (!$namespaceRoot) return null;

            if (!str_starts_with($path, $namespaceRoot)) return null;

            $relative = ltrim(substr($path, strlen($namespaceRoot)), DIRECTORY_SEPARATOR);
            $ns = $this->manager->getNamespace($namespace);
            if (!$ns) return null;

            $virtuals = $ns->getVirtuals();

            # Traverse the virtual tree by segments.
            $segments = $relative === "" ? [] : explode(DIRECTORY_SEPARATOR, $relative);
            $current = $virtuals;
            $consumed = [];

            while (count($segments) > 0) {
                $seg = array_shift($segments);
                $consumed[] = $seg;

                # Non-array current cannot be descended into.
                if (!is_array($current)) return null;

                if (array_key_exists($seg, $current)) {
                    $entry = $current[$seg];
                    $norm = $this->normaliseVirtualEntry($entry);

                    # If this is a file, resolve to its source (join any remaining segments).
                    if ($norm["type"] === "file") {
                        $src = $norm["source"] ?? null;
                        if ($src === null) {
                            # Inline virtual file — nothing to join, return original path.
                            return ResolutionResult::continue($path, $context);
                        }
                        if (count($segments) === 0) return ResolutionResult::continue($src, $context);

                        # A file with extra path is not valid.
                        return null;
                    }

                    # Directory: Descend into its content if present.
                    if ($norm["type"] === "directory") {
                        if (isset($norm["content"]) && is_array($norm["content"])) {
                            $current = $norm["content"];
                            continue;
                        }

                        # If directory has a source, map remainder to source
                        if (isset($norm["source"])) {
                            $src = $norm["source"];
                            $remaining = implode(DIRECTORY_SEPARATOR, $segments);
                            return ResolutionResult::continue(PathUtils::join($src, $remaining), $context);
                        }

                        # For a pure virtual directory without content, return the original path.
                        return ResolutionResult::continue($path, $context);
                    }
                }

                # No direct entry — maybe the current node is a content map (flat).
                if (array_key_exists($seg, $current)) continue;

                # If we cannot find the segment, stop.
                return null;
            }

            # If we've consumed all segments and are at a virtual entry root
            # Try exact match for empty relative (virtual root)
            if ($relative === "") {
                # Top-level virtual representing namespace root.
                return null;
            }

            return null;
        }

        /**
         * Normalises a stored virtual entry into a consistent shape: [type => file|directory, source => ?string, content => ?array]
         * Accepts several input shapes (string, associative array, processed array).
         */
        protected function normaliseVirtualEntry (mixed $entry) : array {
            # If simple string, then it's a file source.
            if (is_string($entry)) {
                return ["type" => "file", "source" => PathUtils::fix($entry)];
            }

            # If it's an associative array with explicit type, use that.
            if (is_array($entry) && (isset($entry["type"]) || isset($entry["source"]) || isset($entry["content"]))) {
                $type = $entry["type"] ?? (isset($entry["content"]) ? "directory" : "file");
                $source = isset($entry["source"]) ? PathUtils::fix($entry["source"]) : null;
                $content = $entry["content"] ?? null;
                return ["type" => $type, "source" => $source, "content" => $content];
            }

            # If it's a processed array (e.g., ['file','/tmp/a.txt'] or ['directory','./assets']), infer the type from first element and source from second. 
            if (is_array($entry)) {
                $norm = array_values($entry);
                $first = $norm[0] ?? null;
                $second = $norm[1] ?? null;
                if (in_array($first, ["file", "directory"])) {
                    return ["type" => $first, "source" => $second ? PathUtils::fix($second) : null, "content" => null];
                }
                # Otherwise treat as content map (children).
                return ["type" => "directory", "source" => null, "content" => $entry];
            }

            return ["type" => "file", "source" => null, "content" => null];
        }
    }
?>