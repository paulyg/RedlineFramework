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

use Redline\Config,
    Redline\ClassLoader,
    Redline\Request,
    Redline\Response,
    Redline\Router\Router,
    Redline\View;

/**
 * Master class for interaction with the framework.
 *
 * @package RedlineFramework
 *
 * @property Redline\Config $config
 * @property Redline\Loader $loader
 * @property Redline\Request $request
 * @property Redline\Reponse $response
 * @property Redline\Router\Router $router
 * @property Redline\View $view
 */
class Application
{
    /**
     * The global configuration object, see {@link config()}.
     * @var Redline\Config
     */
    public $config;
    
    /**
     * Service container/dependency injection container object, see {@link container()}.
     * @var Pimple
     */
    public $container;

    /**
     * Class loader and module manager, see {@link loader()}.
     * @var Redline\Loader
     */
    public $loader;

    /**
     * Loaded module classes.
     * @var array
     */
    public $modules = array();
    
    /**
     * Global request object, see {@link request()}.
     * @var Redline\Request
     */
    public $request;

    /**
     * Global response object, see {@link response()}.
     * @var Redline\Reponse
     */
    public $response;
    
    /**
     * The router object, see {@link router()}.
     * @var Redline\Router\Mapper
     */
    public $router;

    /**
     * The view object, see {@link view()}.
     * @var Redline\View
     */
    public $view;

    /**
     * Object constructor.
     *
     * Accepts configuration options for the core framework sets up defaults for the
     * service container.
     *
     * Configuration keys:
     * - 'env' => an environment string that affects which config files are loaded, default empty
     * - 'namespaced_application' => whether code in the 'app/' folder is namespaced, default true
     * - 'app_namespace_prefix' => a namespace prefix to apply to all code in the 'app/' folder.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->config = new Config($config);
        $this->defineDefaultServices();
    }

    /**
     * Creates all objects not all ready set and prepares the application to run.
     */
    public function startup()
    {
        foreach ($this->modules as $module) {
            $module->startup($this->container);
            if ($mod_config = $module->getConfig()) {
                $this->container['config']->merge($mod_config);
            }
        }
        
        $config_files = array(APP_DIR.'/config/config.php', APP_DIR."/config/config-{$this->env}.php");
        foreach ($config_files as $config_file) {
            if (file_exists($config_file)) {
                $this->container['config']->merge(include $config_file);
            }
        }
        
        if (file_exists($routing_file = APP_DIR.'/config/routes.php')) {
            $this->loadRoutesFromFile($routing_file);
        }
    }
    
    /**
     * Set or get the configuration store.
     *
     * @param Redline\Config $config
     * @return Redline\Config
     * @return Redline\Application when setting for fluent interface
     */
    public function config(Redline\Config $config = null)
    {
        if (is_null($config)) {
            if (empty($this->config)) {
                $this->config = new Redline\Config();
            }
            return $this->config;
        }
        return $this->config;
    }

 
    /**
     * Get or set the server request object.
     *
     * @param Redline\Request $request
     * @return Redline\Request
     * @return Redline\Application when setting for fluent interface
     */
    public function request(Redline\Request $request = null)
    {
        if (is_null($request)) {
            if (empty($this->request)) {
                $this->request = new Redline\Request();
            }
            return $this->request;
        }
        $this->request = $request;
        return $this;
    }

    /**
     * Get or set the server response object.
     *
     * @param Redline\Response $response
     * @return Reline\Response when getting
     * @return Redline\Application when setting for fluent interface
     */
    public function response(Redline\Response $reponse = null)
    {
        if (is_null($reponse)) {
            if (empty($this->response)) {
                $this->response = new Redline\Response();
            }
            return $this->reponse;
        }
        $this->reqsponse = $response;
        return $this;
    }

    /**
     * Get or set the routing mapper object.
     *
     * @param Redline\Router\Mapper $router
     * @return Redline\Router\Mapper when getting
     * @return Redline\Application when setting for fluent interface
     */
    public function router(Redline\Router\Mapper $router = null)
    {
        if (is_null($router)) {
            if (empty($this->router)) {
                $this->router = new Redline\Router\Mapper($this->request());
            }
            return $this->router;
        }
    $this->router = $router;
        return $this
    }


