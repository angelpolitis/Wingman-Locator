<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Resolve Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\ResolveCommand;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the ResolveCommand console bridge command, covering attribute registration,
     * default-type resolution, and typed existence checks with both found and missing paths.
     */
    class ResolveCommandTest extends Test {

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

        #[Group("Commands")]
        #[Define(
            name: "ResolveCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on ResolveCommand declares the name 'locator:resolve'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(ResolveCommand::class);

            $this->assertTrue($name === "locator:resolve", "ResolveCommand should declare the name 'locator:resolve'.");
        }

        #[Group("Commands")]
        #[Define(
            name: "resolve — Returns One When Directory Not Found",
            description: "When type is 'dir' and getPathToDirectory() returns null, run() returns 1."
        )]
        public function testReturnsOneWhenDirectoryNotFound () : void {
            $this->setNullReturningLocator();

            $cmd = new ResolveCommand("@app/missing --type=dir");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 1, "run() should return 1 when getPathToDirectory() returns null.");
        }

        #[Group("Commands")]
        #[Define(
            name: "resolve — Returns One When File Not Found",
            description: "When type is 'file' and getPathToFile() returns null, run() returns 1."
        )]
        public function testReturnsOneWhenFileNotFound () : void {
            $this->setNullReturningLocator();

            $cmd = new ResolveCommand("@app/missing --type=file");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 1, "run() should return 1 when getPathToFile() returns null.");
        }

        #[Group("Commands")]
        #[Define(
            name: "resolve — Returns Zero For Any Type",
            description: "When type is 'any' (the default), run() returns 0 because getPathFor() always returns a string."
        )]
        public function testReturnsZeroForAnyType () : void {
            $this->setPathReturningLocator("/resolved/path.php");

            $cmd = new ResolveCommand("@app/test");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when getPathFor() returns a non-empty string.");
        }

        #[Group("Commands")]
        #[Define(
            name: "resolve — Returns Zero When File Found",
            description: "When type is 'file' and getPathToFile() returns a non-null path, run() returns 0."
        )]
        public function testReturnsZeroWhenFileFound () : void {
            $this->setPathReturningLocator("/resolved/existing.php");

            $cmd = new ResolveCommand("@app/test --type=file");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when getPathToFile() returns a non-null path.");
        }
    }
?>