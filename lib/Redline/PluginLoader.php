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
 * A utility class for managing plugins to another class.
 *
 * @package RedlineFramework
 */
class PluginLoader
{
	/**
	 * Map of registered plugin names to classes.
	 * @var array
	 */
	protected $registered = array();

	/**
	 * Collection of plugin instances.
	 * @var array
	 */
	protected $loaded = array();

	/**
	 * Description of prop6
	 * @var Some_Other_Class
	 */
	private $prop6;

	/**
	 * Object constructor.
	 * @param Redline\subpackage\foo\dep_class
	 */
	public function __construct(<bar>\<dep_class> $dep = null)
	{
	}

	/**
	 */
	public function registerPlugin($name, $class)
	{
        $name = strtolower($name);
        $this->registered[$name] = $class;
        return $this;
	}

    public function registerPlugins($map)
    {
        foreach ($map as $name => $class) {
            $this->registerPlugin($name, $class);
        }
        return $this;
    }

    public function unregisterPlugin($name)
    {
        $name = strtolower($name);
        if (isset($this->registered[$name])) {
            unset($this->registered[$name]);
    }

    public function isRegistered($name)
    {
        $name = strtolower($name);
        return isset($this->registered[$name]);
    }

    public function load($name)
    {
        $name = strtolower($name);
        if (isset($this->loaded[$name])) {
            return $this->loaded[$name];
        }

        if (!isset($this->registered[$name])) {
            throw new RuntimeException("Could not load plugin with name '$name'. No plugin with that name is registered.");
        }

        $class = $this->registered[$name];
        $instance = new $class;

        $this->loaded[$name] = $instance;
        return $instance;
    }
}
