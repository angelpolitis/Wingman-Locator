<?php
    /*/
     * Project Name:    Wingman — Locator — Resolution Result Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 12 2026
     * Last Modified:   Mar 12 2026
    /*/

    # Use the Locator.Tests namespace.
    namespace Wingman\Locator\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Test;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;

    /**
     * Tests for the ResolutionResult value object, covering continuation, terminal and accessor behaviours.
     */
    class ResolutionResultTest extends Test {

        /**
         * Returns a minimal ResolutionContext for use in tests.
         * @return ResolutionContext The context.
         */
        private function makeContext () : ResolutionContext {
            return ResolutionContext::create(NamespaceManager::DEFAULT_NAMESPACE);
        }

        #[Define(
            name: "continue() — Is Not Terminal",
            description: "A result created with continue() returns false from isTerminal()."
        )]
        public function testContinueIsNotTerminal () : void {
            $result = ResolutionResult::continue("/some/path", $this->makeContext());

            $this->assertTrue(!$result->isTerminal(), "A continuation result should not be terminal.");
        }

        #[Define(
            name: "terminal() — Is Terminal",
            description: "A result created with terminal() returns true from isTerminal()."
        )]
        public function testTerminalIsTerminal () : void {
            $result = ResolutionResult::terminal("/resolved/resource.php", $this->makeContext());

            $this->assertTrue($result->isTerminal(), "A terminal result should report isTerminal() as true.");
        }

        #[Define(
            name: "continue() — getPath() Returns Value",
            description: "getPath() on a continuation result returns the path passed to continue()."
        )]
        public function testContinueGetPathReturnsValue () : void {
            $result = ResolutionResult::continue("/some/path", $this->makeContext());

            $this->assertTrue($result->getPath() === "/some/path", "getPath() should return the value given to continue().");
        }

        #[Define(
            name: "terminal() — getResource() Returns Value",
            description: "getResource() on a terminal result returns the resource passed to terminal()."
        )]
        public function testTerminalGetResourceReturnsValue () : void {
            $result = ResolutionResult::terminal("/resolved/resource.php", $this->makeContext());

            $this->assertTrue($result->getResource() === "/resolved/resource.php", "getResource() should return the terminal value.");
        }

        #[Define(
            name: "getContext() — Returns Stored Context",
            description: "getContext() returns the same ResolutionContext instance that was passed in."
        )]
        public function testGetContextReturnsStoredContext () : void {
            $context = $this->makeContext();
            $result = ResolutionResult::continue("/path", $context);

            $this->assertTrue($result->getContext() === $context, "getContext() should return the exact context passed in.");
        }

        #[Define(
            name: "continue() — PathExpression Object Is Preserved",
            description: "When a PathExpression is passed to continue(), getPath() returns the same PathExpression."
        )]
        public function testContinuePreservesPathExpressionObject () : void {
            $expr = \Wingman\Locator\Objects\PathExpression::from("@App/Models");
            $result = ResolutionResult::continue($expr, $this->makeContext());

            $this->assertTrue($result->getPath() === $expr, "getPath() should return the original PathExpression instance.");
        }

        #[Define(
            name: "terminal() — Null Resource Is Accepted",
            description: "terminal() accepts null as a resource without throwing."
        )]
        public function testTerminalNullResourceIsAccepted () : void {
            $result = ResolutionResult::terminal(null, $this->makeContext());

            $this->assertTrue($result->isTerminal(), "A terminal null result should still report isTerminal() as true.");
            $this->assertTrue($result->getResource() === null, "getResource() should return null.");
        }
    }
?>
