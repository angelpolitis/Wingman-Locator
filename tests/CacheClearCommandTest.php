<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Cache Clear Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\CacheClearCommand;
    use Wingman\Locator\Locator;

    /**
     * Tests for the CacheClearCommand console bridge command, covering attribute registration
     * and clean exit when caching is disabled or the cache manager has not yet been initialised.
     *
     * These tests use a real Locator instance (injected via setGlobal) because CacheClearCommand
     * accesses internal Locator state through reflection and requires an actual `Locator` object
     * rather than a mock implementing only the public interface.
     */
    class CacheClearCommandTest extends Test {
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
         * Creates a minimal Console instance with colours and verbosity disabled,
         * suitable for command tests where only the return code matters.
         * @return Console The configured Console instance.
         */
        private function makeConsole () : Console {
            return new Console(["coloursEnabled" => false, "verbose" => true]);
        }

        /**
         * Resets the Locator singleton and discards any buffered console output after each test.
         */
        public function tearDown () : void {
            Locator::setGlobal(null);
            if (ob_get_level() > 0) ob_end_clean();
        }

        #[Group("Commands")]
        #[Define(
            name: "CacheClearCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on CacheClearCommand declares the name 'locator:cache:clear'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(CacheClearCommand::class);

            $this->assertTrue($name === "locator:cache:clear", "CacheClearCommand should declare the name 'locator:cache:clear'.");
        }

        #[Group("Commands")]
        #[Define(
            name: "cache:clear — Returns Zero When Caching Disabled",
            description: "When the active Locator instance has caching disabled, run() warns and returns 0."
        )]
        public function testReturnsZeroWhenCachingDisabled () : void {
            Locator::setGlobal(new Locator(["locator.caching.enabled" => false]));

            $cmd = new CacheClearCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when caching is disabled on the active Locator.");
        }

        #[Group("Commands")]
        #[Define(
            name: "cache:clear — Returns Zero When No Manager Present",
            description: "When the Locator has caching enabled but no cache manager has been initialised, run() warns and returns 0."
        )]
        public function testReturnsZeroWhenNoManagerPresent () : void {
            Locator::setGlobal(new Locator());

            $cmd = new CacheClearCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when no cache manager has been initialised.");
        }
    }
?>