<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Symbols Command
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
    if (class_exists(__NAMESPACE__ . '\SymbolsCommand', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;

    /**
     * Dumps the symbol table registered across all loaded manifests.
     *
     * Each symbol maps a short identifier to a raw path expression. This command
     * is the primary diagnostic tool when a path expression is not resolving as
     * expected — inspecting the registered symbols confirms exactly what the Locator
     * has been told a given identifier points to.
     *
     * `--namespace` restricts output to a single namespace. `--filter` accepts a
     * case-insensitive substring match applied to symbol names.
     *
     * Usage examples:
     * ```
     * wingman locator:symbols
     * wingman locator:symbols --namespace=App
     * wingman locator:symbols --filter=model
     * wingman locator:symbols --namespace=App --filter=ctrl
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:symbols", description: "Dumps the symbol table registered across all loaded manifests.")]
    class SymbolsCommand extends Command {
        /**
         * A case-insensitive substring filter applied to symbol names before rendering.
         * @var string
         */
        #[Option(name: "filter", alias: "f", description: "Case-insensitive substring filter on symbol names")]
        protected string $filter = "";

        /**
         * Restricts output to the symbols belonging to the given namespace.
         * @var string
         */
        #[Option(name: "namespace", alias: "ns", description: "Filter output to a specific namespace")]
        protected string $namespace = "";

        /**
         * Renders the symbol table in a three-column table: namespace, symbol name, raw expression.
         * @return int Exit code — 0 on success.
         */
        public function run () : int {
            $locator = Locator::get();
            $manifests = $locator->getManifestRepository()->getAll();
            $rows = [];
            $filterLower = strtolower($this->filter);

            foreach ($manifests as $manifest) {
                $ns = $manifest->getNamespace();

                if (!empty($this->namespace) && strcasecmp($ns, $this->namespace) !== 0) {
                    continue;
                }

                foreach ($manifest->getSymbols() as $symbol => $expression) {
                    if (!empty($filterLower) && !str_contains(strtolower($symbol), $filterLower)) {
                        continue;
                    }

                    $rows[] = [$ns, $symbol, $expression];
                }
            }

            if (empty($rows)) {
                $qualifier = [];
                if (!empty($this->namespace)) $qualifier[] = "namespace \"{$this->namespace}\"";
                if (!empty($this->filter))    $qualifier[] = "filter \"{$this->filter}\"";
                $this->console->warn("No symbols found" . (!empty($qualifier) ? " for " . implode(" and ", $qualifier) : "") . ".");
                return 0;
            }

            usort($rows, fn ($a, $b) => strcmp($a[0], $b[0]) ?: strcmp($a[1], $b[1]));

            $this->console->style(function (Style $s) use ($rows) {
                yield PHP_EOL . $s->format(" Symbol Table ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable(["Namespace", "Symbol", "Expression"], $rows) . PHP_EOL;
                yield $s->colour(count($rows) . " symbol(s) found.", "info") . PHP_EOL;
            });

            return 0;
        }
    }
?>