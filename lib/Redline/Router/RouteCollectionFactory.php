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
 * A collection of routes that can create specialized route types underneath it.
 *
 * Allows creation of ResourceRoute, CrudRoute, ControllerRoute objects under
 * the path prefix (and optionally name prefix) represented by this collection.
 *
 * @package RedlineFramework
 */
class RouteCollectionFactory extends RouteCollection
{
    /**
     * Create a new RESTful route group underneath this collection.
     *
     * @param string $name Name of the controller to use in the REST actions and URL.
     * @param array $options Array of route options.
     * @return ResourceRoute
     */
    public function resource($name, array $options = array())
    {

        $options['path_prefix'] = $this->pathPrefix;
        $options['name_prefix'] = $this->namePrefix;
        $options['defaults'] = array_replace($this->defaults, $options['defaults']);

        return $this->routes[] = new ResourceRoute($name, $options);
    }

    /**
     * Create a new CRUD route group underneath this collection.
     *
     * @param string $controller Name of the controller to use and in the URL.
     * @param array $options Array of route options.
     * @return CrudRoute
     */
    public function crud($controller, array $options = array())
    {
        $options['path_prefix'] = $this->pathPrefix;
        $options['name_prefix'] = $this->namePrefix;
        $options['defaults'] = array_replace($this->defaults, $options['defaults']);

        return $this->routes[] = new CrudRoute($controller, $options);
    }

    /**
     * Create a new route group based on the actions of a controller underneath this collection.
     *
     * @param string $name Name of the controller to use.
     * @param array $options Array of route options.
     * @return ControllerRoute
     */
    public function controller($name, array $options = array())
    {
        $options['path_prefix'] = $this->pathPrefix;
        $options['name_prefix'] = $this->namePrefix;
        $options['defaults'] = array_replace($this->defaults, $options['defaults']);

        return $this->routes[] = new ControllerRoute($name, $options);
    }

    /**
     * Create a new collection with as base route underneath this collection.
     *
     * @param string $name Name for the route.
     * @param string $path URL path used for route and collection prefix.
     * @param string $controller Module:Controller#Action specification.
     * @param array $options Array of route options
     * @throws RuntimeException If name of route will overwrite an earlier route.
     * @return ResourceRoute
     */
    public function collection($name, $path, $controller, array $options = array())
    {
        $prefixed_name = (empty($this->namePrefix)) ? $name : $this->namePrefix . '_' . $name;
        $this->add($name, $path, $controller, $options);
        $route = $this->get($prefixed_name);
        
        $defaults = isset($options['defaults']) ? $options['defaults'] : array();

        return $this->routes[] = new RouteCollection($route->getPath(), $route->getName(), $defaults);
    }
}
