<?php
/**
 * @package RedlineFramework
 * @author Paul Garvin <paul@paulgarvin.net>
 * @copyright Copyright 2011 Paul Garvin. Some rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GNU General Public License
 * @version @package_version@
 *
 * Redline PHP Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Redline PHP Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Redline PHP Framework. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Redline\Router;

/**
 * Common interface for multiple URL routing types.
 *
 * @package RedlineFramework
 */
interface RouteInterface
{
	/**
	 * Object constructor.
     *
     * @param string $path URI path specification to match against.
     * @param string $controller Controller specification.
	 * @param array $options Route options.
	 */
	public function __construct($path, $controller, array $options = array());

	/**
	 * Test whether given URL path matches the route.
	 *
	 * @param string $path URL path to test for match against.
	 * @return boolean
	 */
	public function match($path);
	
    /**
     * Build a URL path from given parameter values.
     *
     * @param array $params Parameter names and values.
     * @return string
     */
    public function build($params);

    /**
     * Return the name (identifier) for this route.
     *
     * @return string
     */
    public function name();

    /**
     * Check whether any contained/child routes has this name.
     *
     * @param string $name
     * @return boolean
     */
    public function has($name);

    /**
     * Remove/delete a contained/child route by name.
     *
     * @param string $name
     * @return void
     */
    public function remove($name);

    /**
     * Retrieve the parameters captured from the URL pattern during the match.
     *
     * @return array
     */
    public function getParams();
}
