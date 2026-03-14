<?php
    /**
	 * Project Name:    Wingman — Locator — URI
	 * Created by:      Angel Politis
	 * Creation Date:   Nov 06 2025
	 * Last Modified:   Feb 23 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Locator.Objects namespace.
    namespace Wingman\Locator\Objects;

    /**
     * Represents a URI (Uniform Resource Identifier).
     * `scheme:[//[user:password@]host[:port]]path[?query][#fragment]`
     * @package Wingman\Locator\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class URI {
        /**
         * The scheme of a URI.
         * @var string
         */
        public readonly ?string $scheme;

        /**
         * The user info of a URI.
         * @var string|null
         */
        public readonly ?string $userInfo;

        /**
         * The host of a URI.
         * @var string
         */
        public readonly ?string $host;
        
        /**
         * The port of a URI.
         * @var int|null
         */
        public readonly ?int $port;
        
        /**
         * The path of a URI.
         * @var string
         */
        public readonly string $path;
        
        /**
         * The query of a URI.
         * @var string
         */
        public readonly string $query;

        /**
         * The fragment of a URI.
         * @var string|null
         */
        public readonly ?string $fragment;

        /**
         * Creates a new URI.
         * @param ?string $scheme The scheme.
         * @param ?string $host The host.
         * @param string $path The path.
         * @param ?int $port The port.
         * @param string $query The query.
         * @param ?string $fragment The fragment.
         * @param ?string $userInfo The user info.
         */
        public function __construct (
            ?string $scheme = null,
            ?string $host = null,
            string $path = '/',
            ?int $port = null,
            string $query = "",
            ?string $fragment = null,
            ?string $userInfo = null
        ) {
            $this->scheme = $scheme ? strtolower($scheme) : null;
            $this->host = $host ? strtolower($host) : null;
            $this->path = $path ?: '/';
            $this->port = $port;
            $this->query = $query;
            $this->fragment = $fragment;
            $this->userInfo = $userInfo;
        }

        /**
         * Gets a full URI as a string.
         * @return string The full URI as a string.
         */
        public function __toString () : string {
            $uri = "";

            if ($this->scheme !== null) {
                $uri .= $this->scheme . ':';
            }

            if ($this->host !== null || $this->userInfo !== null) {
                $uri .= "//" . $this->getAuthority();
            }

            # Ensure path starts with / if we have an authority.
            $path = $this->path;
            if ($uri !== "" && $path !== "" && $path[0] !== '/') {
                $path = '/' . $path;
            }

            $uri .= $path;

            if ($this->query !== "") {
                $uri .= '?' . $this->query;
            }

            if ($this->fragment !== null) {
                $uri .= '#' . $this->fragment;
            }

            return $uri;
        }

        /**
         * Builds a URI from a string.
         * @param string $uri A URI.
         * @return static The URI.
         */
        public static function from (string $uri) : static {
            $parts = parse_url($uri);

            if ($parts === false) {
                return new static(path: $uri);
            }

            return new static(
                scheme: $parts["scheme"] ?? null,
                host: $parts["host"] ?? null,
                path: $parts["path"] ?? '/',
                port: $parts["port"] ?? null,
                query: $parts["query"] ?? "",
                fragment: $parts["fragment"] ?? null,
                userInfo: $parts["user"] ?? null
                    ? ($parts["user"] . (isset($parts["pass"]) ? ':' . $parts["pass"] : ""))
                    : null
            );
        }

        /**
         * Gets the fragment of a URI.
         * @return string|null The fragment of a URI.
         */
        public function getFragment () : ?string {
            return $this->fragment;
        }

        /**
         * Gets the host of a URI.
         * @return string|null The host of a URI.
         */
        public function getHost () : ?string {
            return $this->host;
        }

        /**
         * Gets the path of a URI.
         * @return string The path of a URI.
         */
        public function getPath () : string {
            return $this->path;
        }

        /**
         * Gets the port of a URI.
         * @return int|null The port of a URI.
         */
        public function getPort () : ?int {
            return $this->port;
        }

        /**
         * Gets the query of a URI.
         * @return string The query of a URI.
         */
        public function getQuery () : string {
            return $this->query;
        }

        /**
         * Gets the scheme of a URI.
         * @return string|null The scheme of a URI.
         */
        public function getScheme () : ?string {
            return $this->scheme;
        }

        /**
         * Gets the authority portion: `[userInfo@]host[:port]` of a URI.
         * @return string The authority of a URI.
         */
        public function getAuthority () : string {
            $authority = $this->host;

            if ($this->userInfo) {
                $authority = $this->userInfo . '@' . $authority;
            }

            if ($this->port !== null) {
                $authority .= ':' . $this->port;
            }

            return $authority;
        }

        /**
         * Gets the path and query of a URI.
         * @return string The path and query of a URI.
         */
        public function getPathWithQuery () : string {
            return $this->path . ($this->query !== "" ? '?' . $this->query : "");
        }

        /**
         * Creates a new instance of a URI with a modified query to include a specified parameter.
         * @param string $parameter The parameter key.
         * @param mixed $value The parameter value.
         * @return static The URI.
         */
        public function withParam (string $parameter, mixed $value) : static {
            $params = [];
            parse_str($this->query, $params);

            $params[$parameter] = $value;

            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI with multiple modified query parameters.
         * @param array $map Key-value parameter map.
         * @return static The URI.
         */
        public function withParams (array $map) : static {
            $params = [];
            parse_str($this->query, $params);

            foreach ($map as $key => $value) {
                $params[$key] = $value;
            }

            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI without the given parameter.
         * @param string $parameter The parameter to remove.
         * @return static The URI.
         */
        public function withoutParam (string $parameter) : static {
            $params = [];
            parse_str($this->query, $params);

            unset($params[$parameter]);

            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI without the given parameters.
         * @param string $parameter The first parameter to remove.
         * @param string ...$parameters Additional parameters to remove.
         * @return static The URI.
         */
        public function withoutParams (string $parameter, string ...$parameters) : static {
            $params = [];
            parse_str($this->query, $params);

            unset($params[$parameter]);

            foreach ($parameters as $p) {
                unset($params[$p]);
            }

            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI with a modified host.
         * @param string $host The host.
         * @return static The URI.
         */
        public function withHost (string $host) : static {
            return new static(
                scheme: $this->scheme,
                host: $host,
                path: $this->path,
                port: $this->port,
                query: $this->query,
                fragment: $this->fragment,
                userInfo: $this->userInfo
            );
        }

        /**
         * Creates a new instance of a URI with a modified path.
         * @param string $path The path.
         * @return static The URI.
         */
        public function withPath (string $path) : static {
            return new static(
                scheme: $this->scheme,
                host: $this->host,
                path: $path,
                port: $this->port,
                query: $this->query,
                fragment: $this->fragment,
                userInfo: $this->userInfo
            );
        }

        /**
         * Creates a new instance of a URI with a modified query.
         * @param string $query The query.
         * @return static The URI.
         */
        public function withQuery (string $query) : static {
            return new static(
                scheme: $this->scheme,
                host: $this->host,
                path: $this->path,
                port: $this->port,
                query: $query,
                fragment: $this->fragment,
                userInfo: $this->userInfo
            );
        }

        /**
         * Creates a new instance of a URI with a modified fragment.
         * @param string $fragment The fragment.
         * @return static The URI.
         */
        public function withFragment (?string $fragment) : static {
            return new static(
                scheme: $this->scheme,
                host: $this->host,
                path: $this->path,
                port: $this->port,
                query: $this->query,
                fragment: $fragment,
                userInfo: $this->userInfo
            );
        }

        /**
         * Creates a new instance of a URI with a modified scheme.
         * @param string $scheme The scheme.
         * @return static The URI.
         */
        public function withScheme (string $scheme) : static {
            return new static(
                scheme: $scheme,
                host: $this->host,
                path: $this->path,
                port: $this->port,
                query: $this->query,
                fragment: $this->fragment,
                userInfo: $this->userInfo
            );
        }
    }
?>