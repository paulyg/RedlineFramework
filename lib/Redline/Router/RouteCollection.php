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
 * Base class for representing a related collection of routes.
 */
class RouteCollection implements \IteratorAggregate
{
	/**
	 * The collection of all sub-routes.
	 * @var array
	 */
	protected $routes = array();

	/**
	 * URL path prefix to append to all child routes.
	 * @var string
	 */
	protected $pathPrefix;

	/**
	 * Prefix for route names to optionally append.
	 * @var string
	 */
	protected $namePrefix;

    /**
     * Default values or route conditions to apply to child routes.
     * @var array
     */
    protected $defaults;

	/**
	 * Object constructor.
	 * @param Redline\subpackage\foo\dep_class
	 */
	public function __construct($path_prefix, $name_prefix = '', array $defaults = array())
	{
        $this->pathPrefix = $path_prefix;
        if (!empty($name_prefix)) {
            $this->namePrefix = $name_prefix;
        }
        if (!empty($defaults)) {
            $this->defaults = $defaults;
        }
	}

    /**
     * Gets the collection of routes as an Iterator.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }

	/**
	 * Add a route to the collection.
	 *
     * @param string $path URL path pattern to match against
     * @param string $controller Controller specification
     * @param array $options Array of route options
     * @throws RuntimeException If name of route will overwrite an earlier route.
	 */
	public function add($path, $controller, $options)
	{
        $options['name_prefix'] = $this->namePrefix;
		$options['path_prefix'] = $this->pathPrefix;
        $options['defaults'] = array_replace($this->defaults, $options['defaults']);

        $route = new Route($path, $controller, $options);
        $name = $route->name();

        if (isset($this->routes[$name])) {
            throw new RuntimeException("The route name '$name' will overwrite another route already using that name.");
        }
        $this->routes[$name] = $route;
	}

    /**
     * Retreives a route by name.
     *
     * @param  string $name  The route name
     * @return Route  $route A Route instance
     */
    public function getRoute($name)
    {
        if (isset($this->routes[$name])) {
            return $this->routes[$name];
        }

        foreach ($this->routes as $routes) {
            if (!$routes instanceof RouteCollection) {
                continue;
            }

            if (null !== $route = $routes->getRoute($name)) {
                return $route;
            }
        }
        // PHP returns null by default.
    }

    /**
     * Removes a route by name.
     *
     * @param string $name The route name
     */
    public function removeRoute($name)
    {
        if (isset($this->routes[$name])) {
            unset($this->routes[$name]);
        }

        foreach ($this->routes as $routes) {
            if ($routes instanceof RouteCollection) {
                $routes->removeRoute($name);
            }
        }
    }

    /**
     * Set a prefix for all URL paths in this collection.
     *
     * @param string $prefix
     */
    public function setPathPrefix($prefix)
    {
        $this->pathPrefix = $path;
        
        foreach ($this->routes as $route) {
            if ($route instanceof RouteCollection) {
                $route->setPathPrefix($prefix);
            } else {
                $route->addPathPrefix($prefix);
            }
        }
    }

    /**
     * Retrieve the URL path prefix for this collection.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }
    
    /**
     * Set a prefix for all names in this collection.
     *
     * @param string $prefix
     */
    public function setNamePrefix($prefix)
    {
        $this->namePrefix = $path;
        
        foreach ($this->routes as $route) {
            if ($route instanceof RouteCollection) {
                $route->setNamePrefix($prefix);
            } else {
                $route->addNamePrefix($prefix);
            }
        }
    }
}
