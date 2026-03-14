<?php
    /*/
     * Project Name:    Wingman — Locator — End-to-End Integration Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use ReflectionObject;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\CacheManager;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\Manifest;

    /**
     * End-to-end integration tests for the full Locator lifecycle:
     * cold boot → manifest discovery → cache write → warm boot → cache restore → path resolution.
     * Each test uses a temporary directory as the document root, ensuring isolation from
     * the real project filesystem and preventing cross-test contamination.
     * @package Wingman\Locator\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class IntegrationTest extends Test {
        /**
         * The temporary directory used as the document root for each test.
         * @var string
         */
        private string $tempDir;

        /**
         * The path to the planted locator.manifest file inside $tempDir.
         * @var string
         */
        private string $manifestFile;

        /**
         * The original value of $_SERVER["DOCUMENT_ROOT"] before the test overrides it.
         * @var string|null
         */
        private ?string $originalDocRoot;

        /**
         * Creates a fresh temporary directory with a minimal locator.manifest file, and
         * overrides DOCUMENT_ROOT so every Locator instantiated in the test discovers
         * from that directory rather than the real project root.
         */
        public function setUp () : void {
            $DS = DIRECTORY_SEPARATOR;
            $this->tempDir = sys_get_temp_dir() . $DS . "wingman_locator_int_" . uniqid();
            mkdir($this->tempDir, 0755, true);

            $this->manifestFile = $this->tempDir . $DS . "locator.manifest";
            file_put_contents($this->manifestFile, json_encode([
                "namespace" => "WingmanLocatorIntTest",
                "symbols" => ["src" => $this->tempDir]
            ]));

            $this->originalDocRoot = $_SERVER["DOCUMENT_ROOT"] ?? null;
            $_SERVER["DOCUMENT_ROOT"] = $this->tempDir;
        }

        /**
         * Restores the original DOCUMENT_ROOT, removes all temporary files created by the test,
         * clears any Cacher entry written during the test, and resets the Locator singleton.
         */
        public function tearDown () : void {
            if ($this->originalDocRoot === null) {
                unset($_SERVER["DOCUMENT_ROOT"]);
            } else {
                $_SERVER["DOCUMENT_ROOT"] = $this->originalDocRoot;
            }

            @unlink($this->manifestFile);
            @rmdir($this->tempDir);

            if (class_exists(\Wingman\Cacher\Cacher::class)) {
                $cacher = new \Wingman\Cacher\Cacher();
                $cacher->delete(\Wingman\Locator\Bridge\Cacher\CacheManager::CACHE_KEY);
            }

            Locator::setGlobal(null);
        }

        #[Define(
            name: "Cold Boot — Discovers Manifest From Disk",
            description: "A fresh Locator with caching off auto-discovers a manifest planted in a temporary directory used as a fake project root (via DOCUMENT_ROOT override)."
        )]
        public function testColdBootDiscoversManifest () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);

            $namespaces = array_map(
                fn ($m) => $m->getNamespace(),
                $locator->getManifestRepository()->getAll()
            );

            $this->assertTrue(
                in_array("WingmanLocatorIntTest", $namespaces, true),
                "Cold boot should discover the planted manifest from DOCUMENT_ROOT."
            );
        }

        #[Define(
            name: "Path Resolution — Returns String After Discovery",
            description: "getPathFor() returns a non-empty string for a symbol defined in the discovered manifest."
        )]
        public function testPathResolutionAfterDiscovery () : void {
            $locator = new Locator(["locator.caching.enabled" => false]);

            $path = $locator->getPathFor("@WingmanLocatorIntTest/src");

            $this->assertTrue(
                is_string($path) && $path !== "",
                "getPathFor() should resolve a symbol defined in the discovered manifest."
            );
        }

        #[Define(
            name: "CacheManager — File-Based Round-Trip",
            description: "CacheManager::save() writes a PHP cache file and load() restores the exact same manifests and roots."
        )]
        public function testCacheManagerFileBasedRoundTrip () : void {
            $DS = DIRECTORY_SEPARATOR;
            $cacheFile = sys_get_temp_dir() . $DS . "wingman_cm_rt_" . uniqid() . ".php";
            $manager = new CacheManager([CacheManager::FILE_KEY => $cacheFile]);

            $manifest = Manifest::from(
                ["namespace" => "WingmanLocatorIntTest", "symbols" => []],
                $this->manifestFile
            );

            $scannedRoots = [["path" => $this->tempDir, "profile" => ["depth" => 1, "exclude" => [], "onlyRoot" => false]]];

            $saved = $manager->save([$manifest], $scannedRoots);
            $this->assertTrue($saved, "CacheManager::save() should return true.");
            $this->assertTrue(file_exists($cacheFile), "Cache file should exist after save().");

            $restored = $manager->load();
            $this->assertTrue(is_array($restored), "load() should return an array after save().");
            $this->assertTrue(isset($restored["manifests"]) && count($restored["manifests"]) === 1, "Restored data should contain one manifest entry.");
            $this->assertTrue($restored["manifests"][0]["namespace"] === "WingmanLocatorIntTest", "Restored manifest namespace should match the original.");

            @unlink($cacheFile);
        }

        #[Define(
            name: "Warm Boot — Restores Manifests From Cache",
            description: "After a cold boot populates the cache, a new Locator instance with no manifest file on disk still provides the manifest via the warm-boot path."
        )]
        public function testWarmBootRestoresManifestsFromCache () : void {
            $coldLocator = new Locator();
            $coldNs = array_map(fn ($m) => $m->getNamespace(), $coldLocator->getManifestRepository()->getAll());

            $this->assertTrue(
                in_array("WingmanLocatorIntTest", $coldNs, true),
                "Cold boot should discover the planted manifest."
            );

            @unlink($this->manifestFile);

            $warmLocator = new Locator();
            $warmNs = array_map(fn ($m) => $m->getNamespace(), $warmLocator->getManifestRepository()->getAll());

            $this->assertTrue(
                in_array("WingmanLocatorIntTest", $warmNs, true),
                "Warm boot should restore the manifest from cache even after the manifest file is deleted."
            );

            $ref = new ReflectionObject($warmLocator);
            $prop = $ref->getProperty("cacheManager");
            $prop->setAccessible(true);
            $cm = $prop->getValue($warmLocator);
            if ($cm !== null) $cm->clear();
        }
    }
?>