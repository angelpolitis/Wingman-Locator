<?php
    /**
     * Project Name:    Wingman — Locator — Namespace Object Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Locator\Objects\NamespaceObject;
    use Wingman\Locator\Objects\Symbol;
    use Wingman\Locator\PathUtils;

    /**
     * Tests for the NamespaceObject class, covering construction, aliases, symbols, virtuals, settings and import.
     */
    class NamespaceObjectTest extends Test {

        #[Group("Namespaces")]
        #[Define(
            name: "Name Is Stored",
            description: "The name given to the constructor is returned by getName()."
        )]
        public function testNameIsStored () : void {
            $ns = new NamespaceObject("App", "/var/www/app");

            $this->assertTrue($ns->getName() === "App", "getName() should return 'App'.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "Path Is Normalised",
            description: "The path is passed through PathUtils::fix() so separators match the OS."
        )]
        public function testPathIsNormalised () : void {
            $ns = new NamespaceObject("App", "/var/www/app");

            $this->assertTrue($ns->getPath() === PathUtils::fix("/var/www/app"), "Path should be normalised.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "Aliases — Initial Value",
            description: "Aliases provided to the constructor are returned by getAliases()."
        )]
        public function testAliasesInitialValue () : void {
            $ns = new NamespaceObject("App", "/var/www/app", ["Application", "application"]);

            $aliases = $ns->getAliases();
            $this->assertTrue(in_array("Application", $aliases), "Initial alias 'Application' should be present.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addAlias() — Fluent Return",
            description: "addAlias() returns the same NamespaceObject instance."
        )]
        public function testAddAliasReturnsSelf () : void {
            $ns = new NamespaceObject("App", "/tmp");

            $result = $ns->addAlias("Alias");
            $this->assertTrue($result === $ns, "addAlias() should return the same instance.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addAlias() — Alias Is Added",
            description: "After addAlias(), getAliases() contains the new alias."
        )]
        public function testAddAliasAddsAlias () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addAlias("AppAlias");

            $this->assertTrue(in_array("AppAlias", $ns->getAliases()), "Added alias should be in getAliases().");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addAliases() — Deduplicates",
            description: "addAliases() with a value already present does not produce a duplicate."
        )]
        public function testAddAliasesDeduplicates () : void {
            $ns = new NamespaceObject("App", "/tmp", ["Dup"]);
            $ns->addAliases(["Dup", "NewAlias"]);

            $count = count(array_filter($ns->getAliases(), fn ($a) => $a === "Dup"));
            $this->assertTrue($count === 1, "Duplicate alias 'Dup' should appear exactly once.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addSymbol() — Symbol Is Stored",
            description: "After addSymbol(), getSymbol() returns a Symbol with the correct name and target."
        )]
        public function testAddSymbolIsStored () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addSymbol("controllers", "src/Controllers");

            $symbol = $ns->getSymbol("controllers");
            $this->assertTrue($symbol instanceof Symbol, "getSymbol() should return a Symbol instance.");
            $this->assertTrue($symbol->getName() === PathUtils::fix("controllers"), "Symbol name mismatch.");
            $this->assertTrue($symbol->getTarget() === PathUtils::fix("src/Controllers"), "Symbol target mismatch.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addSymbol() — Manifest Is Stored",
            description: "When a manifest path is provided, Symbol::getManifest() returns it."
        )]
        public function testAddSymbolStoresManifest () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addSymbol("pages", "src/Pages", "/tmp/locator.manifest");

            $this->assertTrue(
                $ns->getSymbol("pages")->getManifest() === "/tmp/locator.manifest",
                "Symbol manifest path mismatch."
            );
        }

        #[Group("Namespaces")]
        #[Define(
            name: "getSymbol() — Unknown Returns Null",
            description: "getSymbol() returns null when the requested symbol does not exist."
        )]
        public function testGetSymbolUnknownReturnsNull () : void {
            $ns = new NamespaceObject("App", "/tmp");

            $this->assertTrue($ns->getSymbol("ghost") === null, "getSymbol() should return null for an unknown name.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addSymbols() — Bulk Add",
            description: "addSymbols() registers all entries and getSymbols() returns all of them."
        )]
        public function testAddSymbolsBulkAdd () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addSymbols(["controllers" => "src/Controllers", "models" => "src/Models"]);

            $symbols = $ns->getSymbols();
            $this->assertTrue(count($symbols) === 2, "Two symbols should be registered after addSymbols().");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "setSymbols() — Replaces Existing",
            description: "setSymbols() replaces any previously registered symbols entirely."
        )]
        public function testSetSymbolsReplacesExisting () : void {
            $ns = new NamespaceObject("App", "/tmp", null, null, ["old" => "old/path"]);
            $ns->setSymbols(["new" => "new/path"]);

            $symbols = $ns->getSymbols();
            $this->assertTrue(!isset($symbols[PathUtils::fix("old")]), "Old symbol should be replaced by setSymbols().");
            $this->assertTrue(isset($symbols[PathUtils::fix("new")]), "New symbol from setSymbols() should exist.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addSetting() / addSettings() — Values Merged",
            description: "addSetting() adds a single key-value pair; addSettings() deep-merges an array."
        )]
        public function testAddSettingsMergeValues () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addSetting("debug", true);
            $ns->addSettings(["version" => "1.0", "debug" => false]);

            $settings = $ns->getSettings();
            $this->assertTrue($settings["debug"] === false, "Later addSettings() should overwrite 'debug'.");
            $this->assertTrue($settings["version"] === "1.0", "'version' setting should be present.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addVirtual() — String Entry Normalised",
            description: "A string virtual entry has its separators normalised via PathUtils::fix()."
        )]
        public function testAddVirtualStringEntryNormalised () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addVirtual("index", "public/index.php");

            $virtuals = $ns->getVirtuals();
            $this->assertTrue(isset($virtuals["index"]), "Virtual 'index' should be registered.");
            $this->assertTrue($virtuals["index"] === PathUtils::fix("public/index.php"), "Virtual string should be normalised.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "addVirtual() — Nested Array Normalised Recursively",
            description: "Nested array virtual entries have all string values normalised, preserving structure."
        )]
        public function testAddVirtualNestedArrayNormalisedRecursively () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->addVirtual("pages", [
                "type" => "directory",
                "content" => [
                    "home" => "src/Pages/Home.php"
                ]
            ]);

            $virtuals = $ns->getVirtuals();
            $this->assertTrue(isset($virtuals["pages"]["content"]["home"]), "Nested virtual 'home' should exist.");
            $this->assertTrue(
                $virtuals["pages"]["content"]["home"] === PathUtils::fix("src/Pages/Home.php"),
                "Nested virtual path should be normalised."
            );
        }

        #[Group("Namespaces")]
        #[Define(
            name: "import() — All Keys Merged",
            description: "import() correctly merges aliases, settings, symbols and virtuals in a single call."
        )]
        public function testImportMergesAllKeys () : void {
            $ns = new NamespaceObject("App", "/tmp");
            $ns->import([
                "aliases" => ["Imported"],
                "settings" => ["locale" => "en"],
                "symbols" => ["views" => "src/Views"],
                "virtuals" => ["about" => "pages/about.php"]
            ], "/manifest.path");

            $this->assertTrue(in_array("Imported", $ns->getAliases()), "Imported alias should be present.");
            $this->assertTrue($ns->getSettings()["locale"] === "en", "Imported setting should be present.");
            $this->assertTrue($ns->getSymbol("views") instanceof Symbol, "Imported symbol 'views' should exist.");
            $this->assertTrue(isset($ns->getVirtuals()["about"]), "Imported virtual 'about' should be present.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "import() — Returns Self",
            description: "import() returns the same NamespaceObject, enabling method chaining."
        )]
        public function testImportReturnsSelf () : void {
            $ns = new NamespaceObject("App", "/tmp");

            $result = $ns->import([]);
            $this->assertTrue($result === $ns, "import() should return the same instance.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "from() — Factory Creates From Data Array",
            description: "NamespaceObject::from() creates an instance with the correct name, path and aliases."
        )]
        public function testFromFactoryCreatesFromDataArray () : void {
            $ns = NamespaceObject::from([
                "name" => "Framework",
                "path" => "/opt/framework",
                "aliases" => ["fw"],
                "settings" => [],
                "symbols" => [],
                "virtuals" => []
            ]);

            $this->assertTrue($ns->getName() === "Framework", "Name should be 'Framework'.");
            $this->assertTrue(in_array("fw", $ns->getAliases()), "Alias 'fw' should be present.");
        }
    }
?>
