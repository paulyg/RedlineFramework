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

use ArrayAccess, IteratorAggregate;

/**
 * Manages configuration options for the framework, application, or your own code.
 *
 * @package Redline
 */
class Config implements ArrayAccess, IteratorAggregate
{
	/**
	 * Holds all of the config keys and values.
	 * @var array
	 */
	private $values = array();

	/**
	 * Hold a list of all serialized options so we can reserialize them when saving.
	 * @var array
	 */
	protected $serializedOptions = array();

	/**
	 * Object constructor.
	 * @param TM_ApplicationController $app
	 * @return Options
	 */
	public function __construct(Tm_ApplicationController $app)
	{
		$this->app = $app;
		$this->db = $app->getDB();
		$this->init();
	}

	/**
	 * Retreive a config value.
	 * @param string $name config key
	 * @return mixed config value
	 */
	public function get($name)
	{
		if (isset($this->values[$name])) {
			return $this->values[$name];
		}
	}

	/**
	 * Add or change a config value.
	 * @param string $name config key
	 * @param mixed $value config value
	 */
	public function set($name, $value)
	{
		$this->values[$name] = $value;
	}

    /**
     * Test to see if a config key exists.
     * @param string $name config key
     * @return bool
     */
    public function has($name)
    {
        return isset($this->values[$name]);
    }

    /**
     * Delete a configuration value.
     * @param string $name config key
     */
    public function remove($name)
    {
        unset($this->values[$name]);
	}

    /**
     * Add a config value without overwriting an existing one.
     * @param string $name config key
	 * @param mixed $value config value
     * @throws Exception When key already exists
     */
    public function add($name, $value)
    {
        if (isset($this->values[$name])) {
            throw new \Exception("Trying to add config key '$name' when it already exists.");
        }
        $this->values[$name] = $value;
    }

    /**
     * Retreive all the config values.
     * @return array
     */
    public function all()
    {
        return $this->values;
    }

    /**
     * Return just the config keys.
     * @return array
     */
    public function keys()
    {
        return array_keys($this->values);
    }

    /**
     * Add config values by merging an array with the existing values.
     * @param array $values
     */
    public function merge(array $values)
    {
        $this->values = array_replace($this->values, $values);
    }

    /**
     * Return values based on a pattern search of the config keys
     * @param string $pattern
     * @return array
     */
    public function filter($pattern)
    {
        $pattern = (string) $pattern;
        if (substr($pattern, -1) == '*') {
            $pattern = substr($pattern, 0, -1);
        }
        if (substr($pattern, -1) != '.')) {
            $pattern .= '.';
        }
        $len = strlen($pattern);

        $return = array();

        /*
        $iterator = new CallbackFilterIterator(
            new ArrayIterator($this->values),
            function($v, $k, $it) use ($pattern, $len) {
                return ($pattern === substr($k, 0, $len));
            }
        );
        foreach ($iterator as $key => $val) {
            $key = substr($key, $len);
            $return[$k] = $val;
        }
        */
        array_walk(
            $this->values,
            function($v, $k) use ($pattern, $len, &$return) {
                if ($pattern === substr($k, 0, $len)) {
                    $k = substr($k, $len);
                    $return[$k] = $v;
                }
            }
        );

        return $return
    }

	/** ArrayAccess Methods */

	public function offsetExists($offset)
	{
		return isset($this->values[$offset]);
	}

	public function offsetGet($offset)
	{
		if (isset($this->values[$offset])) {
			return $this->values[$offset];
		}
	}

	public function offsetSet($offset, $value)
	{
		$this->values[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
	}

    public function getIterator()
    {
        return $this->values;
    }
}
