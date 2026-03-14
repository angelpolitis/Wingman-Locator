<?php
    /*/
     * Project Name:    Wingman — Locator — Console Bridge — Check Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\CheckCommand;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the CheckCommand console bridge command, covering attribute registration
     * and exit-code correctness for found and missing paths across all supported types.
     */
    class CheckCommandTest extends Test {
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
         * Injects a mock Locator that returns the given path from every resolution method.
         * @param string $resolvedPath The path all resolution methods will return.
         */
        private function setPathReturningLocator (string $resolvedPath) : void {
            Locator::setGlobal(new class ($resolvedPath) implements LocatorInterface {
                public function __construct (private string $path) {}
                public function getPathFor (string $expr) : string { return $this->path; }
                public function getPathTo (string $expr) : ?string { return $this->path; }
                public function getPathToDirectory (string $expr) : ?string { return $this->path; }
                public function getPathToFile (string $expr) : ?string { return $this->path; }
                public function getPathToNamespace (string $namespace) : ?string { return null; }
                public function getPathToRoot (PathRootVariable|string $root) : ?string { return null; }
                public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {}
                public function getManifestRepository () : ManifestRepository { return new ManifestRepository(); }
            });
        }

        /**
         * Injects a mock Locator whose nullable resolution methods all return null.
         */
        private function setNullReturningLocator () : void {
            Locator::setGlobal(new class () implements LocatorInterface {
                public function getPathFor (string $expr) : string { return $expr; }
                public function getPathTo (string $expr) : ?string { return null; }
                public function getPathToDirectory (string $expr) : ?string { return null; }
                public function getPathToFile (string $expr) : ?string { return null; }
                public function getPathToNamespace (string $namespace) : ?string { return null; }
                public function getPathToRoot (PathRootVariable|string $root) : ?string { return null; }
                public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {}
                public function getManifestRepository () : ManifestRepository { return new ManifestRepository(); }
            });
        }

        /**
         * Resets the Locator singleton and discards any buffered console output after each test.
         */
        public function tearDown () : void {
            Locator::setGlobal(null);
            if (ob_get_level() > 0) ob_end_clean();
        }

        #[Define(
            name: "CheckCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on CheckCommand declares the name 'locator:check'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(CheckCommand::class);

            $this->assertTrue($name === "locator:check", "CheckCommand should declare the name 'locator:check'.");
        }

        #[Define(
            name: "check — Returns One When Any Type Not Found",
            description: "With default type, run() returns 1 when getPathTo() returns null."
        )]
        public function testReturnsOneWhenAnyTypeNotFound () : void {
            $this->setNullReturningLocator();

            $cmd = new CheckCommand("@app/missing");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 1, "run() should return 1 when getPathTo() returns null.");
        }

        #[Define(
            name: "check — Returns One When File Type Not Found",
            description: "When type is 'file' and getPathToFile() returns null, run() returns 1."
        )]
        public function testReturnsOneWhenFileTypeNotFound () : void {
            $this->setNullReturningLocator();

            $cmd = new CheckCommand("@app/missing --type=file");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 1, "run() should return 1 when getPathToFile() returns null.");
        }

        #[Define(
            name: "check — Returns Zero When Any Type Found",
            description: "With default type, run() returns 0 when getPathTo() returns a non-null path."
        )]
        public function testReturnsZeroWhenAnyTypeFound () : void {
            $this->setPathReturningLocator("/found/resource");

            $cmd = new CheckCommand("@app/existing");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when getPathTo() returns a non-null path.");
        }

        #[Define(
            name: "check — Returns Zero When Directory Found",
            description: "When type is 'dir' and getPathToDirectory() returns a path, run() returns 0."
        )]
        public function testReturnsZeroWhenDirectoryFound () : void {
            $this->setPathReturningLocator("/found/directory");

            $cmd = new CheckCommand("@app/dir --type=dir");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when getPathToDirectory() returns a non-null path.");
        }
    }
?>