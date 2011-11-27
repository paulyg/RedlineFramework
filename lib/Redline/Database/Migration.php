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

use Redline\Database\Migration;

/**
 * Provides simplified interface for creating/modifying/deleting database tables, columns, and indicies.
 *
 * @package RedlineFramework
 */
class Migration
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

	/**
	 * Class diagragm of Migration feature:
     * Migration consists of multiple actions:
     *     Create Table - Class
     *         Add Columns - Class
     *         Add Indexes - Class
     *     Alter Table - Class
     *         Rename Table
     *         Add Column - Class
     *         Drop Column
     *         Redefine Column - Class
     *             Rename Column
     *             Change Type
     *             Change Default
     *             Change Unique
     *             Change Null
     *             Change Charset or Collation
     *     Drop Table - simple action
     *     Add Index - somewhat simple action - Class
     *     Remove Index - simple action
	 */
	public function createTable($name)
	{
        $action = 'create_table_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("A definition to create table '$name' already exists.");
        }

        return $this->actions[$action] = new Migration\CreateTable($name);
	}

	public function alterTable($name)
    {
        $action = 'alter_table_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("A definition to alter table '$name' already exists.");
        }

        return $this->actions[$action] = new Migration\AlterTable($name);
    }

	public function dropTable($name)
    {
        $action = 'drop_table_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("A command to drop table '$name' already exists.");
        }

        return $this->actions[$action] = new Migration\DropTable($name);
    }

	public function addIndex($table, $name)
    {
        $action = 'create_index_' . $table . '_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("A definition to create index '$name' on table '$table' already exists.");
        }

        $alter_action = 'alter_table_' . $table;
        if (!isset($this->actions[$alter_action])) {
            $alter_object = $this->alterTable($table);
        } else {
            $alter_object = $this->actions[$alter_action];
        }

        return $this->actions[$action] = $alter_object->addIndex($name);
    }

	public function removeIndex($table, $name)
    {
        $action = 'remove_index_' . $table . '_' . $name;
        if (isset($this->actions[$action])) {
            throw new InvalidArgumentException("A command to remove index '$name' on table '$table' already exists.");
        }

        $alter_action = 'alter_table_' . $table;
        if (!isset($this->actions[$alter_action])) {
            $alter_object = $this->alterTable($table);
        } else {
            $alter_object = $this->actions[$alter_action];
        }

        return $this->actions[$action] = $alter_object->removeIndex($name);
    }
}
