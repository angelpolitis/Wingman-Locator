# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

You can find and compare releases at the [GitHub release page](https://github.com/angelpolitis/Wingman-Locator/releases).

---

## [1.0.0] — 2026-03-13

### Added

- **`Locator`** — Core facade implementing `LocatorInterface`; manages manifest discovery,
  namespace registration, path-resolution pipeline, and a global singleton with
  `setGlobal()` / `get()` semantics.
- **Path-resolution pipeline** — Seven composable resolvers wired in priority order:
  `VariableResolver`, `NamespaceResolver`, `RelativeSegmentResolver`, `SymbolResolver`,
  `RelativeResolver`, `AbsoluteResolver`, and `VirtualResolver`.
- **`NamespaceManager`** — Manages namespace-to-path mappings, virtual paths, and
  root-variable bindings.
- **`ManifestLoader`** — Discovers and parses `locator.manifest` JSON files from a
  given root directory according to a `DiscoveryProfile`.
- **`ManifestRepository`** / **`DiscoveryRepository`** — Typed collection objects for
  `Manifest` and discovery-profile state respectively; both support hydration and
  serialisable round-trips via `dehydrate()` / `__set_state()`.
- **`CacheManager`** — File-based persistence layer for cold/warm boot cycle; writes
  PHP-exportable cache files with atomic `LOCK_EX` writes to prevent race conditions
  under concurrent PHP-FPM workers.
- **`Asserter`** — Static utility for asserting filesystem conditions (directory
  existence, file existence, writability) with typed exception variants.
- **`PathUtils`** — Static utility providing URL detection, path normalisation, and
  platform-agnostic separator handling.
- **`PathResolutionPipeline`** — Chains resolvers and exposes the `resolve()` entry-point;
  maintains an in-memory LRU-eviction cache capped at `Locator::MAX_RESOLUTION_CACHE_SIZE`.
- **`Facades\Path`** — Static facade delegating path-resolution calls to the active
  `LocatorInterface` instance; supports injecting a custom implementation for testing.
- **`Enums\PathRootVariable`** — Backed enum for built-in root variables (`@{cwd}`,
  `@{root}`, `@{temp}`, `@{home}`).
- **Bridge: Stasis** (`Bridge\Stasis\CacheManager`) — Extends `CacheManager` to delegate
  persistence to Wingman Stasis; falls back gracefully when Stasis is absent.
- **Bridge: Cortex** (`Bridge\Cortex\Environment`) — Alias to `Wingman\Cortex\Facades\Environment`
  when Cortex is installed; otherwise provides a no-op stub with `from()`, `populate()`,
  `restore()`, and `snapshot()`.
- **Bridge: Corvus** (`Bridge\Corvus\Emitter`) — Alias to `Wingman\Corvus\Emitter` when
  Corvus is installed; otherwise provides a no-op stub with the full emitter API.
- **Console bridge** — Ten commands wired via the `#[Cmd]` attribute and discovered at
  runtime through reflection; no JSON registry file required:
  - `locator:resolve` — Resolve a path expression to its absolute path.
  - `locator:check` — Check whether an expression resolves to an existing resource (CI-safe exit codes).
  - `locator:namespaces` — List all registered namespaces.
  - `locator:manifests` — List all discovered manifests.
  - `locator:symbols` — List all symbols in a namespace.
  - `locator:virtuals` — List all virtual paths.
  - `locator:discover` — Trigger manifest discovery and print timing statistics.
  - `locator:validate` — Validate every registered namespace path exists on disk.
  - `locator:cache:status` — Display the current caching configuration and stats.
  - `locator:cache:clear` — Delete the discovery cache.
- **Exception hierarchy** — `NonexistentDirectoryException`, `NonexistentFileException`,
  `NotADirectoryException`, `NotAFileException`, `FileNotWritableException`.
- **Test suite** — 28 test classes covering resolvers, `Locator` integration, `CacheManager`,
  all ten console commands, bridge stubs, `Asserter`, `Path` facade, and a full
  cold-boot → discover → cache → warm-boot → resolve integration scenario.
