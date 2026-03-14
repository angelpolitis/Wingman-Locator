<?php
    /*/
     * Project Name:    Wingman — Locator — Console Bridge — Symbols Command Tests
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
    use Wingman\Locator\Bridge\Console\Commands\SymbolsCommand;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\Manifest;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the SymbolsCommand console bridge command, covering attribute registration,
     * empty symbol tables, and substring filtering with no matching symbols.
     */
    class SymbolsCommandTest extends Test {

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
         * Builds a Manifest for the given namespace with the supplied symbol map.
         * @param string $namespace The manifest namespace.
         * @param array $symbols A map of symbol names to path expressions.
         * @return Manifest The constructed Manifest.
         */
        private function makeManifest (string $namespace, array $symbols = []) : Manifest {
            return Manifest::from(
                ["namespace" => $namespace, "aliases" => [], "symbols" => $symbols, "virtuals" => [], "settings" => [], "namespaceAliases" => []],
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

        #[Define(
            name: "SymbolsCommand — Cmd Attribute Name",
            description: "The #[Cmd] attribute on SymbolsCommand declares the name 'locator:symbols'."
        )]
        public function testCmdAttributeHasCorrectName () : void {
            $name = $this->getCommandName(SymbolsCommand::class);

            $this->assertTrue($name === "locator:symbols", "SymbolsCommand should declare the name 'locator:symbols'.");
        }

        #[Define(
            name: "symbols — Returns Zero When Filter Matches Nothing",
            description: "When --filter is set but no symbol name contains the substring, run() returns 0."
        )]
        public function testReturnsZeroWhenFilterMatchesNothing () : void {
            $this->setManifestLocator([
                $this->makeManifest("App", ["controller" => "@app/controllers"]),
            ]);

            $cmd = new SymbolsCommand("--filter=zzz_no_match");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when the filter matches no symbols.");
        }

        #[Define(
            name: "symbols — Returns Zero When No Symbols Registered",
            description: "When all manifests have empty symbol tables, run() returns 0."
        )]
        public function testReturnsZeroWhenNoSymbolsRegistered () : void {
            $this->setManifestLocator([
                $this->makeManifest("App"),
            ]);

            $cmd = new SymbolsCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when no symbols are registered.");
        }

        #[Define(
            name: "symbols — Returns Zero With Populated Symbol Table",
            description: "When symbols are present and no filter is applied, run() returns 0 and renders the table."
        )]
        public function testReturnsZeroWithPopulatedSymbolTable () : void {
            $this->setManifestLocator([
                $this->makeManifest("App", ["controller" => "@app/ctrl", "model" => "@app/model"]),
            ]);

            $cmd = new SymbolsCommand("");
            $cmd->setConsole($this->makeConsole());

            ob_start();
            $result = $cmd->run();
            ob_end_clean();

            $this->assertTrue($result === 0, "run() should return 0 when symbols are present.");
        }
    }
?>