<?php
    /**
     * Project Name:    Wingman — Locator — Resolvers Tests
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
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Exceptions\UnknownNamespaceException;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\NamespaceObject;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\PathResolutionPipeline;
    use Wingman\Locator\PathUtils;
    use Wingman\Locator\Resolvers\AbsoluteResolver;
    use Wingman\Locator\Resolvers\NamespaceResolver;
    use Wingman\Locator\Resolvers\RelativeResolver;
    use Wingman\Locator\Resolvers\RelativeSegmentResolver;
    use Wingman\Locator\Resolvers\SymbolResolver;
    use Wingman\Locator\Resolvers\VariableResolver;
    use Wingman\Locator\Resolvers\VirtualResolver;

    /**
     * Tests for every Resolver implementation in the Locator package.
     * Covers the happy path, sad path (null / no-op returns) and edge cases for each resolver.
     */
    class ResolversTest extends Test {

        /**
         * Returns a NamespaceManager pre-seeded with an 'App' namespace rooted at /var/www/app.
         * @return NamespaceManager The manager.
         */
        private function makeManager () : NamespaceManager {
            $ns = new NamespaceObject("App", "/var/www/app");
            return new NamespaceManager([$ns]);
        }

        /**
         * Returns a ResolutionContext seeded with the 'App' namespace and a fixed server root.
         * @return ResolutionContext The context.
         */
        private function makeContext (string $namespace = "App") : ResolutionContext {
            return ResolutionContext::create($namespace)
                ->setRoots(["server" => "/var/www", "cwd" => "/var/www", "os" => "/"]);
        }

        // ── RelativeSegmentResolver ────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "RelativeSegmentResolver — Dot Dot Collapsed",
            description: "A relative path containing '..' is collapsed by RelativeSegmentResolver."
        )]
        public function testRelativeSegmentResolverDotDotCollapsed () : void {
            $resolver = new RelativeSegmentResolver();
            $expr = PathExpression::from("@App/src/../Controllers");

            $result = $resolver->resolve($expr, $this->makeContext());

            $this->assertTrue($result instanceof ResolutionResult, "Resolver should return a result when '..' is present.");
            $this->assertTrue(
                !str_contains((string) $result->getPath(), ".."),
                "Resolved path should not contain '..'."
            );
        }

        #[Group("Resolution")]
        #[Define(
            name: "RelativeSegmentResolver — No Segments Returns Null",
            description: "A relative path without any '.' or '..' segments causes the resolver to return null (no-op)."
        )]
        public function testRelativeSegmentResolverNoChangeReturnsNull () : void {
            $resolver = new RelativeSegmentResolver();
            $expr = PathExpression::from("@App/Controllers/User.php");

            $result = $resolver->resolve($expr, $this->makeContext());

            $this->assertTrue($result === null, "Resolver should return null when no relative segments need collapsing.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "RelativeSegmentResolver — Single Dot Stripped",
            description: "A '.' segment in the relative path is stripped correctly."
        )]
        public function testRelativeSegmentResolverSingleDotStripped () : void {
            $resolver = new RelativeSegmentResolver();
            $expr = PathExpression::from("@App/src/./Controllers");

            $result = $resolver->resolve($expr, $this->makeContext());

            $this->assertTrue($result instanceof ResolutionResult, "Resolver should produce a result for a '.' segment.");
            $this->assertTrue(
                !str_contains((string) $result->getPath(), DIRECTORY_SEPARATOR . "." . DIRECTORY_SEPARATOR),
                "Resolved path should not contain a lone '.' segment."
            );
        }

        // ── RelativeResolver ───────────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "RelativeResolver — Explicit Relative Resolved",
            description: "A RELATIVE_EXPLICIT path is joined with the context's relative root."
        )]
        public function testRelativeResolverExplicitRelative () : void {
            $manager = new NamespaceManager();
            $resolver = new RelativeResolver();
            $context = $this->makeContext(NamespaceManager::DEFAULT_NAMESPACE);
            $expr = PathExpression::from("./config.php");

            $result = $resolver->resolve($expr, $context);

            $this->assertTrue($result instanceof ResolutionResult, "Resolver should handle RELATIVE_EXPLICIT paths.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "RelativeResolver — Implicit Relative Resolved",
            description: "A RELATIVE_IMPLICIT path is joined with the context's relative root."
        )]
        public function testRelativeResolverImplicitRelative () : void {
            $resolver = new RelativeResolver();
            $context = ResolutionContext::create(NamespaceManager::DEFAULT_NAMESPACE)
                ->setRoots(["server" => "/var/www", "cwd" => "/var/www", "os" => "/"]);
            $expr = PathExpression::from("config.php");

            $result = $resolver->resolve($expr, $context);

            $this->assertTrue($result instanceof ResolutionResult, "Resolver should handle RELATIVE_IMPLICIT paths.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "RelativeResolver — Non-Relative Returns Null",
            description: "An absolute or namespace-rooted path causes RelativeResolver to return null."
        )]
        public function testRelativeResolverNonRelativeReturnsNull () : void {
            $resolver = new RelativeResolver();
            $expr = PathExpression::from("/absolute/path.php");

            $result = $resolver->resolve($expr, $this->makeContext());

            $this->assertTrue($result === null, "RelativeResolver should return null for a non-relative path.");
        }

        // ── AbsoluteResolver ──────────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "AbsoluteResolver — Prepends Server Root",
            description: "An ABSOLUTE path (/api/config) is joined to the server root from the context."
        )]
        public function testAbsoluteResolverPrependsServerRoot () : void {
            $resolver = new AbsoluteResolver();
            $context = ResolutionContext::create(NamespaceManager::DEFAULT_NAMESPACE)
                ->setRoots(["server" => "/var/www", "cwd" => "/var/www", "os" => "/"]);
            $expr = PathExpression::from("/api/config.php");

            $result = $resolver->resolve($expr, $context);

            $this->assertTrue($result instanceof ResolutionResult, "AbsoluteResolver should produce a result.");
            $resolved = PathUtils::fix((string) $result->getPath());
            $this->assertTrue(str_starts_with($resolved, PathUtils::fix("/var/www")), "Resolved path should begin with the server root.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "AbsoluteResolver — Non-Absolute Returns Null",
            description: "A NAMESPACE or RELATIVE path causes AbsoluteResolver to return null."
        )]
        public function testAbsoluteResolverNonAbsoluteReturnsNull () : void {
            $resolver = new AbsoluteResolver();
            $expr = PathExpression::from("@App/Controllers");

            $result = $resolver->resolve($expr, $this->makeContext());

            $this->assertTrue($result === null, "AbsoluteResolver should return null for non-absolute paths.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "AbsoluteResolver — Already Resolved Is No-Op",
            description: "A path that already begins with the server root is returned unchanged (no loop)."
        )]
        public function testAbsoluteResolverAlreadyResolvedIsNoOp () : void {
            $resolver = new AbsoluteResolver();
            $context = ResolutionContext::create(NamespaceManager::DEFAULT_NAMESPACE)
                ->setRoots(["server" => "/var/www", "cwd" => "/var/www", "os" => "/"]);

            # /var/www/api/route.php is already fully under the server root.
            $expr = PathExpression::from("/var/www/api/route.php");

            $result = $resolver->resolve($expr, $context);

            # The resolver may return null or a result pointing to the same path — it must NOT duplicate the root.
            if ($result !== null) {
                $resolved = PathUtils::fix((string) $result->getPath());
                $serverRoot = PathUtils::fix("/var/www");
                $doubled = $serverRoot . DIRECTORY_SEPARATOR . ltrim($serverRoot, DIRECTORY_SEPARATOR);
                $this->assertTrue(!str_starts_with($resolved, $doubled), "Server root must not be prepended twice.");
            }
            else {
                $this->assertTrue(true, "Returning null is also acceptable for an already-resolved path.");
            }
        }

        // ── NamespaceResolver ─────────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "NamespaceResolver — Known Namespace Resolved",
            description: "A @Namespace/path expression with a registered namespace is resolved to the namespace root."
        )]
        public function testNamespaceResolverKnownNamespaceResolved () : void {
            $manager = $this->makeManager();
            $resolver = new NamespaceResolver($manager);
            $context = $this->makeContext();

            $result = $resolver->resolve("@App/Controllers/User.php", $context);

            $this->assertTrue($result instanceof ResolutionResult, "NamespaceResolver should produce a result for a known namespace.");
            if (!$result instanceof ResolutionResult) return;
            $resolved = PathUtils::fix((string) $result->getPath());
            $this->assertTrue(str_contains($resolved, "app"), "Resolved path should contain the namespace root.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "NamespaceResolver — Unknown Namespace Throws UnknownNamespaceException",
            description: "A @UnknownNs/path expression throws UnknownNamespaceException."
        )]
        public function testNamespaceResolverUnknownNamespaceThrows () : void {
            $manager = $this->makeManager();
            $resolver = new NamespaceResolver($manager);
            $context = $this->makeContext();
            $thrown = false;

            try {
                $resolver->resolve("@Ghost/path.php", $context);
            }
            catch (UnknownNamespaceException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "NamespaceResolver should throw UnknownNamespaceException for an unregistered namespace.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "NamespaceResolver — Non-Namespace Path Returns Null",
            description: "An absolute path is not handled by NamespaceResolver and it returns null."
        )]
        public function testNamespaceResolverNonNamespaceReturnsNull () : void {
            $manager = $this->makeManager();
            $resolver = new NamespaceResolver($manager);

            $result = $resolver->resolve("/absolute/path.php", null);

            $this->assertTrue($result === null, "NamespaceResolver should return null for non-namespace input.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "NamespaceResolver — Namespace With No Relative Path",
            description: "A bare @Namespace expression with no relative part resolves to the namespace root path."
        )]
        public function testNamespaceResolverBareNamespaceResolved () : void {
            $manager = $this->makeManager();
            $resolver = new NamespaceResolver($manager);

            $result = $resolver->resolve("@App", $this->makeContext());

            $this->assertTrue($result instanceof ResolutionResult, "Bare @App should produce a result.");
            if (!$result instanceof ResolutionResult) return;
            $this->assertTrue(
                str_contains(PathUtils::fix((string) $result->getPath()), "app"),
                "Result path should reference the namespace root."
            );
        }

        // ── VariableResolver ──────────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "VariableResolver — SERVER Variable Resolved",
            description: "@{server}/path resolves to a path rooted at the context's server root."
        )]
        public function testVariableResolverServerVariable () : void {
            $manager = $this->makeManager();
            $resolver = new VariableResolver($manager);
            $context = $this->makeContext()
                ->setRoots(["server" => "/var/www", "cwd" => "/var/www", "os" => "/"]);

            $result = $resolver->resolve("@{server}/public", $context);

            $this->assertTrue($result instanceof ResolutionResult, "VariableResolver should resolve @{server}.");
            $resolved = $result->getResource();
            $this->assertTrue(str_contains(PathUtils::fix($resolved), "var"), "Resolved path should be under /var/www.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "VariableResolver — CWD Variable Resolved",
            description: "@{cwd}/path resolves to a path rooted at the context's CWD root."
        )]
        public function testVariableResolverCwdVariable () : void {
            $manager = $this->makeManager();
            $resolver = new VariableResolver($manager);
            $context = ResolutionContext::create("App")
                ->setRoots(["server" => "/var/www", "cwd" => "/home/deploy", "os" => "/"]);

            $result = $resolver->resolve("@{cwd}/logs", $context);

            $this->assertTrue($result instanceof ResolutionResult, "VariableResolver should resolve @{cwd}.");
            $this->assertTrue(str_contains(PathUtils::fix($result->getResource()), "deploy"), "Result should reference the CWD root.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "VariableResolver — Non-Variable Returns Null",
            description: "A NAMESPACE path causes VariableResolver to return null."
        )]
        public function testVariableResolverNonVariableReturnsNull () : void {
            $manager = $this->makeManager();
            $resolver = new VariableResolver($manager);

            $result = $resolver->resolve("@App/Controllers", $this->makeContext());

            $this->assertTrue($result === null, "VariableResolver should return null for non-variable paths.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "VariableResolver — Unknown Variable Throws",
            description: "@{nonexistent}/path throws a RuntimeException because the variable is UNKNOWN."
        )]
        public function testVariableResolverUnknownVariableThrows () : void {
            $manager = $this->makeManager();
            $resolver = new VariableResolver($manager);
            $context = $this->makeContext();
            $thrown = false;

            try {
                $resolver->resolve("@{nonexistent}/path", $context);
            }
            catch (\RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "An unknown variable should throw a RuntimeException.");
        }

        // ── SymbolResolver ────────────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "SymbolResolver — Bounded Syntax %{name}",
            description: "A path containing %{controllers} is replaced with the symbol's target."
        )]
        public function testSymbolResolverBoundedSyntax () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"), null, null, ["controllers" => "src/Controllers"]);
            $manager = new NamespaceManager([$ns]);
            $resolver = new SymbolResolver($manager);
            $context = $this->makeContext();

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/%{controllers}/User.php"), $context);

            $this->assertTrue($result instanceof ResolutionResult, "SymbolResolver should resolve %{controllers}.");
            $resolved = PathUtils::fix((string) $result->getPath());
            $this->assertTrue(!str_contains($resolved, "%{controllers}"), "Symbol token should have been replaced.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "SymbolResolver — Unbounded Syntax %name",
            description: "A path containing %controllers is replaced with the symbol's target."
        )]
        public function testSymbolResolverUnboundedSyntax () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"), null, null, ["controllers" => "src/Controllers"]);
            $manager = new NamespaceManager([$ns]);
            $resolver = new SymbolResolver($manager);
            $context = $this->makeContext();

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/%controllers/User.php"), $context);

            $this->assertTrue($result instanceof ResolutionResult, "SymbolResolver should resolve %controllers.");
            $resolved = PathUtils::fix((string) $result->getPath());
            $this->assertTrue(!str_contains($resolved, "%controllers"), "Symbol token should have been replaced.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "SymbolResolver — Unknown Symbol Returns Null",
            description: "A path whose %symbol name is not registered causes the resolver to return null."
        )]
        public function testSymbolResolverUnknownSymbolReturnsNull () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"));
            $manager = new NamespaceManager([$ns]);
            $resolver = new SymbolResolver($manager);

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/%ghost/file.php"), $this->makeContext());

            $this->assertTrue($result === null, "Resolver should return null for an unregistered symbol.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "SymbolResolver — Result Context Carries Symbol",
            description: "After successful resolution, the result's context symbol is set to the matched Symbol."
        )]
        public function testSymbolResolverContextCarriesSymbol () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"), null, null, ["pages" => "src/Pages"]);
            $manager = new NamespaceManager([$ns]);
            $resolver = new SymbolResolver($manager);

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/%pages/Home.php"), $this->makeContext());

            $this->assertTrue($result !== null, "Resolver should return a result.");
            $this->assertTrue($result->getContext()->getSymbol() !== null, "Result context should carry the matched symbol.");
            $this->assertTrue($result->getContext()->getSymbol()->getName() === PathUtils::fix("pages"), "Symbol name mismatch.");
        }

        // ── VirtualResolver ───────────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "VirtualResolver — String Virtual Resolved",
            description: "A path mapped to a string virtual entry resolves to the string target."
        )]
        public function testVirtualResolverStringVirtualResolved () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"), null, null, [], [
                "index" => PathUtils::fix("/var/www/app/public/index.php")
            ]);
            $manager = new NamespaceManager([$ns]);
            $resolver = new VirtualResolver($manager);
            $context = $this->makeContext();

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/index"), $context);

            $this->assertTrue($result instanceof ResolutionResult, "VirtualResolver should resolve a string virtual.");
            $this->assertTrue(
                str_contains(PathUtils::fix((string) $result->getPath()), "index.php"),
                "Resolved path should point to the virtual's target."
            );
        }

        #[Group("Resolution")]
        #[Define(
            name: "VirtualResolver — Non-Matching Path Returns Null",
            description: "A path that has no matching virtual entry causes VirtualResolver to return null."
        )]
        public function testVirtualResolverNonMatchingReturnsNull () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"));
            $manager = new NamespaceManager([$ns]);
            $resolver = new VirtualResolver($manager);

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/nonvirtual/file.php"), $this->makeContext());

            $this->assertTrue($result === null, "VirtualResolver should return null for a non-matching path.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "VirtualResolver — Non-Absolute Path Returns Null",
            description: "A relative or namespace-rooted path causes VirtualResolver to return null immediately."
        )]
        public function testVirtualResolverNonAbsoluteReturnsNull () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"));
            $manager = new NamespaceManager([$ns]);
            $resolver = new VirtualResolver($manager);

            $result = $resolver->resolve("@App/index", $this->makeContext());

            $this->assertTrue($result === null, "VirtualResolver should return null for non-absolute input.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "VirtualResolver — Directory Virtual With Source Resolves Remainder",
            description: "A directory virtual with a source path maps remaining path segments under its source."
        )]
        public function testVirtualResolverDirectoryWithSourceResolvesRemainder () : void {
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"), null, null, [], [
                "assets" => [
                    "type" => "directory",
                    "source" => PathUtils::fix("/var/www/public/assets")
                ]
            ]);
            $manager = new NamespaceManager([$ns]);
            $resolver = new VirtualResolver($manager);

            $result = $resolver->resolve(PathUtils::fix("/var/www/app/assets/css/main.css"), $this->makeContext());

            $this->assertTrue($result instanceof ResolutionResult, "Directory virtual with source should produce a result.");
            $resolved = PathUtils::fix((string) $result->getPath());
            $this->assertTrue(str_contains($resolved, "main.css"), "Resolved path should include the remainder segment 'main.css'.");
        }

        // ── PathResolutionPipeline ─────────────────────────────────────────────────

        #[Group("Resolution")]
        #[Define(
            name: "Pipeline — Circular Detection Throws",
            description: "A pipeline that would cycle on the same fingerprint throws a RuntimeException."
        )]
        public function testPipelineCircularDetectionThrows () : void {
            # Build a stub resolver that always transforms the path into itself for an infinite loop.
            $loopResolver = new class implements \Wingman\Locator\Interfaces\ResolverInterface {
                public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
                    # Return the same input, triggering the duplicate fingerprint check on the next pass.
                    return null;
                }
            };

            $manager = new NamespaceManager();
            $pipeline = new PathResolutionPipeline($manager, $loopResolver);

            # No resolver changes anything, so will complete in one pass — that's not a loop.
            # To trigger circular detection we need a resolver that changes path then reverts.
            $count = 0;
            $cyclicResolver = new class ($count) implements \Wingman\Locator\Interfaces\ResolverInterface {
                private int $calls = 0;
                public function __construct (private int &$ref) {}
                public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
                    $this->calls++;
                    if ($this->calls === 1) {
                        return ResolutionResult::continue("a/b/c", $context ?? ResolutionContext::create(NamespaceManager::DEFAULT_NAMESPACE));
                    }
                    if ($this->calls === 2) {
                        return ResolutionResult::continue("a/b/c", $context ?? ResolutionContext::create(NamespaceManager::DEFAULT_NAMESPACE));
                    }
                    return null;
                }
            };

            $manager2 = new NamespaceManager();
            $pipeline2 = new PathResolutionPipeline($manager2, $cyclicResolver);
            $thrown = false;

            try {
                $pipeline2->resolve("a/b/c");
            }
            catch (\RuntimeException $e) {
                $thrown = str_contains($e->getMessage(), "Circular");
            }

            $this->assertTrue($thrown, "The pipeline should throw a RuntimeException when a circular resolution is detected.");
        }

        #[Group("Resolution")]
        #[Define(
            name: "Pipeline — Resolvers Are Tried In Order",
            description: "The pipeline restarts after any change; a later resolver's change triggers the first resolver again."
        )]
        public function testPipelineResolvesInOrder () : void {
            # Seeded with only a NamespaceResolver and an AbsoluteResolver so that
            # @App/file.php → namespace root joined → then absolute resolver prepends server root.
            $ns = new NamespaceObject("App", PathUtils::fix("/var/www/app"));
            $manager = new NamespaceManager([$ns]);

            $pipeline = new PathResolutionPipeline(
                $manager,
                new NamespaceResolver($manager),
                new AbsoluteResolver()
            );

            $context = ResolutionContext::create("App")
                ->setRoots(["server" => "/var/www", "cwd" => "/var/www", "os" => "/"]);

            $result = $pipeline->resolve("@App/Controllers", $context);

            $this->assertTrue($result !== null, "Pipeline should produce a result.");
            $resolved = PathUtils::fix((string) $result->getPath());
            $this->assertTrue(str_contains($resolved, "app"), "Final path should reference the App namespace root.");
        }
    }
?>