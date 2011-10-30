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
namespace Redline\ViewHelper;

/**
 * Class for managing scripts in HTML documents.
 *
 * @package RedlineFramework
 */
class Script
{
	/**
	 * Description of prop1
	 * @var string
	 */
	public static $prop1;

	/**
	 * Description of prop2
	 * @var boolean
	 */
	protected static $prop2 = false;

	/**
	 * Description of prop3
	 * @var integer
	 */
	private static $prop3;

	/**
	 * Description of prop4
	 * @var array
	 */
	public $prop4 = array();

	/**
	 * Description of prop5
	 * @var Redline\subpackage\class
	 */
	protected $prop5;

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

    public function addInHead($file, $name, $deps = array())
    {
    }

    public function shiftInHead($file, $name, $deps = array())
    {
    }

    public function addInTail($file, $name, $deps = array())
    {
    }

    public function shiftInTail($file, $name, $deps = array())
    {
    }

    public function addInline($contents)
    {
    }

    public function shiftInline($contents)
    {
    }

    public function printHead()
    {
    }

    public function printTail()
    {
    }

    public function printInline()
    {
    }
}
