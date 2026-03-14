# API Reference

---

## `Locator`

**FQCN:** `Wingman\Locator\Locator`

The primary entry point. Implements `LocatorInterface`. Manages manifest discovery, namespace registration, path resolution, and caching.

### Constants

| Constant | Value | Description |
|---|---|---|
| `MAX_RESOLUTION_CACHE_SIZE` | `2048` | Maximum entries in the in-memory resolution LRU cache |

### Static Methods

| Method | Returns | Description |
|---|---|---|
| `Locator::get()` | `LocatorInterface` | Returns the global singleton, creating it on first call |
| `Locator::setGlobal(?LocatorInterface $locator)` | `void` | Injects a custom instance; pass `null` to reset and allow a fresh instance on next `get()` |

### Constructor

```php
new Locator(array|Environment $config = [], ?object $cachingAdapter = null)
```

See [configuration.md](configuration.md) for all accepted keys.

### Instance Methods

| Method | Returns | Description |
|---|---|---|
| `discoverManifests(?string $root, ?DiscoveryProfile $profile)` | `void` | Discover manifests in a root directory; idempotent for the same root+profile pair |
| `getManifestRepository()` | `ManifestRepository` | Returns the repository of all loaded manifests |
| `getPathFor(string $expression)` | `string` | Resolve to absolute path, no existence check |
| `getPathTo(string $expression)` | `string\|null` | Resolve and verify the resource exists |
| `getPathToDirectory(string $expression)` | `string\|null` | Resolve and verify it is a directory |
| `getPathToFile(string $expression)` | `string\|null` | Resolve and verify it is a file |
| `getPathToNamespace(string $namespace)` | `string\|null` | Resolve the root directory of a namespace |
| `getPathToRoot(PathRootVariable\|string $root)` | `string\|null` | Resolve a named root variable |

---

## `LocatorInterface`

**FQCN:** `Wingman\Locator\Interfaces\LocatorInterface`

The contract for all locator implementations. Depend on this interface in your code rather than the concrete `Locator` class.

```php
interface LocatorInterface {
    public function discoverManifests(?string $rootDirectory, ?DiscoveryProfile $profile) : void;
    public function getManifestRepository() : ManifestRepository;
    public function getPathFor(string $pathExpression) : string;
    public function getPathTo(string $pathExpression) : ?string;
    public function getPathToDirectory(string $pathExpression) : ?string;
    public function getPathToFile(string $pathExpression) : ?string;
    public function getPathToNamespace(string $namespace) : ?string;
    public function getPathToRoot(PathRootVariable|string $root) : ?string;
}
```

---

## `Path` (Facade)

**FQCN:** `Wingman\Locator\Facades\Path`

Static facade over the global `Locator` singleton. Supports locator injection for testing.

| Method | Returns | Description |
|---|---|---|
| `Path::for(string $expr)` | `string` | `getPathFor()` via the active locator |
| `Path::to(string $expr)` | `string\|null` | `getPathTo()` via the active locator |
| `Path::toFile(string $expr)` | `string\|null` | `getPathToFile()` — verifies it is a file |
| `Path::toDirectory(string $expr)` | `string\|null` | `getPathToDirectory()` — verifies it is a directory |
| `Path::toNamespace(string $ns)` | `string\|null` | `getPathToNamespace()` |
| `Path::toRoot(PathRootVariable\|string $root)` | `string\|null` | `getPathToRoot()` |
| `Path::setLocator(?LocatorInterface $locator)` | `void` | Inject a custom locator (pass `null` to restore singleton) |

---

## `CacheManager`

**FQCN:** `Wingman\Locator\CacheManager`

File-based persistence layer for the discovery cache. Writes PHP-exportable files with `LOCK_EX` to prevent corrupt writes under concurrent access.

### Constructor

```php
new CacheManager(string $filePath, int $maxAge = 0)
```

### Methods

| Method | Returns | Description |
|---|---|---|
| `save(array $manifestData, array $scannedRoots)` | `bool` | Serialise and write the discovery state to disk |
| `load()` | `array\|null` | Read and validate the cache file; returns `null` if absent, invalid, or stale |
| `clear()` | `bool` | Delete the cache file; returns `true` when the file no longer exists |

---

## `DiscoveryProfile`

