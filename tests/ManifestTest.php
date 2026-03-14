<?php
    /*/
     * Project Name:    Wingman — Locator — Manifest Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\Manifest;

    /**
     * Tests for the Manifest value object, covering construction, namespace matching and serialisation.
     */
    class ManifestTest extends Test {

        /**
         * The source path used across all tests.
         * @var string
         */
        private string $sourcePath = "/tmp/locator.manifest";

        /**
         * Returns a base data array for building Manifest instances.
         * @return array The base manifest data.
         */
        private function baseData () : array {
            return [
                "namespace" => "App",
                "aliases" => ["app/pages" => "pages"],
                "symbols" => ["controllers" => "src/Controllers"],
                "virtuals" => [],
                "settings" => ["autoScan" => true],
                "namespaceAliases" => ["AppAlias"]
            ];
        }

        #[Define(
            name: "Namespace Is Stored",
            description: "The namespace provided in data is accessible via getNamespace()."
        )]
        public function testNamespaceIsStored () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue($manifest->getNamespace() === "App", "Expected namespace 'App'.");
        }

        #[Define(
            name: "Default Namespace",
            description: "When no namespace key is present in data, the default namespace is used."
        )]
        public function testDefaultNamespaceIsApplied () : void {
            $manifest = Manifest::from([], $this->sourcePath);

            $this->assertTrue(
                $manifest->getNamespace() === NamespaceManager::DEFAULT_NAMESPACE,
                "Expected the default namespace when none is provided."
            );
        }

        #[Define(
            name: "Source Path Is Stored",
            description: "The source path passed to from() is available via getSourcePath()."
        )]
        public function testSourcePathIsStored () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue($manifest->getSourcePath() === $this->sourcePath, "Source path mismatch.");
        }

        #[Define(
            name: "HasNamespace — Direct Match",
            description: "hasNamespace() returns true when the manifest's own namespace matches."
        )]
        public function testHasNamespaceDirectMatch () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue($manifest->hasNamespace("App"), "Expected hasNamespace() to return true for direct match.");
        }

        #[Define(
            name: "HasNamespace — Alias Match",
            description: "hasNamespace() returns true when the given name matches a namespace alias."
        )]
        public function testHasNamespaceAliasMatch () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue($manifest->hasNamespace("AppAlias"), "Expected hasNamespace() to return true for alias match.");
        }

        #[Define(
            name: "HasNamespace — No Match",
            description: "hasNamespace() returns false for a name that is neither the namespace nor an alias."
        )]
        public function testHasNamespaceNoMatch () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue(!$manifest->hasNamespace("Unknown"), "Expected hasNamespace() to return false for an unrelated name.");
        }

        #[Define(
            name: "Symbols Are Stored",
            description: "Symbol entries provided in data are accessible via getSymbols()."
        )]
        public function testSymbolsAreStored () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $symbols = $manifest->getSymbols();
            $this->assertTrue(!empty($symbols), "Expected non-empty symbols array.");
        }

        #[Define(
            name: "Aliases Are Stored",
            description: "Path aliases provided in data are accessible via getAliases()."
        )]
        public function testAliasesAreStored () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue($manifest->getAliases() === $this->baseData()["aliases"], "Alias map mismatch.");
        }

        #[Define(
            name: "Settings Are Stored",
            description: "Custom settings provided in data are accessible via getSettings()."
        )]
        public function testSettingsAreStored () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $this->assertTrue($manifest->getSettings()["autoScan"] === true, "Expected autoScan setting to be true.");
        }

        #[Define(
            name: "Dehydrate Preserves Namespace",
            description: "dehydrate() output retains the namespace, aliases, symbols and settings."
        )]
        public function testDehydratePreservesFields () : void {
            $manifest = Manifest::from($this->baseData(), $this->sourcePath);

            $data = $manifest->dehydrate();

            $this->assertTrue($data["namespace"] === "App", "Dehydrated namespace mismatch.");
            $this->assertTrue($data["namespaceAliases"] === ["AppAlias"], "Dehydrated namespace aliases mismatch.");
            $this->assertTrue($data["settings"]["autoScan"] === true, "Dehydrated settings mismatch.");
        }

        #[Define(
            name: "Hydrate Round-Trip",
            description: "A manifest dehydrated and then rehydrated has the same namespace, aliases and settings."
        )]
        public function testHydrateRoundTrip () : void {
            $packageRoot = dirname(dirname(__DIR__)) . "/modules/Locator";
            $sourcePath = $packageRoot . "/tests/ManifestTest.php";
            $manifest = Manifest::from($this->baseData(), $sourcePath);

            $dehydrated = $manifest->dehydrate();
            $rehydrated = Manifest::hydrate($dehydrated);

            $this->assertTrue($rehydrated->getNamespace() === "App", "Rehydrated namespace mismatch.");
            $this->assertTrue($rehydrated->hasNamespace("AppAlias"), "Rehydrated alias not found.");
            $this->assertTrue($rehydrated->getSettings()["autoScan"] === true, "Rehydrated settings mismatch.");
        }
    }
?>