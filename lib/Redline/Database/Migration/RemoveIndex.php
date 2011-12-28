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
namespace Redline\Database\Migration;

use Redline\<foo> as <bar>;

/**
 * Description of class.
 *
 * @package RedlineFramework
 */
class RemoveIndex implements Action
{
	/**
	 * Name of the index to drop.
	 * @var string
	 */
	protected $name;

    /**
     * Whether to use "IF EXISTS" on DROP INDEX statement.
     * @var boolean
     */
    protected $ifExists = false;

	/**
	 * Object constructor.
     *
	 * @param string $name Index name.
	 */
	public function __construct($name)
	{
        $this->name = (string) $name;
	}

	/**
	 * Set the IF EXISTS flag to suppress error if the index does not exists.
     *
     * This flag is false by default. Calling this method with no argument will set
     * the flag to true. You can also pass this method a boolean.
	 *
	 * @param boolean $flag
	 * @return self
	 */
	public function ifExists($flag = true)
	{
        $this->ifExists = (boolean) $flag;

        return $this;
	}

    /**
     * @inheritdoc
     */
    public function getSql()
    {
        $sql = 'DROP INDEX ' . (($this->ifExists) ? 'IF EXISTS ' : '') . $this->name;
        return $sql;
    }
}
