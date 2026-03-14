<?php
    /**
	 * Project Name:    Wingman — Locator — Relative Segment Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 17 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Resolvers namespace.
    namespace Wingman\Locator\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Locator\Interfaces\ResolverInterface;
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;
    use Wingman\Locator\PathUtils;

    /**
     * Represents a resolver that resolves path segments (`.` and `..`) in a path.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RelativeSegmentResolver implements ResolverInterface {
        /**
         * Resolves relative segments in a path.
         * @param PathExpression|string $input The input path.
         * @param ResolutionContext|null $context The resolution context (unused here but required by interface).
         * @return ResolutionResult|null The resolution result or null if no changes were made.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $path = PathExpression::normalise($input);

            $relativePath = $path->getRelativePath();

            $resolved = PathUtils::normalise($relativePath);

            if (trim($resolved, DIRECTORY_SEPARATOR) !== trim($relativePath, DIRECTORY_SEPARATOR)) {
                return ResolutionResult::continue($path->withRelativePath($resolved), $context);
            }

            return null;
        }
    }
?>