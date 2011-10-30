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
 * Master class for interaction with the framework.
 *
 * @package RedlineFramework
 */
class Application
{
	/**
	 * The global configuration object, see {@link getConfig()}.
	 * @var Redline\Config
	 */
	protected $config;

	/**
	 * The database connection, see {@link getDatabase()}.
	 * @var Redline\Database\Connection
	 */
	protected $db;

	/**
	 * Configuration for database connection.
	 * @var array
	 */
	protected $dbconfig;

    /**
     * Class loader and module manager, see {@link getLoader()}.
     * @var Redline\Loader
     */
    protected $loader;

	/**
	 * Global request object, see {@link getRequest()}.
	 * @var Redline\Request
	 */
	protected $request;

    /**
	 * Global response object, see {@link getResponse()}.
	 * @var Redline\Reponse
	 */
	protected $response;
	
	/**
	 * The router object, see {@link getRouter()}.
	 * @var Redline\Router\Mapper
	 */
	protected $router;

	/**
	 * Object representing the current user, see {@link getUser()}.
	 * @var Tm_UserModel
	 */
	protected $user;

	/**
	 * The view object, see {@link getView()}.
	 * @var Redline\View
	 */
	protected $view;

	/**
	 * Object constructor.
	 *
	 * @param array $dbconfig
	 * @return Tm_ApplicationController
	 */
	public function __construct(array $dbconfig)
	{
		$this->dbconfig = $dbconfig;
	}

	/**
	 * Make sure we can connect to the database and grab the config options.
	 * @return bool
	 */
	public function setup()
	{
		require APP_PATH . 'DBALite.php';

		$driver = $this->dbconfig['type'];
		$this->dbconfig['driver_options'] = array(PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING);

		try {
			$this->db = DBALite::factory($driver, $this->dbconfig, false);
		} catch (DBALite_Exception $e) {
			if (DEBUG_MODE) {
				$message = 'Tiramisu could not connect to the database: ' . $e->getMessage();
			} else {
				$message = 'Database connection error. Please try again later.';
			}
			tm_bail('Database connection error', $message);
		}

		$this->config = $this->loadModel('Options');
	}

	/**
	 * Returns the configuration store.
	 * @return Redline\Config
	 */
	public function getConfig()
	{
		if (empty($this->config)) {
            $this->config = new Redline\Config;
        }
        return $this->config;
	}

    /**
     * Set the configuration store.
     * @param Redline\Config $config
     * @return Redline\Application Fluent interface
     */
    public function setConfig(Redline\Config $config)
    {
        $this->config = $config;
        return $this;
    }

	/**
	 * Return the database connection object.
	 * 
	 * Creates the object if it has not been instantiated yet. Allows lazy loading.
	 * @return Redline\Database\Connection
	 */
	public function getDatabase()
	{	
		if (empty($this->db)) {
            //$this->db = new ...;
        }
        return $this->db;
	}

    /**
     * Set the database connection object.
     * @param Redline\Database\Connection
     * @return Redline\Application Fluent interface
     */
    public function setDatabase(Redline\Database\Connection $db)
    {
        $this->db = $db;
        return $this;
    }

	/**
	 * Return the request object.
	 * @return Redline\Request
	 */
	public function getRequest()
	{
        if (empty($this->request)) {
            $this->request = new Redline\Request;
        }
		return $this->request;
	}

	/**
	 * Set the request object.
	 * @param Redline\Request
     * @return Redline\Application Fluent interface
	 */
	public function setRequest(Redline\Request $request)
	{
		$this->request = $request;
		return $this;
	}

	/**
	 * Returns the routing mapper object.
	 * @return Redline\Router\Mapper
	 */
	public function getRouter()
	{
		if (empty($this->router)) {
			$this->router = new Redline\Router\Mapper($this->getRequest());
		}
		return $this->router;
	}

	/**
	 * Set the routing mapper object.
	 * @param Redline\Router\Mapper
	 * @return Redline\Application Fluent interface
	 */
	public function setRouter(Redline\Router\Mapper $router)
	{
		$this->router = $router;
		return $this;
	}

	/**
	 * Return the an object representing the current user.
	 * @return Tm_UserModel
	 */
	public function getUser()
	{
		if (!isset($this->user)) {
			throw new LogicException("User object not set in class Tm_ApplicationController.");
		}
		return $this->user;
	}

	/**
	 * Returns the global view object.
	 * @return Redline\View
	 */
	public function getView()
	{
		if (empty($this->view)) {
			$this->view = new Redline\View;
		}
		return $this->view;
	}

	/**
	 * Set the view object.
	 * @param Redline\View
	 * @return Redline\Application Fluent interface
	 */
	public function setView(Redline\View $view)
	{
		$this->view = $view;
		return $this;
	}

