<?php
    /**
	 * Project Name:    Wingman — Locator — Resolver Interface
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 17 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    
    # Use the Locator.Interfaces namespace.
    namespace Wingman\Locator\Interfaces;

    # Import the following classes to the current scope.
    use Wingman\Locator\Objects\PathExpression;
    use Wingman\Locator\Objects\ResolutionContext;
    use Wingman\Locator\Objects\ResolutionResult;

    /**
     * Represents a resolver.
     * @package Wingman\Locator\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    interface ResolverInterface {
        /**
         * Resolves a given input path.
         * @param PathExpression|string $input The input path.
         * @param ResolutionContext|null $context The resolution context.
         * @return ResolutionResult|null The resolution result, or `null` if not applicable.
         */
        public function resolve (PathExpression|string $input, ?ResolutionContext $context = null) : ?ResolutionResult;
    }
?>