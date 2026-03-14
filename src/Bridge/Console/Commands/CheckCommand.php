<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Check Command
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
    if (class_exists(__NAMESPACE__ . '\CheckCommand', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Console\Attributes\Argument;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Attributes\Option;
    use Wingman\Console\Command;
    use Wingman\Locator\Locator;

    /**
     * Checks whether a path expression resolves to an existing filesystem path and exits accordingly.
     *
     * Unlike `locator:resolve`, this command is purpose-built for use in shell scripts and CI pipelines.
     * It always performs an existence check and exits with code 1 when the path is not found, making it
     * suitable for conditional logic:
     *
     * ```bash
     * if php wingman locator:check "@app/config.php" --type=file; then
     *     echo "Config found"
     * fi
     * ```
     *
     * Use `--type` to restrict the check to either a file or a directory. The default ("any") accepts
     * both files and directories.
     * @package Wingman\Locator\Bridge\Console\Commands
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Cmd(name: "locator:check", description: "Exits with 0 if a path expression resolves to an existing path, 1 otherwise.")]
    class CheckCommand extends Command {
        /**
         * The path expression to check.
         * @var string
         */
        #[Argument(index: 0, description: "The path expression to check (e.g. \"@app/config.php\")")]
        protected string $expression;

        /**
         * Restricts the existence check to a specific resource type. Accepted values are "any" (default),
         * "file", and "dir".
         * @var string
         */
        #[Option(name: "type", alias: "t", description: "Restrict check to a type: any (default), file, dir")]
        protected string $type = "any";

        /**
         * Resolves the expression, checks for existence and exits appropriately.
         * @return int Exit code — 0 if the path exists, 1 if it does not.
         */
        public function run () : int {
            $locator = Locator::get();

            $path = match ($this->type) {
                "file" => $locator->getPathToFile($this->expression),
                "dir" => $locator->getPathToDirectory($this->expression),
                default => $locator->getPathTo($this->expression),
            };

            if ($path === null) {
                $this->console->error("Not found: \"{$this->expression}\"");
                return 1;
            }

            $this->console->success("Found: " . $path);
            return 0;
        }
    }
?>