**FQCN:** `Wingman\Locator\Objects\DiscoveryProfile`

Governs the rules for a manifest discovery scan.

### Constructor

```php
new DiscoveryProfile(array $settings = [])
```

Accepted keys: `depth` (int), `onlyRoot` (bool), `include` (string[]), `exclude` (string[]), `omitHidden` (bool).

### Static Methods

| Method | Returns | Description |
|---|---|---|
| `DiscoveryProfile::from(array $settings)` | `static` | Factory, equivalent to `new DiscoveryProfile($settings)` |
| `DiscoveryProfile::__set_state(array $properties)` | `static` | Reconstitutes an instance from serialised property data |

### Instance Methods

| Method | Returns | Description |
|---|---|---|
| `validate(string $relativePath, int $depth)` | `bool` | Whether a given path and depth pass all the profile's rules |
| `equals(array\|self $profile)` | `bool` | Whether two profiles are equivalent |
| `dehydrate()` | `array` | Returns a plain array of internal property values for cache persistence |
| `__toString()` | `string` | JSON representation of the profile settings |

---

## `Manifest`

**FQCN:** `Wingman\Locator\Objects\Manifest`

Immutable value object representing a parsed `locator.manifest` file.

### Static Methods

| Method | Returns | Description |
|---|---|---|
| `Manifest::from(array $data, string $sourcePath)` | `static` | Create from raw manifest data |
| `Manifest::hydrate(array $data)` | `static` | Reconstitute from cached (dehydrated) data, restoring absolute paths |
| `Manifest::__set_state(array $properties)` | `static` | `var_export` / cache reconstitution |

### Instance Methods

| Method | Returns | Description |
|---|---|---|
| `getNamespace()` | `string` | The canonical namespace name |
| `getSourcePath()` | `string` | Absolute path of the manifest file |
| `getAliases()` | `array` | Registered short aliases |
| `getNamespaceAliases()` | `array` | Additional namespace alias names |
| `getSymbols()` | `array` | Map of symbol name → path expression |
| `getSymbol(string $symbol)` | `string\|null` | Path expression for a single symbol |
| `getVirtuals()` | `array` | Map of virtual name → definition |
| `getSettings()` | `array` | Arbitrary key/value settings |
| `hasNamespace(string $namespace)` | `bool` | Whether the manifest matches a given namespace name |
| `dehydrate()` | `array` | Plain array suitable for cache persistence |
| `setNamespace(string $ns)` | `static` | Set the canonical namespace name |
| `setAliases(array $aliases)` | `static` | Replace the list of short aliases |
| `setNamespaceAliases(array $aliases)` | `static` | Replace the list of additional namespace alias names |
| `setSymbols(array $symbols)` | `static` | Merges symbols (separator-normalised keys) |
| `setSymbol(string $symbol, string $path)` | `static` | Add or overwrite a single symbol |
| `setVirtuals(array $virtuals)` | `static` | Replace the map of virtual name → definition |
| `setSettings(array $settings)` | `static` | Deep-merges settings |

---

## `ManifestRepository`

**FQCN:** `Wingman\Locator\Objects\ManifestRepository`

Typed collection of `Manifest` objects.

| Method | Returns | Description |
|---|---|---|
| `add(Manifest $manifest)` | `static` | Append a manifest; throws `ManifestOverwriteException` if the path is already registered |
| `get(string\|int $pathOrIndex)` | `Manifest\|null` | Retrieve a manifest by absolute path or zero-based index |
| `getAll()` | `Manifest[]` | All registered manifests in insertion order |
| `getAllPaths()` | `string[]` | Absolute paths of all registered manifests |
| `getByNamespace(string $namespace)` | `Manifest[]` | All manifests registered under the given namespace; returns `[]` if none exist |
| `dehydrate()` | `array` | Plain array of all manifests suitable for cache persistence |
| `ManifestRepository::hydrate(array $data)` | `static` | Reconstitute a repository from previously dehydrated data |

---

## `NamespaceManager`

**FQCN:** `Wingman\Locator\NamespaceManager`

Manages the namespace registry, alias table, root-variable bindings, and the implicit namespace.

### Constants

| Constant | Value |
|---|---|
| `DEFAULT_NAMESPACE` | `"default"` |

