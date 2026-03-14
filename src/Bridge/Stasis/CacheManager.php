<?php
    /**
     * Project Name:    Wingman — Locator — Stasis Bridge
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 20 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Bridge.Stasis namespace.
    namespace Wingman\Locator\Bridge\Stasis;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the class is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\CacheManager', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Stasis\Adapters\LocalAdapter;
    use Wingman\Stasis\Cacher;
    use Wingman\Locator\CacheManager as BaseCacheManager;

    /**
     * A CacheManager implementation that delegates persistence to a Stasis Cacher instance.
     *
     * This class is not intended to be used directly. When Wingman Stasis is installed, `Locator`
     * automatically instantiates this bridge in place of the default file-based `CacheManager`,
     * providing proper key-value storage with sharding and pluggable adapter support.
     *
     * The only reason to construct this manually is to supply a pre-configured `Cacher` instance
     * with a custom adapter (e.g. Redis, Memcached) instead of the default local filesystem adapter:
     *
     * ```php
     * $cacher = (new Cacher())->setAdapter(new RedisAdapter(...));
     * $locator = new Locator();
     * $locator->discoverManifests(); // will auto-use Cacher internally
     * // For a custom adapter, subclass Locator and override createCacheManager().
     * ```
     *
     * @package Wingman\Locator\Bridge\Stasis
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CacheManager extends BaseCacheManager {
        /**
         * The cache key used to store the discovery payload.
         * @var string
         */
        public const string CACHE_KEY = "locator.discovery.cache";

        /**
         * The underlying Cacher instance.
         * @var Cacher
         */
        private Cacher $cacher;

        /**
         * Creates a new Cacher-backed cache manager.
         * @param Cacher $cacher The Cacher instance to delegate persistence to.
         * @param int $maxAge The maximum age in seconds before a cache entry is considered stale; passed to Cacher as TTL on every write. Use `0` to let Cacher decide [default: `0`].
         */
        public function __construct (Cacher $cacher, int $maxAge = 0) {
            parent::__construct(["locator.caching.ttl" => $maxAge]);
            $this->cacher = $cacher;
        }

        /**
         * Clears the discovery cache by deleting the entry from the Cacher backend.
         * @return bool Whether the cache was successfully cleared.
         */
        public function clear () : bool {
            return $this->cacher->delete(static::CACHE_KEY);
        }

        /**
         * Loads the cached discovery state from the Cacher backend.
         * Returns `null` if no entry exists or the value is not a valid array.
         * TTL expiry is managed entirely by Cacher; no timestamp check is performed here.
         * @return array|null The cached data, or `null` if unavailable.
         */
        public function load () : ?array {
            $data = $this->cacher->get(static::CACHE_KEY, null);
            return is_array($data) ? $data : null;
        }

        /**
         * Persists the discovery state to the Cacher backend.
         * The TTL passed to Cacher is the `$maxAge` provided at construction time.
         * @param array $manifestData The manifest data to save.
         * @param array $scannedRoots The scanned roots to save.
         * @return bool Whether the data was successfully saved.
         */
        public function save (array $manifestData, array $scannedRoots) : bool {
            $manifests = [];

            foreach ($manifestData as $manifest) {
                $manifests[] = $manifest->dehydrate();
            }

            return $this->cacher->set(
                static::CACHE_KEY,
                ["manifests" => $manifests, "roots" => $scannedRoots],
                $this->maxAge > 0 ? $this->maxAge : null,
            );
        }

        /**
         * Sets the adapter of a cache manager.
         * @param object|null $adapter The adapter to set on the underlying Cacher instance.
         * @return static The cache manager instance for chaining.
         */
        public function setAdapter (?object $adapter) : static {
            $this->cacher->setAdapter($adapter ? $adapter : new LocalAdapter());
            return $this;
        }
    }
?>