<?php
    /*/
     * Project Name:    Wingman — Locator — Manifest Repository Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\Exceptions\ManifestOverwriteException;
    use Wingman\Locator\Objects\Manifest;
    use Wingman\Locator\Objects\ManifestRepository;

    /**
     * Tests for the ManifestRepository class, covering add, retrieval, duplication guards and hydration.
     */
    class ManifestRepositoryTest extends Test {

        /**
         * Builds a Manifest for a given namespace and path.
         * @param string $namespace The namespace.
         * @param string $sourcePath The source path.
         * @return Manifest The manifest.
         */
        private function makeManifest (string $namespace, string $sourcePath) : Manifest {
            return Manifest::from(
                ["namespace" => $namespace, "aliases" => [], "symbols" => [], "virtuals" => [], "settings" => [], "namespaceAliases" => []],
                $sourcePath
            );
        }

        #[Define(
            name: "add() — Manifest Is Stored",
            description: "After add(), get() by path returns the saved manifest."
        )]
        public function testAddManifestIsStored () : void {
            $repo = new ManifestRepository();
            $manifest = $this->makeManifest("App", "/var/www/app/locator.manifest");

            $repo->add($manifest);

            $stored = $repo->get("/var/www/app/locator.manifest");
            $this->assertTrue($stored instanceof Manifest, "get() should return the stored Manifest.");
            $this->assertTrue($stored->getNamespace() === "App", "Stored manifest should have namespace 'App'.");
        }

        #[Define(
            name: "add() — Returns Self",
            description: "add() returns the same repository instance."
        )]
        public function testAddReturnsSelf () : void {
            $repo = new ManifestRepository();
            $manifest = $this->makeManifest("App", "/tmp/a.manifest");

            $result = $repo->add($manifest);
            $this->assertTrue($result === $repo, "add() should return the same instance.");
        }

        #[Define(
            name: "add() — Duplicate Path Throws",
            description: "Adding a second manifest with the same source path throws ManifestOverwriteException."
        )]
        public function testAddDuplicatePathThrows () : void {
            $repo = new ManifestRepository();
            $repo->add($this->makeManifest("App", "/tmp/locator.manifest"));

            $thrown = false;
            try {
                $repo->add($this->makeManifest("Other", "/tmp/locator.manifest"));
            }
            catch (ManifestOverwriteException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding two manifests with the same path should throw ManifestOverwriteException.");
        }

        #[Define(
            name: "get() — By Index",
            description: "get() with an integer index returns the manifest at that position."
        )]
        public function testGetByIndex () : void {
            $repo = new ManifestRepository();
            $repo->add($this->makeManifest("First", "/tmp/first.manifest"));
            $repo->add($this->makeManifest("Second", "/tmp/second.manifest"));

            $manifest = $repo->get(0);
            $this->assertTrue($manifest instanceof Manifest, "get(0) should return a Manifest.");
            $this->assertTrue($manifest->getNamespace() === "First", "First manifest added should be at index 0.");
        }

        #[Define(
            name: "get() — Unknown Path Returns Null",
            description: "get() returns null for a path that was never added."
        )]
        public function testGetUnknownPathReturnsNull () : void {
            $repo = new ManifestRepository();

            $this->assertTrue($repo->get("/nonexistent.manifest") === null, "get() should return null for an unknown path.");
        }

        #[Define(
            name: "get() — Unknown Index Returns Null",
            description: "get() returns null when the integer index is out of bounds."
        )]
        public function testGetUnknownIndexReturnsNull () : void {
            $repo = new ManifestRepository();

            $this->assertTrue($repo->get(99) === null, "get() should return null for an out-of-bounds index.");
        }

        #[Define(
            name: "getAll() — Returns All Manifests",
            description: "getAll() returns every manifest that was added, in insertion order."
        )]
        public function testGetAllReturnsAllManifests () : void {
            $repo = new ManifestRepository();
            $repo->add($this->makeManifest("A", "/tmp/a.manifest"));
            $repo->add($this->makeManifest("B", "/tmp/b.manifest"));

            $all = $repo->getAll();
            $this->assertTrue(count($all) === 2, "getAll() should return 2 manifests.");
        }

        #[Define(
            name: "getAllPaths() — Returns All Paths",
            description: "getAllPaths() returns the source paths of all added manifests."
        )]
        public function testGetAllPathsReturnsAllPaths () : void {
            $repo = new ManifestRepository();
            $repo->add($this->makeManifest("A", "/tmp/a.manifest"));
            $repo->add($this->makeManifest("B", "/tmp/b.manifest"));

            $paths = $repo->getAllPaths();
            $this->assertTrue(in_array("/tmp/a.manifest", $paths), "/tmp/a.manifest should be in getAllPaths().");
            $this->assertTrue(in_array("/tmp/b.manifest", $paths), "/tmp/b.manifest should be in getAllPaths().");
        }

        #[Define(
            name: "dehydrate() — Returns Serialisable Array",
            description: "dehydrate() returns an array where every element has a 'namespace' key."
        )]
        public function testDehydrateReturnsSerializableArray () : void {
            $repo = new ManifestRepository();
            $repo->add($this->makeManifest("App", "/tmp/locator.manifest"));

            $data = $repo->dehydrate();
            $this->assertTrue(is_array($data), "dehydrate() should return an array.");
            $this->assertTrue(count($data) === 1, "One entry for one manifest.");
            $this->assertTrue(isset($data[0]["namespace"]), "Each entry should contain a 'namespace' key.");
        }

        #[Define(
            name: "Multiple Namespaces — Stored Independently",
            description: "Two manifests with different namespaces can coexist and each retrieve correctly."
        )]
        public function testMultipleNamespacesStoredIndependently () : void {
            $repo = new ManifestRepository();
            $repo->add($this->makeManifest("Alpha", "/tmp/alpha.manifest"));
            $repo->add($this->makeManifest("Beta", "/tmp/beta.manifest"));

            $this->assertTrue($repo->get("/tmp/alpha.manifest")->getNamespace() === "Alpha", "Alpha namespace mismatch.");
            $this->assertTrue($repo->get("/tmp/beta.manifest")->getNamespace() === "Beta", "Beta namespace mismatch.");
        }
    }
?>