### Methods

| Method | Returns | Description |
|---|---|---|
| `registerNamespace(NamespaceObject $ns)` | `void` | Add a namespace to the registry |
| `getNamespace(string $name)` | `NamespaceObject\|null` | Retrieve by canonical name or alias |
| `hasNamespace(string $name)` | `bool` | Whether a namespace is registered |
| `getCanonicalNamespace(string $name)` | `string\|null` | Resolve an alias to the canonical name |
| `getAliases(string $namespace)` | `string[]` | All registered aliases for a namespace |
| `getImplicitNamespace(bool $static = true)` | `string` | The current implicit namespace; dynamic mode uses `debug_backtrace()` |
| `setImplicitNamespace(string $namespace)` | `void` | Override the implicit namespace |
| `getPathNamespace(string $path)` | `string` | Find which namespace a given path belongs to |
| `refreshRegistry()` | `void` | Rebuild the internal alias→canonical lookup table |

---

## `PathUtils`

**FQCN:** `Wingman\Locator\PathUtils`

Static utility class — pure, side-effect free. Cannot be instantiated.

| Method | Returns | Description |
|---|---|---|
| `PathUtils::analyse(string $path)` | `array{namespace, path}` | Extract the namespace prefix and tail from a path expression |
| `PathUtils::clamp(string $root, string $path, bool $strict = true)` | `string` | Resolve `$path` against `$root`, preventing traversal outside root |
| `PathUtils::fix(?string $path, string $new = DS, array $old = ['\\', '/'])` | `string\|null` | Standardise separators |
| `PathUtils::forceTrailingSeparator(string $path)` | `string` | Ensure path ends with the directory separator |
| `PathUtils::getAbsolutePath(string $path, ?string $base = null)` | `string` | Resolve a path to absolute |
| `PathUtils::getRelativePath(string $from, string $to)` | `string` | Calculate relative path from `$from` to `$to` |
| `PathUtils::isAbsolutePath(string $path)` | `bool` | Whether the path is absolute on any platform |
| `PathUtils::isDataURL(string $path)` | `bool` | Whether the path is a `data:` URL |
| `PathUtils::isFileUrl(string $path)` | `bool` | Whether the path is a `file:///` URL |
| `PathUtils::isLocal(string $path)` | `bool` | Whether the path is not a URL |
| `PathUtils::isPHPStream(string $path)` | `bool` | Whether the path is a PHP stream wrapper |
| `PathUtils::isRelativePath(string $path)` | `bool` | Whether the path is relative |
| `PathUtils::isUnixAbsolutePath(string $path)` | `bool` | Whether the path begins with `/` |
| `PathUtils::isURL(string $path)` | `bool` | Whether the path is a URL |
| `PathUtils::isWindowsAbsolutePath(string $path)` | `bool` | Whether the path is an absolute Windows path |
| `PathUtils::join(string ...$fragments)` | `string` | Join path fragments with the platform separator |
| `PathUtils::normalise(string $path)` | `string` | Collapse `..` / `.`, standardise separators |

---

## `Asserter`

**FQCN:** `Wingman\Locator\Asserter`

Static utility class for filesystem assertions. Cannot be instantiated.

| Method | Returns | Throws | Description |
|---|---|---|---|
| `Asserter::requireDirectoryAt(string $path)` | `void` | `NonexistentDirectoryException`, `NotADirectoryException` | Assert `$path` is an existing directory |
| `Asserter::requireFileAt(string $path, bool $requireWritable = false)` | `void` | `NonexistentFileException`, `NotAFileException`, `FileNotWritableException` | Assert `$path` is an existing file (skips check for URLs and PHP streams) |
| `Asserter::isDirectoryEmpty(string $directory)` | `bool` | `NonexistentDirectoryException`, `NotADirectoryException` | Whether the directory contains no entries |

---

## `Asserter` (Trait)

**FQCN:** `Wingman\Locator\Bridge\Argus\Traits\Asserter`

Extends an Argus test class with Locator-specific assertions. All assertions delegate result recording to the abstract `recordAssertion()` method that the consuming class must provide. All public methods accept an optional trailing `string $message` parameter.

### Private Helpers

