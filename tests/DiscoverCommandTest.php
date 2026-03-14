<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Discover Command Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 13 2026
     * Last Modified:   Mar 13 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use ReflectionClass;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Console;
    use Wingman\Locator\Bridge\Console\Commands\DiscoverCommand;

    /**
     * Tests for the DiscoverCommand console bridge command, covering attribute registration,
     * rejection of non-existent roots, and a clean run against a known empty directory.
     *
     * Note: DiscoverCommand constructs its own Locator instance internally and does not use
     * the global singleton, so no Locator mock injection is required.
     */
    class DiscoverCommandTest extends Test {
        /**
         * Extracts the name declared on the `#[Cmd]` attribute of a command class.
         * @param string $commandClass The fully qualified class name of the command.
         * @return string|null The declared name, or null if the attribute is absent.
         */
        private function getCommandName (string $commandClass) : ?string {
            $attributes = (new ReflectionClass($commandClass))->getAttributes(Cmd::class);
            return empty($attributes) ? null : $attributes[0]->newInstance()->name;
        }

        /**
         * Creates a minimal Console instance with colours disabled, suitable for command tests.
         * @return Console The configured Console instance.
         */
        private function makeConsole () : Console {
            return new Console(["coloursEnabled" => false]);
        }

        /**
         * Discards any buffered console output after each test.
         */
        public function tearDown () : void {
            if (ob_get_level() > 0) ob_end_clean();
        }

        #[Group("Commands")]
        #[Define(
            name: "DiscoverCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on DiscoverCommand declares the name 'locator:discover'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(DiscoverCommand::class);

            $this->assertTrue($name === "locator:discover", "DiscoverCommand should declare the name 'locator:discover'.");
        }

        #[Group("Commands")]
        #[Define(
            name: "discover — Returns One For Non-Existent Root",
            description: "When --root points to a directory that does not exist, run() returns 1."
        )]
        public function testReturnsOneForNonExistentRoot () : void {
            $cmd = new DiscoverCommand("--root=/wingman_locator_nonexistent_dir_77261");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 1, "run() should return 1 when the --root directory does not exist.");
        }

        #[Group("Commands")]
        #[Define(
            name: "discover — Returns Zero For Valid Root",
            description: "When --root points to a valid existing directory, run() performs the scan and returns 0."
        )]
        public function testReturnsZeroForValidRoot () : void {
            $cmd = new DiscoverCommand("--root=/tmp --no-cache");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when the --root directory exists.");
        }

        #[Group("Commands")]
        #[Define(
            name: "discover — Returns Zero With No Root Option",
            description: "When no --root is supplied, the command skips discovery and reports an empty result with exit code 0."
        )]
        public function testReturnsZeroWithNoRootOption () : void {
            $cmd = new DiscoverCommand("--no-cache");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when no --root is specified.");
        }
    }
?>