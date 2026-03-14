# Console Commands

Locator ships ten Wingman Console commands. They are auto-discovered at runtime via `#[Command]` attribute reflection — no configuration file is needed.

**Requires:** Wingman Console

---

## Quick Reference

| Command | Description |
| --- | --- |
| [`locator:resolve`](#locatorresolve) | Resolve a path expression to its absolute path |
| [`locator:check`](#locatorcheck) | CI-safe existence check with process exit codes |
| [`locator:namespaces`](#locatornamespaces) | List all registered namespaces |
| [`locator:manifests`](#locatormanifests) | List all loaded manifests |
| [`locator:symbols`](#locatorsymbols) | Dump the symbol table |
| [`locator:virtuals`](#locatorvirtuals) | List all virtual entries |
| [`locator:discover`](#locatordiscover) | Run a discovery scan with timing output |
| [`locator:validate`](#locatorvalidate) | Audit all symbols for broken paths |
| [`locator:cache:status`](#locatorcachestatus) | Show caching configuration and entry state |
| [`locator:cache:clear`](#locatorcacheclear) | Delete the discovery cache |

---

## `locator:resolve`

Resolves a path expression to its absolute path and prints it. Useful for interactively debugging path expressions without writing PHP.

**Usage:**

```bash
wingman locator:resolve <expression> [--type=<any|file|dir>]
```

**Arguments:**

| Name | Description |
| --- | --- |
| `expression` | The path expression to resolve (e.g. `@app/config.php`) |

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--type` | `-t` | `any` | Restrict resolution to a resource type: `any`, `file`, or `dir` |

**Exit codes:** `0` on success, `1` if a typed check is requested and the path does not exist.

**Examples:**

```bash
wingman locator:resolve "@app/config.php"
wingman locator:resolve "%journal.txt" --type=file
wingman locator:resolve "@{manifest}/models" --type=dir
```

> When `--type=any` (default), `getPathFor()` is used — no existence check. When `--type=file` or `--type=dir`, `getPathToFile()`/`getPathToDirectory()` is used, and a missing path exits with code 1.

---

## `locator:check`

Checks whether a path expression resolves to an existing filesystem path and exits accordingly. Purpose-built for shell scripts and CI pipelines.

**Usage:**

```bash
wingman locator:check <expression> [--type=<any|file|dir>]
```

**Arguments:**

| Name | Description |
| --- | --- |
| `expression` | The path expression to check |

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--type` | `-t` | `any` | Restrict the check to a specific type: `any`, `file`, or `dir` |

**Exit codes:** `0` if the path exists, `1` if it does not.

**Examples:**

```bash
# Use in a shell conditional:
if wingman locator:check "@app/config.php" --type=file; then
    echo "Config present"
fi

# Check that a directory exists before deploying:
wingman locator:check "@app/public" --type=dir || exit 1
```

---

## `locator:namespaces`

Lists every namespace registered with the Locator, sorted alphabetically.

**Usage:**

```bash
wingman locator:namespaces [--aliases] [--paths]
```

**Flags:**

| Flag | Alias | Description |
| --- | --- | --- |
| `--aliases` | `-a` | Include a column showing each namespace's registered aliases |
| `--paths` | `-p` | Include a column showing the resolved root directory for each namespace |

**Exit codes:** `0` always.

**Examples:**

```bash
wingman locator:namespaces
wingman locator:namespaces --aliases
wingman locator:namespaces --aliases --paths
```

**Sample output:**

```
 Registered Namespaces

  Namespace   Aliases        Root Path
  ─────────   ─────────────  ────────────────────────────
  App         app, app-core  /srv/www/modules/App
  Wingman     wm             /srv/www/modules/Wingman

  2 namespace(s) registered.
```

---

## `locator:manifests`

Lists all manifests currently loaded by the Locator.

**Usage:**

```bash
wingman locator:manifests [--namespace=<ns>] [--path=<dir>] [--full]
```

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--namespace` | `--ns` | _(all)_ | Filter output to manifests in this namespace |
| `--path` | `-p` | _(none)_ | Trigger an additional `discoverManifests()` scan against this root before listing |
| `--full` | `-f` | _false_ | Show full source paths without truncation |

**Exit codes:** `0` always.

**Examples:**

```bash
wingman locator:manifests
wingman locator:manifests --namespace=App
wingman locator:manifests --path=/srv/plugins --full
```

---

## `locator:symbols`

Dumps the complete symbol table across all loaded manifests. Each row shows the namespace, the symbol name, and the raw path expression it maps to.

**Usage:**

```bash
wingman locator:symbols [--namespace=<ns>] [--filter=<substring>]
```

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--namespace` | `--ns` | _(all)_ | Restrict output to a specific namespace |
| `--filter` | `-f` | _(none)_ | Case-insensitive substring filter on symbol names |

**Exit codes:** `0` always.

**Examples:**

```bash
wingman locator:symbols
wingman locator:symbols --namespace=App
wingman locator:symbols --filter=model
wingman locator:symbols --namespace=App --filter=ctrl
```

---

## `locator:virtuals`

Lists all virtual entries registered across loaded manifests.

**Usage:**

```bash
wingman locator:virtuals [--namespace=<ns>]
```

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--namespace` | `--ns` | _(all)_ | Restrict output to a specific namespace |

**Exit codes:** `0` always.

**Examples:**

```bash
wingman locator:virtuals
wingman locator:virtuals --namespace=App
```

---

## `locator:discover`

Triggers a manifest discovery scan against a fresh `Locator` instance (not the global singleton) and reports what was found, along with timing statistics. Because a fresh instance is used, reported timings accurately reflect the cost of an actual discovery pass rather than a cached load.

**Usage:**

```bash
wingman locator:discover [--root=<dir>] [--depth=<n>] [--exclude=<patterns>] [--no-cache]
```

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--root` | `-r` | _(default root)_ | Root directory to scan |
| `--depth` | `-d` | `5` | Maximum recursion depth; `-1` for unlimited |
| `--exclude` | `-e` | `vendor/*,tests/*,temp/*,cache/*,**/.*` | Comma-separated glob patterns to exclude |
| `--no-cache` | `-n` | _false_ | Disable the discovery cache to guarantee a cold scan |

**Exit codes:** `0` on success, `1` if the specified root directory does not exist.

**Examples:**

```bash
wingman locator:discover
wingman locator:discover --root=/srv/app --depth=3
wingman locator:discover --no-cache
wingman locator:discover --exclude="vendor/*,tests/*"
```

---

## `locator:validate`

Audits every symbol in every loaded manifest by attempting to resolve it and checking whether the resulting path exists on the filesystem. Each symbol is categorised as:

- **OK** — resolved and exists on disk
- **Missing** — resolved but the path does not exist
- **Error** — the Locator threw an exception during resolution

**Usage:**

```bash
wingman locator:validate [--namespace=<ns>] [--strict]
```

**Options:**

| Flag | Alias | Default | Description |
| --- | --- | --- | --- |
| `--namespace` | `--ns` | _(all)_ | Restrict validation to a specific namespace |
| `--strict` | `-s` | _false_ | Exit with code 1 if any symbol is missing or errored |

**Exit codes:** `0` on success (or when no symbols are found). With `--strict`: `1` if any symbol is Missing or Error.

**Examples:**

```bash
wingman locator:validate
wingman locator:validate --namespace=App
wingman locator:validate --strict         # suitable for CI
```

---

## `locator:cache:status`

Displays the current caching configuration and the state of the active cache entry. Reads protected properties from the running `Locator` singleton via reflection — this is intentional; it surfaces internal state without requiring it to be part of the public API.

**Usage:**

```bash
wingman locator:cache:status
```

**Output includes:**

| Property | Description |
| --- | --- |
| Caching enabled | Whether caching is currently on |
| Backend | `File` or `Wingman Stasis` |
| Location / Key | Cache file path or Stasis key |
| TTL | Configured TTL or "disabled" |
| Custom adapter | Adapter class name or "— (default)" |
| Entry status | `Valid (N manifest(s) cached)` or `Empty / expired` |

**Exit codes:** `0` always.

---

## `locator:cache:clear`

Clears the discovery cache, targeting the correct backend (file-based or Stasis-backed) automatically via reflection on the running Locator singleton.

**Usage:**

```bash
wingman locator:cache:clear
```

**Exit codes:** `0` on success or when caching is disabled or no manager exists, `1` if clearing failed.

**Notes:**

- If caching is disabled on the active Locator instance, the command reports that fact and exits cleanly.
- If the cache manager has not been initialised yet (i.e., no discovery has been run since the last clear), the command reports that and exits cleanly.
