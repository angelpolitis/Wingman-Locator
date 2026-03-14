<?php
    /**
	 * Project Name:    Wingman — Locator — Path Resolution Pipeline
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 17 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    
    # Use the Locator namespace.
    namespace Wingman\Locator;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;

    /**
     * Represents a path resolution pipeline.
     * @package Wingman\Locator
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PathResolutionPipeline {
        /**
         * The namespace manager of a pipeline.
         * @var NamespaceManager
         */
        protected NamespaceManager $manager;

        /**
         * The resolvers of a pipeline.
         * @var ResolverInterface[]
         */
        protected array $resolvers;
    
        /**
         * Creates a new path resolution pipeline.
         * @param ResolverInterface ...$resolvers The resolvers.
         */
        public function __construct (NamespaceManager $manager, ResolverInterface ...$resolvers) {
            $this->manager = $manager;
            $this->resolvers = $resolvers;
        }
    
        /**
         * Runs the path resolution pipeline on the given input, cycling through all registered resolvers
         * until no resolver produces a change. Circular paths are detected via a fingerprint set and
         * trigger a `RuntimeException` rather than looping forever.
         * @param PathExpression|string $input The raw path expression to resolve.
         * @param ResolutionContext|null $context An optional resolution context carrying namespace, manifest, and symbol state.
         * @return ResolutionResult|null The resolved result, or `null` if no resolver could handle the input.
         * @throws RuntimeException If circular resolution is detected.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $context ??= new ResolutionContext($this->manager->getImplicitNamespace());
            $current = PathExpression::normalise($input);
            $seen = [];

            while (true) {
                $fingerprint = $current instanceof PathExpression ? $current->__toString() : (string) $current;

                if (isset($seen[$fingerprint])) {
                    throw new RuntimeException("Circular resolution detected: " . implode(" → ", array_keys($seen)) . " → $fingerprint");
                }

                $seen[$fingerprint] = true;
                $changed = false;

                foreach ($this->resolvers as $resolver) {
                    $result = $resolver->resolve($current, $context);

                    # If there's no result, keep the current path and go to next resolver.
                    if ($result === null) continue;

                    $current = $result->getPath();
                    $context = $result->getContext();
                    $changed = true;

                    # Restart the pipeline from resolver #1.
                    break;
                }

                # If nothing changed during this pass, we're done.
                if (!$changed) break;
            }

            return ResolutionResult::continue($current, $context);
        }
    }
?>