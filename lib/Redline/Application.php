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
    Redline\Request,
    Redline\Response,
    Redline\Router\Router,
    Redline\View,
    paulyg\Vessel,
    Symfony\Component\ClassLoader\UniversalClassLoader,
    Symfony\Component\EventDispatcher\Event,
    Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Master class for interaction with the framework.
 *
 * Usage:
 * use Redline\Application;
 * $app = new Application();
 * $app->registerModules(array(
 *    'users' => 'Acme\Redline\UserModule',
 *    'facebook' => 'Timmy\FacebookConnector'
 * ));
 * In Symfony modules are loaded before config (and routing).
 * $app->loadModules();
 * $app->loadConfig();
 * $app->loadRoutes();
 * $app->run()->send();
 *
 * @package RedlineFramework
 *
 * @property Symfony\Component\ClassLoader\UniversalClassLoader $classLoader
 * @property Redline\Config $config
 * @property Symfony\Component\EventDispatcher\EventDispatcher $eventManager
 * @property Redline\Request $request
 * @property Redline\Reponse $response
 * @property Redline\Router\Router $router
 * @property Redline\View $view
 */
class Application extends Vessel implements EventSubscriberInterface
{
    /**
     * Namespace prefix to apply for all classes in the app/ directory.
     * @var string
     */
    protected $appNamespace;
    
    /**
     * Class name prefix to apply for all classes in the app/ directory.
     * @var string
     */
    protected $appPrefix;
    
    /**
     * Flag whether to show verbose error messages or not.
     * @var bool
     */
    protected $debug = false;
    
    /**
     * A string representing an operating environment.
     * @var string
     */
    protected $env;

    /**
     * Loaded modules classes.
     * @var array
     */
    protected $loadedModules = array();

    /**
     * Flag to determine if modules loadModules() has been called.
     * @var boolean
     */
    protected $modulesLoaded = false;
    
    /**
     * An identifier for the application, the name of the directory containing it.
     * @var string
     */
    protected $name;

    /**
     * List of registered modules.
     * @var array
     */
    protected $registeredModules = array();
    
    /**
     * The root of the application on the filesystem.
     * @var string
     */
    public $rootDir;

    /**
     * Object constructor.
     *
     * Accepts basic configuration options for the core framework sets up defaults for the
     * service container.
     *
     * @param string $root The root of the application on the filesystem.
     * @param string $ns A namespace prefix to apply to all application classes. Leave empty to use PEAR style naming.
     * @param string $prefix A class name prefix to apply to all application classes. Leave empty to use namespaced classes.
     * @param string an environment string that affects which config files are loaded, default empty.
     * @param boolean A flag to enable debug mode, default false.
     */
    public function __construct($root = '', $ns = '', $prefix = '', $env = '', $debug = false)
    {
        /*
        Boot process, how do we accomodate:
        - include autoloader class file
        - create autoloader instance
        - configure autoloader with all framework namespaces
        - configure autoloader with app namespace or prefix
        - create application instance
        - pass list of modules to application
        - load modules
        - give modules a chance to add to config keys
        - give modules a chance to add event hooks
        - give modules a chance to add view helpers, register css, js
        - pull in "global" app config
        - load routes, allowing app author to pull routes from modules if they wish
        - fire an event indicating the app is ready to run, is this same as the module init?, pre-route?
        */
        if ($ns && $prefix) {
            throw new Exception("You can not declare both a namespace prefix and PEAR style prefix for your classes. Choose only one approach.");
        }
        $this->appNamespace = $ns;
        $this->appPrefix = $prefix;
        
        $this->rootDir = realpath($root);
        if (!is_dir($this->rootDir)) {
            throw new Exception("The given root directory, $root, is not a valid directory.");
        }
        $this->name = basename($this->rootDir);
        
        $this->env = $env;
        $this->debug = $debug;
        
        $this->defineDefaultServices();
    }

    /**
     * Convenience function that loads modules, loads default configuration file,
     * loads default routes. You can call these  methods individually instead of
     * calling boot().
     *
     * return Redline\Application fluent interface
     */
    public function boot()
    {
        $this->loadModules();        
        $this->loadConfig();
        $this->loadRoutes();
        
        return $this;
    }
    
    /**
     * Register modules to be used in the application.
     *
     * @param array $modules Array of Module namespaces/paths.
     * @return Redline\Application fluent interface
     */
    public function registerModules(array $modules)
    {
        if ($this->modulesLoaded) {
            trigger_error("Call to Application::registerModules() not allowed after modules have been loaded.", E_USER_NOTICE);
            return;
        }
        
        $this->registeredModules = array_replace($this->registeredModules, $modules);
        return $this;
    }
    
    /**
     * Return all of the loaded modules.
     *
     * @return array
     */
    public function getModules()
    {
        return $this->loadedModules;
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
        return (isset($this->loadedModules[$name])) ? $this->loadedModules[$name] : null;
    }
    
