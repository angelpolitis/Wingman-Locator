<?php
    /**
	 * Project Name:    Wingman — Locator — Manifest Loader
	 * Created by:      Angel Politis
	 * Creation Date:   Feb 23 2026
	 * Last Modified:   Mar 14 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */
    # Use the Locator namespace.
    namespace Wingman\Locator;

    # Import the following classes to the current scope.
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use Wingman\Locator\Bridge\Corvus\Emitter;
    use Wingman\Locator\Enums\Signal;
    use Wingman\Locator\Objects\DiscoveryFilter;
    use Wingman\Locator\Objects\DiscoveryProfile;
    use Wingman\Locator\Objects\Manifest;

    /**
     * The manifest loader is responsible for discovering and loading locator manifests from the filesystem.
     * It processes the manifest data and updates the namespace manager accordingly, allowing for dynamic configuration of namespaces, symbols, virtuals, and settings based on the contents of the discovered manifests.
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ManifestLoader {
        /**
         * The name of the manifest file to look for in the filesystem.
         * @var string
         */
        protected string $manifestFilename = "locator.manifest";

        /**
         * Creates a new manifest loader.
         * @param string|null $manifestFilename The name of the manifest file to look for. If `null`, the default filename will be used.
         */
        public function __construct (?string $manifestFilename = null) {
            if ($manifestFilename !== null) {
                $this->manifestFilename = $manifestFilename;
            }
        }

        /**
         * Discovers manifest files starting from a given directory.
         * @param string $directory The root directory to start the search.
         * @return Manifest[] The discovered manifests.
         */
        public function discover (string $rootDirectory, ?DiscoveryProfile $profile = null) : array {
            Asserter::requireDirectoryAt($rootDirectory);
            $profile ??= new DiscoveryProfile();
            $manifests = [];

            $dirIterator = new RecursiveDirectoryIterator($rootDirectory, RecursiveDirectoryIterator::SKIP_DOTS);
            $filter = new DiscoveryFilter($dirIterator, $profile, $rootDirectory);
            $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === $this->manifestFilename) {
                    $filePath = $file->getRealPath();
                    $content = file_get_contents($filePath);
                    $data = json_decode($content, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Emitter::create()
                            ->with(["filePath" => $filePath, "error" => json_last_error_msg()])
                            ->emit(Signal::MANIFEST_INVALID);
                        continue;
                    }

                    $manifest = Manifest::from($data, $filePath);
                    $manifests[] = $manifest;
                }
            }

            return $manifests;
        }
    }
?>