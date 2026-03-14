<?php
    /**
	 * Project Name:    Wingman — Locator — Signal
	 * Created by:      Angel Politis
	 * Creation Date:   Mar 14 2026
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Enums namespace.
    namespace Wingman\Locator\Enums;

    /**
     * Represents a signal emitted by the Locator during its lifecycle operations.
     *
     * Each case maps to a dot-notation string identifier consumed by Corvus listeners.
     * Cases can be passed directly to `emit()` — coercion to their string value is automatic.
     *
     * @package Wingman\Locator\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum Signal : string {
        /**
         * Emitted after a manifest has been successfully loaded and processed.
         * Payload: `namespace` (string), `sourcePath` (string).
         */
        case MANIFEST_PROCESSED = "locator.manifest.processed";

        /**
         * Emitted when a discovery root is resolved from an existing cache entry.
         * Payload: `root` (string).
         */
        case CACHE_HIT = "locator.cache.hit";

        /**
         * Emitted when a discovery root has no matching cache entry and must be scanned.
         * Payload: `root` (string).
         */
        case CACHE_MISS = "locator.cache.miss";

        /**
         * Emitted when a manifest file is found but cannot be parsed as valid JSON.
         * Payload: `filePath` (string), `error` (string).
         */
        case MANIFEST_INVALID = "locator.manifest.invalid";

        /**
         * Emitted after all manifests for a discovery root have been processed.
         * Payload: `root` (string), `count` (int).
         */
        case DISCOVERY_COMPLETED = "locator.discovery.completed";

        /**
         * Emitted after a path expression has been successfully resolved to a concrete path.
         * Payload: `expression` (string), `resolved` (string).
         */
        case PATH_RESOLVED = "locator.path.resolved";
    }
?>