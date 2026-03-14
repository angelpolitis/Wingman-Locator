# Wingman — Locator

A powerful and flexible path resolution system for PHP applications, enabling dynamic discovery and resolution of file paths based on namespaces, symbols, and virtual paths.

---

## Requirements

- PHP **8.0** or later
- **Wingman/Cortex** *(optional)* — enables environment-based configuration injection

---

## Installation

Copy or symlink the package into your project and include its autoloader:

```php
require_once '/path/to/locator/autoload.php';
```

The autoloader registers a PSR-style class map for the `Wingman\Locator` namespace.

---

## Quick Start

```php
use Wingman\Locator\Locator;
use Wingman\Locator\Facades\Path;

// Obtain the global singleton — manifests are discovered automatically on first call.
$locator = Locator::get();

// Resolve a path expression to an absolute path (no filesystem check).
$path = $locator->getPathFor('@App/src/Controllers/UserController.php');

// Or use the static facade for convenience.
$path = Path::for('@App/src/Controllers/UserController.php');

// Resolve only when the resource actually exists on disk.
$path = Path::to('@App/src/Controllers/UserController.php'); // null if missing
```

---

## Path Expression Syntax

Every path passed to `getPathFor()` (and related methods) is a *path expression*. The resolution pipeline evaluates these in order and the first resolver that produces a result wins.

### Namespace Paths

Map a logical namespace to its registered root directory and append a relative tail.

| Notation | Example |
| --- | --- |
| `@Namespace/relative` | `@App/src/Controllers` |
| `Namespace:relative` | `App:src/Controllers` |

The namespace is looked up in the registry. Its root directory is joined with the relative portion to produce the final path.

### Variable Paths

Substitute a named variable with a runtime-resolved root directory.

