# Configuration

Locator is configured at construction time via a flat array of dot-notation keys or, when Wingman Cortex is installed, via an `Environment` instance.

---

## Constructor Signature

```php
new Locator(array|Configuration $config = [], ?object $cachingAdapter = null)
```

| Parameter | Type | Description |
|---|---|---|
| `$config` | `array` or `Configuration` | Configuration map or Cortex Configuration instance |
| `$cachingAdapter` | `object\|null` | Optional caching adapter for Wingman Stasis |

---

## Configuration Keys

| Key | Type | Default | Description |
|---|---|---|---|
| `locator.caching.enabled` | `bool` | `true` | Whether to enable discovery caching |
| `locator.caching.file` | `string` | `temp/cache.php` | Path to the file-based cache file |
| `locator.caching.ttl` | `int` | `0` | Cache TTL in seconds; `0` means no expiry |
| `locator.caching.adapter` | `object\|null` | `null` | Custom adapter for Wingman Stasis |
| `locator.namespace.dynamic` | `bool` | `false` | Infer the implicit namespace from the call stack |
| `locator.discovery.root` | `string` | `""` | Root directory for automatic manifest discovery on construction; empty defers to the document root |
| `locator.server.root` | `string` | `""` | Web server document root for `${server}` path variable resolution; empty defers to `$_SERVER["DOCUMENT_ROOT"]` |

### `locator.caching.enabled`

Disabling caching forces a full filesystem scan on every request. Useful during development or when profiling discovery performance. In production this should always be `true`.

```php
$locator = new Locator(['locator.caching.enabled' => false]);
```

### `locator.caching.file`

Overrides the path of the PHP file used to persist the discovery cache when the file-based backend is active (i.e., Wingman Stasis is not installed).

```php
$locator = new Locator(['locator.caching.file' => '/var/cache/locator.php']);
```

### `locator.caching.ttl`

When set to a positive integer, cached discovery results older than this many seconds are treated as stale and a new discovery scan is performed.

```php
$locator = new Locator(['locator.caching.ttl' => 3600]); // 1-hour cache
```

### `locator.caching.adapter`

Provides a custom adapter to the Wingman Stasis backend. This option is only meaningful when Wingman Stasis is installed; it is silently ignored otherwise.

```php
use Wingman\Stasis\Adapters\RedisAdapter;

$locator = new Locator(
    ['locator.caching.ttl' => 3600],
    new RedisAdapter('127.0.0.1', 6379)
);
```

### `locator.namespace.dynamic`

When `true`, the Locator inspects `debug_backtrace()` on every cache-miss resolution to infer which registered namespace the calling file belongs to and uses that as the implicit namespace. This enables transparent namespace-scoped resolution at the cost of a backtrace call per cache miss.

```php
$locator = new Locator(['locator.namespace.dynamic' => true]);
```

### `locator.discovery.root`

Overrides the root directory used when the Locator automatically performs manifest discovery inside its constructor. When this key is absent or set to an empty string, the Locator falls back to the server document root (`$_SERVER["DOCUMENT_ROOT"]`) or the current working directory when running in CLI mode.

This is useful when the Locator is instantiated from a location that is not the project root, or when you want to constrain automatic discovery to a sub-tree.

```php
$locator = new Locator(['locator.discovery.root' => '/srv/app']);
```

### `locator.server.root`

Sets the web server document root that the `${server}` path variable resolves to. In HTTP contexts this is populated automatically from `$_SERVER["DOCUMENT_ROOT"]`, so this key is primarily useful in CLI scripts or test harnesses where the super-global is not available.

```php
$locator = new Locator(['locator.server.root' => '/var/www/html']);
```

---

## Using a Flat Array

```php
use Wingman\Locator\Locator;

$locator = new Locator([
    'locator.caching.enabled' => true,
    'locator.caching.ttl'     => 7200,
    'locator.namespace.dynamic' => false,
]);
```

---

## Using a Cortex Environment

When [Wingman Cortex](../src/Bridge/Cortex/Environment.php) is installed, you can pass an `Environment` directly. The environment's `populate()` method is called, which copies matching configuration keys into the Locator's properties.

```php
use Wingman\Cortex\Facades\Environment;
use Wingman\Locator\Locator;

$env = Environment::from('production');
$locator = new Locator($env);
```

When Cortex is **not** installed, the `Configuration` bridge provides a no-op stub so the same constructor call works regardless of whether Cortex is present. See [bridges.md](bridges.md) for details.

---

## Applying Caching Configuration to the Singleton

The global singleton is created on first access via `Locator::get()`. To control its configuration, construct it explicitly and inject it before the first call to `get()`:

```php
use Wingman\Locator\Locator;

Locator::setGlobal(new Locator([
    'locator.caching.ttl' => 3600,
]));

// All subsequent calls to Locator::get() return your configured instance.
$locator = Locator::get();
```

---

## DiscoveryProfile Settings

A `DiscoveryProfile` governs the rules for a specific discovery scan. It is separate from the Locator configuration above.

Create a profile via the `DiscoveryProfile::from()` factory, which accepts a short-key array and applies sensible defaults:

| Key | Type | Default (via `from()`) | Description |
|---|---|---|---|
| `depth` | `int` | `-1` (unlimited) | Maximum directory recursion depth; `-1` for unlimited |
| `onlyRoot` | `bool` | `false` | Scan only the root directory without recursing |
| `include` | `string[]` | `[]` | Glob patterns — a path must match at least one to be included |
| `exclude` | `string[]` | `[]` | Glob patterns — matching paths are skipped |
| `omitHidden` | `bool` | `true` | Automatically skip hidden files and directories (those starting with `.`) |

**Glob pattern support:**

| Pattern | Matches |
|---|---|
| `vendor/*` | Any single-level path under `vendor/` |
| `**/.*` | Any path segment starting with `.` at any depth |
| `tests/**` | The `tests/` tree at any depth |

```php
use Wingman\Locator\Objects\DiscoveryProfile;

$profile = DiscoveryProfile::from([
    'depth'      => 3,
    'omitHidden' => true,
    'exclude'    => ['vendor/*', 'packages/*', 'tests/*', 'temp/*', '**/.*'],
]);

$locator->discoverManifests('/srv/app', $profile);
```

The default scan profile used by `Locator::__construct()` is:

```php
DiscoveryProfile::from([
    'depth'    => 5,
    'exclude'  => ['vendor/*', 'packages/*', 'tests/*', 'temp/*', 'cache/*', '**/.*'],
    'onlyRoot' => false,
])
```

---

## Resolver Settings

Resolvers that participate in the path resolution pipeline expose their own configurable properties. These are populated from the same `Configuration` (or flat array) passed to the `Locator` constructor, so no additional wiring is required.

### `locator.resolvers.symbol.implicitEnabled`

| Key | Type | Default | Description |
|---|---|---|---|
| `locator.resolvers.symbol.implicitEnabled` | `bool` | `false` | Enable implicit (segment-aware) symbol matching in `SymbolResolver` |

The `SymbolResolver` supports two explicit symbol syntaxes — bounded (`%{name}`) and unbounded (`%name`) — and one optional implicit mode.

When `implicitEnabled` is `false` (the default), only expressions that begin with `%` are treated as symbol tokens. Any path that does not start with `%` is left untouched by this resolver.

When set to `true`, the resolver additionally walks the path segment by segment, looking for the longest registered symbol that matches a prefix of the relative path. This allows symbols to be addressed without a `%` sigil, but it may cause unexpected matches if symbol names overlap with real directory names — use with care.

```php
$locator = new Locator(['locator.resolvers.symbol.implicitEnabled' => true]);
```
