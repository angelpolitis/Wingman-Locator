<?php
    /*/
     * Project Name:    Wingman — Locator — PathRootTypeTest Tests
     * Created by:      Angel Politis
     * Creation Date:   Feb 23 2026
     * Last Modified:   Feb 23 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    use Wingman\Argus\Test;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\PathUtils;

    /**
     * Tests for the PathRootType enum strategy and detection logic.
     */
    class PathRootTypeTest extends Test {

        #[Define(
            name: "Drive Path Detection",
            description: "Tests that Windows-style drive paths are correctly identified and parsed."
        )]
        public function testDriveDetection() : void {
            $path = 'C:\Windows\System32';
            [$type, $arg, $relative] = PathRootType::detect($path);

            $this->assertTrue($type === PathRootType::DRIVE, "Expected DRIVE type for '$path'.");
            $this->assertTrue($arg === 'C', "Expected drive argument 'C', got '$arg'.");
            // PathUtils::fix will ensure separators match DIRECTORY_SEPARATOR
            $this->assertTrue($relative === PathUtils::fix('Windows\System32'), "Relative mismatch: $relative");
        }

        #[Define(
            name: "Variable Path Detection",
            description: "Tests that bracketed variables like @{os} are correctly extracted."
        )]
        public function testVariableDetection() : void {
            $path = '@{package}/src/Locator';
            [$type, $arg, $relative] = PathRootType::detect($path);

            $this->assertTrue($type === PathRootType::VARIABLE, "Expected VARIABLE type.");
            $this->assertTrue($arg === 'package', "Expected variable 'package', got '$arg'.");
            $this->assertTrue($relative === PathUtils::fix('src/Locator'), "Relative path mismatch.");
        }

        #[Define(
            name: "Namespace Notation Detection",
            description: "Tests both @path and colon:notation for namespaces."
        )]
        public function testNamespaceDetection() : void {
            # Test @path notation
            $path1 = '@App/Controllers/User';
            [$type1, $arg1, $relative1] = PathRootType::detect($path1);
            $this->assertTrue($type1 === PathRootType::NAMESPACE, "Failed @ notation.");
            $this->assertTrue($arg1 === 'App', "Failed to extract namespace 'App'.");

            # Test colon notation
            $path2 = 'Framework:log/error.txt';
            [$type2, $arg2, $relative2] = PathRootType::detect($path2);
            $this->assertTrue($type2 === PathRootType::NAMESPACE, "Failed colon notation.");
            $this->assertTrue($arg2 === 'Framework', "Failed to extract namespace 'Framework'.");
        }

        #[Define(
            name: "Absolute vs Explicit Relative",
            description: "Ensures /path is Absolute but ./path is Explicit Relative."
        )]
        public function testAbsoluteAndExplicitRelative() : void {
            # Absolute
            $abs = '/usr/bin/php';
            [$typeA] = PathRootType::detect($abs);
            $this->assertTrue($typeA === PathRootType::ABSOLUTE, "Failed Absolute check.");

            # Explicit Relative
            $rel = './local/config';
            [$typeR, $argR, $relativeR] = PathRootType::detect($rel);
            $this->assertTrue($typeR === PathRootType::RELATIVE_EXPLICIT, "Failed Explicit Relative check.");
            $this->assertTrue($relativeR === PathUtils::fix('local/config'), "Relative path should strip './'.");
        }

        #[Define(
            name: "URL Protection",
            description: "Ensures URLs are not misidentified as Namespaces."
        )]
        public function testUrlExclusion() : void {
            // http: is not a Wingman namespace, it's a protocol.
            $url = 'https://angelpolitis.com';
            [$type] = PathRootType::detect($url);

            // It should fall through to RELATIVE_IMPLICIT because it's not a local path.
            // Or if you handle URLs specifically, ensure it doesn't match NAMESPACE.
            $this->assertTrue($type !== PathRootType::NAMESPACE, "URL scheme identified as Namespace.");
        }

        #[Define(
            name: "IsRelative Helper",
            description: "Verifies the isRelative() method correctly identifies relative types."
        )]
        public function testIsRelativeHelper() : void {
            $this->assertTrue(PathRootType::RELATIVE_EXPLICIT->isRelative());
            $this->assertTrue(PathRootType::RELATIVE_IMPLICIT->isRelative());
            $this->assertTrue(!PathRootType::ABSOLUTE->isRelative());
            $this->assertTrue(!PathRootType::VARIABLE->isRelative());
        }
    }
?>