| Variable | Resolves to |
| --- | --- |
| `@{server}` | `$_SERVER["DOCUMENT_ROOT"]` (falls back to `cwd`) |
| `@{cwd}` | `getcwd()` |
| `@{os}` | Filesystem root (`/` on Unix, `C:\` on Windows) |
| `@{namespace}` | Root of the current implicit namespace |
| `@{manifest}` | Directory containing the active manifest file |
| `@{package}` | Root directory of the active package |

Examples:

```
@{server}/assets/logo.png
@{cwd}/config/app.json
@{manifest}/templates/header.html
```

### Symbol Paths

Symbols are named path aliases declared inside a `locator.manifest`. They let you create stable, refactor-proof references to key directories or files.

```
@App/%controllers/UserController.php
@App/%{controllers}/UserController.php   # brace form for disambiguation
```

The `%controllers` token is replaced with the path registered under the `controllers` symbol in the `App` namespace manifest.

### Virtual Paths

Virtual paths are logical file/directory names that exist only in a manifest's `virtuals` map and are redirected to real filesystem locations at resolution time. They let you expose a clean public API without exposing the underlying directory structure.

```
@App/api/users    # may map to src/Handlers/UserHandler.php
```

See the [manifest schema](docs/manifest-schema.md) for how virtuals are declared.

### Absolute Paths

Paths beginning with `/` (Unix) or a drive letter (`C:\`) are passed through unchanged after normalisation.

```
/var/www/html/index.php
C:\inetpub\wwwroot\index.php
```

### Explicit Relative Paths

Paths beginning with `./` or `../` are resolved relative to the implicit namespace root (or the server root when no namespace is active).

```
./config/database.php
../shared/helpers.php
```

### Implicit Relative Paths

Bare paths with no recognisable prefix are treated as implicitly relative and resolved against the current implicit namespace root.

```
config/database.php
```

---

## Configuration

The `Locator` constructor accepts either a flat array of options (using dot-notation keys) or a `Wingman\Cortex\Facades\Environment` instance.

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `locator.caching.enabled` | `bool` | `true` | Enable / disable discovery caching |
| `locator.caching.file` | `string` | `temp/cache.php` | Path to the cache file |
| `locator.caching.ttl` | `int` | `0` | Cache TTL in seconds; `0` disables expiry |
| `locator.namespace.dynamic` | `bool` | `false` | Infer the implicit namespace from the call stack |

```php
// Flat array:
$locator = new Locator([
    'locator.caching.enabled' => true,
    'locator.caching.ttl'     => 3600,
]);

// Cortex Environment:
$locator = new Locator($environment);
```

---

## Caching

On the first run, the locator performs a recursive filesystem scan to discover all `locator.manifest` files. The results are serialised to a PHP file (default: `temp/cache.php`) so subsequent requests can skip the scan entirely.

- Set `locator.caching.ttl` to a positive integer (seconds) to automatically expire the cache.
- Call `CacheManager::clear()` to invalidate the cache manually.
- The cache file is safe to commit-ignore (add `temp/` to `.gitignore`).

---

## `locator.manifest` Files

Place a `locator.manifest` JSON file in any directory you want Locator to recognise as a named namespace. The file is discovered automatically during the scan phase.

**One namespace per tree.** The first (topmost) manifest encountered for a given namespace name becomes the root of that namespace — its containing directory is registered as the namespace root path. Any deeper `locator.manifest` that declares the **same** namespace name extends the namespace: its symbols, virtuals, aliases, and settings are merged in. Declaring a *different* namespace name in a nested manifest registers a completely new, independent namespace; it does not create a child or sub-namespace of the parent.

```json
{
    "namespace": "App",
    "aliases": ["app", "application"],
    "namespaceAliases": ["LegacyApp"],
    "symbols": {
        "controllers": "src/Http/Controllers",
        "models":      "@{manifest}/src/Domain/Models"
    },
    "virtuals": {
        "api": {
            "type": "directory",
            "content": {
                "users": "src/Handlers/UserHandler.php"
            }
        },
        "note.txt": {
            "type": "file",
            "source": "/temp/note.txt"
        }
    },
    "settings": {}
}
```

See [docs/manifest-schema.md](docs/manifest-schema.md) for the full schema reference.

---

## The `Path` Facade

`Wingman\Locator\Facades\Path` provides a static API over the global `Locator` singleton.

| Method | Returns | Description |
| --- | --- | --- |
| `Path::for(string $expr)` | `string` | Resolve expression → absolute path (no existence check) |
| `Path::to(string $expr)` | `string\|null` | Resolve and verify the resource exists |
| `Path::toFile(string $expr)` | `string\|null` | Resolve and verify it is a file |
| `Path::toDirectory(string $expr)` | `string\|null` | Resolve and verify it is a directory |
| `Path::toNamespace(string $ns)` | `string\|null` | Resolve the root directory of a namespace |
| `Path::toRoot(PathRootVariable\|string $root)` | `string\|null` | Resolve a named root variable |

---

## Dependency Injection and Testing

`Locator` implements `Wingman\Locator\Interfaces\LocatorInterface`, making it straightforward to swap the implementation in tests or in a DI container.

The `Path` facade supports locator injection to avoid touching the filesystem in unit tests:

```php
use Wingman\Locator\Facades\Path;
use Wingman\Locator\Interfaces\LocatorInterface;

// Inject a mock locator for tests.
Path::setLocator($mockLocator);

// ... run assertions ...

// Restore the default singleton.
Path::setLocator(null);
```

---

## Documentation

| Document | Description |
| --- | --- |
| [Architecture Overview](docs/overview.md) | Package architecture, bootstrapping lifecycle, and directory structure |
| [Path Expression Syntax](docs/path-expressions.md) | Full reference for all resolver types and variable tokens |
| [Configuration](docs/configuration.md) | All configuration keys, `DiscoveryProfile` settings, and Cortex integration |
| [Caching](docs/caching.md) | File-based and Cacher backends, TTL, write safety, and clearing |
| [Discovery](docs/discovery.md) | Scan lifecycle, namespace registration rules, and `DiscoveryProfile` |
| [Manifest Schema](docs/manifest-schema.md) | `locator.manifest` file format and all supported fields |
| [API Reference](docs/api-reference.md) | Every public class, method, and enum with signatures |
| [Console Commands](docs/console.md) | All 10 CLI commands with arguments, options, and examples |
| [Bridge Classes](docs/bridges.md) | Optional Cacher, Cortex, and Corvus integrations |
| [Testing Guide](docs/testing.md) | Patterns for unit testing, mocking, and isolation |

---

## Licence

This project is licensed under the **Mozilla Public License 2.0 (MPL 2.0)**.

Wingman Locator is part of the **Wingman Framework**, Copyright (c) 2018–2026 Angel Politis.

For the full licence text, please see the [LICENSE](LICENSE) file.
