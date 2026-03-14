<?php
    /**
	 * Project Name:    Wingman — Locator — Max Symbol Depth Exceeded Exception
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 15 2025
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Exceptions namespace.
    namespace Wingman\Locator\Exceptions;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Locator\Interfaces\Exception;

    /**
     * Exception thrown when the maximum symbol depth is exceeded during resolution.
     * @package Wingman\Locator\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class MaxSymbolDepthExceededException extends RuntimeException implements Exception {}
?>