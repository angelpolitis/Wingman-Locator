<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Virtuals Command
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
    if (class_exists(__NAMESPACE__ . '\VirtualsCommand', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;

    /**
     * Lists all virtual entries registered across loaded manifests.
     *
     * Virtuals are named filesystem projections — files or directories whose canonical
     * path within the Locator differs from their actual location on disk. This command
     * makes all registered virtuals visible in one place, showing their name, type, and
     * underlying source path.
     *
     * `--namespace` restricts the output to a single namespace.
     *
     * Usage examples:
     * ```
     * wingman locator:virtuals
     * wingman locator:virtuals --namespace=App
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:virtuals", description: "Lists all virtual entries registered across loaded manifests.")]
    class VirtualsCommand extends Command {
        /**
         * Restricts output to the virtual entries belonging to the given namespace.
         * @var string
         */
        #[Option(name: "namespace", alias: "ns", description: "Filter output to a specific namespace")]
        protected string $namespace = "";

        /**
         * Renders the virtual table in a four-column format: namespace, name, type, source.
         * @return int Exit code — 0 on success.
         */
        public function run () : int {
            $locator = Locator::get();
            $manifests = $locator->getManifestRepository()->getAll();
            $rows = [];

            foreach ($manifests as $manifest) {
                $ns = $manifest->getNamespace();

                if (!empty($this->namespace) && strcasecmp($ns, $this->namespace) !== 0) {
                    continue;
                }

                foreach ($manifest->getVirtuals() as $name => $definition) {
                    $type   = $definition["type"]   ?? "—";
                    $source = $definition["source"] ?? "—";
                    $rows[] = [$ns, $name, $type, $source];
                }
            }

            if (empty($rows)) {
                $qualifier = !empty($this->namespace) ? " for namespace \"{$this->namespace}\"" : "";
                $this->console->warn("No virtual entries found{$qualifier}.");
                return 0;
            }

            usort($rows, fn ($a, $b) => strcmp($a[0], $b[0]) ?: strcmp($a[1], $b[1]));

            $this->console->style(function (Style $s) use ($rows) {
                yield PHP_EOL . $s->format(" Virtual Entries ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable(["Namespace", "Name", "Type", "Source"], $rows) . PHP_EOL;
                yield $s->colour(count($rows) . " virtual entry/entries found.", "info") . PHP_EOL;
            });

            return 0;
        }
    }
?>