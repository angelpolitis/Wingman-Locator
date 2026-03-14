<?php
    /*/
     * Project Name:    Wingman — Locator — Bridge Stub Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\Bridge\Cacher\CacheManager as BridgeCacheManager;
    use Wingman\Locator\Bridge\Cortex\Configuration as CortexBridge;
    use Wingman\Locator\Bridge\Corvus\Emitter;
    use Wingman\Locator\CacheManager as BaseCacheManager;
    use Wingman\Locator\Locator;
    use Wingman\Locator\Objects\Manifest;

    /**
     * Tests for all three Locator bridge classes: Cortex/Environment, Corvus/Emitter, and Cacher/CacheManager.
     * Each bridge either aliases the real library class (when installed) or falls back to a no-op stub.
     * These tests verify that the public contract is fulfilled in both cases, ensuring that Locator
     * behaves correctly regardless of which optional dependencies are present.
     * @package Wingman\Locator\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class BridgeStubTest extends Test {
        /**
         * Resets the global singleton after every test to prevent bleed-through.
         */
        public function tearDown () : void {
            Locator::setGlobal(null);
        }

        // ─── Cortex / Configuration ───────────────────────────────────────────────

        #[Define(
            name: "Cortex Bridge — find() Returns Null Or Instance",
            description: "CortexBridge::find() returns null when no named configuration exists, or a Configuration instance when one does."
        )]
        public function testCortexBridgeFindReturnsNullOrInstance () : void {
            $result = CortexBridge::find("nonexistent_" . uniqid());

            $this->assertTrue(
                $result === null || $result instanceof CortexBridge,
                "find() should return null or a Configuration instance."
            );
        }

        #[Define(
            name: "Cortex Bridge — exists() Returns Bool",
            description: "CortexBridge::exists() returns false for an unregistered name and always returns a boolean."
        )]
        public function testCortexBridgeExistsReturnsBool () : void {
            $result = CortexBridge::exists("nonexistent_" . uniqid());

            $this->assertTrue(is_bool($result), "exists() should return a boolean.");
            $this->assertFalse($result, "exists() should return false for an unregistered name.");
        }

        #[Define(
            name: "Cortex Bridge — getAll() And getAllNames() Return Arrays",
            description: "Both registry accessors always return arrays, even when Cortex is unavailable."
        )]
        public function testCortexBridgeRegistryAccessorsReturnArrays () : void {
            $this->assertTrue(is_array(CortexBridge::getAll()), "getAll() should return an array.");
            $this->assertTrue(is_array(CortexBridge::getAllNames()), "getAllNames() should return an array.");
        }

        #[Define(
            name: "Cortex Bridge — hydrate() Returns Configuration Instance",
            description: "CortexBridge::hydrate() returns the source Configuration when one is passed in."
        )]
        public function testCortexBridgeHydrateReturnsConfiguration () : void {
            $config  = new CortexBridge();
            $locator = new Locator(["locator.caching.enabled" => false]);

            $returned = CortexBridge::hydrate($locator, $config);

            $this->assertTrue($returned instanceof CortexBridge, "hydrate() should return a Configuration instance.");
        }

        #[Define(
            name: "Cortex Bridge — captureObject() Returns Fluent Instance",
            description: "captureObject() returns the same Configuration instance for fluent chaining."
        )]
        public function testCortexBridgeCaptureObjectReturnsFluent () : void {
            $config  = new CortexBridge();
            $locator = new Locator(["locator.caching.enabled" => false]);

            $returned = $config->captureObject($locator, "test");

            $this->assertTrue($returned instanceof CortexBridge, "captureObject() should return a Configuration instance.");
        }

        #[Define(
            name: "Cortex Bridge — restoreObject() Returns Fluent Instance",
            description: "restoreObject() returns the same Configuration instance for fluent chaining."
        )]
        public function testCortexBridgeRestoreObjectReturnsFluent () : void {
            $config  = new CortexBridge();
            $locator = new Locator(["locator.caching.enabled" => false]);
            $config->captureObject($locator, "test");

            $returned = $config->restoreObject($locator, "test");

            $this->assertTrue($returned instanceof CortexBridge, "restoreObject() should return a Configuration instance.");
        }

        // ─── Corvus / Emitter ─────────────────────────────────────────────────────

        #[Define(
            name: "Corvus Bridge — create() Returns Instance",
            description: "Emitter::create() returns an object satisfying the emitter contract."
        )]
        public function testCorvusBridgeCreateReturnsInstance () : void {
            $emitter = Emitter::create();

            $this->assertTrue(is_object($emitter), "Emitter::create() should return an object.");
        }

        #[Define(
            name: "Corvus Bridge — for() Returns Instance",
            description: "Emitter::for() returns an object satisfying the emitter contract."
        )]
        public function testCorvusBridgeForReturnsInstance () : void {
            $target = new \stdClass();
            $emitter = Emitter::for($target);

            $this->assertTrue(is_object($emitter), "Emitter::for() should return an object.");
        }

        #[Define(
            name: "Corvus Bridge — emit() Returns Fluent Instance",
            description: "Emitter::emit() returns the same emitter instance for fluent chaining."
        )]
        public function testCorvusBridgeEmitReturnsFluent () : void {
            $emitter = Emitter::create();
            $returned = $emitter->emit("locator.discovery.started");

            $this->assertTrue($returned === $emitter, "emit() should return the same instance for fluent chaining.");
        }

        #[Define(
            name: "Corvus Bridge — with() Returns Fluent Instance",
            description: "Emitter::with() returns the same emitter instance for fluent chaining."
        )]
        public function testCorvusBridgeWithReturnsFluent () : void {
            $emitter = Emitter::create();
            $returned = $emitter->with(["key" => "value"]);

            $this->assertTrue($returned === $emitter, "with() should return the same instance for fluent chaining.");
        }

        #[Define(
            name: "Corvus Bridge — getPayload() Returns Array",
            description: "Emitter::getPayload() returns an array (empty for the stub, possibly populated for the real Corvus)."
        )]
        public function testCorvusBridgeGetPayloadReturnsArray () : void {
            $emitter = Emitter::create();
            $payload = $emitter->getPayload();

            $this->assertTrue(is_array($payload), "getPayload() should return an array.");
        }

        #[Define(
            name: "Corvus Bridge — hasPredicates() Returns Bool",
            description: "Emitter::hasPredicates() returns a boolean (false for the stub when no predicates are set)."
        )]
        public function testCorvusBridgeHasPredicatesReturnsBool () : void {
            $emitter = Emitter::create();
            $result = $emitter->hasPredicates();

            $this->assertTrue(is_bool($result), "hasPredicates() should return a boolean.");
        }

        // ─── Cacher / CacheManager ────────────────────────────────────────────────

        #[Define(
            name: "Cacher Bridge — Extends BaseCacheManager",
            description: "BridgeCacheManager is a subclass of the base CacheManager to satisfy Liskov substitution."
        )]
        public function testCacherBridgeExtendsCacheManager () : void {
            if (!class_exists(\Wingman\Cacher\Cacher::class)) {
                $this->assertTrue(true, "Skipped: Wingman Cacher is not installed.");
                return;
            }

            $cacher = new \Wingman\Cacher\Cacher();
            $bridge = new BridgeCacheManager($cacher, 0);

            $this->assertTrue(
                $bridge instanceof BaseCacheManager,
                "BridgeCacheManager should extend the base CacheManager."
            );
        }

        #[Define(
            name: "Cacher Bridge — load() Returns Null When Cache Empty",
            description: "BridgeCacheManager::load() returns null when no locator.discovery.cache entry exists in Cacher."
        )]
        public function testCacherBridgeLoadReturnsNullWhenCacheEmpty () : void {
            if (!class_exists(\Wingman\Cacher\Cacher::class)) {
                $this->assertTrue(true, "Skipped: Wingman Cacher is not installed.");
                return;
            }

            $cacher = new \Wingman\Cacher\Cacher();
            $bridge = new BridgeCacheManager($cacher, 0);
            $bridge->clear();

            $result = $bridge->load();

            $this->assertTrue($result === null, "load() should return null when the Cacher entry has been cleared.");
        }

        #[Define(
            name: "Cacher Bridge — save() And load() Round-Trip",
            description: "After calling save(), load() restores the exact manifest and root data that was saved."
        )]
        public function testCacherBridgeSaveAndLoadRoundTrip () : void {
            if (!class_exists(\Wingman\Cacher\Cacher::class)) {
                $this->assertTrue(true, "Skipped: Wingman Cacher is not installed.");
                return;
            }

            $cacher = new \Wingman\Cacher\Cacher();
            $bridge = new BridgeCacheManager($cacher, 0);
            $bridge->clear();

            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "bridge_stub_test.manifest";
            file_put_contents($tempFile, json_encode(["namespace" => "RoundTripTest"]));

            $manifest = Manifest::from(["namespace" => "RoundTripTest"], $tempFile);
            $roots = [["path" => "/tmp/bridge_test", "profile" => ["depth" => 1, "exclude" => [], "onlyRoot" => false]]];

            $saved = $bridge->save([$manifest], $roots);
            $this->assertTrue($saved, "save() should return true.");

            $restored = $bridge->load();
            $this->assertTrue(is_array($restored), "load() should return an array after save().");
            $this->assertTrue(
                isset($restored["manifests"][0]["namespace"]) && $restored["manifests"][0]["namespace"] === "RoundTripTest",
                "Restored data should contain the saved manifest namespace."
            );

            $bridge->clear();
            @unlink($tempFile);
        }

        #[Define(
            name: "Cacher Bridge — clear() Returns True",
            description: "BridgeCacheManager::clear() returns true regardless of whether an entry exists."
        )]
        public function testCacherBridgeClearReturnsTrue () : void {
            if (!class_exists(\Wingman\Cacher\Cacher::class)) {
                $this->assertTrue(true, "Skipped: Wingman Cacher is not installed.");
                return;
            }

            $cacher = new \Wingman\Cacher\Cacher();
            $bridge = new BridgeCacheManager($cacher, 0);

            $result = $bridge->clear();

            $this->assertTrue($result === true, "clear() should return true.");
        }
    }
?>