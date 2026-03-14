<?php
    /**
	 * Project Name:    Wingman — Locator — Path Traversal Exception
	 * Created by:      Angel Politis
	 * Creation Date:   Feb 23 2026
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Exceptions namespace.
    namespace Wingman\Locator\Exceptions;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Locator\Interfaces\Exception;

    /**
     * Represents a path traversal exception.
     * @package Wingman\Locator\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PathTraversalException extends RuntimeException implements Exception {
        /**
         * The default message of an exception.
         * @var string
         */
        protected $message = "Path traversal detected.";
    }
?>