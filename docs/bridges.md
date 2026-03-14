# Bridge Classes

Locator provides three optional bridge classes that integrate with other Wingman packages. Every bridge is designed so that the package functions identically whether or not the optional dependency is installed.

---

## How Bridges Work

Each bridge file begins with a `class_exists()` guard:

```php
if (class_exists(\Wingman\Package\RealClass::class)) {
    class_alias(\Wingman\Package\RealClass::class, __NAMESPACE__ . '\BridgeClass');
    return; // stop parsing this file
}

// The file continues to define a no-op stub class with the same name.
class BridgeClass { ... }
```

When the optional package **is** installed, `class_alias` is used — the bridge class becomes a transparent alias for the real one. When the package is **not** installed, the stub class is defined in its place, exposing the same public API but doing nothing.

This means callers always interact with the same fully-qualified class name (`Wingman\Locator\Bridge\<Package>\ClassName`) regardless of what is installed.

---

## Bridge: Stasis — `Bridge\Stasis\CacheManager`

**Optional dependency:** `wingman/stasis`

**File:** `src/Bridge/Stasis/CacheManager.php`

When Wingman Stasis is installed, the file-based `CacheManager` is replaced by this subclass, which delegates persistence to a `Wingman\Stasis\Cacher` instance.

### Key Differences from the File-Based `CacheManager`

| Aspect | File-based | Cacher-backed |
|---|---|---|
| Storage | PHP file on disk | Key-value store (local / Redis / Memcached) |
| Cache key | n/a (the file path is the key) | `locator.discovery.cache` |
| TTL enforcement | Checked at load time against a `timestamp` field | Delegated entirely to Cacher |
| Write safety | `LOCK_EX` flag on `file_put_contents()` | Managed by the adapter |
| Adapter | None | Any `Wingman\Stasis` adapter |

### Constant

```php
BridgeCacheManager::CACHE_KEY  // = "locator.discovery.cache"
```

### Using a Custom Adapter

```php
use Wingman\Stasis\Adapters\RedisAdapter;
use Wingman\Locator\Locator;

$locator = new Locator(
    ['locator.caching.ttl' => 3600],
    new RedisAdapter('127.0.0.1', 6379)
);
```

The adapter is passed to the `Cacher` instance that is created internally by `Locator::createCacheManager()`.

---

## Bridge: Cortex — `Bridge\Cortex\Environment`

**Optional dependency:** `Wingman/Cortex`

**File:** `src/Bridge/Cortex/Environment.php`

Enables `Environment`-based configuration injection. When Cortex **is** installed, this class is an exact alias for `Wingman\Cortex\Facades\Environment` and exposes the full Cortex environment API. When Cortex is **absent**, a no-op stub is used.

### Stub API

| Method | Returns | Behaviour |
|---|---|---|
| `Environment::from(?string $name)` | `static` | Returns a new inert instance |
| `getEnvironment()` | `null` | Always `null` in the stub |
| `populate(Locator $locator)` | `static` | No-op; returns `$this` |
| `restore(Locator $locator, string $name)` | `static` | No-op; returns `$this` |
| `snapshot(Locator $locator, string $name)` | `static` | No-op; returns `$this` |

### Usage

```php
use Wingman\Locator\Bridge\Cortex\Environment;
use Wingman\Locator\Locator;

// Works whether or not Cortex is installed.
$env = Environment::from('production');
$locator = new Locator($env);
```

> `Locator::__construct()` accepts both the bridge class and the original `Wingman\Cortex\Facades\Environment` class since the bridge is an alias when Cortex is present.

---

## Bridge: Corvus — `Bridge\Corvus\Emitter`

**Optional dependency:** `Wingman/Corvus`

**File:** `src/Bridge/Corvus/Emitter.php`

Enables signal emission at key points in the Locator lifecycle (manifest discovered, namespace registered, cache hit, cache miss, etc.). When Corvus **is** installed, this class is an exact alias for `Wingman\Corvus\Emitter`. When Corvus is **absent**, every method is a fluent no-op.

### Stub API

