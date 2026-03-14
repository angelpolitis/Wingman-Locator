<?php
    /**
	 * Project Name:    Wingman — Locator — Locator
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 18 2025
	 * Last Modified:   Mar 20 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator namespace.
    namespace Wingman\Locator;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Locator\Bridge\Cortex\Configuration;
    use Wingman\Locator\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Locator\Bridge\Corvus\Emitter;
    use Wingman\Locator\Bridge\Stasis\CacheManager as BridgeCacheManager;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Enums\Signal;
    use Wingman\Locator\Interfaces\LocatorInterface;
    use Wingman\Locator\ManifestLoader;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\DiscoveryRepository;
    use Wingman\Locator\Objects\Manifest;
    use Wingman\Locator\Objects\ManifestRepository;
    use Wingman\Locator\Objects\NamespaceObject;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\PathResolutionPipeline;
    use Wingman\Locator\Resolvers\AbsoluteResolver;
    use Wingman\Locator\Resolvers\NamespaceResolver;
    use Wingman\Locator\Resolvers\RelativeSegmentResolver;
    use Wingman\Locator\Resolvers\RelativeResolver;
    use Wingman\Locator\Resolvers\SymbolResolver;
    use Wingman\Locator\Resolvers\VariableResolver;
    use Wingman\Locator\Resolvers\VirtualResolver;
    use Wingman\Stasis\Cacher;

    /**
     * A facade used to locate resources.
     * @package Wingman\Locator
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Locator implements LocatorInterface {
        /**
         * The maximum number of entries to keep in the in-memory resolution cache.
         * Once this limit is reached, the oldest half of the entries are evicted to prevent unbounded growth in long-running processes.
         * @var int
         */
        public const MAX_RESOLUTION_CACHE_SIZE = 2048;

        /**
         * The singleton instance of the locator.
         * @var LocatorInterface|null
         */
        protected static ?LocatorInterface $global = null;

        /**
         * The manifest loader.
         * @var ManifestLoader
         */
        protected ManifestLoader $loader;

        /**
         * The namespace manager.
         * @var NamespaceManager
         */
        protected NamespaceManager $namespaceManager;

        /**
         * The path resolution pipeline.
         * @var PathResolutionPipeline
         */
        protected PathResolutionPipeline $pipeline;

        /**
         * The discovery repository.
         * @var DiscoveryRepository
         */
        protected DiscoveryRepository $discoveries;

        /**
         * The manifest repository of a locator.
         * @var ManifestRepository
         */
        protected ManifestRepository $manifestRepository;

        /**
         * A cache for resolved paths to improve performance by avoiding redundant resolution of the same path expressions.
         * The cache maps path expressions to their resolved absolute paths.
         * @var array<string, string>
         */
        protected array $resolutionCache = [];

        /**
         * Whether to enable caching of discovered manifests and scanned roots; defaults to `true` for improved performance on subsequent runs.
         * 
         * When enabled, the locator will save the results of manifest discovery and scanned roots to a cache file after the first discovery process.
         * On subsequent runs, if the cache file exists and is valid, the locator will load the cached data instead of performing a new discovery, significantly reducing startup time.
         * 
         * **Note**: Caching is only applied when discovering manifests in the default root directory (i.e., `$_SERVER["DOCUMENT_ROOT"]`). If you frequently scan custom roots, consider implementing per-root caching for optimal performance.
         * @var bool
         */
        #[Configurable("locator.caching.enabled", "Whether to enable caching of discovered manifests and scanned roots.")]
        protected bool $cachingEnabled = true;

        /**
         * The file path to store the locator cache; defaults to `temp/cache.php` in the package root.
         * @var string
         */
        #[Configurable(CacheManager::FILE_KEY, "The file path to store the locator cache.")]
        protected string $cacheFile = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . "cache.php";

        /**
         * The cache manager instance responsible for loading and saving the discovery cache. This is initialised lazily when caching is first used.
         * @var CacheManager|null
         */
        protected ?CacheManager $cacheManager = null;

        /**
         * The maximum age of the discovery cache in seconds. A value of `0` disables TTL and keeps the cache until it is explicitly cleared.
         * @var int
         */
        #[Configurable(CacheManager::TTL_KEY, "The maximum age of the discovery cache in seconds. Set to 0 to disable TTL.")]
        protected int $cacheTtl = 0;

        /**
         * An optional caching adapter to use when Wingman Stasis is installed. This allows you to specify a custom adapter (e.g., Redis, Memcached) for caching discovered manifests and scanned roots.
         * If Stasis is not installed, this property is ignored.
         * @var object|null
         */
        #[Configurable("locator.caching.adapter", "An optional caching adapter to use when Wingman Stasis is installed. This allows you to specify a custom adapter (e.g., Redis, Memcached) for caching discovered manifests and scanned roots.")]
        protected ?object $cachingAdapter = null;

        /**
         * Whether to infer the implicit namespace dynamically from the call stack rather than using the statically assigned one.
         * When enabled, the locator inspects the call stack on every cache-miss resolution to determine which registered namespace the
         * caller's file belongs to. This provides transparent namespace-scoped resolution but incurs a `debug_backtrace()` call.
         * @var bool
         */
        #[Configurable("locator.namespace.dynamic", "Whether to infer the implicit namespace dynamically from the caller's call stack.")]
        protected bool $dynamicNamespace = false;

        /**
         * An optional root directory used when performing automatic manifest discovery in the constructor.
         * When empty, the default root (document root or working directory) is used.
         * @var string
         */
        #[Configurable("locator.discovery.root", "Root directory used for the automatic manifest discovery on construction.")]
        protected string $discoveryRoot = "";

        /**
         * The name of the manifest file to look for in the filesystem.
         * @var string
         */
        #[Configurable("locator.manifest.filename", "The name of the manifest file to look for in the filesystem.")]
        protected string $manifestFilename = "locator.manifest";

        /**
         * The web server document root used to resolve the `\${server}` path variable in path expressions.
         * In HTTP contexts this is populated automatically from `\$_SERVER["DOCUMENT_ROOT"]`; set this explicitly
         * when running in CLI mode so that expressions such as `@{server}/modules/Console` resolve correctly.
         * Leaving this empty defers to `\$_SERVER["DOCUMENT_ROOT"]` at resolution time.
         * @var string
         */
        #[Configurable("locator.server.root", "The web server document root used to resolve the \${server} path variable.")]
        protected string $serverRoot = "";

        /**
         * Creates a new locator.
         * @param array|Configuration $config An optional flat configuration map using dot-notation keys, or a Cortex `Configuration` instance.
         * Supported keys: `locator.caching.enabled`, `locator.caching.file`, `locator.caching.ttl`, `locator.namespace.dynamic`, `locator.server.root`, `locator.manifest.filename`.
         * @param object|null $cachingAdapter An optional caching adapter to use when Wingman Stasis is installed. This allows you to specify a custom adapter (e.g., Redis, Memcached) for caching discovered manifests and scanned roots.
         */
        public function __construct (array|Configuration $config = [], ?object $cachingAdapter = null) {
            $config = Configuration::hydrate($this, $config);

            $manager = $this->namespaceManager = new NamespaceManager();
            $this->discoveries = new DiscoveryRepository();
            $this->manifestRepository = new ManifestRepository();

            $this->pipeline = new PathResolutionPipeline(
                $manager,
                new VariableResolver($manager),
                new NamespaceResolver($manager),
                new RelativeSegmentResolver(),
                new SymbolResolver($manager, $config),
                new RelativeResolver(),
                new AbsoluteResolver(),
                new VirtualResolver($manager)
            );

            $this->cachingAdapter = $cachingAdapter;

            $this->discoverManifests(!empty($this->discoveryRoot) ? $this->discoveryRoot : null, DiscoveryProfile::from([
                "depth" => 5,
                "exclude" => ["vendor/*", "packages/*", "tests/*", "temp/*", "cache/*", "**/.*"],
                "onlyRoot" => false
            ]));
        }

        /**
         * Resolves the appropriate cache manager for the current environment.
         * When Wingman Stasis is installed, a Stasis-backed manager is returned automatically, providing proper
         * key-value storage with sharding and adapter support. Otherwise, the default file-based manager is used.
         * @return CacheManager The cache manager to use for this discovery run.
         */
        private function createCacheManager () : CacheManager {
            if (class_exists(Cacher::class)) {
                $cacher = new Cacher();
                if ($this->cachingAdapter) $cacher->setAdapter($this->cachingAdapter);
                return new BridgeCacheManager($cacher, $this->cacheTtl);
            }

            return new CacheManager([
                CacheManager::FILE_KEY => $this->cacheFile,
                CacheManager::TTL_KEY  => $this->cacheTtl,
            ]);
        }

        /**
         * Resolves the default root directory for manifest discovery.
         * In web-server contexts, returns the configured document root. In CLI contexts where no document root is available,
         * falls back to the current working directory to prevent scanning from the filesystem root or failing silently.
         * @return string The resolved default root directory.
         */
        private function resolveDefaultRoot () : string {
            if (!empty($this->serverRoot)) return $this->serverRoot;
            $documentRoot = $_SERVER["DOCUMENT_ROOT"] ?? "";
            return (!empty($documentRoot) && is_dir($documentRoot)) ? $documentRoot : (getcwd() ?: ".");
        }

        /**
         * Processes a manifest by importing its configurations into the namespace manager and adding it to the manifest repository.
         * When the manifest's source path is already registered (e.g. because a previous discovery sweep from a different root already
         * loaded the same file), the call is silently ignored to prevent `ManifestOverwriteException` on overlapping scans.
         * @param Manifest $manifest The manifest to process.
         */
        protected function processManifest (Manifest $manifest) : void {
            $name = $manifest->getNamespace();
            $sourcePath = $manifest->getSourcePath();

            if ($this->manifestRepository->get($sourcePath) !== null) return;

            if (!$this->namespaceManager->hasNamespace($name)) {
                $nsObj = new NamespaceObject($name, dirname($sourcePath));
                $this->namespaceManager->registerNamespace($nsObj);
            }
            else {
                $nsObj = $this->namespaceManager->getNamespace($name);
            }

            $nsObj->import([
                "aliases"  => $manifest->getAliases(),
                "symbols"  => $manifest->getSymbols(),
                "virtuals" => $manifest->getVirtuals(),
                "settings" => $manifest->getSettings()
            ], $sourcePath);

            $this->namespaceManager->refreshRegistry();
            $this->manifestRepository->add($manifest);

            Emitter::create()
                ->with(["namespace" => $name, "sourcePath" => $sourcePath])
                ->emit(Signal::MANIFEST_PROCESSED);
        }

        /**
         * Discovers manifests in the specified root directory and applies their configurations to the namespace manager.
         * @param string|null $rootDirectory The root directory to search for manifests. Defaults to the document root in web contexts or the current working directory in CLI contexts.
         * @param DiscoveryProfile|null $profile The discovery profile to use. Defaults to a new profile with default settings.
         */
        public function discoverManifests (?string $rootDirectory = null, ?DiscoveryProfile $profile = null) : void {
            $defaultRoot = $this->resolveDefaultRoot();
            $rootDirectory ??= $defaultRoot;
            $profile ??= new DiscoveryProfile();

            if ($this->discoveries->has($rootDirectory, $profile)) {
                return;
            }

            $this->loader ??= new ManifestLoader($this->manifestFilename);

            if ($this->cachingEnabled) {
                $this->cacheManager = $this->createCacheManager();
                $cached = $this->cacheManager->load();

                if ($cached && $rootDirectory === $defaultRoot) {
                    $this->applyCachedState($cached);

                    Emitter::create()
                        ->with(["root" => $rootDirectory])
                        ->emit(Signal::CACHE_HIT);

                    return;
                }

                if ($rootDirectory === $defaultRoot) {
                    Emitter::create()
                        ->with(["root" => $rootDirectory])
                        ->emit(Signal::CACHE_MISS);
                }

                $this->discoveries->add($rootDirectory, $profile);
                $manifests = $this->loader->discover($rootDirectory, $profile);

                $manifestDataForCache = [];
                foreach ($manifests as $manifest) {
                    $this->processManifest($manifest);
                    $manifestDataForCache[] = $manifest;
                }

                $this->cacheManager->save($manifestDataForCache, $this->discoveries->exportContent());

                Emitter::create()
                    ->with(["root" => $rootDirectory, "count" => count($manifests)])
                    ->emit(Signal::DISCOVERY_COMPLETED);

                return;
            }

            $this->discoveries->add($rootDirectory, $profile);
            $manifests = $this->loader->discover($rootDirectory, $profile);

            foreach ($manifests as $manifest) {
                $this->processManifest($manifest);
            }

            Emitter::create()
                ->with(["root" => $rootDirectory, "count" => count($manifests)])
                ->emit(Signal::DISCOVERY_COMPLETED);
        }

        /**
         * Applies the cached state to a locator, restoring the discovery repository and manifest repository from the cache.
         * @param array $cached The cached state containing manifests and scanned roots.
         */
        protected function applyCachedState (array $cached) : void {
            foreach ($cached["manifests"] as $manifest) {
                $this->processManifest(Manifest::hydrate($manifest));
            }
            foreach ($cached["roots"] as ["path" => $path, "profile" => $profile]) {
                $this->discoveries->add($path, DiscoveryProfile::__set_state($profile));
            }
        }

        /**
         * Gets the singleton instance of the locator. This ensures that there is a single, globally accessible instance of the locator throughout the application,
         * allowing for consistent path resolution and resource management.
         * @return LocatorInterface The singleton instance of the locator.
         */
        public static function get () : LocatorInterface {
            if (static::$global === null) {
                static::$global = new Locator();
            }
            return static::$global;
        }
        
        /**
         * Gets the manifests of a locator.
         * @return ManifestRepository The manifest repository.
         */
        public function getManifestRepository () : ManifestRepository {
            return $this->manifestRepository;
        }

        /**
         * Gets the absolute path to a path expression.
         * Does not check if the path exists. Use `pathTo()` for that.
         * @param string $pathExpression The path expression.
         * @return string The absolute path.
         */
        public function getPathFor (string $pathExpression) : string {
            $implicitNamespace = $this->namespaceManager->getImplicitNamespace(!$this->dynamicNamespace);
            $cacheKey = $implicitNamespace . ':' . $this->serverRoot . ':' . $pathExpression;
            if (isset($this->resolutionCache[$cacheKey])) {
                return $this->resolutionCache[$cacheKey];
            }

            $roots = !empty($this->serverRoot) ? ["server" => $this->serverRoot] : [];
            $context = ResolutionContext::create($implicitNamespace)->setRoots($roots);
            $resolved = $this->pipeline->resolve($pathExpression, $context)->getPath();

            if (count($this->resolutionCache) >= static::MAX_RESOLUTION_CACHE_SIZE) {
                $this->resolutionCache = array_slice($this->resolutionCache, (int) (static::MAX_RESOLUTION_CACHE_SIZE / 2), null, true);
            }

            $this->resolutionCache[$cacheKey] = $resolved;

            Emitter::create()
                ->with(["expression" => $pathExpression, "resolved" => $resolved])
                ->emit(Signal::PATH_RESOLVED);

            return $resolved;
        }

        /**
         * Gets the absolute path to a path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the resource, or `null` if it doesn't exist.
         */
        public function getPathTo (string $pathExpression) : ?string {
            $path = $this->getPathFor($pathExpression);
            return file_exists($path) ? $path : null;
        }

        /**
         * Gets the absolute path to a directory path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the directory, or `null` if it doesn't exist.
         */
        public function getPathToDirectory (string $pathExpression) : ?string {
            $path = $this->getPathFor($pathExpression);
            return is_dir($path) ? $path : null;
        }

        /**
         * Gets the absolute path to a file path expression.
         * @param string $pathExpression The path expression.
         * @return string|null The absolute path to the file, or `null` if it doesn't exist.
         */
        public function getPathToFile (string $pathExpression) : ?string {
            $path = $this->getPathFor($pathExpression);
            return is_file($path) ? $path : null;
        }

        /**
         * Gets the absolute path to a namespace.
         * @param string $namespace The namespace.
         * @return string|null The absolute path to the namespace, or `null` if it doesn't exist.
         */
        public function getPathToNamespace (string $namespace) : ?string {
            $namespace = trim($namespace, "@");
            return $this->getPathToDirectory("@$namespace");
        }

        /**
         * Gets the absolute path to a root variable.
         * @param PathRootVariable|string $root The root variable.
         * @return string|null The absolute path to the root variable, or `null` if it doesn't exist.
         */
        public function getPathToRoot (PathRootVariable|string $root) : ?string {
            if ($root instanceof PathRootVariable) {
                $root = $root->value;
            }
            return $this->getPathTo(sprintf("@{%s}", $root));
        }

        /**
         * Sets the caching adapter for a locator's cache manager. If Wingman Stasis is installed, this method allows you to set a custom adapter (e.g., Redis, Memcached) for caching discovered manifests and scanned roots.
         * If Stasis is not installed, this method throws an exception.
         * @param object|null $adapter The caching adapter to use (must be compatible with Wingman Stasis); `null` to reset to the default adapter.
         * @return static The locator.
         * @throws RuntimeException If Wingman Stasis is not installed.
         */
        public function setCachingAdapter (?object $adapter = null) : static {
            if (!$this->cachingEnabled) return $this;
            if (!class_exists(\Wingman\Stasis\Cacher::class)) {
                throw new RuntimeException("Wingman Stasis must be installed to specify a caching adapter.");
            }
            $this->cachingAdapter = $adapter;
            if ($this->cacheManager instanceof BridgeCacheManager) {
                $this->cacheManager->setAdapter($adapter);
            }
            return $this;
        }
        
        /**
         * Returns the currently registered global locator instance without auto-creating one.
         * Returns `null` when no explicit instance has been registered via `setGlobal()`.
         * Prefer this over `get()` when the caller must distinguish between "explicitly set" and "not yet initialised".
         * @return LocatorInterface|null The registered global locator, or `null` if none has been set.
         */
        public static function getGlobal () : ?LocatorInterface {
            return static::$global;
        }

        /**
         * Overrides the singleton instance of the locator. Intended for use in integration tests and
         * bootstrapping scenarios where a custom or mock locator must be injected globally.
         * Pass `null` to reset the singleton so the next call to `get()` creates a fresh instance.
         * @param LocatorInterface|null $locator The locator instance to set, or `null` to reset.
         */
        public static function setGlobal (?LocatorInterface $locator) : void {
            static::$global = $locator;
        }
    }
?>