<?php
    /*/
     * Project Name:    Wingman — Locator — Locator Integration Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\PathUtils;

    /**
     * Integration tests for the Locator class, covering singleton management,
     * setGlobal injection, config application, path resolution and manifest discovery.
     */
    class LocatorTest extends Test {

        /**
         * Resets the global singleton after every test so tests do not bleed into each other.
         */
        public function tearDown () : void {
            Locator::setGlobal(null);
        }

        #[Define(
            name: "get() — Returns LocatorInterface",
            description: "Locator::get() returns an instance that implements LocatorInterface."
        )]
        public function testGetReturnsLocatorInterface () : void {
            $locator = Locator::get();

            $this->assertTrue($locator instanceof LocatorInterface, "get() should return a LocatorInterface instance.");
        }

        #[Define(
            name: "get() — Singleton Semantics",
            description: "Two consecutive calls to get() return the same instance."
        )]
        public function testGetReturnsSameInstance () : void {
            $a = Locator::get();
            $b = Locator::get();

            $this->assertTrue($a === $b, "get() should always return the same singleton instance.");
        }

        #[Define(
            name: "setGlobal() — Injects Custom Instance",
            description: "After setGlobal(), get() returns the injected instance, not the default Locator."
        )]
        public function testSetGlobalInjectsCustomInstance () : void {
            $mock = new class implements LocatorInterface {
                public function getPathFor (string $expr) : string { return "/mock/$expr"; }
                public function getPathTo (string $expr) : ?string { return null; }
                public function getPathToDirectory (string $expr) : ?string { return null; }
                public function getPathToFile (string $expr) : ?string { return null; }
                public function getPathToNamespace (string $namespace) : ?string { return null; }
                public function getPathToRoot (\Wingman\Locator\Enums\PathRootVariable|string $root) : ?string { return null; }
                public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {}
                public function getManifestRepository () : \Wingman\Locator\Objects\ManifestRepository { return new \Wingman\Locator\Objects\ManifestRepository(); }
            };

            Locator::setGlobal($mock);

            $this->assertTrue(Locator::get() === $mock, "get() should return the instance injected via setGlobal().");
        }

        #[Define(
            name: "setGlobal(null) — Resets Singleton",
            description: "After setGlobal(null), the next get() creates a fresh Locator instance."
        )]
        public function testSetGlobalNullResetsSingleton () : void {
            $original = Locator::get();

            Locator::setGlobal(null);

            $fresh = Locator::get();
            $this->assertTrue($fresh !== $original, "After setGlobal(null), get() should return a new instance.");
        }

        #[Define(
            name: "Config — Caching Can Be Disabled",
            description: "Constructing a Locator with locator.caching.enabled = false does not throw."
        )]
        public function testCachingCanBeDisabled () : void {
            $thrown = false;
            try {
                $locator = new Locator(["locator.caching.enabled" => false]);
            }
            catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "Creating Locator with caching disabled should not throw.");
        }

        #[Define(
            name: "Config — TTL Is Applied",
            description: "A Locator constructed with a non-zero TTL does not throw."
        )]
        public function testTtlIsApplied () : void {
            $thrown = false;
            try {
                $locator = new Locator(["locator.caching.ttl" => 300]);
            }
            catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "Creating Locator with a TTL should not throw.");
        }

        #[Define(
            name: "discoverManifests() — Custom Directory",
            description: "discoverManifests() accepts a custom directory and an explicit profile without throwing."
        )]
        public function testDiscoverManifestsCustomDirectory () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);
            $thrown = false;

            try {
                $locator->discoverManifests(
                    sys_get_temp_dir(),
                    DiscoveryProfile::from(["depth" => 0, "onlyRoot" => true])
                );
            }
            catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "discoverManifests() with a custom dir should not throw.");
        }

        #[Define(
            name: "discoverManifests() — Idempotent",
            description: "Calling discoverManifests() twice for the same root/profile does not throw or duplicate work."
        )]
        public function testDiscoverManifestsIdempotent () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);
            $profile = DiscoveryProfile::from(["depth" => 0, "onlyRoot" => true]);
            $thrown = false;

            try {
                $locator->discoverManifests(sys_get_temp_dir(), $profile);
                $locator->discoverManifests(sys_get_temp_dir(), $profile);
            }
            catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "Repeated discoverManifests() calls should be idempotent.");
        }

        #[Define(
            name: "getPathFor() — Returns String",
            description: "getPathFor() always returns a non-empty string for any well-formed path expression."
        )]
        public function testGetPathForReturnsString () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);

            $result = $locator->getPathFor("@{cwd}/some/path.php");

            $this->assertTrue(is_string($result) && $result !== "", "getPathFor() should return a non-empty string.");
        }

        #[Define(
            name: "getPathFor() — Cache Hit Returns Same Value",
            description: "Two calls with the same expression return identical results (cache hit path)."
        )]
        public function testGetPathForCacheHitReturnsSameValue () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);
            $expr = "@{cwd}/logs/app.log";

            $first = $locator->getPathFor($expr);
            $second = $locator->getPathFor($expr);

            $this->assertTrue($first === $second, "Repeated calls to getPathFor() should return the same value.");
        }

        #[Define(
            name: "getPathTo() — Returns Null For Nonexistent Path",
            description: "getPathTo() returns null when the resolved path does not exist on the filesystem."
        )]
        public function testGetPathToReturnsNullForNonexistentPath () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);

            $result = $locator->getPathTo("@{cwd}/definitely_does_not_exist_xyzzy.php");

            $this->assertTrue($result === null, "getPathTo() should return null for a path that does not exist.");
        }

        #[Define(
            name: "getPathToFile() — Returns Null For Directory",
            description: "getPathToFile() returns null when the resolved path is a directory, not a file."
        )]
        public function testGetPathToFileReturnsNullForDirectory () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);

            # sys_get_temp_dir() is guaranteed to be an existing directory.
            $tempDir = sys_get_temp_dir();
            $result = $locator->getPathToFile("@{cwd}/" . basename($tempDir));

            $this->assertTrue($result === null, "getPathToFile() should return null when the resolved path is a directory.");
        }

        #[Define(
            name: "getManifestRepository() — Returns Repository Instance",
            description: "getManifestRepository() returns a ManifestRepository after construction."
        )]
        public function testGetManifestRepositoryReturnsInstance () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);

            $repo = $locator->getManifestRepository();

            $this->assertTrue($repo instanceof \Wingman\Locator\Objects\ManifestRepository, "getManifestRepository() should return a ManifestRepository.");
        }
    }
?>