	/**
	 * View a frontend page.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function runFront()
	{
		$controller = $this->loadController('Page');
		try {
			if ($this->request->fetchVar('preview', false) && 
				$theme = $this->request->fetchVar('theme', '')) {
				if (!$this->userLoggedIn()) {
					throw new Exception("Not allowed to preview theme if not logged in", ERR_NOT_FOUND);
				}
				$this->view->setPaths($theme);
			} else {
				$this->view->setPaths($this->config['theme']);
			}
			$controller->viewAction();
			$this->view->renderLayout();
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	/**
	 * Run the dispatch loop, calling the action controller and method for Admin section.
	 *
	 * @returm void
	 * @throws Exception
	 */
	public function runAdmin()
	{
		// Localize dependencies and make sure they are set.
		$request = $this->getRequest();
		$router = $this->getRouter();
		$view = $this->getView();

		try {
			$router->route();

			$controller = $this->loadController($router->getController());

			$action = strtolower($router->getAction()) . 'Action';

			$view->setPaths($router->getController());
				
			if (method_exists($controller, $action)) {
				$controller->$action();
			} else {
				throw new Exception("Not a valid action.", ERR_NOT_FOUND);
			}

			if (!$request->isAsync()) {
				$view->renderLayout();
			}
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	/**
	 * Load a controller class and populate it with the proper dependencies.
	 * @param string $controller
	 * @return ControllerAbstract
	 */
	public function loadController($controller)
	{
		$filename = ucfirst($controller) . 'Controller';
		$core_file = APP_PATH . 'Controllers' . DS . $filename . '.php';
		$plugin_file = PLUGIN_PATH . $controller . DS . $filename . '.php';
		
		if (file_exists($core_file)) {
			include_once $core_file;
			$class = 'Tm_' . $filename;
		} elseif (file_exists($plugin_file)) {
			include_once $plugin_file;
			$class = $filename;
		} else {
			throw new Exception("Controller class file '$filename' not found.", ERR_NOT_FOUND);
		}

		return new $class($this, $this->getRequest(), $this->getView());
	}

	/**
	 * Load a model class and populate it with proper dependencies.
	 * @param string $model
	 * @return ModelAbstract
	 */
	public function loadModel($model)
	{
		$filename = ucfirst($model);
		$file = APP_PATH . 'Models' . DS . $filename . '.php';
		$class = 'Tm_' . $filename;
		if (file_exists($file)) {
			include_once $file;
		} else {
			throw new Exception("Model class '$class' not found.", ERR_FATAL);
		}

		return new $class($this);
	}

	/**
	 * Load a plugin class and populate it with proper dependencies.
	 * @param string $name
	 * @return
	 */
	public function loadPlugin($name)
	{
	}

	/**
	 * Handle exceptions called during dispatch/action cycle and display error page to user.
	 * @param Exception $e
	 * @return void
	 */
	public function handleException($e)
	{
		$controller = $this->loadController('error');
		$controller->defaultAction($e);
		$this->view->setPaths('error');
		$this->view->addStylesheet('main.css');
		$this->view->current_lang = 'en_US';
		$this->view->renderLayout('error.php');
		exit;
		// Render
		// echo $e; // Force __toString()
	}

	public function userLoggedIn()
	{
		if (!isset($this->user)) {
			$this->user = $this->loadModel('User');
			if (isset($_COOKIE[AUTH_COOKIE_NAME]) &&
				$this->authenticateCookie($_COOKIE[AUTH_COOKIE_NAME])) {
				$return = true;
			} else {
				$return = false;
			}
			Tm_Locale::setup($this->getUser(), $this->getConfig());
			return $return;
		}
		return ($this->user->id != 'ANONYMOUS');
	}

	/**
	 * Authenticate the user by checking the authentication cookie.
	 * @param string $cookie Authentication cookie value.
	 * @return bool
	 */
	public function authenticateCookie($cookie)
	{
		$parts = explode(':', $cookie);
		if (count($parts) != 3) {
			return false;
		}

		list($username, $expiration, $cookie_hmac) = $parts;

		$expired = $expiration;

		// Allow a grace period for POST and AJAX requests
		if ($this->request->isPost() || $this->request->isAsync()) {
			$expired += 1800;
		}

		if ($expired < time()) {
			return false;
		}

		$input = $username . ':' . $expiration . ':' . $_SERVER['REMOTE_ADDR'];

		$key = hash_hmac('sha1', $input, COOKIE_SALT);
		$calc_hmac = hash_hmac('sha1', $input, $key);

		if ($cookie_hmac == $calc_hmac) {
			if ($this->user->findByUsername($username)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Send headers for a cachable file.
	 * @param int $expires Unix timestamp representing the expiration of the page cache.
	 * @return void
	 */
	public static function cacheHeaders($expires)
	{
		header('Expires: ' . gmdate('D, d M Y H:i:s', $expires) . ' GMT');
	/*	header('Last-Modified: ' . Tm_DateTime::toHttpDate($lastmodified));	  */
	}

	/**
	 * Send headers to prevent caching of the page or data.
	 * @return void
	 */
	public static function noCacheHeaders()
	{
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
	}
}
