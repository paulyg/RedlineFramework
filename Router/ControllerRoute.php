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
class Route implements \RecursiveIterator
{
	/**
	 * Unique name or identifier of this route
	 * @var string
	 */
	protected $name;

    /**
	 * Regex patttern that will be used for matching.
	 * @var string
	 */
	protected $pattern;

    /**
	 * Original path spec, before being converted to a regex pattern.
	 * @var string
	 */
	protected $path;

    /**
	 * Regex pattern conditions to be applied to the path spec.
	 * @var array
	 */
	protected $conditions = array();

    /**
	 * Default values for the named path segments.
	 * @var array
	 */
	protected $default = array();

    /**
	 * List of HTTP methods this route may be used with.
	 * @var array
	 */
	protected $methods = array();

    /**
     * Module in which routed controller is located, leave blank for 'app'.
     * @var string
     */
    protected $module;

    /**
     * Controller class which method to be invoked is located.
     * @var string
     */
    protected $class;

    /**
     * Action which is to be invoked when route is matched.
     * @var string
     */
    protected $action;

	/**
	 * Children routes which will inherit the path and name as a prefix.
	 * @var array
	 */
	protected $subroutes = array();

	/**
	 * Object constructor.
	 * @param string $path URL path specification to match route against.
	 * @param string $controller_spec Special format indicating module, controller class
     *                                & action to call.
	 * @param array  $options Name, method, conditions, and default options.
	 */
	public function __construct($path, $controller_spec, array $options = array())
	{
		if (isset($options['path_prefix'])) {
            $path = $options['path_prefix'] . '/' . $path;
        }

        if (isset($options['conditions'])) {
            $this->conditions = $options['conditions'];
        }

        $this->pattern = $ths->compilePattern($path);

        // The hash char must not be 1st char, so we accept 0 as false below instead of 
        // using === false
        if (!strpos($controller_spec, '#')) {
            throw new Exception("The controller specification '$controller_spec' does not contain a controller class and action seperated by the '#' character.");
        }
        list($controller, $action) = explode('#', $controller_spec);
        
        // The colon must not be 1st char, se we accept 0 as false again.
        if (strpos($controller, ':')) {
            list($module, $controller) = explode(':', $controller);
        } else {
            $module = '';
        }
        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;

        if (isset($options['name'])) {
            $name = $options['name'];
        } else {
            $name = $controller . "_" . $action;
            $name = (empty($module)) ? $name : $module . "_" . $name;
        }

        if (isset($options['name_prefix'])) {
            $name = $options['name_prefix'] . '_' . $name;
        }
        $this->name = $name;

        $this->defaults = $options['defaults'];
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

	public function hasChildren()
	{
		return !empty($this->subroutes);
	}

	public function getChildren()
	{
		return $this->subroutes;
	}


	/**
	 * Create multiple common routing paths for working with a single entity type.
     * @param string $name Resource name
	 * @return type
	 */
	public function resource($name)
	{
        $path = $this->path . '/' . $name . '/';
        $id_condition = array(
            'conditions' => array('id' => '(\d+)'),
            'method' => 'GET'
        );
        $this->subroutes[] = new Route($path, $name . '#index');
        $this->subroutes[] = new Route($path . '{:id}', $name . '#show', array(
            'conditions' => array('id' => '(\d+)'),
            'method' => 'GET'
        ));
        $this->subroutes[] = new Route($path . 'new', $name . '#new', array(
            'method' => 'GET|POST'
        ));
        $this->subroutes[] = new Route($path, $name . '#create', array(
            'method' => 'POST'
        ));        
        $this->subroutes[] = new Route($path . 'edit/{:id}', $name . '#edit', array(
            'conditions' => array('id' => '(\d+)'),
            'method' => 'GET|POST'
        ));
        $this->subroutes[] = new Route($path . '{:id}', $name . '#update', array(
            'method' => 'PUT'
        ));
        $this->subroutes[] = new Route($path . 'delete/{:id}', $name . '#delete', array(
            'conditions' => array('id' => '(\d+)'),
            'method' => 'POST'
        ));
        $this->subroutes[] = new Route($path . '{:id}', $name . '#delete', array(
            'conditions' => array('id' => '(\d+)'),
            'method' => 'DELETE'
        ));
	}
}
