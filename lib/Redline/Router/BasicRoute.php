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
 * Represents a single URI path definition.
 *
 * @package RedlineFramework
 */
class BasicRoute implements RouteInterface
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
	protected $conditions;

    /**
	 * Default values for the named path segments.
	 * @var array
	 */
	protected $default;

    /**
	 * List of HTTP methods this route may be used with.
	 * @var array
	 */
	protected $methods;

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
	 * Array of parameter names and values captured by routing match.
	 * @var array
	 */
	protected $params = array();

    /**
     * Reference back to the Mapper class for adding subroutes.
     * @var Mapper
     */
    protected $mapper;

	/**
	 * Construct a new BasicRoute.
     *
	 * @param string $path URL path specification to match route against.
	 * @param string $controller_spec Special format indicating module, controller class
     *                                & action to call.
	 * @param array  $options Name, method, conditions, and default options.
     * @param Mapper $mapper  Reference back to the mapper for creating subroutes.
	 */
	public function __construct($path, $controller_spec, array $options = array(), $mapper = null)
	{
		if (isset($options['path_prefix'])) {
            $path = rtrim($options['path_prefix'], '/') . '/' . ltrim($path, '/');
        }

        $this->conditions = isset($options['conditions']) ? $options['conditions'] : array();
        $this->defaults = isset($options['defaults']) ? $options['defaults'] : array();

        $this->path = $path;

        // Hash char must not be 1st char so we accept 0 as false instead of using === false
        if (!strpos($controller_spec, '#')) {
            throw new Exception("The controller specification '$controller_spec' does not contain a controller class and action seperated by the '#' character.");
        }
        list($controller, $action) = explode('#', $controller_spec);
        
        // Colon must not be 1st char, so we accept 0 as false again.
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

        if ($mapper instanceof Mapper) {
            $this->mapper = $mapper;
        }
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
        if ($this->mapper) {
		    $options['name_prefix'] = $this->name;
		    $options['path_prefix'] = $this->path;
            $options['conditions'] = array_replace($this->conditions, $options['conditions']);
            $options['defaults'] = array_replace($this->defaults, $options['defaults']);

		    return $this->mapper->connect($path, $controller_spec, $options);
        }
        throw new RuntimeException("Could not add subroute to '{$this->name}'. A Mapper object is not defined in this route.");
	}

	/**
     * @inheritdoc
     */
    public function match($uri_path)
    {
        if (empty($this->pattern)) {
            $this->compilePattern();
        }

        if (preg_match("@^{$this->pattern}$@", $uri_path, $param_matches) {
            $params = $this->defaults;
            array_shift($param_matches);
            foreach ($param_matches as $key => $val) {
                if (is_string($key)) {
                    $params[$key] = $val;
                }
            }
            // merge in module, controller & action for fetch them separatly?
            // if there are no params in route (static) but it still matches
            // calling code may think the empty array is false!
            return $params;
        }
        return false;
    }

    /**
     * Create the regex pattern to be matched against the requested URI path.
     *
     * Creating the regex pattern is done "lazily", only when we are asked to do a match
     * for performance reasons.
     * 
     * @return void
     */
    public function compilePattern()
    {
        $pattern = str_replace(')', ')?' $this->path);
        preg_match_all('@:([\w]+)@', $pattern, $param_names, PREG_PATTERN_ORDER);
        $param_names = $param_names[0];

        if ($param_names) {
            $names = array();
            $conditions = array();
            foreach ($param_names as $key) {
                $condition = isset($this->conditions[$key]) ? $this->conditions[$key] : '([^/]+)';
                $names[] = ':' . $key;
                $conditions[] = "(?P<$key>" . substr($condition, 1);
            }
            $pattern = str_replace($names, $conditions, $pattern)
        }
        $this->pattern = $pattern;
    }

    /**
     * @inheritdoc
     */
    public function build($params)
    {
        $keys = array();
        $vals = array();
        $params= array_merge($this->defaults, $params);
        foreach ($params as $key => $val) {
            $keys[] = ':' . $key;
            $vals = urlencode($val);
        }
        return str_replace($keys, $vals, $this->path);
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function has($name)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getParams()
    {
        return $this->params;
    }
}