    /**
     * Load modules classes to be used in the application.
     *
     * @param array $modules Array of Module namespaces/paths.
     * @return Redline\Application fluent interface
     */
    public function loadModules(array $modules = array())
    {
        if ($this->modulesLoaded) {
            trigger_error("Application::loadModules() called more than once.", E_USER_NOTICE);
            return;
        }
        
        if (!empty($modules)) {
            $this->registerModules($modules);
        }

        foreach ($this->registeredModules as $moduleNS) {
            
            $class = trim($moduleNS, '\\') . '\\Module';
            
            $path = $this->modulesDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            $basePath = $this->moduleDir . str_replace('\\', DIRECTORY_SEPARATOR, $moduleNS);
            if (!file_exists($path)) {
                throw new Excpetion("Module file '$path' does not exist.");
            }
            
            require $path;
            $module = new $class;
            
            if (!($module instanceof Redline\ModuleInterface)) {
                throw new InvalidArgumentException('Objects passed must be an instance of Redline\ModuleInterface.');
            }
            
            $name = $module->getName();

            if (isset($this->loadedModules[$name])) {
                $firstNS = $this->loadedModules[$name]->getNamespace();
                throw new RuntimeException("Module name '$name' is already being used by module '$firstNS'.");
            }
            
            $this->classLoader->registerNamespace($moduleNS, $basePath);
            
            if (method_exists($module, 'init')) {
                $module->init($this);
            }
            
            $this->loadedModules[$name] = $module;
        }

        $this->modulesLoaded = true;
        
        return $this;
    }

    /**
     * Creates definitions for default version of services required by the framework.
     */
    protected function defineDefaultServices()
    {
        $this->classLoader = function() {
            return new UniversalClassLoader();
        };
            
        $this->config = function() {
            return new Config();
        };

        $this->eventManager = function() {
            return new EventDispatcher();
        };

        $this->request = function() {
            return Request::createFromGlobals();
        };

        $this->response = function($c) {
            return new Response($c->request);
        };

        $this->router = function($c) {
            return new Router($c->request);
        };

        $this->view = function($t) {
            return new View($t);
        };
    }

    /**
     * Convinience function for loading a config file and merging it into the Config instance.
     *
     * The file should return an array that can be passed to Config::merge().
     *
     * @param string $__file The config file name, defaults to app/config/config.php.
     */
    public function loadConfig($__file = null)
    {
        if (is_null($__file)) {
            $__file = $this->rootDir . 'app/config/config.php';
        }
        $__values = include $__file;
        $this->config->merge($__values);
        
        return $this;
    }

    /**
     * Convinience function to load routes from a file such as app/config/routes.php.
     *
     * This is a separate function so that the only two variables in the local scope when
     * including the file are $router and $__file.
     *
     * @param string $__file A file name to load, defaults to app/config/routes.php.
     */
    public function loadRoutes($__file = null)
    {
        if (is_null($__file)) {
            $__file = $this->rootDir . 'app/config/routes.php';
        }
        $router = $this->router;
        
        include_once $__file;
        
        return $this;
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
            $plugin = $this->getPlugin($name)
            if (!$plugin) {
                throw new Exception("A plugin with the name '$name' could not be found.");
            }
            $base_ns = $plugin->getNamespace();
        } else {
            $base_ns = $this->appNamespace;
        }
        
        if ($base_ns) {
            $controller = '\\Controller\\' . ltrim($controller, '\\') . 'Controller';
        } else {
            $controller = 'Controller_' . $controller . 'Controller';
        }
        $controller = $base_ns . $controller;
        $controller = ltrim($controller, '\\');
        
        $action = $action . 'Action';

        return array($controller, $action);
    }

        /*
        Event hooks, we will need to add them in, this is how others do it:
        Slim
            slim.before
            slim.before.router
            slim.before.dispatch
            slim.after.dispatch
            slim.after.router
            slim.after
        Zend Framework 2
            bootstrap
            route (does actual framework routing)
            dispatch (does actual framework dispatching)
            dispatch.error
            finish
        Symfony 2
            kernel.request
            kernel.controller
            kernel.exception
            kernel.view
            kernel.response
        */
    /**
     * Run the routing match and dispatch cycle.
     */
    public function run()
    {
        // Localize dependencies and make sure they are set.
        $eventManager = $this->eventManager;
        $request = $this->request;
        $router = $this->router;
        $view = $this->view;
        
        $event = new MvcEvent;
        $event->setDispatcher($eventManager);
        $event->setApplication($this);

        try {
            $this->eventManager->dispatch('app.loaded', $event);
            
            $match = $router->match($request->rewrittenPath(), $request->method());
            $event->matchedRoute($match);
            $event->setName('app.routed');
            $eventManager->dispatch('app.routed', $event);
            
            /*
            if ($event->sendResponse()) {
                return $this->response;
            }
            */

            $controller = $match->getController();
            $request->mergeQueryParams($match->getParams());
            if (!is_callable($controller)) {
                list($class, $action) = $this->resolveControllerSpec($controller);
                $object = new $class($this);
                $controller = array($object, $action);
            }

            $result = call_user_func($controller);
            $event->dispatchResult($result);
            $event->setName('app.dispatched');
            $eventManager->dispach('app.dispatched', $event);
                
          } catch (Exception $e) {
            $this->handleException($e, $event);
        }
        
        return $this->reponse;
    }

    /**
     * Handle exceptions called during routing/dispatch cycle and display error page to user.
     * @param Exception $e
     * @param MvcEvent $event
     */
    public function handleException(Exception $e, MvcEvent $event = null)
    {
        if (is_null($event)) {
            $event = new MvcEvent;
            $event->setDispatcher($this->eventManager);
            $event->setAppication($this);
        }
        $event->setName('app.exception');
        $this->eventManager->dispatch('app.exception', $event);
        
        $controller = $this->errorController();
        if (!is_callable($controller)) {
            list($class, $action) = $this->resolveControllerSpec($controller);
            $object = new $class($this);
            $controller = array($object, $action);
        }
        
        call_user_func_array($controller, array($e));
    }
}
