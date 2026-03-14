# Path Expression Syntax

A **path expression** is a string that the Locator's resolution pipeline transforms into an absolute filesystem path. The pipeline applies seven resolvers in priority order; the first resolver that produces a result wins.

---

## 1. Variable Paths (highest priority)

Replace a named variable with a runtime-resolved root directory.

**Syntax:** `@{variableName}/relative/tail`

| Variable | Resolves To |
|---|---|
| `@{server}` | `$_SERVER["DOCUMENT_ROOT"]` (falls back to `cwd` when absent) |
| `@{cwd}` | `getcwd()` |
| `@{os}` | Filesystem root — `/` on Unix, `C:\` on Windows |
| `@{namespace}` | Root directory of the current implicit namespace |
| `@{manifest}` | Directory containing the active manifest file |
| `@{package}` | Root directory of the active package |

**Examples:**

```
@{server}/assets/logo.png      →  /srv/www/assets/logo.png
@{cwd}/config/app.json         →  /home/user/project/config/app.json
@{manifest}/templates/email    →  /srv/www/modules/App/templates/email
@{os}/etc/hosts                →  /etc/hosts
```

Variable tokens are also valid inside manifest symbol values and virtual `source` fields.

---

## 2. Namespace Paths

Map a logical namespace name to its registered root directory and append a relative tail.

**Syntax:** `@Namespace/relative/path` or `Namespace:relative/path`

```
@App/src/Controllers/UserController.php
App:src/Controllers/UserController.php      ← colon notation, equivalent
@Wingman/config/settings.php
@app/models/User.php                        ← aliases are case-insensitive
```

Namespace lookup checks: the canonical name, all registered `aliases`, and all `namespaceAliases`. The resolution fails (and falls through to the next resolver) only when no match is found.

---

## 3. Relative Segment Paths

Paths that begin with `./` or `../` are resolved relative to the implicit namespace root or, when no namespace is active, the server root.

```
./config/database.php         →  <namespace-root>/config/database.php
../shared/helpers.php         →  <parent-of-namespace-root>/shared/helpers.php
```

---

## 4. Symbol Paths

Symbols are named path aliases declared in a manifest's `symbols` section. A `%symbolName` token is replaced with the registered expression for that symbol.

**Syntax:** `%symbol` or `%{symbol}` (brace form for disambiguation)

```
@App/%controllers/UserController.php
@App/%{controllers}/UserController.php    ← use braces when abutting other text
```

Resolution is namespace-scoped: `%controllers` expands to the value registered under `controllers` in the `App` namespace manifest. Symbols may reference other path expressions, including other symbols (to a configurable depth to prevent circular references).

> **Private symbols** — prefix a symbol name with `~` in the manifest to mark it as internal. It is still resolvable but excluded from public listings.

---

## 5. Bare Relative Paths

Bare paths with no recognisable prefix are treated as relative to the implicit namespace root. When no namespace is active, they are resolved relative to the server root.

```
config/database.php
src/Controllers/UserController.php
```

---

## 6. Absolute Paths

Paths recognised as already absolute are normalised and passed through unchanged.

**Recognised forms:**

| Form | Example |
|---|---|
| Unix absolute | `/var/www/html/index.php` |
| Windows drive | `C:\inetpub\wwwroot\index.php` |
| Windows with forward slashes | `C:/inetpub/wwwroot/index.php` |
| UNC path | `\\Server\Share\file.txt` |
| URL | `https://cdn.example.com/asset.js` |

---

## 7. Virtual Paths (lowest priority)

Virtual names declared in a manifest's `virtuals` section are intercepted at resolution time and redirected to their configured `source` path.

```
@App/api/users       →  the source declared under the "api.content.users" virtual tree
@App/report.pdf      →  the source declared under the "report.pdf" virtual entry
```

Virtual resolution walks the virtual tree segment by segment. An exact key match in a `content` map takes priority over appending remaining segments to a `source` directory.

See [manifest-schema.md](manifest-schema.md) for how to declare virtual entries.

---

## Resolution Rules

- **First resolver wins.** As soon as one resolver returns a non-null result, the remaining resolvers are not invoked.
- **No existence check.** `getPathFor()` returns the resolved absolute path without verifying it exists on disk. Use `getPathTo()`, `getPathToFile()`, or `getPathToDirectory()` when existence is required.
- **Normalisation.** The result is always normalised: redundant separators collapsed, `.` and `..` segments resolved, platform-appropriate separator applied.
- **In-memory cache.** Resolved paths are cached in a per-instance LRU map. The first call resolves the full pipeline; subsequent calls for the same expression return the cached result immediately.

---

## Quick Reference

```
@{variable}/path          →  variable root + path
@Namespace/path           →  namespace root + path
Namespace:path            →  namespace root + path (colon notation)
./path or ../path         →  relative to implicit namespace root
%symbol or %{symbol}      →  symbol expansion
path/with/no/prefix       →  implicit relative (namespace root + path)
/absolute/path            →  passed through unchanged
@Namespace/%sym/tail      →  namespace + symbol expansion + tail
```
