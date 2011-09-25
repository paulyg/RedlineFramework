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
class Mapper 
{
	/**
	 * Collection of route objects
	 * @var RouteInterface
	 */
	protected $routes = array();

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
	 * Object constructor.
	 * @param Redline\
	 */
	public function __construct($scheme, $hostname, $baseUrlPath)
	{
        $this->schema = $schema;
        $this->hostname = $hostname;
        $this->baseurlPath = $baseUrlPath;
	}

	/**
	 * Short description of function.
	 *
	 * Long description of function. If only short description is used params list can
	 * come right after short description without a blank line separating them. Wrap
	 * code examples in
	 * <code></code>
	 * tags on their own lines. Wrap text at approx 85-90 chars.
	 *
	 * @param type $name Short description
	 * @param type $args This parameter has a long description. The description can wrap
	 * to multiple lines if necessary. Unless the arg list is long (discouraged) you do
	 * not have to justify the start of the descriptions to all line up. Furthermore you do
	 * not have to indent the beginning of the wrapped lines unless you justify the start.
	 * @throws \Exception|Redline\Exception|\LogicException|\DomainException|\InvalidArgumentException|\BadMethodCallExcpetion|\RuntimeException
	 * @return type
	 */

    /**
     * Add an arbitrary route insance to the routing table.
     * 
     * @param RouteInterface $route
     * @throws RuntimeException If name of route will overwrite an earlier route.
     * @return void
     */
    public function add(RouteInterface $route)
    {
        $name = $route->name();
        if ($name) {
            $this->addWithName($route, $name);
        } else {
            $this->routes[] = $route;
        }
    }

    /**
     * Remove (delete) a named route.
     *
     * @param string $name Route name
     * @return boolean
     */
    public function remove($name)
    {
        if (isset($this->routes[$name])) {
            unset($this->routes[$name]);
            return true;
        }

        return false;
    }

    /**
     * Add a basic (single) route connection between a path and controller/action.
     *
     * @param string $path URL path pattern to match against
     * @param string $controller Controller specification
     * @param array $options Array of route options
     * @throws RuntimeException If name of route will overwrite an earlier route.
     * @return BasicRoute
     */
	public function connect($path, $controller, $options = array())
	{
        $route = new BasicRoute($path, $controller, $options, $this);
        $this->addWithName($route, $route->name());
        
        return $route;
	}

    /**
     * Add a group of routes following the CRUD convention.
     *
     * @param string $name Name of the controller to use in the CRUD action and URL
     * @param array $options Array of route options
     * @throws RuntimeException If name of route will overwrite an earlier route.
     * @return CrudRoute
     */
	public function crud($name, $options = array())
	{
        $route = new CrudRoute($name, $options, $this);
        $this->addWithName($route, $route->name());

        return $route;
	}

    /**
     * Add a group of routes following the REST convention.
     *
     * @param string $name Name of the controller to use in the REST actions and URL
     * @param array $options Array of route options
     * @throws RuntimeException If name of route will overwrite an earlier route.
     * @return RestRoute
     */
	public function rest($name, $options = array())
	{
        $route = new RestRoute($name, $options, $this);
        $this->addWithName($route, $route->name());

        return $route;
	}

    protected function addWithName(RouteInterface $route, $name)
    {
        if (isset($this->routes[$name])) {
            throw new RuntimeException("The route name '$name' will overwrite another route already using that name.");
        }
        $this->routes[$name] = $route;
    }

    public function match($path)
	{
        foreach ($routes as $route) {
            if ($route->match($path)) {
                 return $route->getParams();
            }
        }
        return false;
	}

    public function urlFor($name, $params = array())
    {
        // pathFor will throw an exception for us if $name doesn't exist
        return $this->schema . $this->hostname . $this->pathFor($name, $params);
    }

    public function pathFor($name, $params = array())
    {
        $path = '';

        if (isset($this->routes[$name])) {
             $path = $this->routes[$name]->build($name, $params);
        } else {
            foreach ($routes as $route) {
                if ($route->has($name)) {
                    $path = $route->build($name, $params);
                    break;
                }
            }
        }

        if (!$path) {
            throw new RuntimeException("A routing with the name '$name' could not be found.");
        }

        return $this->baseUrlPath . $path;
    }
}
