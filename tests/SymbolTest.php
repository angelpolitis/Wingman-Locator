<?php
    /*/
     * Project Name:    Wingman — Locator — Symbol Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\Objects\Symbol;

    /**
     * Tests for the Symbol value object, covering construction and property accessors.
     */
    class SymbolTest extends Test {

        #[Define(
            name: "Name Is Stored",
            description: "The name passed to the constructor is returned by getName()."
        )]
        public function testNameIsStored () : void {
            $symbol = new Symbol("controllers", "src/Controllers");

            $this->assertTrue($symbol->getName() === "controllers", "getName() should return the constructor value.");
        }

        #[Define(
            name: "Target Is Stored",
            description: "The target passed to the constructor is returned by getTarget()."
        )]
        public function testTargetIsStored () : void {
            $symbol = new Symbol("controllers", "src/Controllers");

            $this->assertTrue($symbol->getTarget() === "src/Controllers", "getTarget() should return the constructor value.");
        }

        #[Define(
            name: "Manifest Is Stored When Provided",
            description: "The manifest path passed to the constructor is returned by getManifest()."
        )]
        public function testManifestIsStoredWhenProvided () : void {
            $symbol = new Symbol("controllers", "src/Controllers", "/var/www/app/locator.manifest");

            $this->assertTrue(
                $symbol->getManifest() === "/var/www/app/locator.manifest",
                "getManifest() should return the manifest path."
            );
        }

        #[Define(
            name: "Manifest Defaults To Null",
            description: "When no manifest is given, getManifest() returns null."
        )]
        public function testManifestDefaultsToNull () : void {
            $symbol = new Symbol("controllers", "src/Controllers");

            $this->assertTrue($symbol->getManifest() === null, "getManifest() should return null when not provided.");
        }

        #[Define(
            name: "Empty Name Is Accepted",
            description: "A Symbol with an empty string name does not throw and stores the value faithfully."
        )]
        public function testEmptyNameIsAccepted () : void {
            $symbol = new Symbol("", "some/path");

            $this->assertTrue($symbol->getName() === "", "An empty name should be stored as-is.");
        }

        #[Define(
            name: "Empty Target Is Accepted",
            description: "A Symbol with an empty string target does not throw and stores the value faithfully."
        )]
        public function testEmptyTargetIsAccepted () : void {
            $symbol = new Symbol("controllers", "");

            $this->assertTrue($symbol->getTarget() === "", "An empty target should be stored as-is.");
        }
    }
?>