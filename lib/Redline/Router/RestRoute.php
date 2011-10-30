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
 * Creates a number of routes that conform to generally accepted rules for managing a resource RESTfully.
 *
 * @package RedlineFramework
 */
class RestRoute extends BasicRoute
{
	/**
	 * List of valid collection level actions for this resource.
	 * @var array
	 */
	protected $collections = array('index' => 'GET', 'new' => 'POST');

    /**
	 * List of valid member level actions for this resource.
	 * @var string
	 */
	protected $members = array('show' => 'GET', 'edit' => 'POST|PUT', 'delete' => 'POST|DELETE', );

	/**
	 * Construct a new RestRoute.
     *
	 * @param string $name    Name of the controller to call for all CRUD actions.
	 * @param array  $options Additional options.
     * @param Mapper $mapper  Reference back to the mapper for creating subroutes.
	 */
	public function __construct($name, array $options = array(), $mapper = null)
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
        $this->path = '/' . $path;
        $this->conditions = isset($options['conditions']) ? $options['conditions'] : array();
        $this->defaults = isset($options['defaults']) ? $options['defaults'] : array();

        if (isset($options['name_prefix'])) {
            $name = $options['name_prefix'] . '_' . $name;
        }
        $this->name = $name;

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

        if ($mapper instanceof Mapper) {
            $this->mapper = $mapper;
        }
	}

    /**
     * @inheritdoc
     */
    public function match($uri_path)
    {
        if (parent::match($uri_path) && isset($this->collections['index'])) {
            $this->action = 'index';
            return true;
        }

        $oldpath = $this->path;
        $this->path .= '/:id';
        if (parent::match($uri_path) && isset($this->members['show'])) {
            $this->action = 'show';
            return true;
        }

        $this->path = $oldpath . '/:action';
        if (parent::match($uri_path) && isset($this->collections[$this->params['action']])) {
            $this->action = $this->params['action'];
            return true;
        }

        $this->path = $oldpath . '/:id/:action';
        if (parent::match($uri_path) $$ isset($this->members[$this->params['actions']])) {
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

    public function addCollectionMethod($name, $methods = 'GET|POST')
    {
        $this->collections[$name] = $methods;
    }

    public function addMemberMethod($name, $methods = 'GET|POST')
    {
        $this->members[$name] = $methods;
    }

    public function rest($name, array $options = array())
    {
        if ($this->mapper) {
		    $options['name_prefix'] = $this->name;
		    $options['path_prefix'] = $this->path;
            $options['conditions'][$this->controller . '_id'] = '(\d+)';

		    return $this->mapper->rest($name, $options);
        }
        throw new RuntimeException("Could not add subroute to '{$this->name}'. A Mapper object is not defined in this route.");
    }

    /**
     * @inheritdoc
     */
    public function has($name)
    {
        if (strpos($this->name, $name) === 0) {
            $name = substr($name, strlen($this->name));
            if (array_key_exists($name, $this->members) ||
                array_key_exists($name, $this->collections)) {
                return true;
            }
        }
        return false;
    }
}