| Method | Description |
|---|---|
| `runManifestAssertion(string $namespace, bool $shouldExist, string $message)` | Core logic for manifest loaded/not-loaded checks |
| `runNamespaceAssertion(string $namespace, bool $shouldExist, string $message)` | Core logic for namespace resolvability checks |
| `runPathExistenceAssertion(string $expression, bool $shouldExist, string $message)` | Core logic for filesystem existence checks |
| `runPathResolutionAssertion(string $expression, string $expected, bool $shouldMatch, string $message)` | Core logic for exact resolution value checks |
| `runPathTypeAssertion(string $expression, string $type, bool $shouldMatch, string $message)` | Core logic for file/directory type checks |

### Abstract Requirement

```php
abstract protected function recordAssertion(bool $status, mixed $expected, mixed $actual, string $message) : void;
```

### Public Assertions

| Method | Returns | Description |
|---|---|---|
| `assertManifestLoaded(string $namespace, string $message = "")` | `void` | Assert at least one manifest is registered for the namespace |
| `assertManifestNotLoaded(string $namespace, string $message = "")` | `void` | Assert no manifests are registered for the namespace |
| `assertNamespaceNotResolvable(string $namespace, string $message = "")` | `void` | Assert the namespace cannot be resolved to a directory |
| `assertNamespaceResolvable(string $namespace, string $message = "")` | `void` | Assert the namespace resolves to a directory |
| `assertPathExists(string $expression, string $message = "")` | `void` | Assert the expression resolves to an existing path |
| `assertPathIsDirectory(string $expression, string $message = "")` | `void` | Assert the expression resolves to an existing directory |
| `assertPathIsFile(string $expression, string $message = "")` | `void` | Assert the expression resolves to an existing file |
| `assertPathNotExists(string $expression, string $message = "")` | `void` | Assert the expression does not resolve to an existing path |
| `assertPathNotIsDirectory(string $expression, string $message = "")` | `void` | Assert the expression does not resolve to an existing directory |
| `assertPathNotIsFile(string $expression, string $message = "")` | `void` | Assert the expression does not resolve to an existing file |
| `assertPathNotResolvesTo(string $expression, string $expected, string $message = "")` | `void` | Assert the expression does not resolve to `$expected` |
| `assertPathResolvesTo(string $expression, string $expected, string $message = "")` | `void` | Assert the expression resolves to `$expected` |

---

## `PathRootVariable` (Enum)

**FQCN:** `Wingman\Locator\Enums\PathRootVariable`

Backed enum for the built-in `@{variable}` tokens.

| Case | Token |
|---|---|
| `SERVER` | `@{server}` → `$_SERVER["DOCUMENT_ROOT"]` |
| `CWD` | `@{cwd}` → `getcwd()` |
| `OS` | `@{os}` → filesystem root |
| `NAMESPACE` | `@{namespace}` → current implicit namespace root |
| `MANIFEST` | `@{manifest}` → directory containing the active manifest |
| `PACKAGE` | `@{package}` → root directory of the active package |

---

## Exceptions

All exceptions extend `\RuntimeException` unless otherwise noted.

| Class | Thrown When |
|---|---|
| `CircularSymbolException` | A symbol references itself, directly or indirectly |
| `FileNotWritableException` | `Asserter::requireFileAt($path, true)` when the file is not writable |
| `InvalidAliasException` | A manifest declares a malformed alias |
| `InvalidSymbolException` | A manifest declares a malformed symbol name |
| `ManifestOverwriteException` | A manifest attempts to redefine an already-built namespace root |
| `MaxSymbolDepthExceededException` | Symbol expansion depth exceeds the configured limit |
| `NonexistentDirectoryException` | `Asserter::requireDirectoryAt()` for a path that does not exist |
| `NonexistentFileException` | `Asserter::requireFileAt()` for a path that does not exist |
| `NotADirectoryException` | `Asserter::requireDirectoryAt()` for a path that is not a directory |
| `NotAFileException` | `Asserter::requireFileAt()` for a path that is not a file |
| `PathResolutionException` | The resolution pipeline could not produce a result |
| `PathTraversalException` | `PathUtils::clamp()` when the resolved path escapes the root |
| `UndefinedVariableException` | A `@{variable}` token refers to a variable that has no registered value |
| `UnknownNamespaceException` | An operation references a namespace that is not registered |
