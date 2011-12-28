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
 * Base class which can be extended to create a Module.
 *
 * @package RedlineFramework
 */
abstract class Module
{
	/**
	 * Name of the Module.
	 * @var string
	 */
	protected $name = null;

	/**
	 * Object constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Allows module to initalize itself.
	 *
	 * The init() method is passed the Redline\Application object from which it can
	 * do anything it needs to set itself up. Common tasks include:
	 * 
	 * Setting objects or definitions as services.
	 * Registering event handlers.
	 * Setting up custom autoloaders.
     * Registering plugin classes such as View Helpers or Form Element Types.
	 * 
	 * @param Redline\Application $app
	 */
	public function init(Application $app) { }

    /**
     * If your module needs to provide default configuration values redefine this
     * method and return them as an array.
     *
     * @return array
     */
    public function getConfig() { }

    /**
     * If you want to provide predefined routes redefine this method and return
     * a Redline\Routing\RouteCollection object from it.
     *
     * @return Routing\RouteCollection
     */
    public function getRoutes() { }

    /**
     * Returns the "short name" of the module, and of the class.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->name !== null) {
            return $this->name;
        }

        $name = get_class($this);
        $pos = strrpos($name, '\\');

        return $this->name = ($pos === false ? $name : substr($name, $pos + 1));
    }

    /**
     * Returns the directory where the module class is located, and thus the root
     * of the module.
     *
     * @return string
     */
    public function getDir()
    {
        return __DIR__;
    }

    /**
     * Returns the namespace this class is in, and thus the base namespace for
     * the module.
     *
     * @return string
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }
}
