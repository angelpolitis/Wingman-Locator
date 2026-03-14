<?php
    /*/
     * Project Name:    Wingman — Locator — Discovery Profile Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\Objects\DiscoveryProfile;

    /**
     * Tests for the DiscoveryProfile class, covering depth limits, hidden-path filtering, and glob pattern matching.
     */
    class DiscoveryProfileTest extends Test {

        #[Define(
            name: "Depth — Within Limit",
            description: "Paths at or below the configured maxDepth are accepted."
        )]
        public function testDepthWithinLimit () : void {
            $profile = DiscoveryProfile::from(["depth" => 2]);

            $this->assertTrue($profile->validate("a/b/file.php", 2), "Depth equal to maxDepth should be accepted.");
            $this->assertTrue($profile->validate("a/file.php", 1), "Depth below maxDepth should be accepted.");
            $this->assertTrue($profile->validate("file.php", 0), "Root-level path should always pass the depth check.");
        }

        #[Define(
            name: "Depth — Exceeds Limit",
            description: "Paths deeper than maxDepth are rejected."
        )]
        public function testDepthExceedsLimit () : void {
            $profile = DiscoveryProfile::from(["depth" => 1]);

            $this->assertTrue(!$profile->validate("a/b/file.php", 2), "Depth of 2 should be rejected when maxDepth is 1.");
        }

        #[Define(
            name: "Depth — Unlimited",
            description: "When depth is -1, paths at any depth are accepted."
        )]
        public function testUnlimitedDepth () : void {
            $profile = DiscoveryProfile::from(["depth" => -1]);

            $this->assertTrue($profile->validate("a/b/c/d/e/file.php", 5), "Any depth should be accepted when maxDepth is -1.");
        }

        #[Define(
            name: "OnlyRoot — Root Passes",
            description: "When onlyRoot is true, paths at depth 0 are still accepted."
        )]
        public function testOnlyRootAcceptsDepthZero () : void {
            $profile = DiscoveryProfile::from(["onlyRoot" => true]);

            $this->assertTrue($profile->validate("file.php", 0), "Root-level path should be accepted when onlyRoot is true.");
        }

        #[Define(
            name: "OnlyRoot — Sub-Path Rejected",
            description: "When onlyRoot is true, paths at depth 1 or greater are rejected."
        )]
        public function testOnlyRootRejectsSubPaths () : void {
            $profile = DiscoveryProfile::from(["onlyRoot" => true]);

            $this->assertTrue(!$profile->validate("subdir/file.php", 1), "Sub-paths should be rejected when onlyRoot is true.");
        }

        #[Define(
            name: "Hidden Files — Rejected By Default",
            description: "Paths containing hidden segments (leading dot) are rejected when omitHidden is true."
        )]
        public function testHiddenFilesRejectedByDefault () : void {
            $profile = DiscoveryProfile::from([]);

            $this->assertTrue(!$profile->validate(".git/config", 1), ".git directory should be treated as hidden.");
            $this->assertTrue(!$profile->validate("src/.hidden/file.php", 2), "Nested hidden directory should be rejected.");
            $this->assertTrue(!$profile->validate(".env", 0), "Hidden file at root should be rejected.");
        }

        #[Define(
            name: "Hidden Files — Allowed When Disabled",
            description: "Hidden paths pass validation when omitHidden is explicitly set to false."
        )]
        public function testHiddenFilesAllowedWhenDisabled () : void {
            $profile = DiscoveryProfile::from(["omitHidden" => false]);

            $this->assertTrue($profile->validate(".git/config", 1), ".git should be allowed when omitHidden is false.");
            $this->assertTrue($profile->validate(".env", 0), "Hidden root file should be allowed when omitHidden is false.");
        }

        #[Define(
            name: "Include — Matching Path Passes",
            description: "When an include list is set, a path matching at least one pattern is accepted."
        )]
        public function testIncludeMatchingPathPasses () : void {
            $profile = DiscoveryProfile::from(["include" => ["src/**"]]);

            $this->assertTrue($profile->validate("src/Controllers/User.php", 2), "Path inside src/ should be accepted by include pattern.");
        }

        #[Define(
            name: "Include — Non-Matching Path Is Rejected",
            description: "When an include list is set, a path that matches none of the patterns is rejected."
        )]
        public function testIncludeNonMatchingPathRejected () : void {
            $profile = DiscoveryProfile::from(["include" => ["src/**"]]);

            $this->assertTrue(!$profile->validate("vendor/autoload.php", 1), "Path outside src/ should be rejected by include pattern.");
        }

        #[Define(
            name: "Exclude — Matching Path Is Rejected",
            description: "A path matching any exclude pattern is rejected, regardless of include or depth settings."
        )]
        public function testExcludeMatchingPathRejected () : void {
            $profile = DiscoveryProfile::from(["exclude" => ["vendor/**"]]);

            $this->assertTrue(!$profile->validate("vendor/autoload.php", 1), "vendor/ path should be rejected by exclude pattern.");
        }

        #[Define(
            name: "Exclude — Non-Matching Path Passes",
            description: "A path that does not match any exclude pattern passes validation."
        )]
        public function testExcludeNonMatchingPathPasses () : void {
            $profile = DiscoveryProfile::from(["exclude" => ["vendor/**"]]);

            $this->assertTrue($profile->validate("src/app.php", 1), "src/ path should not be rejected by a vendor/** exclude pattern.");
        }

        #[Define(
            name: "Glob — Single Star Matches Within Segment",
            description: "A single * matches any characters within a path segment but not across separators."
        )]
        public function testGlobSingleStarWithinSegment () : void {
            $profile = DiscoveryProfile::from(["exclude" => ["*.log"]]);

            $this->assertTrue(!$profile->validate("error.log", 0), "*.log should reject error.log.");
            $this->assertTrue($profile->validate("error.php", 0), "*.log should not reject error.php.");
        }

        #[Define(
            name: "Glob — Double Star Crosses Segments",
            description: "** matches any number of path segments at any depth."
        )]
        public function testGlobDoubleStarCrossesSegments () : void {
            $profile = DiscoveryProfile::from(["include" => ["**/Tests/**"]]);

            $this->assertTrue($profile->validate("modules/Locator/Tests/CacheManagerTest.php", 3), "** should match deeply nested Tests/ directory.");
        }

        #[Define(
            name: "Include And Exclude — Exclude Wins",
            description: "When a path satisfies both an include and an exclude pattern, the exclusion takes precedence."
        )]
        public function testExcludeTakesPrecedenceOverInclude () : void {
            $profile = DiscoveryProfile::from([
                "include" => ["src/**"],
                "exclude" => ["src/Legacy/**"]
            ]);

            $this->assertTrue($profile->validate("src/Controllers/User.php", 2), "Non-excluded src/ path should pass.");
            $this->assertTrue(!$profile->validate("src/Legacy/OldClass.php", 2), "Excluded path inside src/ should be rejected.");
        }
    }
?>