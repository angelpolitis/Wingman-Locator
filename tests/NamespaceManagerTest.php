<?php
    /**
     * Project Name:    Wingman — Locator — NamespaceManager Tests
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
    use Wingman\Locator\Exceptions\UnknownNamespaceException;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\NamespaceObject;

    /**
     * Tests for the NamespaceManager class, covering namespace registration, implicit namespace resolution and path matching.
     */
    class NamespaceManagerTest extends Test {

        /**
         * Returns a pre-populated NamespaceManager with an "App" namespace and an "Application" alias.
         * @return NamespaceManager The manager with a registered namespace.
         */
        private function makeManager () : NamespaceManager {
            $ns = new NamespaceObject("App", "/var/www/app", ["Application"]);
            return new NamespaceManager([$ns]);
        }

        #[Group("Namespaces")]
        #[Define(
            name: "Default Implicit Namespace",
            description: "A freshly created manager's implicit namespace is the DEFAULT_NAMESPACE constant."
        )]
        public function testDefaultImplicitNamespace () : void {
            $manager = new NamespaceManager();

            $this->assertTrue(
                $manager->getImplicitNamespace() === NamespaceManager::DEFAULT_NAMESPACE,
                "Expected implicit namespace to equal the DEFAULT_NAMESPACE constant."
            );
        }

        #[Group("Namespaces")]
        #[Define(
            name: "Default Namespace Always Exists",
            description: "hasNamespace() returns true for the default namespace even when nothing is registered."
        )]
        public function testDefaultNamespaceAlwaysExists () : void {
            $manager = new NamespaceManager();

            $this->assertTrue($manager->hasNamespace(NamespaceManager::DEFAULT_NAMESPACE), "Default namespace should always exist.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "Register And Retrieve Namespace",
            description: "A namespace registered via the constructor is retrievable via getNamespace()."
        )]
        public function testRegisterAndRetrieveNamespace () : void {
            $manager = $this->makeManager();

            $ns = $manager->getNamespace("App");
            $this->assertTrue($ns !== null, "Expected a NamespaceObject for 'App'.");
            $this->assertTrue($ns->getName() === "App", "Namespace name mismatch.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "HasNamespace — Registered",
            description: "hasNamespace() returns true for a registered namespace."
        )]
        public function testHasNamespaceForRegistered () : void {
            $manager = $this->makeManager();

            $this->assertTrue($manager->hasNamespace("App"), "hasNamespace() should return true for 'App'.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "HasNamespace — Unknown",
            description: "hasNamespace() returns false for a namespace that has not been registered."
        )]
        public function testHasNamespaceForUnknown () : void {
            $manager = $this->makeManager();

            $this->assertTrue(!$manager->hasNamespace("Phantom"), "hasNamespace() should return false for 'Phantom'.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "HasNamespace — Alias",
            description: "hasNamespace() returns true when queried with a registered alias."
        )]
        public function testHasNamespaceForAlias () : void {
            $manager = $this->makeManager();

            $this->assertTrue($manager->hasNamespace("Application"), "hasNamespace() should accept an alias.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetNamespacePath — Default Returns Server Variable",
            description: "getNamespacePath() for the default namespace returns the @{server} variable string."
        )]
        public function testDefaultNamespacePathIsServerVariable () : void {
            $manager = new NamespaceManager();

            $path = $manager->getNamespacePath(NamespaceManager::DEFAULT_NAMESPACE);
            $this->assertTrue($path === "@{server}", "Default namespace path should be '@{server}'.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetNamespacePath — Registered Namespace",
            description: "getNamespacePath() returns the root path of a registered namespace."
        )]
        public function testGetNamespacePathForRegisteredNamespace () : void {
            $manager = $this->makeManager();

            $path = $manager->getNamespacePath("App");
            $this->assertTrue($path !== null, "Path should not be null for a registered namespace.");
            $this->assertTrue(str_contains($path, "app"), "Path should reference the registered root.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetCanonicalNamespace — Direct",
            description: "getCanonicalNamespace() returns a namespace's own name when queried directly."
        )]
        public function testGetCanonicalNamespaceDirect () : void {
            $manager = $this->makeManager();

            $canonical = $manager->getCanonicalNamespace("App");
            $this->assertTrue($canonical === "app", "Canonical name for 'App' should be 'app' (lower-cased).");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetCanonicalNamespace — Via Alias",
            description: "getCanonicalNamespace() resolves an alias back to the canonical namespace name."
        )]
        public function testGetCanonicalNamespaceViaAlias () : void {
            $manager = $this->makeManager();

            $canonical = $manager->getCanonicalNamespace("Application");
            $this->assertTrue($canonical === "app", "Alias 'Application' should resolve to canonical 'app'.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetCanonicalNamespace — Unknown Returns Null",
            description: "getCanonicalNamespace() returns null for an unregistered name or alias."
        )]
        public function testGetCanonicalNamespaceUnknownReturnsNull () : void {
            $manager = $this->makeManager();

            $this->assertTrue($manager->getCanonicalNamespace("NoSuch") === null, "Unknown namespace should yield null.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetPathNamespace — Longest Prefix Wins",
            description: "When multiple namespaces share a common root, getPathNamespace() returns the namespace with the deepest matching prefix."
        )]
        public function testGetPathNamespaceLongestPrefixWins () : void {
            $parent = new NamespaceObject("Parent", "/var/www");
            $child = new NamespaceObject("Child", "/var/www/app/src");
            $manager = new NamespaceManager([$parent, $child]);

            $resolved = $manager->getPathNamespace("/var/www/app/src/Controllers/User.php");
            $this->assertTrue($resolved === "Child", "The deepest matching prefix should win.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "GetPathNamespace — No Match Returns Default",
            description: "getPathNamespace() returns the default namespace when no registered namespace matches the path."
        )]
        public function testGetPathNamespaceNoMatchReturnsDefault () : void {
            $manager = $this->makeManager();

            $resolved = $manager->getPathNamespace("/completely/unrelated/path");
            $this->assertTrue(
                $resolved === NamespaceManager::DEFAULT_NAMESPACE,
                "An unmatched path should resolve to the default namespace."
            );
        }

        #[Group("Namespaces")]
        #[Define(
            name: "SetImplicitNamespace — Updates The Static Value",
            description: "After setImplicitNamespace(), getImplicitNamespace(true) returns the new namespace."
        )]
        public function testSetImplicitNamespaceUpdatesStaticValue () : void {
            $manager = $this->makeManager();

            $manager->setImplicitNamespace("App");
            $this->assertTrue($manager->getImplicitNamespace(true) === "app", "Implicit namespace should be updated to 'app'.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "SetImplicitNamespace — Unknown Throws",
            description: "setImplicitNamespace() throws UnknownNamespaceException for an unregistered name."
        )]
        public function testSetImplicitNamespaceThrowsForUnknown () : void {
            $manager = new NamespaceManager();
            $thrown = false;

            try {
                $manager->setImplicitNamespace("Ghost");
            }
            catch (UnknownNamespaceException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "An UnknownNamespaceException should be thrown for an unregistered namespace.");
        }

        #[Group("Namespaces")]
        #[Define(
            name: "RegisterNamespace — Fluent Interface",
            description: "registerNamespace() returns the same manager instance, enabling method chaining."
        )]
        public function testRegisterNamespaceReturnsSelf () : void {
            $manager = new NamespaceManager();
            $ns = new NamespaceObject("NewNS", "/tmp/newns");

            $result = $manager->registerNamespace($ns);
            $this->assertTrue($result === $manager, "registerNamespace() should return the manager itself.");
            $this->assertTrue($manager->hasNamespace("NewNS"), "Namespace registered via registerNamespace() should be recognisable.");
        }
    }
?>