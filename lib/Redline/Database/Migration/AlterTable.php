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
class AlterTable implements Action
{
	/**
	 * Name of the table to alter.
	 * @var string
	 */
	protected $name;

	/**
	 * List of actions to perform while altering the table.
	 * @var array
	 */
	protected $actions = array();

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
     * Change the name of the table.
     *
     * @param string $name New table name.
     * @return self
     */
    public function rename($name);
    {
        $this->actions['rename'] = (string) $name;

        return $this;
    }

	/**
	 * Add a column to the table definition.
	 *
	 * This method allows you to define the column by passing an array with all of the
     * column options as the second argument. It also returns a new AddColumn object
     * that you can call methods on in a fluent interface to define the column. The
     * second argument may be omitted or you can use both methods.
	 * 
	 * Keys for the configuration options are:
	 * - type => specify a Redline data type
     * - native_type => specify a database vendor native type
     * - primary_key => true|false, default false
     * - unique => true|false, default false
     * - not_null => true|false, default false
     * - default => default none
     * - collation => default database default
     * - charset => default UTF-8
	 *
	 * @param string $name Column name.
	 * @param array $definition Column configuration options.
	 * @return Redline\Database\Migration\AddColumn
	 */
	public function addColumn($name, array $definition = array())
	{
        $action = 'add_column_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("Column '$name' already defined for table '{$this->name}'.");
        }

        return $this->actions[$action] = new AddColumn($name, $definition);
	}

   	/**
	 * Change a column's definition.
	 *
	 * This method allows you to redefine the column by passing an array with all of the
     * column options as the second argument. It also returns a new AlterColumn object
     * that you can call methods on in a fluent interface to define the column. The
     * second argument may be omitted or you can use both methods.
	 * 
	 * Keys for the configuration options are:
     * - newName => Change the name of the column
	 * - type => specify a Redline data type
     * - native_type => specify a database vendor native type
     * - unique => true|false, default false
     * - not_null => true|false, default false
     * - default => default none
     * - collation => default database default
     * - charset => default UTF-8
	 *
	 * @param string $name Column name.
	 * @param array $definition Column configuration options.
	 * @return Redline\Database\Migration\AlterColumn
	 */
	public function alterColumn($name, array $definition = array())
	{
        $action = 'alter_column_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("New definition for column '$name' already defined for table '{$this->name}'.");
        }

        return $this->actions[$action] = new AlterColumn($name, $definition);
	}

    /**
     * Remove a column from the table.
     *
     * @param string $name Column name.
     * @return self
     */
    public function dropColumn($name)
    {
        $this->actions['drop_column_' . $name] = $name;

        return $this;
    }

    public function execute()
    {
    }
}
