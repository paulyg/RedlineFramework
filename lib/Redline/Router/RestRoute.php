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
class RestRoute extends RouteCollection
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
     * List of basic resource actions for except and only to work.
     * @var array
     */
    protected $basicActions = array(
        'index'  => array('path' => '/', 'type' => 'collection', 'method' => 'GET'),
        'create' => array('path' => '/', 'type' => 'collection', 'method' => 'POST'),
        'new'    => array('path' => '/new', 'type' => 'collection', 'method' => 'GET'),
        'show'   => array('path' => '/{%s}', 'type' => 'member', 'method' => 'GET'),
        'edit'   => array('path' => '/{%s}/edit', 'type' => 'member', 'method' => 'GET'),
        'update' => array('path' => '/{%s}', 'type' => 'member', 'method' => 'PUT|POST'),
        'delete' => array('path' => '/{%s}', 'type' => 'member', 'method' => 'DELETE|POST'),
    );

	/**
	 * List of add-on collection level actions for this resource.
	 * @var array
	 */
	protected $collections = array();

    /**
	 * List of add-on member level actions for this resource.
	 * @var string
	 */
	protected $members = array();

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
            foreach ($this->basicActions as $key => $val) {
                if (!in_array($key, $options['only'])) {
                    unset($this->basicActions[$key];
                }
            }
        } elseif (isset($options['except'])) {
            foreach ($this->basicActions as $key => $value) {
                if (in_array($key, $options['except'])) {
                    unset($this->basicActions[$key];
                }
            }
        }

        if (isset($options['id_param'])) {
            $this->idParam = $options['id_param'];
        }
        if (isset($options['conditions'][$id_param])) {
            $this->idCondition = $options['conditions'][$id_param];
        }

        $opts = array('conditions' => array($this->idParam => $this->idCondition));

        // Make the routes!
        foreach ($this->basicActions as $action => $args) {
            $c_spec = $controller.'#'.$action;
            if ('collection' == $args['type']) {
                $this->add($args['path'], $c_spec, array('method' => $args['method']));
            } elseif ('member' == $props['type']) {
                $path = sprintf($args['path'], $this->idParam);
                $opts['method'] = $args['method'];
                $this->add($path, $c_spec, $opts);
            }
        }
	}

    /**
     * Add a collection level route to this resource collection.
     *
     * @param string $action
     * @param string $methods
     */
    public function collection($action, $methods = 'GET')
    {
        $this->add('/'.$action, $this->controller.'#'.$action, array('method' => $methods));
        $this->collections[] = $action;
    }

    /**
     * Add a member level route to this CRUD resource collection.
     *
     * @param string $action
     * @param string $methods
     */
    public function member($name, $methods = 'GET')
    {
        $path = '/{'.$this->idParam.'}/'.$action;
        $this->add($path, $this->controller.'#'.$action, array('method' => $methods));
        $this->members[] = $action;
    }

    /**
     * Add another group of RESTful routes as a sub-resource underneath this one.
     *
     * @param string $name
     * @param array $options
     * @return RestRoute
     */
    public function rest($name, array $options = array())
    {
        $options['name_prefix'] = $this->namePrefix;
		$options['path_prefix'] = $this->pathPrefix.'/{'.$this->namePrefix.'_id}';
        $options['conditions'][$this->namePrefix.'_id'] = '(\d+)';

		return $this->routes[] = new RestRoute($name, $options);
    }
}
