# Discovery

Manifest discovery is the process of walking the filesystem to find every `locator.manifest` file and register the namespaces, symbols, and virtual paths they declare. Discovery happens automatically on first use but can also be triggered manually.

---

## Automatic Discovery

When `Locator::get()` is first called (or when a `new Locator()` is constructed), `discoverManifests()` is invoked automatically with the default discovery profile:

```php
new DiscoveryProfile([
    'depth'    => 5,
    'exclude'  => ['vendor/*', 'packages/*', 'tests/*', 'temp/*', 'cache/*', '**/.*'],
    'onlyRoot' => false,
])
```

The default root directory is resolved as:

- **Web context** — `$_SERVER["DOCUMENT_ROOT"]` when it is set and points to a real directory.
- **CLI context** — `getcwd()` as a fallback when no document root is available.

---

## Manual Discovery

Additional directories can be registered after construction:

```php
$locator = Locator::get();

// Scan a plugin directory with a custom profile.
$locator->discoverManifests(
    '/srv/plugins',
    new DiscoveryProfile(['depth' => 3, 'exclude' => ['tests/*']])
);
```

`discoverManifests()` is **idempotent**: if the same root directory and profile combination has already been scanned, the method returns immediately without repeating the scan.

---

## DiscoveryProfile

A `DiscoveryProfile` governs the rules for a scan.

### Constructor

```php
new DiscoveryProfile(array $settings = [])
```

| Key | Type | Default | Description |
|---|---|---|---|
| `depth` | `int` | `-1` | Maximum directory recursion depth; `-1` for unlimited |
| `onlyRoot` | `bool` | `false` | Scan only the root directory, no subdirectories |
| `include` | `string[]` | `[]` | Glob patterns; a path must match at least one to be included |
| `exclude` | `string[]` | `[]` | Glob patterns; matching paths are skipped entirely |
| `omitHidden` | `bool` | `true` | Skip files and directories whose name starts with `.` |

### Glob Pattern Syntax

| Token | Meaning |
|---|---|
| `*` | Any characters within a single path segment (no `/`) |
| `**` | Any sequence of path segments at any depth |
| `?` | (not specially treated — treated as a literal) |

**Examples:**

```
vendor/*       →  Any direct child of vendor/
tests/**       →  The entire tests/ tree
**/.*          →  Any dotfile or dotdirectory at any depth
cache/*.php   →  Any .php file directly inside cache/
```

### Static Factory

```php
DiscoveryProfile::from(['depth' => 3, 'exclude' => ['vendor/*']]);
```

### Comparing Profiles

```php
$a = new DiscoveryProfile(['depth' => 3]);
$b = new DiscoveryProfile(['depth' => 3]);

$a->equals($b); // true
```

---

## Namespace Registration Rules

The following rules govern how manifests map to registered namespaces:

### Rule 1 — First manifest establishes the root

The **first** (topmost in the filesystem hierarchy) `locator.manifest` found for a given namespace name establishes that namespace's root directory (the directory containing the file).

```
/srv/www/modules/App/locator.manifest    ← declares "App", root = .../App/
/srv/www/modules/App/subdir/locator.manifest  ← also declares "App" → extends it
```

### Rule 2 — Nested manifests with the same namespace extend rather than replace

A second manifest in a deeper directory that declares the **same** namespace name does not overwrite the root or create a new namespace. Instead, its symbols, virtuals, aliases, and settings are **merged** into the existing namespace object.

### Rule 3 — A different namespace name always creates a new namespace

Declaring a **different** namespace name in a nested manifest registers a completely new, independent namespace rooted at that subdirectory. It is **not** a child or sub-namespace of the parent.

```
/srv/www/modules/App/locator.manifest           ← registers "App"
/srv/www/modules/App/Billing/locator.manifest   ← declares "Billing" → new namespace
```

---

## DiscoveryRepository

The `DiscoveryRepository` tracks every (root, profile) pair that has already been scanned, preventing redundant re-scans.

```php
// Internal check — callers do not normally interact with this directly.
if ($this->discoveries->has($rootDirectory, $profile)) {
    return; // already scanned
}
```

When caching is enabled, the repository's state is persisted to the cache along with the manifest data and restored on warm boot.

---

## Discovery Events (Corvus bridge)

When Wingman Corvus is installed, discovery emits signals at key points in the lifecycle. When Corvus is absent, these calls are silently swallowed by the no-op stub. See [bridges.md](bridges.md) for details.

---

## Cache Interaction

Discovery and caching are tightly coupled:

1. **Before scan** — the cache manager is checked. A valid cached payload short-circuits the scan entirely.
2. **After scan** — the results are written to the cache manager via `save()`.
3. **Custom roots** — discovery scans against a non-default root are never cached automatically; the caching shortcut only applies to the default root.

See [caching.md](caching.md) for full details.
