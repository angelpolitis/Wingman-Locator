<?php
    /**
     * Project Name:    Wingman — Locator — Asserter Tests
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
    use Wingman\Locator\Asserter;
    use Wingman\Locator\Exceptions\NonexistentDirectoryException;
    use Wingman\Locator\Exceptions\NonexistentFileException;
    use Wingman\Locator\Exceptions\NotADirectoryException;
    use Wingman\Locator\Exceptions\NotAFileException;

    /**
     * Isolated unit tests for the Asserter static utility class.
     * Covers every public method: requireDirectoryAt(), requireFileAt(), and isDirectoryEmpty().
     * Both the happy path and every exception variant are exercised.
     * @package Wingman\Locator\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AsserterTest extends Test {
        /**
         * A temporary directory created per test run for filesystem interaction.
         * @var string
         */
        private string $tempDir;

        /**
         * A temporary file created per test run for filesystem interaction.
         * @var string
         */
        private string $tempFile;

        /**
         * Creates a fresh temporary directory and file before each test.
         */
        public function setUp () : void {
            $DS = DIRECTORY_SEPARATOR;
            $this->tempDir = sys_get_temp_dir() . $DS . "wingman_asserter_" . uniqid();
            mkdir($this->tempDir, 0755, true);

            $this->tempFile = $this->tempDir . $DS . "asserter_test.txt";
            file_put_contents($this->tempFile, "test");
        }

        /**
         * Removes the temporary directory and all its contents after each test.
         */
        public function tearDown () : void {
            @unlink($this->tempFile);
            @rmdir($this->tempDir);
        }

        // ─── requireDirectoryAt ───────────────────────────────────────────────────

        #[Group("Utilities")]
        #[Define(
            name: "requireDirectoryAt() — Passes For Existing Directory",
            description: "No exception is thrown when the path points to an existing directory."
        )]
        public function testRequireDirectoryAtPassesForExistingDirectory () : void {
            $thrown = false;
            try {
                Asserter::requireDirectoryAt($this->tempDir);
            } catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "requireDirectoryAt() should not throw for a valid directory.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "requireDirectoryAt() — Throws For Non-Existent Path",
            description: "NonexistentDirectoryException is thrown when the path does not exist on the filesystem."
        )]
        public function testRequireDirectoryAtThrowsForNonExistentPath () : void {
            $thrown = false;
            try {
                Asserter::requireDirectoryAt("/this/path/cannot/possibly/exist_xyzzy");
            } catch (NonexistentDirectoryException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "requireDirectoryAt() should throw NonexistentDirectoryException for a non-existent path.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "requireDirectoryAt() — Throws For File Path",
            description: "NotADirectoryException is thrown when the path points to a file rather than a directory."
        )]
        public function testRequireDirectoryAtThrowsForFilePath () : void {
            $thrown = false;
            try {
                Asserter::requireDirectoryAt($this->tempFile);
            } catch (NotADirectoryException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "requireDirectoryAt() should throw NotADirectoryException when the path is a file.");
        }

        // ─── requireFileAt ────────────────────────────────────────────────────────

        #[Group("Utilities")]
        #[Define(
            name: "requireFileAt() — Passes For Existing File",
            description: "No exception is thrown when the path points to an existing readable file."
        )]
        public function testRequireFileAtPassesForExistingFile () : void {
            $thrown = false;
            try {
                Asserter::requireFileAt($this->tempFile);
            } catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "requireFileAt() should not throw for a valid file.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "requireFileAt() — Throws For Non-Existent Path",
            description: "NonexistentFileException is thrown when the path does not exist on the filesystem."
        )]
        public function testRequireFileAtThrowsForNonExistentPath () : void {
            $thrown = false;
            try {
                Asserter::requireFileAt("/this/file/cannot/possibly/exist_xyzzy.txt");
            } catch (NonexistentFileException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "requireFileAt() should throw NonexistentFileException for a non-existent path.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "requireFileAt() — Throws For Directory Path",
            description: "NotAFileException is thrown when the path points to a directory rather than a file."
        )]
        public function testRequireFileAtThrowsForDirectoryPath () : void {
            $thrown = false;
            try {
                Asserter::requireFileAt($this->tempDir);
            } catch (NotAFileException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "requireFileAt() should throw NotAFileException when the path is a directory.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "requireFileAt() — Skips Check For URLs",
            description: "requireFileAt() returns without throwing when given a URL-style path since URL resources are not validated as local files."
        )]
        public function testRequireFileAtSkipsCheckForUrls () : void {
            $thrown = false;
            try {
                Asserter::requireFileAt("https://example.com/resource.txt");
            } catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "requireFileAt() should not throw for a URL-style path.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "requireFileAt() — Skips Check For PHP Streams",
            description: "requireFileAt() returns without throwing for PHP stream wrappers such as php://input."
        )]
        public function testRequireFileAtSkipsCheckForPhpStreams () : void {
            $thrown = false;
            try {
                Asserter::requireFileAt("php://input");
            } catch (\Throwable $e) {
                $thrown = true;
            }

            $this->assertTrue(!$thrown, "requireFileAt() should not throw for a PHP stream path.");
        }

        // ─── isDirectoryEmpty ─────────────────────────────────────────────────────

        #[Group("Utilities")]
        #[Define(
            name: "isDirectoryEmpty() — Returns False For Non-Empty Directory",
            description: "isDirectoryEmpty() returns false when the directory contains at least one file."
        )]
        public function testIsDirectoryEmptyReturnsFalseForNonEmptyDirectory () : void {
            $result = Asserter::isDirectoryEmpty($this->tempDir);

            $this->assertTrue(!$result, "isDirectoryEmpty() should return false when the directory contains a file.");
        }

        #[Group("Utilities")]
        #[Define(
            name: "isDirectoryEmpty() — Returns True For Empty Directory",
            description: "isDirectoryEmpty() returns true after all files in the directory are removed."
        )]
        public function testIsDirectoryEmptyReturnsTrueForEmptyDirectory () : void {
            @unlink($this->tempFile);

            $result = Asserter::isDirectoryEmpty($this->tempDir);

            $this->assertTrue($result, "isDirectoryEmpty() should return true when the directory contains no files.");

            file_put_contents($this->tempFile, "test");
        }
    }
?>