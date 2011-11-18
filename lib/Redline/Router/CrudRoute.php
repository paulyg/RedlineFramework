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
 * Creates a number of routes that are used to Create, Read, Update, and Delete a resource.
 *
 * @package RedlineFramework
 */
class CrudRoute extends RouteCollection
{
    /**
     * The controller to be called for this resource collection.
     * @var string
     */
    protected $controller;

    /**
     * The actual param name of the "id" for this resource.
     * @var string
     */
    protected $idParam = 'id';

    /**
     * Match condition for the id param.
     * @var string
     */
    protected $idCondition = '(\d+)';

	/**
	 * List of valid collection level actions for this resource.
	 * @var array
	 */
	protected $collections = array('index' => 'GET', 'new' => 'GET|POST');

    /**
	 * List of valid member level actions for this resource.
	 * @var string
	 */
	protected $members = array('edit' => 'GET|POST', 'delete' => 'GET|POST', 'show' => 'GET');

 	/**
	 * Construct a new CrudRoute.
     *
	 * @param string $name    Name of the controller to call for all CRUD actions.
	 * @param array  $options Additional options.
	 */
	public function __construct($controller, array $options = array())
	{
        $path_prefix = str_replace(array('\\', ':'), '/', strtolower($controller));
        $name_prefix = str_replace(array('\\', ':'), '_', strtolower($controller));

		if (isset($options['path_prefix'])) {
            $path_prefix = trim($options['path_prefix'], '/') . '/' . $path_prefix;
            unset($options['path_prefix']);
        }

        if (isset($options['name_prefix'])) {
            $name_prefix = $options['name_prefix'] . '_' . $name_prefix;
            unset($options['name_prefix']);
        }

        $this->pathPrefix = $path_prefix;
        $this->namePrefix = $name_prefix;
        $this->controller = $controller;

        // handle "except" and "only" options
        if (isset($options['only'])) {
            foreach ($this->collections as $key => $val) {
                if (!in_array($key, $options['only'])) {
                    unset($this->collections[$key];
                }
            }
            foreach ($this->members as $key => $val) {
                if (!in_array($key, $options['only'])) {
                    unset($this->members[$key];
                }
            }
        } elseif (isset($options['except'])) {
            foreach ($options['except'] as $action) {
                foreach ($this->collections as $key => $val) {
                    if ($key == $action) {
                        unset($this->collections[$action];
                        break 2;
                    }
                }
                foreach ($this->members as $key => $val) {
                    if ($key == $action) {
                        unset($this->members[$action];
                        break 2;
                    }
                }
            }
        }

        if (isset($options['id_param'])) {
            $this->idParam = $options['id_param'];
        }
        if (isset($options['conditions'][$id_param])) {
            $this->idCondition = $options['conditions'][$id_param];
        }

        $options = array('conditions' => array($this->idParam => $this->idCondition));

        // Make the routes!
        $this->add('/', $controller.'#index');
        
        foreach ($this->collections as $action => $methods) {
            $options['method'] = $methods;
            $this->add('/'.$action, $controller.'#'.$action, $options);
        }

        foreach ($this->members as $action => $methods) {
            $options['method'] = $methods;
            $path = '/'.$action.'/'.$this->idParam;
            $this->add($path, $controller.'#'.$action, $options);
        }
	}

    /**
     * Add a collection level route to this CRUD resource collection.
     *
     * @param string $action
     * @param string $methods
     */
    public function collection($action, $methods = 'GET|POST')
    {
        $this->add('/'.$action, $this->controller.'#'.$action, array('method' => $methods));
    }

    /**
     * Add a member level route to this CRUD resource collection.
     *
     * @param string $action
     * @param string $methods
     */
    public function member($name, $methods = 'GET|POST')
    {
        $path = '/'.$action.'/'.$this->idParam;
        $this->add($path, $this->controller.'#'.$action, array('method' => $methods));
    }

    /**
     * Add another group of CRUD routes as a sub-resource underneath this one.
     *
     * @param string $name
     * @param array $options
     * @return CrudRoute
     */
    public function crud($name, array $options = array())
    {
        $options['name_prefix'] = $this->namePrefix;
		$options['path_prefix'] = $this->pathPrefix.'/{'.$this->namePrefix.'_id}';
        $options['conditions'][$this->namePrefix.'_id'] = '(\d+)';

		return $this->routes[] = new CrudRoute($name, $options);
    }
}
