<?php
    /**
	 * Project Name:    Wingman — Locator — Absolute Resolver
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Mar 14 2026
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
     * Represents a resolver that resolves absolute paths.
     * @package Wingman\Locator\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AbsoluteResolver implements ResolverInterface {
        /**
         * Resolves absolute segments in a path.
         * @param PathExpression|string $input The input path.
         * @param ResolutionContext|null $context The resolution context (unused here but required by interface).
         * @return ResolutionResult|null The resolution result or null if no changes were made.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult {
            $path = PathExpression::normalise($input);

            $rootType = $path->getRootType();

            if ($rootType !== PathRootType::ABSOLUTE) return null;

            $root = $context->getRoot($rootType);

            # Build a candidate absolute path from the relative part. If this candidate
            # already lives under the server root we consider it already resolved and
            # skip re-joining to avoid duplicating the root repeatedly (infinite loop).
            $candidate = PathUtils::normalise(DIRECTORY_SEPARATOR . ltrim($path->getRelativePath(), DIRECTORY_SEPARATOR));
            $rootWithSep = rtrim(PathUtils::normalise($root), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            # Normalize current input for comparison to detect no-op resolutions
            $inputStr = PathUtils::normalise((string) $path);

            if (str_starts_with($candidate, $rootWithSep) || $candidate === rtrim($rootWithSep, DIRECTORY_SEPARATOR)) {
                # Candidate already contains the server root. If it's identical to the
                # current input, treat as no change (return null) to avoid pipeline loops.
                if ($candidate === $inputStr) return null;
                return ResolutionResult::continue($candidate, $context);
            }

            $newPath = PathUtils::join($root, $path->getRelativePath());

            # If joining produced the same path as we already have, it's a no-op.
            if (PathUtils::normalise($newPath) === $inputStr) return null;

            return ResolutionResult::continue($newPath, $context);
        }
    }
?>