| Method | Returns | Behaviour |
|---|---|---|
| `Emitter::create()` | `static` | Returns an inert emitter |
| `Emitter::for(object ...$targets)` | `static` | Returns an inert emitter |
| `emit(string\|array ...$patterns)` | `static` | No-op; returns `$this` |
| `with(mixed ...$data)` | `static` | No-op; returns `$this` |
| `withOnly(mixed ...$data)` | `static` | No-op; returns `$this` |
| `if(callable ...$predicates)` | `static` | No-op; returns `$this` |
| `ifAll(callable ...$predicates)` | `static` | No-op; returns `$this` |
| `ifAny(callable ...$predicates)` | `static` | No-op; returns `$this` |
| `useBus(string $bus)` | `static` | No-op; returns `$this` |
| `getPayload()` | `array` | Returns `[]` |
| `hasPredicates()` | `bool` | Returns `false` |

### Signals

The following signals are emitted by the Locator singleton when Corvus is installed. Each signal name can be used with `Bus::on()` or any Corvus listener.

| Signal | Fired When | Payload Keys |
|---|---|---|
| `locator.manifest.processed` | A manifest is registered into the namespace manager and manifest repository | `namespace` (string), `sourcePath` (string) |
| `locator.cache.hit` | Discovery state is restored from the cache instead of scanning the filesystem | `root` (string) |
| `locator.cache.miss` | The default root is scanned but no valid cache entry exists | `root` (string) |
| `locator.discovery.completed` | A fresh filesystem scan finishes and all discovered manifests are processed | `root` (string), `count` (int) |
| `locator.path.resolved` | A path expression is resolved through the pipeline (in-memory cache misses only) | `expression` (string), `resolved` (string) |

**Example listener:**

```php
use Wingman\Corvus\Listener;

// Listen every time a manifest is processed.
Listener::create()
    ->do(function (array $payload) {
        $data = $payload[0];
        echo "Registered namespace: " . $data["namespace"];
    })
    ->when("locator.manifest.processed");

// Listen every time a fresh discovery scan completes.
Listener::create()
    ->do(function (array $payload) {
        $data = $payload[0];
        echo $data["count"] . " manifest(s) found under " . $data["root"];
    })
    ->when("locator.discovery.completed");

// Listen only for the first cache hit, then stop.
Listener::create()
    ->do(function (array $payload) {
        $data = $payload[0];
        echo "Warm boot from cache, root: " . $data["root"];
    })
    ->once("locator.cache.hit");
```

---

## Argus Bridge — `Bridge\Argus\Traits\Asserter`

**Optional dependency:** `Wingman/Argus`

The Argus bridge provides a trait that extends the assertion vocabulary available inside Argus test classes with Locator-specific checks. Use it alongside the default `CanAssert` trait that Argus provides.

### Usage

```php
use Wingman\Locator\Bridge\Argus\Traits\Asserter as LocatorAsserter;

class MyTest extends Test {
    use LocatorAsserter;

    // ...
}
```

### Available Assertions

| Method | Description |
|---|---|
| `assertPathResolvesTo(string $expr, string $expected)` | Expression must resolve to `$expected` via `getPathFor()` |
| `assertPathNotResolvesTo(string $expr, string $expected)` | Expression must not resolve to `$expected` |
| `assertPathExists(string $expr)` | Resolved path must exist on the filesystem (`getPathTo()`) |
| `assertPathNotExists(string $expr)` | Resolved path must not exist |
| `assertPathIsFile(string $expr)` | Resolved path must be an existing file |
| `assertPathNotIsFile(string $expr)` | Resolved path must not be a file |
| `assertPathIsDirectory(string $expr)` | Resolved path must be an existing directory |
| `assertPathNotIsDirectory(string $expr)` | Resolved path must not be a directory |
| `assertNamespaceResolvable(string $ns)` | Namespace must resolve to a root directory |
| `assertNamespaceNotResolvable(string $ns)` | Namespace must not resolve |
| `assertManifestLoaded(string $ns)` | At least one manifest must be registered for the namespace |
| `assertManifestNotLoaded(string $ns)` | No manifests must be registered for the namespace |

All methods accept an optional trailing `string $message` parameter for custom failure messages.

All assertions call the abstract `recordAssertion(bool $status, mixed $expected, mixed $actual, string $message)` method that the consuming Argus test class must implement.

---

## Console Bridge — `Bridge\Console\Commands\*`

**Optional dependency:** `Wingman/Console`

The console bridge provides ten CLI commands for inspecting and managing the Locator. Commands are auto-discovered by the Wingman Console `Registry` at runtime via `#[Command]` attribute reflection — no JSON configuration file is required.

The bridge has no effect on path resolution itself and is harmlessly absent when Wingman Console is not installed.

See [console.md](console.md) for full command documentation.
