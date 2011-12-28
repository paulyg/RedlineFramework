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
namespace Redline;

/**
 * Implements templating features.
 * @package RedlineFramework
 */
class View
{
    /**
     * Holds a map of Helper "shortnames" to full class names.
     * @var array
     */
    static protected $helperClasses = array();

	/**
	 * Holds a reference to the Redline\Application object to pass to Helpers.
	 * @var Redline\Application
	 */
	protected $app;

	/**
	 * Holds a reference to global Redline\Http\Request instance.
	 * @var Redline\Http\Request
	 */
	protected $request;

    /**
	 * Holds template variables to be included in templates/layouts.
	 * @var array
	 */
	protected $vars = array();

	/**
	 * Collection of helper objects.
	 * @var array
	 */
	protected $helpers = array();

	/**
	 * Paths to view scripts (for a controller action).
	 * @var array
	 */
	protected $script_paths = array();

	/**
	 * Name of layout script (template).
	 * @param string
	 */
	protected $layout_file = '';

	/**
	 * Path to view layouts (templates).
	 * @param string
	 */
	protected $layout_path = '';

	/**
	 * Beginning part of all URLs.
	 * @var string
	 */
	protected $base_url = '';

    /**
     * Beginning path part of all relative URLs.
     * @var string
     */
    protected $base_path = '';

	/**
	 * URL to location where theme related files (css, js, images) are stored.
	 * @var string
	 */
	protected $themeUrl = '';

    /**
     * Register a class to a given helper name.
     *
     * @param string $name
     * @param string $class
     */
    static public function registerHelper($name, $class)
    {
        static::$helperClasses[strtolower($name)] = $class;
    }

    /**
     * Register multiple helper classes at once.
     *
     * @param array $helpers Map in the form 'shortname' => 'class name'
     */
    static public function registerHelpers($helpers)
    {
        foreach ($helpers as $name => $class) {
            static::registerHelper($name, $class);
        }
    }

    /**
     * Remove a Helper from the list of Helpers.
     *
     * @param string $name
     */
    static public function unregisterHelper($name)
    {
        $name = strtolower($name);
        if (array_key_exists($name, static::$helperClasses)) {
            unset(static::$helperClasses[$name]);
        }
    }

    /**
     * Is a Helper registered?
     *
     * @param string $name
     */
    static public function isRegistered($name)
    {
        return isset(static::$helperClasses[strtolower($name)]);
    }

	/**
	 * Object Constructor.
	 * @param Redline\Application $app
	 * @param Redline\Request $request
	 * @return Redline\View
	 */
	public function __construct(Application $app, Request $request)
	{
        $this->app = $app;
		$this->request = $request;
	}

	/**
	 * Magic method for view helpers or decorators.
     *
     * @param string $name
     * @param array $args
	 * @return object
	 * @throws BadMethodCallException
	 */
	public function __call($name, $args)
	{
		if (!$this->isRegistered($name)) {
            throw new BadMethodCallException("View Helper '$name' is not registered. Can not create instance.");
        }

        $name = strtolower($name);

        if (!isset($this->helpers[$name])) {
            $class = static::$helperClasses[$name];
            $helper = $this->helpers[$name] = new $class($this->app);
        } else {
            $helper = $this->helpers[$name];
        }

        if (count($args > 0)) {
            return call_user_func_array(array($helper, '__invoke'), $args);
        }
        return $helper;
	}

	/**
	 * Magic method for returning template variables.
	 * @param string $key Template variable name.
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->vars[$key];
	}

	/**
	 * Magic method for setting template variables.
	 * @param string $key Template variable name.
	 * @param mixed $val Template variable value.
	 * @return void
	 */
	public function __set($key, $val)
	{
		$this->vars[$key] = $val;
	}

	/**
	 * Magic method for determining if a template variable is set.
	 * @param string $key Template variable name.
	 * @return boolean
	 */
	public function __isset($key)
	{
		return isset($this->vars[$key]);
	}

	/**
	 * Magic method for unsetting a template variable.
	 * @param string $key Template variable name.
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->vars[$key]);
	}

 	/**
	 * Set the path to folder with view scripts to be rendered.
	 * @param string $path
	 */
	public function setViewScriptPath($path)
	{
		$path = rtrim($path, '/\\');
		$this->viewScriptPath = $path . DS;
	}

