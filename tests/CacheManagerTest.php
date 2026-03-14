<?php
    /*/
     * Project Name:    Wingman — Locator — Cache Manager Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 14 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\CacheManager;
    use Wingman\Locator\Objects\Manifest;

    /**
     * Tests for the CacheManager class, verifying persistence, rehydration and TTL behaviour.
     */
    class CacheManagerTest extends Test {
        /**
         * The path of the temporary cache file used across tests.
         * @var string
         */
        private string $cachePath;

        /**
         * Creates a unique temporary cache file path before each test method.
         */
        public function setUp () : void {
            $this->cachePath = sys_get_temp_dir() . "/wingman_locator_test_" . uniqid() . ".php";
        }

        /**
         * Removes the temporary cache file after each test method.
         */
        public function tearDown () : void {
            if (file_exists($this->cachePath)) {
                unlink($this->cachePath);
            }
        }

        #[Define(
            name: "Load — Missing File",
            description: "load() returns null when no cache file exists yet."
        )]
        public function testLoadReturnsnullForMissingFile () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath]);

            $this->assertTrue($manager->load() === null, "Expected null for a non-existent cache file.");
        }

        #[Define(
            name: "Save And Load Round-Trip",
            description: "Data saved via save() is faithfully returned by a subsequent load() call."
        )]
        public function testSaveAndLoadRoundTrip () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath]);

            $manifest = Manifest::from(
                ["namespace" => "Test", "aliases" => [], "symbols" => [], "virtuals" => [], "settings" => [], "namespaceAliases" => []],
                "/tmp/locator.manifest"
            );

            $saved = $manager->save([$manifest], ["/tmp"]);
            $this->assertTrue($saved, "save() should return true on success.");

            $data = $manager->load();
            $this->assertTrue(is_array($data), "load() should return an array after a successful save.");
            $this->assertTrue(isset($data["manifests"]), "Loaded data should contain a 'manifests' key.");
            $this->assertTrue(isset($data["roots"]), "Loaded data should contain a 'roots' key.");
            $this->assertTrue(isset($data["timestamp"]), "Loaded data should contain a 'timestamp' key.");
            $this->assertTrue(count($data["manifests"]) === 1, "Loaded data should have exactly one manifest entry.");
        }

        #[Define(
            name: "Clear — File Is Deleted",
            description: "clear() removes the cache file and subsequent load() returns null."
        )]
        public function testClearDeletesFile () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath]);

            $manager->save([], []);
            $this->assertTrue(file_exists($this->cachePath), "Cache file should exist after saving.");

            $manager->clear();
            $this->assertTrue(!file_exists($this->cachePath), "Cache file should be deleted after clear().");
            $this->assertTrue($manager->load() === null, "load() should return null after clear().");
        }

        #[Define(
            name: "Clear — No File Is Silently Ignored",
            description: "clear() returns true and does not throw when the cache file does not exist."
        )]
        public function testClearWithNoFileSucceeds () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath]);

            $result = $manager->clear();
            $this->assertTrue($result, "clear() should return true even when no file exists.");
        }

        #[Define(
            name: "TTL — Fresh Cache Is Returned",
            description: "A cache that is within its maxAge is returned by load()."
        )]
        public function testFreshCacheIsReturnedWithinTtl () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath, CacheManager::TTL_KEY => 60]);

            $manager->save([], []);
            $data = $manager->load();

            $this->assertTrue(is_array($data), "A fresh cache within its TTL should be returned.");
        }

        #[Define(
            name: "TTL — Stale Cache Returns Null",
            description: "A cache whose timestamp is older than maxAge is treated as expired and load() returns null."
        )]
        public function testStaleCacheReturnsNull () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath, CacheManager::TTL_KEY => 1]);

            $manager->save([], []);

            # Overwrite the timestamp with a value that is guaranteed to be stale.
            $data = include $this->cachePath;
            $data["timestamp"] = time() - 120;
            $content = "<?php\nreturn " . var_export($data, true) . ";";
            file_put_contents($this->cachePath, $content);

            $loaded = $manager->load();
            $this->assertTrue($loaded === null, "A stale cache should return null.");
        }

        #[Define(
            name: "TTL Disabled — Old Timestamp Is Accepted",
            description: "When maxAge is 0 (disabled), load() returns data regardless of how old the timestamp is."
        )]
        public function testDisabledTtlAcceptsOldTimestamp () : void {
            $manager = new CacheManager([CacheManager::FILE_KEY => $this->cachePath]);

            $manager->save([], []);

            $data = include $this->cachePath;
            $data["timestamp"] = time() - 99999;
            $content = "<?php\nreturn " . var_export($data, true) . ";";
            file_put_contents($this->cachePath, $content);

            $loaded = $manager->load();
            $this->assertTrue(is_array($loaded), "When TTL is disabled, old caches should still be returned.");
        }
    }
?>