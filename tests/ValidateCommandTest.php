<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Validate Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\ValidateCommand;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\Manifest;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the ValidateCommand console bridge command, covering attribute registration,
     * the empty-symbol early exit, and strict-mode failure detection for missing paths.
     */
    class ValidateCommandTest extends Test {
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
         * Creates a minimal Console instance with colours and verbosity enabled,
         * so that warning output is produced and run() can be exercised fully.
         * @return Console The configured Console instance.
         */
        private function makeConsole () : Console {
            return new Console(["coloursEnabled" => false, "verbose" => true]);
        }

        /**
         * Builds a Manifest for the given namespace with the supplied symbol map.
         * @param string $namespace The manifest namespace.
         * @param array $symbols A map of symbol names to path expressions.
         * @return Manifest The constructed Manifest.
         */
        private function makeManifest (string $namespace, array $symbols = []) : Manifest {
            return Manifest::from(
                ["namespace" => $namespace, "aliases" => [], "symbols" => $symbols, "virtuals" => [], "settings" => [], "namespaceAliases" => []],
                "/tmp/{$namespace}_validate.manifest"
            );
        }

        /**
         * Injects a mock Locator whose getPathFor() always returns the given resolved path
         * and whose manifest repository is pre-populated with the supplied manifests.
         *
         * This is used to simulate scenarios where every symbol resolves to a known path
         * (which may or may not exist on the filesystem).
         * @param string $resolvedPath The path returned by getPathFor() for every expression.
         * @param Manifest[] $manifests The manifests to load into the repository.
         */
        private function setResolvedPathLocator (string $resolvedPath, array $manifests) : void {
            $repo = new ManifestRepository();
            foreach ($manifests as $manifest) {
                $repo->add($manifest);
            }

            Locator::setGlobal(new class ($resolvedPath, $repo) implements LocatorInterface {
                public function __construct (private string $path, private ManifestRepository $repo) {}
                public function getPathFor (string $expr) : string { return $this->path; }
                public function getPathTo (string $expr) : ?string { return $this->path; }
                public function getPathToDirectory (string $expr) : ?string { return null; }
                public function getPathToFile (string $expr) : ?string { return null; }
                public function getPathToNamespace (string $namespace) : ?string { return null; }
                public function getPathToRoot (PathRootVariable|string $root) : ?string { return null; }
                public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {}
                public function getManifestRepository () : ManifestRepository { return $this->repo; }
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
            name: "ValidateCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on ValidateCommand declares the name 'locator:validate'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(ValidateCommand::class);

            $this->assertTrue($name === "locator:validate", "ValidateCommand should declare the name 'locator:validate'.");
        }

        #[Group("Commands")]
        #[Define(
            name: "validate — Returns Zero When No Symbols Registered",
            description: "When the manifest repository has no symbols, run() warns and returns 0."
        )]
        public function testReturnsZeroWhenNoSymbolsRegistered () : void {
            $this->setResolvedPathLocator("/tmp/any.php", [$this->makeManifest("App")]);

            $cmd = new ValidateCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when there are no symbols to validate.");
        }

        #[Group("Commands")]
        #[Define(
            name: "validate — Strict Mode Returns Zero When No Symbols",
            description: "With --strict and no symbols registered, run() still returns 0 — nothing has failed."
        )]
        public function testStrictModeReturnsZeroWhenNoSymbols () : void {
            $this->setResolvedPathLocator("/tmp/any.php", [$this->makeManifest("App")]);

            $cmd = new ValidateCommand("--strict");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() with --strict should return 0 when there are no symbols.");
        }

        #[Group("Commands")]
        #[Define(
            name: "validate — Strict Mode Returns One For Missing Symbols",
            description: "With --strict active and symbols resolving to non-existent paths, run() returns 1."
        )]
        public function testStrictModeReturnsOneForMissingSymbols () : void {
            $nonExistentPath = "/tmp/wingman_locator_test_nonexistent_symbol_77261.php";

            $this->setResolvedPathLocator($nonExistentPath, [
                $this->makeManifest("App", ["config" => "@app/config"]),
            ]);

            $cmd = new ValidateCommand("--strict");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 1, "run() with --strict should return 1 when at least one symbol resolves to a missing path.");
        }

        #[Group("Commands")]
        #[Define(
            name: "validate — Normal Mode Returns Zero Even For Missing Symbols",
            description: "Without --strict, run() returns 0 even when symbols resolve to non-existent paths."
        )]
        public function testNormalModeReturnsZeroEvenForMissingSymbols () : void {
            $nonExistentPath = "/tmp/wingman_locator_test_nonexistent_symbol_77261.php";

            $this->setResolvedPathLocator($nonExistentPath, [
                $this->makeManifest("App", ["config" => "@app/config"]),
            ]);

            $cmd = new ValidateCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() without --strict should return 0 even when symbols are missing.");
        }
    }
?>