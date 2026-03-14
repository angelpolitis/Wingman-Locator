# Testing Guide

Locator is designed for testability. This guide covers: injecting mock locators, isolating the `Path` facade, writing integration tests with temporary manifests, and resetting the singleton between tests.

---

## Core Principle: Program to the Interface

All code that resolves paths should depend on `LocatorInterface`, not the concrete `Locator` class. This makes any resolution logic trivially mockable.

```php
use Wingman\Locator\Interfaces\LocatorInterface;

class MyService {
    public function __construct (private LocatorInterface $locator) {}

    public function getConfigPath () : string {
        return $this->locator->getPathFor('@App/config/app.php');
    }
}
```

In tests:

```php
$mock = new class implements LocatorInterface {
    public function getPathFor (string $expr) : string { return "/mock/$expr"; }
    public function getPathTo (string $expr) : ?string { return null; }
    // ... implement remaining interface methods
};

$service = new MyService($mock);
```

---

## Resetting the Singleton

Many features rely on the global `Locator::get()` singleton. Always reset it in `tearDown()` to prevent test bleed.

```php
public function tearDown () : void {
    Locator::setGlobal(null);
}
```

After `setGlobal(null)`, the next call to `Locator::get()` creates a fresh instance as if the application had just booted.

---

## Injecting Into the Path Facade

`Path` delegates to `Locator::get()`. To isolate it from the real filesystem, inject a mock:

```php
use Wingman\Locator\Facades\Path;
use Wingman\Locator\Interfaces\LocatorInterface;

public function setUp () : void {
    Path::setLocator(new class implements LocatorInterface {
        public function getPathFor (string $expr) : string { return "/mock/$expr"; }
        public function getPathTo (string $expr) : ?string { return "/mock/$expr"; }
        // ...
    });
}

public function tearDown () : void {
    Path::setLocator(null);   // restore singleton fallback
    Locator::setGlobal(null); // reset the singleton too
}
```

---

## Injecting Into the Singleton

When the code under test calls `Locator::get()` directly (not via dependency injection), inject a mock singleton before the test runs:

```php
use Wingman\Locator\Locator;

public function setUp () : void {
    $mock = new class implements LocatorInterface { /* ... */ };
    Locator::setGlobal($mock);
}

public function tearDown () : void {
    Locator::setGlobal(null);
}
```

---

## Writing an Integration Test With a Temporary Manifest

For tests that need a real `Locator` instance with a controlled set of namespaces, plant a `locator.manifest` in a temporary directory and point the Locator at it.

```php
public function setUp () : void {
    $DS = DIRECTORY_SEPARATOR;

    // 1. Create a temp directory with a minimal manifest.
    $this->tempDir = sys_get_temp_dir() . $DS . 'locator_test_' . uniqid();
    mkdir($this->tempDir, 0755, true);

    file_put_contents(
        $this->tempDir . $DS . 'locator.manifest',
        json_encode([
            'namespace' => 'TestApp',
            'symbols'   => ['src' => $this->tempDir],
        ])
    );

    // 2. Override DOCUMENT_ROOT so the auto-discovery picks up the temp dir.
    $_SERVER['DOCUMENT_ROOT'] = $this->tempDir;
}

public function tearDown () : void {
    @unlink($this->tempDir . '/locator.manifest');
    @rmdir($this->tempDir);
    unset($_SERVER['DOCUMENT_ROOT']);
    Locator::setGlobal(null);
}

public function testOwnNamespaceIsDiscovered () : void {
    $locator = new Locator(['locator.caching.enabled' => false]);

    $namespaces = array_map(
        fn ($m) => $m->getNamespace(),
        $locator->getManifestRepository()->getAll()
    );

    $this->assertTrue(in_array('TestApp', $namespaces, true));
}
```

---

## Disabling Caching in Tests

Always disable caching in unit and integration tests to ensure a fresh state per test run and avoid Cacher entries from one test affecting another:

```php
$locator = new Locator(['locator.caching.enabled' => false]);
```

If you do use a Cacher-backed Locator in an integration test, clear the entry in `tearDown()`:

```php
if (class_exists(\Wingman\Stasis\Cacher::class)) {
    (new \Wingman\Stasis\Cacher())->delete(
        \Wingman\Locator\Bridge\Stasis\CacheManager::CACHE_KEY
    );
}
```

---

## Testing the CacheManager Directly

The file-based `CacheManager` can be exercised in complete isolation using a temporary file:

```php
use Wingman\Locator\CacheManager;
use Wingman\Locator\Objects\Manifest;

$cacheFile = sys_get_temp_dir() . '/locator_test.php';
$manager = new CacheManager($cacheFile, 0);

$manifest = Manifest::from(['namespace' => 'Test'], '/tmp/locator.manifest');
$manager->save([$manifest], []);

$data = $manager->load();
$this->assertSame('Test', $data['manifests'][0]['namespace']);

$manager->clear();
```

---

## Testing the `Asserter`

`Asserter` is a pure, side-effect-free static utility. Test it with real temporary filesystem resources — no mocking required.

```php
use Wingman\Locator\Asserter;
use Wingman\Locator\Exceptions\NonexistentDirectoryException;

$this->expectException(NonexistentDirectoryException::class);
Asserter::requireDirectoryAt('/path/that/does/not/exist');
```

---

## Test Patterns Used in This Package

The Locator test suite uses [Wingman Argus](https://github.com/angelpolitis/Wingman-Argus) as its test framework and follows these conventions:

- **`#[Define(name:..., description:...)]`** — annotates each test method with a human-readable name.
- **`setUp()` / `tearDown()`** — always reset `Locator::setGlobal(null)` and `Path::setLocator(null)`.
- **Anonymous classes** — inline `LocatorInterface` mocks avoid creating extra files; they are defined directly inside the test method or `setUp()`.
- **`ob_start()` / `ob_end_clean()`** — console commands print to stdout; capturing output avoids polluting test output.
- **`sys_get_temp_dir()`** — used for all temporary files and directories to guarantee cross-platform compatibility.
