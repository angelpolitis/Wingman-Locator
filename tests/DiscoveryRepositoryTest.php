<?php
    /**
     * Project Name:    Wingman — Locator — Discovery Repository Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\DiscoveryRepository;

    /**
     * Tests for the DiscoveryRepository class, covering add/has semantics, key collision resistance and content export.
     */
    class DiscoveryRepositoryTest extends Test {

        /**
         * Returns a DiscoveryProfile using the default settings.
         * @return DiscoveryProfile The default profile.
         */
        private function defaultProfile () : DiscoveryProfile {
            return DiscoveryProfile::from([]);
        }

        #[Group("Discovery")]
        #[Define(
            name: "has() — Returns False For Empty Repo",
            description: "A freshly created repository reports has() as false for any path/profile combination."
        )]
        public function testHasReturnsFalseForEmptyRepo () : void {
            $repo = new DiscoveryRepository();

            $this->assertTrue(!$repo->has("/var/www", $this->defaultProfile()), "Empty repo should return false for any query.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "add() + has() — Round-Trip",
            description: "After add(), has() returns true for the same path and profile."
        )]
        public function testAddHasRoundTrip () : void {
            $repo = new DiscoveryRepository();
            $profile = $this->defaultProfile();

            $repo->add("/var/www", $profile);

            $this->assertTrue($repo->has("/var/www", $profile), "has() should return true for the added path/profile pair.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "add() — Different Profile Not Found",
            description: "has() returns false when the path exists but with a different profile instance."
        )]
        public function testHasReturnsFalseForDifferentProfile () : void {
            $repo = new DiscoveryRepository();
            $profileA = DiscoveryProfile::from(["depth" => 2]);
            $profileB = DiscoveryProfile::from(["depth" => 3]);

            $repo->add("/var/www", $profileA);

            $this->assertTrue(!$repo->has("/var/www", $profileB), "Different profile should not match even for the same path.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "add() — Different Path Not Found",
            description: "has() returns false when the same profile was added under a different path."
        )]
        public function testHasReturnsFalseForDifferentPath () : void {
            $repo = new DiscoveryRepository();
            $profile = $this->defaultProfile();

            $repo->add("/var/www", $profile);

            $this->assertTrue(!$repo->has("/srv/app", $profile), "A different path with the same profile should not match.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "add() — Returns Self",
            description: "add() returns the same repository instance for method chaining."
        )]
        public function testAddReturnsSelf () : void {
            $repo = new DiscoveryRepository();

            $result = $repo->add("/tmp", $this->defaultProfile());
            $this->assertTrue($result === $repo, "add() should return the same instance.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "getAll() — Returns All Entries",
            description: "getAll() returns every path/profile pair added to the repository in order."
        )]
        public function testGetAllReturnsAllEntries () : void {
            $repo = new DiscoveryRepository();

            $repo->add("/path/one", $this->defaultProfile());
            $repo->add("/path/two", DiscoveryProfile::from(["depth" => 1]));

            $all = $repo->getAll();
            $this->assertTrue(count($all) === 2, "getAll() should return 2 entries.");
            $this->assertTrue($all[0]["path"] === "/path/one", "First entry path should be '/path/one'.");
            $this->assertTrue($all[1]["path"] === "/path/two", "Second entry path should be '/path/two'.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "Key Hardening — Pipe In Path Does Not Collide",
            description: "A path containing a pipe character does not produce the same key as a path without it when profiles differ."
        )]
        public function testPipeInPathDoesNotCollide () : void {
            $repo = new DiscoveryRepository();
            $profileA = $this->defaultProfile();
            $profileB = DiscoveryProfile::from(["depth" => 5]);

            $repo->add("/path|extra", $profileA);

            $this->assertTrue(!$repo->has("/path|extra", $profileB), "Keys must not collide when profiles differ even if path contains a pipe.");
            $this->assertTrue(!$repo->has("/path", $profileA), "A different path must not match even if it shares a prefix.");
        }

        #[Group("Discovery")]
        #[Define(
            name: "exportContent() — Returns Array",
            description: "exportContent() returns a non-empty array when at least one entry has been added."
        )]
        public function testExportContentReturnsArray () : void {
            $repo = new DiscoveryRepository();
            $repo->add("/var/www", $this->defaultProfile());

            $exported = $repo->exportContent();
            $this->assertTrue(is_array($exported), "exportContent() should return an array.");
            $this->assertTrue(count($exported) === 1, "Exported content should have one entry.");
            $this->assertTrue(isset($exported[0]["path"]), "Exported entry should have a 'path' key.");
            $this->assertTrue(isset($exported[0]["profile"]), "Exported entry should have a 'profile' key.");
        }
    }
?>