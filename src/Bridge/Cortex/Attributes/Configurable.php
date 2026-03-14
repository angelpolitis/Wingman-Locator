<?php
    /**
     * Project Name:    Wingman — Locator — Configurable
     * Created by:      Angel Politis
     * Creation Date:   Feb 24 2026
     * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator.Bridge.Cortex.Attributes namespace.
    namespace Wingman\Locator\Bridge\Cortex\Attributes;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the alias or stub is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\Configurable', false)) return;

    # Import the following classes to the current scope.
    use Attribute;

    # If Cortex isn't available, define a dummy attribute to avoid errors.
    if (!class_exists(\Wingman\Cortex\Attributes\Configurable::class)) {
        #[Attribute]
        class Configurable {
            public function __construct(...$args) {}
        }
    }
    else {
        class_alias(\Wingman\Cortex\Attributes\Configurable::class, __NAMESPACE__ . '\Configurable');
    }
?>