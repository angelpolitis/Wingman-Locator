# `locator.manifest` Schema Reference

A `locator.manifest` is a JSON file placed in any directory you want Locator to recognise as a named namespace.  
During the discovery phase Locator walks the filesystem from the configured root, finds every `locator.manifest` file and registers each one.

---

## Top-Level Object

```json
{
    "namespace":       "string  (required)",
    "aliases":         ["string", ...],
    "namespaceAliases":["string", ...],
    "symbols":         { "name": "path-expression", ... },
    "virtuals":        { "name": VirtualEntry, ... },
    "settings":        { ... }
}
```

---

## Fields

### `namespace` — `string` · **required**

The canonical name of the namespace. Used as the prefix in path expressions such as `@App/src/Controllers`.

Only the **topmost** `locator.manifest` in a directory tree that declares a given namespace name establishes that namespace's root path — specifically, the directory that contains the file. Every subsequent `locator.manifest` found deeper in the same tree that declares the **same** namespace name is treated as an extension: its aliases, symbols, virtuals, and settings are merged into the already-registered namespace object rather than creating a new one.

Nested manifests should therefore always reuse the parent namespace name. Declaring a **different** namespace name in a nested manifest will register an entirely new, independent namespace rooted at that subdirectory — not a child or sub-namespace of the parent.

```json
"namespace": "App"
```

---

### `aliases` — `string[]` · optional, default `[]`

Short alternative names for this namespace that may be used interchangeably in path expressions.  
Registered in the namespace registry so that `@app/...` resolves identically to `@App/...`.

```json
"aliases": ["app", "application"]
```

---

### `namespaceAliases` — `string[]` · optional, default `[]`

Additional names under which this manifest matches namespace lookups at the manifest level  
(e.g. `Manifest::hasNamespace()`). These do **not** create routing shortcuts in path expressions;  
use `aliases` for that.

```json
"namespaceAliases": ["LegacyApp"]
```

---

### `symbols` — `object` · optional, default `{}`

An associative map of **symbol name → path expression**. Symbol names are used in `%symbol` tokens  
inside path expressions to create stable, refactor-proof references.

Symbol names may contain letters, digits, underscores and forward slashes (for sub-paths).  
Prefix a symbol name with `~` to mark it as private (resolved internally but hidden from external consumers).

The value may be any valid path expression, including variables, namespace references and other symbols.

```json
"symbols": {
    "controllers": "src/Http/Controllers",
    "models":      "@{manifest}/src/Domain/Models",
    "config":      "@Wingman/config",
    "~internal":   "src/Private"
}
```

Usage in a path expression:

```
@App/%controllers/UserController.php
@App/%{controllers}/UserController.php   ← brace form for disambiguation
```

---

### `virtuals` — `object` · optional, default `{}`

An associative map of **virtual name → VirtualEntry** that exposes a clean public path surface  
without coupling consumers to the real filesystem structure. A virtual name becomes a path  
segment inside the namespace root.

Each value is a **VirtualEntry** — one of the shapes below.

#### File Entry

```json
"report.pdf": {
    "type": "file",
    "source": "@{manifest}/generated/annual-report.pdf"
}
```

Resolving `@App/report.pdf` returns the path at `source`.

#### Directory Entry

```json
"api": {
    "type": "directory",
    "source": "src/Http/Api"
}
```

Resolving `@App/api/users` appends remaining segments to `source`, e.g. `src/Http/Api/users`.

#### Directory Entry with Inline Content

A directory may embed a `content` map instead of (or in addition to) a `source`, allowing further nesting.

```json
"assets": {
    "type": "directory",
    "source": "./public/assets",
    "content": {
        "generated.css": {
            "type": "file",
            "source": "/tmp/build/generated.css"
        }
    }
}
```

Resolution walks the virtual tree segment by segment. An exact key match inside `content` takes  
priority over appending to `source`.

#### Shorthand String Entry

A bare string value is shorthand for a file entry whose `source` is that string.

```json
"note.txt": "/tmp/note.txt"
```

#### Nesting

Virtual trees may be arbitrarily deep:

```json
"docs": {
    "type": "directory",
    "content": {
        "api": {
            "type": "directory",
            "content": {
                "index.html": { "type": "file", "source": "generated/api-docs/index.html" }
            }
        }
    }
}
```

---

### `settings` — `object` · optional, default `{}`

Arbitrary key/value configuration attached to this namespace. The shape is application-defined;  
Locator stores and exposes these values via `NamespaceObject::getSettings()` but does not act  
on them itself.

```json
"settings": {
    "scan": {
        "onlyRoot": true
    },
    "cache": false
}
```

---

## Full Example

```json
{
    "namespace": "App",
    "aliases": ["app"],
    "namespaceAliases": ["LegacyApp"],
    "symbols": {
        "controllers": "src/Http/Controllers",
        "models":      "src/Domain/Models",
        "config":      "@{manifest}/config",
        "~boot":       "src/Bootstrap"
    },
    "virtuals": {
        "api": {
            "type": "directory",
            "content": {
                "users":    { "type": "file", "source": "src/Handlers/UserHandler.php" },
                "products": { "type": "file", "source": "src/Handlers/ProductHandler.php" }
            }
        },
        "assets": {
            "type": "directory",
            "source": "public/assets",
            "content": {
                "generated.css": { "type": "file", "source": "/tmp/build/app.css" }
            }
        },
        "note.txt": "temp/note.txt"
    },
    "settings": {
        "public": true
    }
}
```

---

## Path Expressions in Symbol Values

Symbol values and virtual `source` fields accept the same path expression syntax as the main  
resolution API:

| Syntax | Example | Meaning |
|---|---|---|
| `@{server}` | `@{server}/uploads` | Document root |
| `@{cwd}` | `@{cwd}/config` | Current working directory |
| `@{manifest}` | `@{manifest}/assets` | Directory containing this manifest |
| `@{namespace}` | `@{namespace}/lib` | Root of the current namespace |
| `@{package}` | `@{package}/vendor` | Root of the current package |
| `@{os}` | `@{os}/etc/hosts` | Filesystem root (`/` or `C:\`) |
| `@OtherNS/path` | `@Wingman/config` | Root of another namespace |
| `%symbolName` | `%controllers/User.php` | Another symbol in the same namespace |

---

## Discovery Rules

The discovery scan is controlled by a `DiscoveryProfile`. The defaults used at startup are:

| Setting | Default |
|---|---|
| `depth` | `5` |
| `onlyRoot` | `false` |
| `omitHidden` | `true` |
| `exclude` | `vendor/*`, `tests/*`, `temp/*`, `cache/*`, `**/.*` |

Any directory matching an exclude pattern (or hidden by the `omitHidden` rule) will not be  
recursed into and any manifests inside it will not be discovered.
