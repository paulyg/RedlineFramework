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
class CrudRoute extends BasicRoute
{
	/**
	 * List of valid collection level actions for this resource.
	 * @var array
	 */
	protected $collections = array('new' => 'GET|POST');

    /**
	 * List of valid member level actions for this resource.
	 * @var string
	 */
	protected $members = array('edit' => 'GET|POST', 'delete' => 'GET|POST');

 	/**
	 * Construct a new CrudRoute.
     *
	 * @param string $name    Name of the controller to call for all CRUD actions.
	 * @param array  $options Additional options.
	 */
	public function __construct($name, array $options = array())
	{
        // Colon must not be 1st char so we accept 0 as false instead of using === false
        if (strpos($name, ':')) {
            list($module, $controller) = explode(':', $name);
            $path = $module . '/' . $controller;
            $name = $module . '_' . $controller;
        } else {
            $module = '';
            $path = $controller = $name;
        }

		if (isset($options['path_prefix'])) {
            $path = trim($options['path_prefix'], '/') . '/' . $path;
        }
        
        $this->module = $module;
        $this->controller = $controller;
        $this->path = '/' . $path . '(/:action(/:id))';
        $this->conditions = isset($options['conditions']) ? $options['conditions'] : array();
        $this->defaults = isset($options['defaults']) ? $options['defaults'] : array();

        if (isset($options['name_prefix'])) {
            $name = $options['name_prefix'] . '_' . $name;
        }
        $this->name = $name;

        // handle "except" and "only" options
	}

	/**
	 * Add a new route underneath this route.
	 * @param string $path URL path specification to match route against.
	 * @param string $controller_spec Special format indicating module, controller class
     *                                & action to call.
	 * @param array  $options Name, method, conditions, and default options.
	 */
	public function add($path, $controller_spec, array $options = array())
	{
		$options['name_prefix'] = $this->name;
		$options['path_prefix'] = $this->path;
        $options['conditions'] = array_replace($this->conditions, $options['conditions']);
        $options['defaults'] = array_replace($this->defaults, $options['defaults']);

		$this->subroutes[] = new Route($path, $controller_spec, $options);
	}

    /**
     * @inheritdoc
     */
    public function match($uri_path)
    {
        if (!parent::match($uri_path)) {
            return false;
        }

        if (!isset($this->params['id']) && empty($this->params['action'])) {
            $this->action = 'index';
            return true;
        }

        $id = (int) $this->params['id'];
        if (($id > 0) && empty($this->params['action'])) {
            $this->action = 'show';
            return true;
        }

        if (isset($this->collections[$this->params['id']])) {
            $this->action = $this->params['id'];
            return true;
        }

        if (($id > 0) && isset($this->members[$this->params['action']])) {
            $this->action = $this->params['action'];
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function build($params)
    {
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return $this->name;
    }

    public function addCollectionMethod($name)
    {
    }

    public function addMemberMethod($name)
    {
    }

    public function crud($name, array $options = array())
    {
    }
}
