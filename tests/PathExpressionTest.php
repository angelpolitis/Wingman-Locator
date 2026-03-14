<?php
    /**
     * Project Name:    Wingman — Locator — Path Expression Tests
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
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Objects\PathExpression;

    /**
     * Tests for the PathExpression value object, covering parsing, accessors, toString and mutations.
     */
    class PathExpressionTest extends Test {

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Namespace @ Notation",
            description: "from() correctly identifies @Namespace/path as a NAMESPACE root."
        )]
        public function testParseNamespaceAtNotation () : void {
            $expr = PathExpression::from("@App/Controllers/User.php");

            $this->assertTrue($expr->getRootType() === PathRootType::NAMESPACE, "Expected NAMESPACE root type.");
            $this->assertTrue($expr->getRootArg() === "App", "Expected root arg 'App'.");
            $this->assertTrue(str_contains($expr->getRelativePath(), "Controllers"), "Relative path should contain 'Controllers'.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Namespace Colon Notation",
            description: "from() correctly identifies Framework:path as a NAMESPACE root."
        )]
        public function testParseNamespaceColonNotation () : void {
            $expr = PathExpression::from("Framework:log/error.txt");

            $this->assertTrue($expr->getRootType() === PathRootType::NAMESPACE, "Expected NAMESPACE root type.");
            $this->assertTrue($expr->getRootArg() === "Framework", "Expected root arg 'Framework'.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Variable Bracketed",
            description: "from() correctly identifies @{server}/path as a VARIABLE root with the 'server' variable."
        )]
        public function testParseVariableBracketed () : void {
            $expr = PathExpression::from("@{server}/public/index.php");

            $this->assertTrue($expr->getRootType() === PathRootType::VARIABLE, "Expected VARIABLE root type.");
            $this->assertTrue($expr->getVariable() === PathRootVariable::SERVER, "Expected SERVER variable.");
            $this->assertTrue(str_contains($expr->getRelativePath(), "public"), "Relative path should contain 'public'.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Variable CWD",
            description: "from() identifies @{cwd} as a VARIABLE root with the CWD variable."
        )]
        public function testParseVariableCwd () : void {
            $expr = PathExpression::from("@{cwd}/config.php");

            $this->assertTrue($expr->getVariable() === PathRootVariable::CWD, "Expected CWD variable.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Variable Unknown",
            description: "from() stores UNKNOWN for unrecognised variable names."
        )]
        public function testParseVariableUnknown () : void {
            $expr = PathExpression::from("@{nonexistent}/file.php");

            $this->assertTrue($expr->getRootType() === PathRootType::VARIABLE, "Expected VARIABLE root type.");
            $this->assertTrue($expr->getVariable() === PathRootVariable::UNKNOWN, "Expected UNKNOWN variable for unrecognised name.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Absolute Path",
            description: "from() identifies /unix/path as an ABSOLUTE root."
        )]
        public function testParseAbsolutePath () : void {
            $expr = PathExpression::from("/var/www/html/index.php");

            $this->assertTrue($expr->getRootType() === PathRootType::ABSOLUTE, "Expected ABSOLUTE root type.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Explicit Relative",
            description: "from() identifies ./relative as RELATIVE_EXPLICIT and strips the leading dot-slash."
        )]
        public function testParseExplicitRelative () : void {
            $expr = PathExpression::from("./config/app.php");

            $this->assertTrue($expr->getRootType() === PathRootType::RELATIVE_EXPLICIT, "Expected RELATIVE_EXPLICIT root type.");
            $this->assertTrue(!str_starts_with($expr->getRelativePath(), "./"), "Relative path should not retain leading './'.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Implicit Relative",
            description: "from() identifies a bare word path as RELATIVE_IMPLICIT."
        )]
        public function testParseImplicitRelative () : void {
            $expr = PathExpression::from("src/Models/User.php");

            $this->assertTrue($expr->getRootType() === PathRootType::RELATIVE_IMPLICIT, "Expected RELATIVE_IMPLICIT root type.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Parse — Windows Drive",
            description: "from() identifies C:\\Windows\\System32 as a DRIVE root."
        )]
        public function testParseDrivePath () : void {
            $expr = PathExpression::from("C:\\Windows\\System32");

            $this->assertTrue($expr->getRootType() === PathRootType::DRIVE, "Expected DRIVE root type.");
            $this->assertTrue($expr->getRootArg() === "C", "Expected drive letter 'C'.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "Namespace With No Relative Part",
            description: "from() handles a bare namespace (@App) with no relative path; getRelativePath() returns an empty string."
        )]
        public function testNamespaceWithNoRelativePart () : void {
            $expr = PathExpression::from("@App");

            $this->assertTrue($expr->getRootType() === PathRootType::NAMESPACE, "Expected NAMESPACE root type.");
            $this->assertTrue($expr->getRootArg() === "App", "Root arg should be 'App'.");
            $this->assertTrue($expr->getRelativePath() === "", "Relative path should be empty for a bare namespace.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "URL Is Not Parsed As Namespace",
            description: "https://example.com is not identified as a NAMESPACE root (protocol schemes are not Wingman namespaces)."
        )]
        public function testUrlIsNotNamespace () : void {
            $expr = PathExpression::from("https://example.com/path");

            $this->assertTrue($expr->getRootType() !== PathRootType::NAMESPACE, "A URL should not be classified as NAMESPACE.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "normalise() — String Input Returns PathExpression",
            description: "normalise() called with a string returns a PathExpression instance."
        )]
        public function testNormaliseStringReturnsPathExpression () : void {
            $result = PathExpression::normalise("@App/foo");

            $this->assertTrue($result instanceof PathExpression, "normalise() should return a PathExpression for string input.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "normalise() — PathExpression Input Returns Same Instance",
            description: "normalise() called with an existing PathExpression returns the same object."
        )]
        public function testNormaliseSameInstance () : void {
            $expr = PathExpression::from("@App/foo");
            $result = PathExpression::normalise($expr);

            $this->assertTrue($result === $expr, "normalise() should return the same PathExpression instance when given one.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "withRelativePath() — Creates New Instance",
            description: "withRelativePath() returns a new PathExpression with the updated relative path, leaving the original unchanged."
        )]
        public function testWithRelativePathCreatesNewInstance () : void {
            $original = PathExpression::from("@App/old/path.php");
            $modified = $original->withRelativePath("new/path.php");

            $this->assertTrue($modified !== $original, "withRelativePath() should return a new instance.");
            $this->assertTrue($modified->getRelativePath() === "new/path.php", "New instance should have the new relative path.");
            $this->assertTrue(str_contains($original->getRelativePath(), "old"), "Original instance should retain its relative path.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "__toString() — Namespace Expression Roundtrip",
            description: "A NAMESPACE expression serialises back to a recognisable string that re-parses to the same root type and arg."
        )]
        public function testToStringNamespaceRoundtrip () : void {
            $expr = PathExpression::from("@App/Controllers");
            $str = (string) $expr;
            $reparsed = PathExpression::from($str);

            $this->assertTrue($reparsed->getRootType() === PathRootType::NAMESPACE, "Re-parsed type should be NAMESPACE.");
            $this->assertTrue($reparsed->getRootArg() === "App", "Re-parsed root arg should be 'App'.");
        }

        #[Group("Path Expressions")]
        #[Define(
            name: "__toString() — Variable Expression Roundtrip",
            description: "A VARIABLE expression serialises back to a string that re-parses to the same variable."
        )]
        public function testToStringVariableRoundtrip () : void {
            $expr = PathExpression::from("@{cwd}/logs");
            $str = (string) $expr;
            $reparsed = PathExpression::from($str);

            $this->assertTrue($reparsed->getVariable() === PathRootVariable::CWD, "Re-parsed variable should be CWD.");
        }
    }
?>