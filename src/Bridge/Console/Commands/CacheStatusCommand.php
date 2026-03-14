<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Cache Status Command
     * Created by:      Angel Politis
     * Creation Date:   Mar 13 2026
     * Last Modified:   Mar 20 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Bridge.Console.Commands namespace.
    namespace Wingman\Locator\Bridge\Console\Commands;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the class is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\CacheStatusCommand', false)) return;

    # Import the following classes to the current scope.
    use ReflectionObject;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Bridge\Stasis\CacheManager as BridgeCacheManager;
    use Wingman\Locator\Locator;

    /**
     * Inspects and displays the current discovery cache configuration and state.
     *
     * Reports whether caching is enabled, which backend is in use (file-based or Stasis-backed),
     * the configured TTL, and whether a valid cache entry currently exists. When a cache entry is
     * present, its age is also shown so stale entries can be identified at a glance.
     *
     * The command accesses protected properties of the running Locator singleton via reflection,
     * which is intentional — this is a diagnostic tool that deliberately surfaces internal state
     * without requiring it to be part of the public API.
     *
     * Usage:
     * ```
     * wingman locator:cache:status
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:cache:status", description: "Reports the discovery cache configuration and whether a valid entry currently exists.")]
    class CacheStatusCommand extends Command {
        /**
         * Reads cache configuration and state from the running Locator singleton via reflection.
         * @return int Exit code — 0 on success.
         */
        public function run () : int {
            $locator = Locator::get();
            $reflection = new ReflectionObject($locator);

            $read = function (string $property) use ($reflection, $locator) {
                $prop = $reflection->getProperty($property);
                /** @disregard P1007 */
                if (method_exists($prop, "setAccessible")) $prop->setAccessible(true);
                return $prop->getValue($locator);
            };

            $cachingEnabled  = $read("cachingEnabled");
            $cacheFile = $read("cacheFile");
            $cacheTtl = $read("cacheTtl");
            $manager = $read("cacheManager");
            $cachingAdapter = $read("cachingAdapter");

            $backend = "—";
            $backendDetail = "—";
            $entryStatus = "—";

            if ($cachingEnabled && $manager !== null) {
                if ($manager instanceof BridgeCacheManager) {
                    $backend = "Wingman Stasis";
                    $backendDetail = BridgeCacheManager::CACHE_KEY;

                    $cached = $manager->load();
                    $entryStatus = $cached !== null
                        ? "Valid (" . count($cached["manifests"] ?? []) . " manifest(s) cached)"
                        : "Empty / expired";
                }
                else {
                    $backend = "File";
                    $backendDetail = $cacheFile;

                    $cached = $manager->load();
                    $entryStatus = $cached !== null
                        ? "Valid (" . count($cached["manifests"] ?? []) . " manifest(s) cached)"
                        : "Empty / expired";
                }
            }

            $ttlDisplay = $cacheTtl > 0 ? "{$cacheTtl}s" : "disabled (entries never expire)";

            $adapterDisplay = $cachingAdapter !== null
                ? get_class($cachingAdapter)
                : "— (default)";

            $rows = [
                ["Caching enabled",  $cachingEnabled ? "Yes" : "No"],
                ["Backend",          $backend],
                ["Location / Key",   $backendDetail],
                ["TTL",              $ttlDisplay],
                ["Custom adapter",   $adapterDisplay],
                ["Entry status",     $entryStatus],
            ];

            $this->console->style(function (Style $s) use ($rows) {
                yield PHP_EOL . $s->format(" Discovery Cache Status ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable(["Property", "Value"], $rows) . PHP_EOL;
            });

            return 0;
        }
    }
?>