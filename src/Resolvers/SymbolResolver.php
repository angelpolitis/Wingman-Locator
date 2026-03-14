<?php
    /**
	 * Project Name:    Wingman — Locator — Symbol Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 15 2025
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Resolvers namespace.
    namespace Wingman\Locator\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Locator\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Locator\Bridge\Cortex\Configuration;
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\NamespaceObject;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\Objects\Symbol;
    use Wingman\Locator\PathUtils;

    /**
     * Represents a symbol resolver.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SymbolResolver implements ResolverInterface {
        /**
         * The manager of a namespace resolver.
         * @var NamespaceManager
         */
        protected NamespaceManager $manager;

        /**
         * Whether to enable implicit symbol matching (segment-aware). Defaults to `false` for strict matching.
         * 
         * When enabled, the resolver will attempt to match the longest valid symbol in a path segment chain,
         * allowing for more flexible symbol definitions that can span multiple segments.
         * 
         * **Warning**: Enabling this may lead to unexpected matches if not used carefully, as it will consider any
         * prefix of the path as a potential symbol. Use with well-defined namespaces and symbols to avoid ambiguity.
         * @var bool
         */
        #[Configurable("locator.resolvers.symbol.implicitEnabled", "Enable implicit symbol matching (segment-aware)")]
        protected bool $implicitEnabled = false;

        /**
         * Creates a new symbol resolver.
         * @param NamespaceManager $manager The namespace manager.
         * @param array|Configuration $config The configuration to hydrate resolver settings from.
         */
        public function __construct (NamespaceManager $manager, array|Configuration $config = []) {
            Configuration::hydrate($this, $config);
            $this->manager = $manager;
        }

        /**
         * Finds the longest valid symbol from a given sequence.
         * @param string $sequence The sequence.
         * @param string $namespace The namespace to search in.
         * @return Symbol|null The longest valid symbol, or `null` if none was found.
         */
        protected function findLongestSymbol (string $sequence, string $namespace): ?Symbol {
            $namespaceObj = $this->manager->getNamespace($namespace);
            if (!$namespaceObj instanceof NamespaceObject) return null;
            $symbols = $namespaceObj->getSymbols();

            # 1. Check for Bounded Syntax: %{name}
            if (preg_match('/^%\{(.+?)\}/', $sequence, $matches)) {
                $name = $matches[1];
                return $symbols[$name] ?? null;
            }

            # 2. Check for Unbounded Syntax: %name
            if (str_starts_with($sequence, '%')) {
                $parts = explode(DIRECTORY_SEPARATOR, substr($sequence, 1), 2);
                $name = $parts[0];
                return $symbols[$name] ?? null;
            }

            # 3. Fallback to Implicit (Your current logic, but segment-safe)
            if ($this->implicitEnabled) {
                $segments = explode(DIRECTORY_SEPARATOR, PathUtils::fix($sequence));
                $currentPath = "";
                $bestMatch = null;

                foreach ($segments as $segment) {
                    $currentPath = ($currentPath === "") ? $segment : $currentPath . DIRECTORY_SEPARATOR . $segment;
                    if (isset($symbols[$currentPath])) {
                        $bestMatch = $symbols[$currentPath];
                    }
                    else break; 
                }
                return $bestMatch;
            }

            return null;
        }

        /**
         * Resolves symbols in a given path recursively.
         * @param PathExpression|string $input The input path.
         * @param ResolutionContext|null $context The resolution context.
         * @return ResolutionResult|null The resolution result or `null` if no symbol was found.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $pathExpr = PathExpression::normalise($input);

            $rootType = $pathExpr->getRootType();

            # Handle namespace-rooted expressions explicitly
            if ($rootType === PathRootType::NAMESPACE) {
                $ns = $pathExpr->getRootArg();
                $nsPath = $this->manager->getNamespacePath($ns);
                if (!$nsPath) return null;
                $path = PathUtils::join($nsPath, $pathExpr->getRelativePath());
            }
            # Allow relative paths when resolving inside an existing context (resolve against context namespace)
            elseif ($rootType->isRelative()) {
                if ($context === null) return null;
                $ctxNsPath = $this->manager->getNamespacePath($context->getNamespace());
                if (!$ctxNsPath) return null;
                $path = PathUtils::join($ctxNsPath, $pathExpr->getRelativePath());
            }
            # Absolute/drive paths: use as-is
            elseif ($rootType === PathRootType::ABSOLUTE || $rootType === PathRootType::DRIVE) {
                $path = (string) $pathExpr;
            }
            else return null;

            $namespace = $this->manager->getPathNamespace($path);

            $namespaceRoot = rtrim($this->manager->getNamespacePath($namespace), DIRECTORY_SEPARATOR);
            $sequence = ltrim(substr($path, strlen($namespaceRoot)), DIRECTORY_SEPARATOR);

            $symbol = $this->findLongestSymbol($sequence, $namespace);

            if (!$symbol) return null;

            $name = $symbol->getName();
            $target = $symbol->getTarget();

            # If we are using explicit grammar, we replace the token (%name or %{name}).
            # If implicit, we ensure we replace the segment, not just a substring.
            
            if (str_starts_with($sequence, "%{")) {
                $search = "%{" . $name . "}";
            }
            elseif (str_starts_with($sequence, '%')) {
                $search = "%$name";
            }
            else $search = $name;

            # Replace only the first occurrence to avoid recursive mess in one step
            $nextPath = preg_replace('/' . preg_quote($search, '/') . '/', $target, $sequence, 1);

            $context = ResolutionContext::create($namespace, $symbol->getManifest())
                ->setSymbol($symbol)
                ->setRelativeRoot(PathRootVariable::NAMESPACE)
                ->setRoots($context?->getRoots() ?? []);
            
            return ResolutionResult::continue($nextPath, $context);
        }
    }
?>