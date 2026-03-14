<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Cache Clear Command
     * Created by:      Angel Politis
     * Creation Date:   Mar 13 2026
     * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Bridge.Console.Commands namespace.
    namespace Wingman\Locator\Bridge\Console\Commands;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the class is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\CacheClearCommand', false)) return;

    # Import the following classes to the current scope.
    use ReflectionObject;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Command;
    use Wingman\Locator\Locator;

    /**
     * Clears the Locator discovery cache, regardless of whether a file-based or Cacher-backed store is in use.
     *
     * The running Locator singleton is inspected via reflection to retrieve its active cache manager instance
     * and call `clear()` on it directly, ensuring the right backend is targeted without requiring the cache
     * manager to be exposed on the public API.
     *
     * If caching is disabled on the active Locator instance, this command reports that fact and exits cleanly.
     *
     * Usage:
     * ```
     * wingman locator:cache:clear
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:cache:clear", description: "Clears the Locator discovery cache (file-based or Cacher-backed).")]
    class CacheClearCommand extends Command {
        /**
         * Reads the active cache manager from the running Locator singleton via reflection and clears it.
         * @return int Exit code — 0 on success or when caching is already disabled, 1 on failure.
         */
        public function run () : int {
            $locator = Locator::get();

            $reflection = new ReflectionObject($locator);

            $enabledProp = $reflection->getProperty("cachingEnabled");
            /** @disregard P1007 */
            if (method_exists($enabledProp, "setAccessible")) $enabledProp->setAccessible(true);
            $cachingEnabled = $enabledProp->getValue($locator);

            if (!$cachingEnabled) {
                $this->console->warn("Caching is disabled on the active Locator instance. Nothing to clear.");
                return 0;
            }

            $managerProp = $reflection->getProperty("cacheManager");
            /** @disregard P1007 */
            if (method_exists($managerProp, "setAccessible")) $managerProp->setAccessible(true);
            $manager = $managerProp->getValue($locator);

            if ($manager === null) {
                $this->console->warn("No active cache manager found. The cache may not have been initialised yet.");
                return 0;
            }

            $success = $manager->clear();

            if ($success) {
                $this->console->success("Discovery cache cleared successfully.");
            }
            else {
                $this->console->error("Failed to clear the discovery cache.");
                return 1;
            }

            return 0;
        }
    }
?>