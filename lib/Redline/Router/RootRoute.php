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
 * Represents the root of the application (homepage).
 *
 * @package RedlineFramework
 */
class RootRoute implements RouteInterface
{
    /**
     * Controller specification indicating class and method the be invoked.
     * @var string
     */
    protected $controller;

    /**
     * Default parameter values.
     * @var array
     */
    protected $defaults;

	/**
	 * Object constructor.
     *
     * @param string $controller Controller specification.
	 */
	public function __construct($controller, array $defaults = array())
    {
        $this->controller = $controller;
        $this->defaults = $defaults;
    }

	/**
	 * Test whether given URL path matches the route.
	 *
	 * @param string $path URL path to test for match against.
	 * @return boolean
	 */
	public function match($path)
    {
        if ($path !== '/') {
            return false;
        }
        return true;
    }
	
    /**
     * Build a URL path from given parameter values.
     *
     * @param array $params Parameter names and values.
     * @return string
     */
    public function build($params)
    {
        return '/';
    }

    /**
     * Return the name (identifier) for this route.
     *
     * @return string
     */
    public function name()
    {
        return 'root';
    }

    /**
     * Return the HTTP methods allowed for this route.
     *
     * @return array
     */
    public function methods()
    {
        return array('GET');
    }

    /**
     * Return the controller specification string or callback represented by this route.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

     /**
     * Retrieve the parameters captured from the URL pattern during the match.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->defaults;
    }
}
