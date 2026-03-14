<?php
    /**
     * Project Name:    Wingman — Locator — Path Facade Tests
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
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Facades\Path;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Isolated unit tests for the Path static facade.
     * Each test injects an anonymous LocatorInterface implementation so that the facade
     * is exercised in complete isolation from the real filesystem and the Locator singleton.
     * @package Wingman\Locator\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PathFacadeTest extends Test {
        /**
         * The anonymous mock locator injected into the facade for each test.
         * @var LocatorInterface
         */
        private LocatorInterface $mock;

        /**
         * Creates a mock locator and injects it into the facade before each test,
         * guaranteeing that no real Locator singleton is involved.
         */
        public function setUp () : void {
            $this->mock = new class implements LocatorInterface {
                public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {}
                public function getManifestRepository () : ManifestRepository { return new ManifestRepository(); }
                public function getPathFor (string $pathExpression) : string { return "/mock/for/$pathExpression"; }
                public function getPathTo (string $pathExpression) : ?string { return "/mock/to/$pathExpression"; }
                public function getPathToDirectory (string $pathExpression) : ?string { return "/mock/toDir/$pathExpression"; }
                public function getPathToFile (string $pathExpression) : ?string { return "/mock/toFile/$pathExpression"; }
                public function getPathToNamespace (string $namespace) : ?string { return "/mock/ns/$namespace"; }
                public function getPathToRoot (PathRootVariable|string $root) : ?string { return "/mock/root/" . (is_string($root) ? $root : $root->value); }
            };

            Path::setLocator($this->mock);
        }

        /**
         * Removes the injected mock and resets both the facade and the Locator singleton after each test.
         */
        public function tearDown () : void {
            Path::setLocator(null);
            Locator::setGlobal(null);
        }

        #[Group("Locator")]
        #[Define(
            name: "Path::for() — Delegates To getPathFor()",
            description: "Path::for() calls getPathFor() on the injected locator and returns its exact string result."
        )]
        public function testForDelegatesToGetPathFor () : void {
            $result = Path::for("@Wingman/src");

            $this->assertTrue(
                $result === "/mock/for/@Wingman/src",
                "Path::for() should delegate to getPathFor() and return its result."
            );
        }

        #[Group("Locator")]
        #[Define(
            name: "Path::to() — Delegates To getPathTo()",
            description: "Path::to() calls getPathTo() on the injected locator and returns its exact result."
        )]
        public function testToDelegatesToGetPathTo () : void {
            $result = Path::to("@Wingman/config.php");

            $this->assertTrue(
                $result === "/mock/to/@Wingman/config.php",
                "Path::to() should delegate to getPathTo() and return its result."
            );
        }

        #[Group("Locator")]
        #[Define(
            name: "Path::toDirectory() — Delegates To getPathToDirectory()",
            description: "Path::toDirectory() calls getPathToDirectory() on the injected locator and returns its result."
        )]
        public function testToDirectoryDelegatesToGetPathToDirectory () : void {
            $result = Path::toDirectory("@Wingman/src");

            $this->assertTrue(
                $result === "/mock/toDir/@Wingman/src",
                "Path::toDirectory() should delegate to getPathToDirectory() and return its result."
            );
        }

        #[Group("Locator")]
        #[Define(
            name: "Path::toFile() — Delegates To getPathToFile()",
            description: "Path::toFile() calls getPathToFile() on the injected locator and returns its result."
        )]
        public function testToFileDelegatesToGetPathToFile () : void {
            $result = Path::toFile("@Wingman/bootstrap.php");

            $this->assertTrue(
                $result === "/mock/toFile/@Wingman/bootstrap.php",
                "Path::toFile() should delegate to getPathToFile() and return its result."
            );
        }

        #[Group("Locator")]
        #[Define(
            name: "Path::toNamespace() — Delegates To getPathToNamespace()",
            description: "Path::toNamespace() calls getPathToNamespace() on the injected locator and returns its result."
        )]
        public function testToNamespaceDelegatesToGetPathToNamespace () : void {
            $result = Path::toNamespace("Wingman");

            $this->assertTrue(
                $result === "/mock/ns/Wingman",
                "Path::toNamespace() should delegate to getPathToNamespace() and return its result."
            );
        }

        #[Group("Locator")]
        #[Define(
            name: "Path::toRoot() — Delegates To getPathToRoot()",
            description: "Path::toRoot() calls getPathToRoot() on the injected locator and returns its result."
        )]
        public function testToRootDelegatesToGetPathToRoot () : void {
            $result = Path::toRoot("cwd");

            $this->assertTrue(
                $result === "/mock/root/cwd",
                "Path::toRoot() should delegate to getPathToRoot() and return its result."
            );
        }

        #[Group("Locator")]
        #[Define(
            name: "setLocator(null) — Restores Singleton Fallback",
            description: "After calling setLocator(null), the facade falls back to the Locator singleton instead of the injected mock."
        )]
        public function testSetLocatorNullRestoresSingletonFallback () : void {
            $sentinel = new class implements LocatorInterface {
                public bool $called = false;
                public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {}
                public function getManifestRepository () : ManifestRepository { return new ManifestRepository(); }
                public function getPathFor (string $pathExpression) : string { $this->called = true; return "/sentinel/$pathExpression"; }
                public function getPathTo (string $pathExpression) : ?string { return null; }
                public function getPathToDirectory (string $pathExpression) : ?string { return null; }
                public function getPathToFile (string $pathExpression) : ?string { return null; }
                public function getPathToNamespace (string $namespace) : ?string { return null; }
                public function getPathToRoot (PathRootVariable|string $root) : ?string { return null; }
            };

            Locator::setGlobal($sentinel);
            Path::setLocator(null);

            Path::for("any/expression");

            $this->assertTrue($sentinel->called, "After setLocator(null), the facade should fall back to the global Locator singleton.");
        }
    }
?>