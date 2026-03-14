<?php
    /**
	 * Project Name:    Wingman — Locator — Namespace Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 16 2025
	 * Last Modified:   Mar 12 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Resolvers namespace.
    namespace Wingman\Locator\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Exceptions\UnknownNamespaceException;
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\PathUtils;

    /**
     * Represents a namespace resolver.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NamespaceResolver implements ResolverInterface {
        /**
         * The manager of a namespace resolver.
         * @var NamespaceManager
         */
        protected NamespaceManager $manager;

        /**
         * Creates a new namespace resolver.
         */
        public function __construct (NamespaceManager $manager) {
            $this->manager = $manager;
        }

        /**
         * Resolves a path containing a named namespace into an absolute path.
         * **Note**: This method doesn't verify the existence of the resource.
         * @param PathExpression|string $input The path to resolve.
         * @return string The resolved absolute path.
         * @throws UnknownNamespaceException If the namespace is not defined.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $path = PathExpression::normalise($input);
    
            if ($path->getRootType() !== PathRootType::NAMESPACE) return null;
    
            $namespace = $path->getRootArg();
    
            if (!$namespace) return null;

            if (!$this->manager->hasNamespace($namespace)) {
                throw new UnknownNamespaceException("Namespace '$namespace' is not defined.");
            }

            $namespacePath = $this->manager->getNamespacePath($namespace);
            $relative = $path->getRelativePath() ?? "";
    
            # If there's no relative path, use the namespace path.
            $resolvedPath = $relative === "" ? $namespacePath : PathUtils::join($namespacePath, $relative);
    
            return ResolutionResult::continue($resolvedPath, $context);
        }
    }
?>