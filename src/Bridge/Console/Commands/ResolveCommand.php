<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Resolve Command
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
    if (class_exists(__NAMESPACE__ . '\ResolveCommand', false)) return;

    # Import the following classes to the current scope.
    use Throwable;
    use Wingman\Console\Attributes\Argument;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Console\Style;
    use Wingman\Locator\Locator;

    /**
     * Resolves a Locator path expression to its absolute path and prints it.
     *
     * Useful for debugging path expressions interactively without writing PHP.
     * When `--type` is "any" (the default), the expression is resolved without
     * checking whether the resulting path actually exists on the filesystem.
     * Specifying "file" or "dir" additionally verifies existence and exits with
     * a non-zero code if the path is not found.
     *
     * Usage examples:
     * ```
     * wingman locator:resolve "@app/config.php"
     * wingman locator:resolve "%journal.txt" --type=file
     * wingman locator:resolve "@{manifest}/models" --type=dir
     * ```
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:resolve", description: "Resolves a path expression to its absolute path.")]
    class ResolveCommand extends Command {
        /**
         * The path expression to resolve (e.g. "@app/config.php", "%journal.txt", "@{manifest}/models").
         * @var string
         */
        #[Argument(index: 0, description: "The path expression to resolve (e.g. \"@app/config.php\")")]
        protected string $expression;

        /**
         * Restricts the resolution to a specific resource type. Accepted values are "any" (default),
         * "file", and "dir". When "any" is used the path is resolved without checking for existence.
         * @var string
         */
        #[Option(name: "type", alias: "t", description: "Limit resolution to a type: any (default), file, dir")]
        protected string $type = "any";

        /**
         * Resolves the path expression and prints the resulting absolute path.
         * Exits with code 1 when a typed check is requested and the path does not exist.
         * @return int Exit code — 0 on success, 1 if the path does not exist for the requested type.
         */
        public function run () : int {
            $locator = Locator::get();

            try {
                $path = match ($this->type) {
                    "file" => $locator->getPathToFile($this->expression),
                    "dir"  => $locator->getPathToDirectory($this->expression),
                    default => $locator->getPathFor($this->expression),
                };
            }
            catch (Throwable $e) {
                $this->console->error($e->getMessage());
                return 1;
            }

            if ($path === null) {
                $this->console->error("Expression \"{$this->expression}\" resolved to a non-existent {$this->type}.");
                return 1;
            }

            $this->console->style(function (Style $s) use ($path) {
                yield $s->colour($this->expression, "info") . " → " . $path . PHP_EOL;
            });

            return 0;
        }
    }
?>