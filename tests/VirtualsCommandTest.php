<?php
    /**
     * Project Name:    Wingman — Locator — Console Bridge — Virtuals Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\VirtualsCommand;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\Manifest;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the VirtualsCommand console bridge command, covering attribute registration,
     * empty virtual tables, and namespace-scoped filtering.
     */
    class VirtualsCommandTest extends Test {

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
         * Builds a Manifest for the given namespace with the supplied virtual entries.
         * @param string $namespace The manifest namespace.
         * @param array $virtuals A map of virtual names to definition arrays (type, source).
         * @return Manifest The constructed Manifest.
         */
        private function makeManifest (string $namespace, array $virtuals = []) : Manifest {
            return Manifest::from(
                ["namespace" => $namespace, "aliases" => [], "symbols" => [], "virtuals" => $virtuals, "settings" => [], "namespaceAliases" => []],
                "/tmp/{$namespace}.manifest"
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
            name: "VirtualsCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on VirtualsCommand declares the name 'locator:virtuals'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(VirtualsCommand::class);

            $this->assertTrue($name === "locator:virtuals", "VirtualsCommand should declare the name 'locator:virtuals'.");
        }

        #[Group("Commands")]
        #[Define(
            name: "virtuals — Returns Zero When Namespace Filter Yields No Match",
            description: "When --namespace is specified but no virtual entry belongs to it, run() returns 0."
        )]
        public function testReturnsZeroWhenNamespaceFilterYieldsNoMatch () : void {
            $this->setManifestLocator([
                $this->makeManifest("App", ["uploads" => ["type" => "dir", "source" => "/var/uploads"]]),
            ]);

            $cmd = new VirtualsCommand("--namespace=NonExistent");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when the namespace filter matches no virtuals.");
        }

        #[Group("Commands")]
        #[Define(
            name: "virtuals — Returns Zero When No Virtuals Registered",
            description: "When all manifests have empty virtual tables, run() returns 0."
        )]
        public function testReturnsZeroWhenNoVirtualsRegistered () : void {
            $this->setManifestLocator([$this->makeManifest("App")]);

            $cmd = new VirtualsCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when no virtual entries exist.");
        }

        #[Group("Commands")]
        #[Define(
            name: "virtuals — Returns Zero With Populated Virtual Table",
            description: "When virtual entries are present and no filter is applied, run() returns 0 and renders the table."
        )]
        public function testReturnsZeroWithPopulatedVirtualTable () : void {
            $this->setManifestLocator([
                $this->makeManifest("App", [
                    "uploads" => ["type" => "dir", "source" => "/var/uploads"],
                    "config"  => ["type" => "file", "source" => "/etc/app/config.php"],
                ]),
            ]);

            $cmd = new VirtualsCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when virtual entries are present.");
        }
    }
?>