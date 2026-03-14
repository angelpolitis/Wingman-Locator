<?php
    /*/
     * Project Name:    Wingman — Locator — Console Bridge — Cache Status Command Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 13 2026
     * Last Modified:   Mar 13 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use ReflectionClass;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Console\Attributes\Command as Cmd;
    use Wingman\Console\Console;
    use Wingman\Locator\Bridge\Console\Commands\CacheStatusCommand;
    use Wingman\Locator\Locator;

    /**
     * Tests for the CacheStatusCommand console bridge command, covering attribute registration
     * and the invariant that the command always returns 0 regardless of cache state.
     *
     * These tests use a real Locator instance because CacheStatusCommand reads internal
     * Locator properties via reflection and cannot be satisfied by an interface-only mock.
     */
    class CacheStatusCommandTest extends Test {
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
         * Resets the Locator singleton and discards any buffered console output after each test.
         */
        public function tearDown () : void {
            Locator::setGlobal(null);
            if (ob_get_level() > 0) ob_end_clean();
        }

        #[Define(
            name: "CacheStatusCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on CacheStatusCommand declares the name 'locator:cache:status'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(CacheStatusCommand::class);

            $this->assertTrue($name === "locator:cache:status", "CacheStatusCommand should declare the name 'locator:cache:status'.");
        }

        #[Define(
            name: "cache:status — Always Returns Zero (Caching Disabled)",
            description: "When the Locator has caching disabled, run() still returns 0 — status is a read-only diagnostic."
        )]
        public function testAlwaysReturnsZeroWhenCachingDisabled () : void {
            Locator::setGlobal(new Locator(["locator.caching.enabled" => false]));

            $cmd = new CacheStatusCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should always return 0 regardless of cache state.");
        }

        #[Define(
            name: "cache:status — Always Returns Zero (Default Locator)",
            description: "With a default Locator instance, run() returns 0 and renders the status table."
        )]
        public function testAlwaysReturnsZeroWithDefaultLocator () : void {
            Locator::setGlobal(new Locator());

            $cmd = new CacheStatusCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should always return 0 — cache:status is a diagnostic command.");
        }
    }
?>