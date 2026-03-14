<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Manifests Command
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
    if (class_exists(__NAMESPACE__ . '\ManifestsCommand', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Flag;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;

    /**
     * Lists all manifests currently loaded by the Locator, with their namespace, source path, and entry counts.
     *
     * Optionally filters to a single namespace via `--namespace`. When `--path` is provided, an additional
     * discovery pass is triggered against that root directory before listing, allowing inspection of manifests
     * in directories that were not scanned at boot time.
     *
     * Usage examples:
     * ```
     * wingman locator:manifests
     * wingman locator:manifests --namespace=App
     * wingman locator:manifests --path=/srv/plugins
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:manifests", description: "Lists all loaded manifests with their namespace, source path, and entry counts.")]
    class ManifestsCommand extends Command {
        /**
         * Filters the output to manifests belonging to the given namespace.
         * @var string
         */
        #[Option(name: "namespace", alias: "ns", description: "Filter output to a specific namespace")]
        protected string $namespace = "";

        /**
         * An optional root directory to scan for additional manifests before listing.
         * When provided, `discoverManifests()` is called against this path before the table is rendered.
         * @var string
         */
        #[Option(name: "path", alias: "p", description: "Root directory to scan before listing")]
        protected string $path = "";

        /**
         * Whether to include the absolute source path of each manifest as a full column.
         * By default, the source path is truncated to fit the terminal width.
         * @var bool
         */
        #[Flag(name: "full", alias: "f", description: "Show full source paths without truncation")]
        protected bool $full = false;

        /**
         * Lists all loaded manifests in a formatted table.
         * @return int Exit code — 0 on success.
         */
        public function run () : int {
            $locator = Locator::get();

            if (!empty($this->path)) {
                $locator->discoverManifests($this->path);
            }

            $manifests = $locator->getManifestRepository()->getAll();

            if (!empty($this->namespace)) {
                $manifests = array_values(array_filter($manifests, function ($m) {
                    return strcasecmp($m->getNamespace(), $this->namespace) === 0;
                }));
            }

            if (empty($manifests)) {
                $this->console->warn("No manifests found" . (!empty($this->namespace) ? " for namespace \"{$this->namespace}\"" : "") . ".");
                return 0;
            }

            $rows = [];

            foreach ($manifests as $manifest) {
                $sourcePath = $manifest->getSourcePath();

                if (!$this->full) {
                    $width = $this->console->getTerminalWidth();
                    $maxLen = max(20, (int) ($width * 0.4));
                    if (strlen($sourcePath) > $maxLen) {
                        $sourcePath = "…" . substr($sourcePath, -($maxLen - 1));
                    }
                }

                $aliases = $manifest->getAliases();
                $aliasDisplay = empty($aliases) ? "—" : implode(", ", $aliases);

                $rows[] = [
                    $manifest->getNamespace(),
                    $sourcePath,
                    $aliasDisplay,
                    (string) count($manifest->getSymbols()),
                    (string) count($manifest->getVirtuals()),
                ];
            }

            $this->console->style(function (Style $s) use ($rows) {
                yield PHP_EOL . $s->format(" Loaded Manifests ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable(["Namespace", "Source Path", "Aliases", "Symbols", "Virtuals"], $rows) . PHP_EOL;
                yield $s->colour(count($rows) . " manifest(s) loaded.", "info") . PHP_EOL;
            });

            return 0;
        }
    }
?>