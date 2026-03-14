<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Namespaces Command
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
    if (class_exists(__NAMESPACE__ . '\NamespacesCommand', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Flag;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;

    /**
     * Lists every namespace registered with the Locator, derived from all loaded manifests.
     *
     * Optionally extends the output with each namespace's aliases (`--aliases`) and its resolved
     * root directory on the filesystem (`--paths`). Both flags may be combined.
     *
     * Usage examples:
     * ```
     * wingman locator:namespaces
     * wingman locator:namespaces --aliases
     * wingman locator:namespaces --aliases --paths
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:namespaces", description: "Lists all registered namespaces derived from loaded manifests.")]
    class NamespacesCommand extends Command {
        /**
         * Whether to include a column showing the aliases registered for each namespace.
         * @var bool
         */
        #[Flag(name: "aliases", alias: "a", description: "Include the aliases column")]
        protected bool $aliases = false;

        /**
         * Whether to include a column showing the resolved root directory for each namespace.
         * @var bool
         */
        #[Flag(name: "paths", alias: "p", description: "Include the resolved root path column")]
        protected bool $paths = false;

        /**
         * Renders a table of registered namespaces with optional extra columns.
         * @return int Exit code — 0 on success.
         */
        public function run () : int {
            $locator = Locator::get();
            $manifests = $locator->getManifestRepository()->getAll();

            if (empty($manifests)) {
                $this->console->warn("No namespaces registered.");
                return 0;
            }

            $seen = [];
            $rows = [];

            foreach ($manifests as $manifest) {
                $namespace = $manifest->getNamespace();

                if (isset($seen[$namespace])) {
                    continue;
                }

                $seen[$namespace] = true;

                $row = [$namespace];

                if ($this->aliases) {
                    $aliases = $manifest->getAliases();
                    $row[] = empty($aliases) ? "—" : implode(", ", $aliases);
                }

                if ($this->paths) {
                    $resolved = $locator->getPathToNamespace($namespace);
                    $row[] = $resolved ?? "—";
                }

                $rows[] = $row;
            }

            usort($rows, fn ($a, $b) => strcmp($a[0], $b[0]));

            $headers = ["Namespace"];

            if ($this->aliases) {
                $headers[] = "Aliases";
            }

            if ($this->paths) {
                $headers[] = "Root Path";
            }

            $this->console->style(function (Style $s) use ($rows, $headers) {
                yield PHP_EOL . $s->format(" Registered Namespaces ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable($headers, $rows) . PHP_EOL;
                yield $s->colour(count($rows) . " namespace(s) registered.", "info") . PHP_EOL;
            });

            return 0;
        }
    }
?>