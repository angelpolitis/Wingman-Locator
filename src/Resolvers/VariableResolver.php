<?php
    /**
	 * Project Name:    Wingman — Locator — Variable Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 15 2025
	 * Last Modified:   Feb 25 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Resolvers namespace.
    namespace Wingman\Locator\Resolvers;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Enums\PathRootVariable;
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\NamespaceManager;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\PathUtils;

    /**
     * Represents a variable resolver.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class VariableResolver implements ResolverInterface {
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
         * Gets the root path of a namespace from the context.
         * @param ResolutionContext $context The resolution context.
         * @return string The root path of the namespace.
         */
        protected function getNamespaceRoot (ResolutionContext $context) : string {
            $namespace = $context->getNamespace();
            if (!$this->manager->hasNamespace($namespace)) {
                throw new RuntimeException("Unknown namespace '$namespace' cannot be resolved.");
            }
            return $this->manager->getNamespacePath($namespace);
        }

        /**
         * Resolves a single constant name to its absolute base path.
         * @param ResolutionContext|null $context The resolution context.
         * @param PathRootVariable $variable The variable to resolve.
         * @param string|null $rootArg The argument of the root, if applicable.
         * @return string The resolved absolute base path.
         * @throws RuntimeException If the variable is unknown.
         */
        protected function resolveRoot (ResolutionContext $context, PathRootVariable $variable, ?string $rootArg = null) : string {
            $name = strtolower($rootArg ?? $variable->name);

            $resolved = match ($variable) {
                PathRootVariable::UNKNOWN => throw new RuntimeException("Unknown variable '$name'."),
                PathRootVariable::NAMESPACE => $this->getNamespaceRoot($context),
                default => $context->getRoot($variable)
            };

            return PathUtils::forceTrailingSeparator($resolved);
        }

        /**
         * Resolves a Path instance that uses a constant root into a full string path.
         * This runs recursively if the resolved path itself contains variables.
         * @param PathExpression|string $path The Path instance to resolve.
         * @param ResolutionContext|null $context The resolution context.
         * @return ResolutionResult|null The resolution result, or `null` if the resolver cannot handle the input.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null): ?ResolutionResult {
            $path = PathExpression::normalise($input);

            if ($path->getRootType() !== PathRootType::VARIABLE) return null;
    
            $base = $this->resolveRoot($context, $path->getVariable(), $path->getRootArg());
            $resolved = $base . $path->getRelativePath();
    
            return ResolutionResult::terminal($resolved, $context);
        }
    }
?>