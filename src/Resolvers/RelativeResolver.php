<?php
    /**
	 * Project Name:    Wingman — Locator — Relative Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 18 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Resolvers namespace.
    namespace Wingman\Locator\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Locator\Enums\PathRootType;
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\PathUtils;

    /**
     * Represents a resolver that resolves relative paths.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RelativeResolver implements ResolverInterface {
        /**
         * Resolves relative segments in a path.
         * @param PathExpression|string $input The input path.
         * @param ResolutionContext|null $context The resolution context (unused here but required by interface).
         * @return ResolutionResult|null The resolution result or null if no changes were made.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $path = PathExpression::normalise($input);

            $rootType = $path->getRootType();

            switch ($rootType) {
                case PathRootType::RELATIVE_EXPLICIT:
                case PathRootType::RELATIVE_IMPLICIT:
                    $relativeRoot = $context->getRoot($rootType);
                    $newPath = PathUtils::join($relativeRoot, $path->getRelativePath());
                    return ResolutionResult::continue($newPath, $context);
            }

            return null;
        }
    }
?>