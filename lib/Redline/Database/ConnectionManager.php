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
namespace Redline\Database;

/**
 * Manages database connection objects & configs.
 *
 * @package RedlineFramework
 */
class ConnectionManager
{
	/**
	 * Database connection configurations.
	 * @var array
	 */
	protected static $configs = array();

	/**
	 * Database connection objects.
	 * @var array
	 */
	protected static $connections = array();

	/**
	 * Private constructor prevents instanciation.
	 */
	private function __construct() {}


    public static function getConnection($name)
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        if (isset(self::$configs[$name])) {
            return self::createConnection($name);
        }
    }

    public static function setConnection($name, $connection)
    {
        self::$connections[$name] = $connection;
    }

    public static function createConnection($name, array $config = array())
    {
        if (isset(self::$configs[$name]) && empty($config)) {
            $config = self::$configs[$name];
        }

        return self::$connections[$name] = Connection::factory($config);
    }

    public static function closeConnection($name)
    {
        if (isset(self::$connections[$name])) {
            unset(self::$connections[$name]);
        }
    }

    public static function setConfig($name, array $config)
    {
        self::$configs[$name] = $config;
    }

    public static function getConfig($name)
    {
        if (isset(self::$configs[$name])) {
            return self::$configs[$name];
        }
    }

    public static function addConfigs(array $new_configs)
    {
        self::$configs = array_replace(self::$configs, $new_configs
    }

    public static function removeConfig($name)
    {
        if (isset(self::$configs[$name])) {
            unset(self::$configs[$name]);
        }
    }
}
