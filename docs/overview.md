# Wingman Locator — Architecture Overview

## What It Does

Wingman Locator is a **path-resolution engine** for PHP applications. Instead of scattering hard-coded `__DIR__ . '/src/...'` strings throughout your code, you define namespaces, symbols, and virtual paths in `locator.manifest` JSON files and then resolve everything through a single, testable API:

```php
$path = Locator::get()->getPathFor('@App/src/Controllers/UserController.php');
// or, using the static facade:
$path = Path::for('@App/src/Controllers/UserController.php');
```

---

## Core Concepts

### Namespace

A **namespace** is a named directory root. Every `locator.manifest` file declares a namespace name; the directory containing that file becomes the root of that namespace.

```
/srv/www/                       ← $_SERVER["DOCUMENT_ROOT"]
  modules/
    App/
      locator.manifest          ← declares namespace "App", root = /srv/www/modules/App
      src/
        Controllers/
```

Once discovered, `@App/src/Controllers` resolves to `/srv/www/modules/App/src/Controllers`.

### Symbol

A **symbol** is a named path alias declared inside a manifest. It lets you create stable, refactor-proof references to key directories or files without coupling path expressions to relative offsets.

```json
"symbols": { "controllers": "src/Http/Controllers" }
```

`@App/%controllers/UserController.php` → `/srv/www/modules/App/src/Http/Controllers/UserController.php`

### Virtual Path

A **virtual path** is a logical name that exists only in the manifest and is redirected to a real filesystem location at resolution time. They let you expose a clean public surface without revealing the underlying directory structure.

### Resolution Pipeline

Every path expression passes through an ordered **resolver pipeline**. The first resolver that produces a valid result wins:

| Priority | Resolver | Handles |
|---|---|---|
| 1 | `VariableResolver` | `@{server}`, `@{cwd}`, `@{manifest}`, … |
| 2 | `NamespaceResolver` | `@Namespace/path`, `Namespace:path` |
| 3 | `RelativeSegmentResolver` | `./path`, `../path` |
| 4 | `SymbolResolver` | `%symbol`, `%{symbol}` |
| 5 | `RelativeResolver` | Bare relative paths |
| 6 | `AbsoluteResolver` | `/abs/path`, `C:\...` |
| 7 | `VirtualResolver` | Virtual entries declared in manifests |

An in-memory LRU cache (capped at `Locator::MAX_RESOLUTION_CACHE_SIZE = 2048` entries) ensures repeated resolutions of the same expression don't re-traverse the pipeline.

---

## Bootstrapping Lifecycle

```
Application starts
       │
       ▼
 Locator::get()       ← creates a singleton Locator with default config
       │
       ▼
 __construct()        ← applies config, wires resolvers
       │
       ▼
 discoverManifests()  ← scans DOCUMENT_ROOT (or cwd in CLI) for locator.manifest files
       │
       ├──► Cache hit? ──► applyCachedState()  ← warm boot (fast, no filesystem scan)
       │
       └──► Cache miss ──► ManifestLoader::discover()  ← cold boot (filesystem scan)
                                │
                                ▼
                          processManifest()   ← registers namespace, symbols, virtuals
                                │
                                ▼
                          CacheManager::save()  ← persists results for next run
```

Subsequent calls to `Locator::get()` return the same singleton. Subsequent calls to `discoverManifests()` for the same root + profile combination are idempotent — the discovery repository prevents redundant scans.

---

## Directory Structure

```
src/
  Locator.php                    ← singleton facade, entry point
  NamespaceManager.php           ← namespace registry, alias lookup
  ManifestLoader.php             ← filesystem scanner + manifest parser
  PathResolutionPipeline.php     ← wires the resolver chain, LRU cache
  CacheManager.php               ← file-based persistence with LOCK_EX
  PathUtils.php                  ← pure static path utilities
  Asserter.php                   ← filesystem assertion helpers

  Objects/
    Manifest.php                 ← parsed manifest value object
    ManifestRepository.php       ← typed collection of Manifest objects
    DiscoveryProfile.php         ← scan rules (depth, excludes, …)
    DiscoveryRepository.php      ← tracks already-scanned root+profile pairs
    NamespaceObject.php          ← namespace with its symbols, virtuals, aliases

  Resolvers/
    AbsoluteResolver.php
    NamespaceResolver.php
    RelativeResolver.php
    RelativeSegmentResolver.php
    SymbolResolver.php
    VariableResolver.php
    VirtualResolver.php

  Facades/
    Path.php                     ← static facade over Locator::get()

  Interfaces/
    LocatorInterface.php         ← contract for DI / mocking

  Bridge/
    Stasis/CacheManager.php      ← delegates persistence to Wingman Stasis
    Cortex/Environment.php       ← aliases Cortex Environment or provides a stub
    Corvus/Emitter.php           ← aliases Corvus Emitter or provides a stub
    Console/Commands/            ← 10 CLI commands

  Enums/
    PathRootVariable.php
    PathRootType.php
    NamespaceNotation.php

  Exceptions/                    ← typed exception hierarchy
  Attributes/                    ← Configurable attribute
```

---

## Optional Dependencies

All optional dependencies are resolved at runtime. The package functions fully without any of them.

| Package | Benefit |
|---|---|
| **Wingman/Stasis** | Replaces the file-based cache with a key-value store (Redis, Memcached, local) |
| **Wingman/Cortex** | Supplies `Environment`-based configuration injection |
| **Wingman/Corvus** | Enables real signal emission during discovery and resolution events |
| **Wingman/Console** | Required only for the CLI bridge; has no effect on path resolution itself |

---

## Further Reading

- [Path Expression Syntax](path-expressions.md)
- [Manifest Schema](manifest-schema.md)
- [Discovery & DiscoveryProfile](discovery.md)
- [Configuration](configuration.md)
- [Caching](caching.md)
- [API Reference](api-reference.md)
- [Console Commands](console.md)
- [Bridge Classes](bridges.md)
- [Testing Guide](testing.md)