    /**
     * Get or set global view object.
     *
     * @param Reline\View $view
     * @return Redline\View when getting
     * @return Redline\Application when setting for fluent interface
     */
    public function view(Redline\View $view = null)
    {
        if (is_null($view)) {
            if (empty($this->view)) {
                $this->view = new Redline\View();
            }
            return $this->view;
        }
        $this->view = $view
        return $this;
    }
    
    /**
     * Register modules to be used in the application.
     *
     * @param array $modules array of Module objects, one for each module to register.
     * @return Redline\Application fluent interface
     */
    public function registerModules(array $modules)
    {
        foreach ($modules as $name => $module) {
            
            if (!($module instanceof Redline\ModuleInterface)) {
                throw new InvalidArgumentException('Objects passed must be an instance of Redline\ModuleInterface.');
            }
            if (is_numeric($name)) {
                $name = $module->getName();
            }
            if (isset($this->modules[$name])) {
                throw new RuntimeException('Attempt to use same name for two modules is not allowed.');
            }
            
            $this->modules[$name] = $module;
        }
        return $this;
    }
    
    /**
     * Return all of the registered modules.
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Return a Module object by name.
     *
     * You can test whether a module is registered by checking for a null return value.
     *
     * @param string $name
     * @return ModuleInterface|null
     */
    public function getModule($name)
    {
        return (isset($this->modules[$name])) ? $this->modules[$name] : null;
    }

    /**
     * Creates definitions for default version of services required by the framework.
     */
    public function defineDefaultServices()
    {
        $container = $this;

        $container['config'] = function() {
            return new Config();
        };

        $container['loader'] = function() {
            return new ClassLoader();
        };

        $container['request'] = function() {
            return Request::createFromGlobals();
        };

        $container['response'] = function() use ($container) {
            return new Response($container['request']);
        };

        $container['router'] = function() use ($container) {
            return new Router($container['request']);
        };

        $container['view'] = function() use ($container) {
            return new View($container);
        };
    }

    /**
     * Convinience function for loading a config file and merging it into the Config instance.
     *
     * The file should return an array that can be passed to Config::merge().
     *
     * @param string $_file The config file name.
     */
    public function loadConfig($_file)
    {
        $values = include $_file;
        $this->config->merge($values);
    }

    /**
     * Convinience function to load routes from a file such as app/config/routes.php.
     *
     * This is a separate function so that the only two variables in the local scope when
     * including the file are $mapper and $__file.
     *
     * @param string $__file A file name to load.
     */
    public function loadRoutes($_file)
    {
        $router = $this->container['router'];
        
        include_once $_file;
    }

    /**
     * Parses the controller specification strings found in routes.
     *
     * The specification string is split into a class and action. Any module
     * references are replaced with the module's namespace resulting in a
     * fully qualified class name. The return array is the same format as
     * as expected by call_user_func_array & friends.
     *
     * @param string $spec
     * @return array
     */
    public function resolveControllerSpec($spec)
    {
        if (substr_count($spec, '#') !== 1) {
            throw new Exception("The controller specification '$spec' does not contain a controller class and action seperated by the '#' character.");
        }
        list($controller, $action) = explode('#', $spec);

        if ($controller[0] == '@') {
            $pos = strpos($controller, '\\');
            $name = substr($controller, 1, $pos -1);
            $controller = substr($controller, $pos);
            if (! $module = $this->getModule($name)) {
                throw new Exception("A module with the name '$name' could not be found.");
            }
            $base_ns = $module->getNamespace();
        } else {
            $base_ns = $this->config['app_namespace_prefix'] ?: '';
        }
        
        if ($this->config['namespaced_app']) {
            $controller = '\\Controller\\' . ltrim($controller, '\\') . 'Controller';
        } else {
            $controller = 'Controller_' . $controller . 'Controller';
        }
        $controller = $base_ns . $controller;
        $controller = ltrim($controller, '\\');

        return array($controller, $action);
    }

    /**
     * View a frontend page.
     *
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
    /*  header('Last-Modified: ' . Tm_DateTime::toHttpDate($lastmodified));   */
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
