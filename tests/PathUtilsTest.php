<?php
    /*/
     * Project Name:    Wingman — Locator — PathUtils Tests
     * Created by:      Angel Politis
     * Creation Date:   Feb 23 2026
     * Last Modified:   Feb 23 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Test;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Locator\PathUtils;
    use Wingman\Locator\Exceptions\PathTraversalException;

    /**
     * Tests for the PathUtils static utility class.
     */
    class PathUtilsTest extends Test {

        #[Define(
            name: "Path Normalisation",
            description: "Tests that paths are correctly collapsed and directory separators are standardised."
        )]
        public function testNormalisation() : void {
            $testCases = [
                ['/var/www/../www/./html//', '/var/www/html'],
                ['C:\\Windows\\System32/../', 'C:\Windows'],
                ['relative/path/./to/something/..', 'relative/path/to'],
                ['//network-share/path//file.txt', '//network-share/path/file.txt'],
            ];

            foreach ($testCases as [$input, $expected]) {
                $result = PathUtils::normalise($input);
                // Note: normalise() uses DIRECTORY_SEPARATOR internally, 
                // so we 'fix' expected to match the OS environment.
                $expected = PathUtils::fix($expected);
                
                $this->assertTrue(
                    $result === $expected,
                    "Failed normalising '$input'. Expected '$expected', got '$result'."
                );
            }
        }

        #[Define(
            name: "Path Clamping / Strict Mode",
            description: "Ensures resolvePath/clamp throws an exception when attempting to escape the root."
        )]
        public function testClampStrict() : void {
            $root = '/var/www/html';
            $maliciousPath = '../html_backups/config.php';

            $thrown = false;
            try {
                PathUtils::clamp($root, $maliciousPath, true);
            } catch (PathTraversalException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Expected PathTraversalException for escaping path '$maliciousPath'.");
        }

        #[Define(
            name: "Path Clamping / Sibling Directory Exploit",
            description: "Ensures /var/www/html cannot access /var/www/html_data via string prefix matching."
        )]
        public function testClampSiblingExploit() : void {
            $root = '/var/www/html';
            $siblingPath = '../html_data/secret.txt';

            // Even though html_data starts with html, the separator check should fail.
            $result = PathUtils::clamp($root, $siblingPath, false);

            $this->assertTrue(
                $result === PathUtils::normalise($root),
                "Clamp failed to catch sibling directory exploit. Result was: $result"
            );
        }

        #[Define(
            name: "Windows Absolute Path Detection",
            description: "Tests cross-platform absolute path detection including Windows drive letters and UNC paths."
        )]
        public function testIsAbsolutePath() : void {
            $this->assertTrue(PathUtils::isAbsolutePath('/unix/path'), "Unix path should be absolute.");
            $this->assertTrue(PathUtils::isAbsolutePath('C:\\Windows'), "Windows drive path should be absolute.");
            $this->assertTrue(PathUtils::isAbsolutePath('\\\\Server\\Share'), "UNC path should be absolute.");
            $this->assertTrue(PathUtils::isAbsolutePath('/C:/Windows'), "Leading-slash Windows path should be absolute.");
            
            $this->assertTrue(!PathUtils::isAbsolutePath('relative/path'), "Relative path should not be absolute.");
        }

        #[Define(
            name: "URL and Stream Detection",
            description: "Tests detection of various stream wrappers and URL schemes."
        )]
        public function testDetectionMethods() : void {
            $this->assertTrue(PathUtils::isURL('https://google.com'), "HTTPS should be detected as URL.");
            $this->assertTrue(PathUtils::isURL('//protocol-relative.com'), "Protocol-relative should be detected as URL.");
            $this->assertTrue(PathUtils::isPHPStream('php://input'), "php://input should be detected as PHP stream.");
            $this->assertTrue(PathUtils::isPHPStream('php://filter/read=convert.base64-encode/resource=foo'), "Complex PHP stream should be detected.");
            $this->assertTrue(PathUtils::isDataURL('data:text/plain;base64,SGVsbG8='), "Data URI should be detected.");
        }

        #[Define(
            name: "Path Joining",
            description: "Tests that joining fragments handles separators correctly without duplication."
        )]
        public function testPathJoining() : void {
            $result = PathUtils::join('root/', '/subdir/', '/file.php');
            $expected = PathUtils::fix('root/subdir/file.php');

            $this->assertTrue(
                $result === $expected,
                "Path joining failed. Got '$result', expected '$expected'."
            );
        }
    }
?>