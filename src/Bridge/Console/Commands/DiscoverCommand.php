<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Discover Command
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
    if (class_exists(__NAMESPACE__ . '\DiscoverCommand', false)) return;

    # Import the following classes to the current scope.
    use Throwable;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Flag;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;

    /**
     * Triggers a manifest discovery scan and reports what was found, along with timing.
     *
     * By default this command runs a cold scan using a fresh Locator instance, which ensures timing
     * is accurate and reflects the actual cost of a discovery pass, not a cached load. The `--root`
     * option targets an arbitrary directory; without it the default root (document root or cwd) is used.
     *
     * `--depth` controls the maximum directory recursion depth (default 5, -1 for unlimited).
     * `--exclude` accepts a comma-separated list of glob patterns to ignore during the scan.
     * `--no-cache` disables the discovery cache so a genuinely fresh scan is always performed.
     *
     * Usage examples:
     * ```
     * wingman locator:discover
     * wingman locator:discover --root=/srv/app --depth=3
     * wingman locator:discover --no-cache
     * wingman locator:discover --exclude="vendor/*,tests/*"
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:discover", description: "Triggers a manifest discovery scan and reports the results and timing.")]
    class DiscoverCommand extends Command {
        /**
         * The maximum directory depth to recurse into during the scan.
         * Set to -1 for an unlimited depth scan.
         * @var int
         */
        #[Option(name: "depth", alias: "d", description: "Max recursion depth (-1 for unlimited, default 5)")]
        protected int $depth = 5;

        /**
         * A comma-separated list of glob patterns for paths to exclude during the scan.
         * @var string
         */
        #[Option(name: "exclude", alias: "e", description: "Comma-separated glob patterns to exclude")]
        protected string $exclude = "";

        /**
         * Whether to disable the discovery cache, forcing a genuinely fresh filesystem scan.
         * @var bool
         */
        #[Flag(name: "no-cache", alias: "n", description: "Disable the discovery cache for this run")]
        protected bool $noCache = false;

        /**
         * The root directory to scan for manifests. Defaults to the configured document root or cwd when empty.
         * @var string
         */
        #[Option(name: "root", alias: "r", description: "Root directory to scan (defaults to document root / cwd)")]
        protected string $root = "";

        /**
         * The web server document root, used to resolve `@{server}` path expressions. Required in CLI mode when
         * manifests contain `@{server}`-rooted symbols. Defaults to `$_SERVER["DOCUMENT_ROOT"]` when empty.
         * @var string
         */
        #[Option(name: "server-root", alias: "sr", description: "Web server document root for resolving @{server} expressions (required in CLI)")]
        protected string $serverRoot = "";

        /**
         * Runs a discovery scan, measures the duration, and renders a summary table.
         * @return int Exit code — 0 on success, 1 if the root directory does not exist.
         */
        public function run () : int {
            $root = !empty($this->root) ? $this->root : null;

            if ($root !== null && !is_dir($root)) {
                $this->console->error("Root directory \"{$root}\" does not exist.");
                return 1;
            }

            $excludePatterns = !empty($this->exclude)
                ? array_map("trim", explode(',', $this->exclude))
                : ["vendor/*", "tests/*", "temp/*", "cache/*", "**/.*"];

            $config = [
                "locator.caching.enabled" => !$this->noCache,
                "locator.server.root"     => $this->serverRoot,
            ];

            $profile = DiscoveryProfile::from([
                "depth"   => $this->depth,
                "exclude" => $excludePatterns,
            ]);

            $start = hrtime(true);
            $locator = new Locator($config);

            try {
                $locator->discoverManifests($root, $profile);
            }
            catch (Throwable $e) {
                $this->console->error($e->getMessage());
                return 1;
            }

            $elapsed = (hrtime(true) - $start) / 1e9;

            $resolvedRoot = $root ?? ((!empty($_SERVER["DOCUMENT_ROOT"]) && is_dir($_SERVER["DOCUMENT_ROOT"]))
                ? $_SERVER["DOCUMENT_ROOT"]
                : (getcwd() ?: "."));

            $manifests = $locator->getManifestRepository()->getAll();
            $namespacesSeen = [];
            $symbolCount = 0;
            $virtualCount = 0;

            foreach ($manifests as $manifest) {
                $namespacesSeen[$manifest->getNamespace()] = true;
                $symbolCount  += count($manifest->getSymbols());
                $virtualCount += count($manifest->getVirtuals());
            }

            $rows = [
                ["Root",         $resolvedRoot],
                ["Cache",        $this->noCache ? "disabled" : "enabled"],
                ["Manifests",    (string) count($manifests)],
                ["Namespaces",   (string) count($namespacesSeen)],
                ["Symbols",      (string) $symbolCount],
                ["Virtuals",     (string) $virtualCount],
                ["Duration",     number_format($elapsed, 4) . "s"],
            ];

            $this->console->style(function (Style $s) use ($rows) {
                yield PHP_EOL . $s->format(" Discovery Results ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable(["Metric", "Value"], $rows) . PHP_EOL;
            });

            return 0;
        }
    }
?>