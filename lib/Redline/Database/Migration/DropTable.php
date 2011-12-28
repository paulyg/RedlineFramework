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
class DropTable implements Action
{
	/**
	 * Name of the table to drop.
	 * @var string
	 */
	protected $name;

    /**
     * Whether to use "IF EXISTS" on DROP TABLE statement.
     * @var boolean
     */
    protected $ifExists = false;

    /**
     * Whether to use CASCADE clause on DROP TABLE statement.
     * @var boolean
     */
    protected $cascade = false;

	/**
	 * Object constructor.
     *
	 * @param string $name Table name.
	 */
	public function __construct($name)
	{
        $this->name = (string) $name;
	}

	/**
	 * Set the IF EXISTS flag to suppress error if the table does not exists.
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
	 * Add the CASCADE flag to the statement.
	 *
	 * CASCADE automatically deletes items that depend on the table such as views.
     * This option only works on PostgreSQL. MySQL will let you add it to the statement
     * but it does nothing (there for portability).
     * omitted or you can use both methods.
	 * 
     * This flag is false by default. Calling this method with no argument will set
     * the flag to true. You can also pass this method a boolean.
	 *
	 * @param boolean $flag
	 * @return self
	 */
	public function cascade($flag = true;)
	{
        $this->cascade = (boolean) $flag;

        return $this;
	}

    /**
     * @inheritdoc
     */
    public function getSql()
    {
        $sql = 'DROP TABLE ' . (($this->ifExists) ? 'IF EXISTS ' : '');
        $sql .= $this->name . (($this->cascade) ? ' CASCADE' : '');
        return $sql;
    }
}
