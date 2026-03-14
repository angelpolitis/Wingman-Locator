<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Validate Command
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
    if (class_exists(__NAMESPACE__ . '\ValidateCommand', false)) return;

    # Import the following classes to the current scope.
    use Throwable;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Flag;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;

    /**
     * Audits every symbol in every loaded manifest by attempting to resolve it and checking for existence.
     *
     * Each symbol is categorised into one of three states:
     *
     * - **OK** — resolved successfully and the path exists on the filesystem.
     * - **Missing** — resolved successfully but the path does not exist on the filesystem.
     * - **Error** — the Locator threw an exception while attempting resolution.
     *
     * This command is the primary tool for detecting stale or broken manifest entries after a
     * deployment, refactor, or directory restructure.
     *
     * `--namespace` restricts validation to a single namespace. `--strict` causes the command
     * to exit with a non-zero code if any symbol is missing or errored — useful in CI pipelines.
     *
     * Usage examples:
     * ```
     * wingman locator:validate
     * wingman locator:validate --namespace=App
     * wingman locator:validate --strict
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:validate", description: "Audits all registered symbols and reports which resolve to existing paths and which do not.")]
    class ValidateCommand extends Command {
        /**
         * Restricts validation to the symbols belonging to the given namespace.
         * @var string
         */
        #[Option(name: "namespace", alias: "ns", description: "Restrict validation to a specific namespace")]
        protected string $namespace = "";

        /**
         * The root directory to scan for manifests. Defaults to the configured document root or cwd when empty.
         * @var string
         */
        #[Option(name: "root", alias: "r", description: "Root directory to scan for manifests (defaults to document root / cwd)")]
        protected string $root = "";

        /**
         * The web server document root, used to resolve `@{server}` path expressions. Required in CLI mode when
         * manifests contain `@{server}`-rooted symbols. Defaults to `$_SERVER["DOCUMENT_ROOT"]` when empty.
         * @var string
         */
        #[Option(name: "server-root", alias: "sr", description: "Web server document root for resolving @{server} expressions (required in CLI)")]
        protected string $serverRoot = "";

        /**
         * Exits with a non-zero code if any symbol fails to resolve or points to a non-existent path.
         * @var bool
         */
        #[Flag(name: "strict", alias: "s", description: "Exit with code 1 if any symbol is missing or errors")]
        protected bool $strict = false;

        /**
         * Validates all registered symbols and renders a categorised summary.
         * @return int Exit code — 0 on success, 1 in strict mode if any symbol failed.
         */
        public function run () : int {
            $root = !empty($this->root) ? $this->root : null;

            if ($root !== null && !is_dir($root)) {
                $this->console->error("Root directory \"$root\" does not exist.");
                return 1;
            }

            $config = [
                "locator.server.root"    => $this->serverRoot,
                "locator.discovery.root" => $root ?? "",
            ];

            $locator = Locator::getGlobal() ?? new Locator($config);

            $manifests = $locator->getManifestRepository()->getAll();
            $rows = [];
            $okCount = 0;
            $missingCount = 0;
            $errorCount = 0;

            foreach ($manifests as $manifest) {
                $ns = $manifest->getNamespace();

                if (!empty($this->namespace) && strcasecmp($ns, $this->namespace) !== 0) {
                    continue;
                }

                foreach ($manifest->getSymbols() as $symbol => $expression) {
                    try {
                        $resolved = $locator->getPathFor("@{$ns}/%{{$symbol}}");
                        $exists = file_exists($resolved);

                        if ($exists) {
                            $status = "OK";
                            $okCount++;
                        }
                        else {
                            $status = "Missing";
                            $missingCount++;
                        }

                        $rows[] = [$ns, $symbol, $resolved, $status];
                    }
                    catch (Throwable $e) {
                        $rows[] = [$ns, $symbol, $e->getMessage(), "Error"];
                        $errorCount++;
                    }
                }
            }

            if (empty($rows)) {
                $qualifier = !empty($this->namespace) ? " for namespace \"{$this->namespace}\"" : "";
                $this->console->info("No symbols to validate{$qualifier}.");
                return 0;
            }

            $hasFailed = ($missingCount + $errorCount) > 0;

            $this->console->style(function (Style $s) use ($rows, $okCount, $missingCount, $errorCount, $hasFailed) {
                yield PHP_EOL . $s->format(" Symbol Validation ", "info") . PHP_EOL . PHP_EOL;
                yield $s->renderTable(["Namespace", "Symbol", "Resolved Path / Error", "Status"], $rows) . PHP_EOL;

                $summary = "Total: " . ($okCount + $missingCount + $errorCount)
                    . "  |  OK: {$okCount}"
                    . "  |  Missing: {$missingCount}"
                    . "  |  Errors: {$errorCount}";

                $summaryType = $hasFailed ? "error" : "success";
                yield $s->colour($summary, $summaryType) . PHP_EOL;
            });

            return ($this->strict && $hasFailed) ? 1 : 0;
        }
    }
?>