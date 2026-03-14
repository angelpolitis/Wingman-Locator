<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Namespaces Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\NamespacesCommand;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\Manifest;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the NamespacesCommand console bridge command, covering attribute registration
     * and return codes for empty and populated manifest repositories.
     */
    class NamespacesCommandTest extends Test {
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
         * Builds a bare Manifest for the given namespace and source path.
         * @param string $namespace The manifest namespace.
         * @param string $sourcePath The source path used as the manifest's identity key.
         * @return Manifest The constructed Manifest.
         */
        private function makeManifest (string $namespace, string $sourcePath) : Manifest {
            return Manifest::from(
                ["namespace" => $namespace, "aliases" => [], "symbols" => [], "virtuals" => [], "settings" => [], "namespaceAliases" => []],
                $sourcePath
            );
        }

        /**
         * Injects a mock Locator whose manifest repository contains the given manifests.
         * @param Manifest[] $manifests The manifests to populate the repository with.
         */
        private function setManifestLocator (array $manifests) : void {
            $repo = new ManifestRepository();
            foreach ($manifests as $manifest) {
                $repo->add($manifest);
            }

            Locator::setGlobal(new class ($repo) implements LocatorInterface {
                public function __construct (private ManifestRepository $repo) {}
                public function getPathFor (string $expr) : string { return $expr; }
                public function getPathTo (string $expr) : ?string { return null; }
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
            name: "NamespacesCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on NamespacesCommand declares the name 'locator:namespaces'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(NamespacesCommand::class);

            $this->assertTrue($name === "locator:namespaces", "NamespacesCommand should declare the name 'locator:namespaces'.");
        }

        #[Group("Commands")]
        #[Define(
            name: "namespaces — Returns Zero When No Manifests Loaded",
            description: "When the manifest repository is empty, run() returns 0."
        )]
        public function testReturnsZeroWhenNoManifestsLoaded () : void {
            $this->setManifestLocator([]);

            $cmd = new NamespacesCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when no manifests are registered.");
        }

        #[Group("Commands")]
        #[Define(
            name: "namespaces — Returns Zero With Populated Repository",
            description: "When manifests are present, run() returns 0 and renders the namespace table."
        )]
        public function testReturnsZeroWithPopulatedRepository () : void {
            $this->setManifestLocator([
                $this->makeManifest("App", "/tmp/app.manifest"),
                $this->makeManifest("Core", "/tmp/core.manifest"),
            ]);

            $cmd = new NamespacesCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when manifests are present.");
        }

        #[Group("Commands")]
        #[Define(
            name: "namespaces — Returns Zero With Aliases And Paths Flags",
            description: "Combining --aliases and --paths with a populated repository still returns 0."
        )]
        public function testReturnsZeroWithAliasesAndPathsFlags () : void {
            $this->setManifestLocator([$this->makeManifest("App", "/tmp/app.manifest")]);

            $cmd = new NamespacesCommand("--aliases --paths");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when --aliases and --paths are set with a populated repository.");
        }
    }
?>