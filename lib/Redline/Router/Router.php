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
 * Description of class.
 *
 * @package RedlineFramework
 */
class Router extends RouteCollectionFactory
{
    /**
     * The schema of the current HTTP request (HTTP or HTTPS)
     * @var string
     */
    protected $schema;

    /**
     * The hostname on which this application is running
     * @var string
     */
    protected $hostname;

    /**
     * The URL path representing base or entry point of this application
     * @var string
     */
    protected $baseUrlPath;
    
    /**
     * The controller/action to call when the root URL (homepage) is requested.
     * @var RootRoute
     */
    protected $root;

    /**
     * Cache of HTTP methods allowed on matched routes.
     * @var array
     */
    protected $allowed = array();

    /**
     * Object constructor.
     * @param Redline\Request
     */
    public function __construct(Redline\Request $request)
    {
        $this->schema = $request->schema();
        $this->hostname = $request->hostname();
        $this->baseUrlPath = $request->baseUrlPath();
    }

    /**
     * Sets the controller & action to call when the root URL, aka homepage, is requested.
     *
     * @param string $controller_spec
     * @param array $defaults
     */
    public function root($controller_spec, array $defaults = array())
    {
        $this->root = new RootRoute($controller_spec, $defaults);
    }
    
    /**
     * Add a RouteCollection object that has already been composed.
     *
     * This is useful for allowing modules to provide preconfigured RouteCollection that can be added underneath
     * a single path prefix in one command.
     *
     * @param RouteCollection $collection
     * @param string $path_prefix Optional
     * @param string $name_prefix Optional
     */
    public function addCollection($collection, $path_prefix = '', $name_prefix = '')
    {
        if (!empty($path_prefix)) {
            $collection->setPathPrefix($path_prefix);
        }

        if (!empty($name_prefix)) {
            $collection->setNamePrefix($name_prefix);
        }

        $this->routes[] = $collection;
    }

    /**
     * Search for a route that matches the requested URL path.
     *
     * @param string $path
     * @param string $method
     * @return Route|boolean
     */
    public function match($path, $method)
    {
        $this->allowed = array();
        $path = urldecode($path);
        if ($method == 'HEAD') {
            $method = 'GET';
        }

        if ($path == '/') {
            if ($method != 'GET') {
                throw new MethodNotAllowedExcpetion(array('GET', 'HEAD'));
            }
            return $this->root;
        }

        if ($route = $this->matchCollection($path, $this->routes)) {
            return $route;
        }

        throw (count($this->allowed) > 0)
            ? new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allowed)))
            : new ResourceNotFoundException();
    }

    /**
     * Internal method allowing to recursivly iterate over RouteCollection objects.
     *
     * @param string $path
     * @param string $method
     * @param Traversable $routes
     * @return Route|boolean
     */
    protected function matchCollection($path, $method, $routes)
    {
        foreach ($routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                $prefix = $route->getPathPrefix();
                if (false === strpos($prefix, '{') &&
                    $prefix !== substr($path, 0, strlen($prefix))) {
                    continue;
                }

                if ($ret = $this->routeColletion($path, $route)) {
                    return $ret;
                }
            }

            if ($route->match($path)) {
                if (!in_array($method, $route->methods()) {
                    $this->allowed = array_merge($this->allowed, $route->methods());
                    continue;
                }

                return $route;
            }
        }

        return false;
    }

    /**
     * Create a URL, absolute path only, for a named route with the given parameter values.
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public function pathFor($name, $params = array())
    {
        if ($name == 'root') {
            return '/';
        }

        if (null === $route = $this->getRoute($name)) {
            throw new RuntimeException("A route with the name '$name' could not be found.");
        }

        return $this->baseUrlPath . $route->build($params);
    }

    /**
     * Create a URL, inclusing hostname & scheme, for a named route with the given parameter values.
     *
     * @param string $name
     * @param array $params
     * @return array
     */
    public function urlFor($name, $params = array())
    {
        // pathFor will throw an exception for us if $name doesn't exist
        return $this->schema . $this->hostname . $this->pathFor($name, $params);
    }
}