	/**
	 * Set multiple template variables at once.
	 *
	 * The parameter may be an associative array of 'variable_name' => 'variable_value'
	 * pairs or an object. If an object is passed all public variables will be assigned
	 * to the template.
	 *
	 * @param array|object $vars
	 * @return bool
	 * @throws InvalidArgumentException on incorrect parameter type or setting private variable.
	 */
	public function merge($vars)
	{
        if (is_array($vars)) {
            $this->vars = array_replace($this->vars, $vars);
        } elseif (is_object($vars)) {
            $this->vars = array_replace($this->vars, get_object_vars($vars));
        } else {
            $type = gettype($vars);
            throw new InvalidArgumentException("Only an array or object may be passed to View:assign(). You passed '$type'.");
        }
	}

    /**
     * Replace the current template variables with a new set. Removes all current vars.
     *
     * @param array|object $vars
     * @throws InvalidArgumentException on incorrect parameter type or setting private variable.
     */
    public function replace($vars)
    {
        if (!is_array($vars) || !is_object($vars)) {
            $type = gettype($vars);
			throw new InvalidArgumentException("Only an array or object may be passed to View:replace(). You passed '$type'.");
        }
        $this->clear();
        $this->assign($vars);
    }

    /**
     * Return the currently assigned template variables.
     *
     * @return array
     */
    public function all()
    {
        return $this->vars;
    }

    /**
     * Remove all currently assigned template variables.
     */
    public function clear()
    {
        $this->vars = array();
    }

    /**
	 * Set the name of the layout script to render.
	 * @param string $file Layout script file name.
	 * @return void
	 */
	public function setLayout($file)
	{
		$this->layoutFile = $file;
	}

	public function urlFor($route, $args)
	{
	}

	public function linkFor($route, $args, $relative = true)
	{
	}

	public function pathFor($route, $args)
	{
	}

	public function img($filename)
	{
	}

	/**
	 * Renders a view script/template file.
	 *
	 * @param string $script The name of the view script to render.
	 * @param string $varName Name of the placeholder var to store the output in.
	 * @return void
	 * @throws LogicException if the view script does not exist.
	 */
	public function render($__file = null, $__vars = array(), $__into = '')
	{
		$__file = $this->findScript($__file);
		
		if (!file_exists($__file)) {
			throw new LogicException("The view script '$__file' does not exist.");
		}

        if (empty($__vars)) {
            $__vars = $this->vars;
        }

		ob_start();
		extract($__vars, EXTR_SKIP);
		include $__file;
        if (!empty($__into)) {
            $this->vars[$__into] = ob_get_clean();
            return;
        }
        return ob_get_clean();
	}

	/**
	 * Render a view layout, sending output to the user.
	 * @return void
	 */
	public function renderLayout()
	{
		$file = $this->layoutFile;

		if (substr($file, -4) != '.php') {
			$file = $file . '.php';
		}
		$file = $this->layoutPath . $file;

		if (!file_exists($file)) {
			throw new LogicException("The layout script '$file' does not exist.");
		}

		$this->setCommon();
		extract($this->vars, EXTR_OVERWRITE);
		include $file;
	}

    protected function findFile($spec)
    {
        extract($this->app->getDispatchParams());
        $module = $this->app->getModule($module);
        $buildPath = function() use ($module, $controller, $action) {
            if (is_object($module)) {
                $modulePath = $module->getPath();
            } else {
                $modulePath = ROOT_DIR . 'application' . DIRECTORY_SEPARATOR;
            }
            return  $modulePath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR .
                $controller . DIRECTORY_SEPARATOR . $action . '.php';
        };

        if (empty($spec)) {
            return $buildPath();
        } elseif (strpos($spec, '#') === false) {
            $action = $spec;
            return $buildPath();
        } else {
            list($controller, $action) = explode('#', $controller_spec);
            if (strpos($controller, ':') !== false) {
                list($module, $controller) = explode(':', $controller);
            }
            return $buildPath();
        }
    }
}
