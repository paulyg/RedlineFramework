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
// Will Migration namespace and Migration class name conflict?

/**
 * Provides simplified interface for creating/modifying/deleting database tables, columns, and indicies.
 *
 * @package RedlineFramework
 */
class Migration
{
	/**
	 * List of actions to perform on the database.
	 * @var array
	 */
	protected $actions = array();

	/**
	 * The database connection.
	 * @var Connection
	 */
	protected $conn;

	/**
	 * The name of the database driver (brand).
	 * @var string
	 */
	protected $driver;

	/**
	 * SQL generator for the driver/connection.
	 * @var Migration\SQL\Generator
	 */
	protected $sql;

	/**
	 * Object constructor.
	 * @param Redline\Database\Connection $connection
	 */
	public function __construct(Connection $connection = null)
	{
        if (is_null($connnection)) {
            $connection = ConnectionManager::getConnection('default');
        }
        $driver = $connection->getDriverName();
        $sql_class = 'Redline\\Database\\Migration\\Sql\\' . ucfirst($driver);
        $this->sql = new $sql_class;
        $this->driver = $driver;
        $this->conn = $connection;
	}

	/**
	 * Class diagragm of Migration feature:
     * Migration consists of multiple actions:
     *     Create Table - done except SQL
     *         Add Column - done except SQL & generic type
     *         Add Index - done except SQL
     *     Alter Table - done except SQL
     *         Rename Table
     *         Add Column - see create table
     *         Drop Column
     *         Redefine Column - done except SQL & generic type
     *             Rename Column
     *             Change Type
     *             Change Default
     *             Change Unique
     *             Change Null
     *             Change Charset or Collation
     *     Drop Table - done
     *     Add Index - see create table
     *     Remove Index - done
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
        // MySQL is the only one to support creating index as part of a CREATE/ALTER TABLE. SQLite & Postgres go
        // through CREATE INDEX. Should we just create a CreateIndex object?
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
        // Same as above, dropping index as part of alter table is MySQL specific. Shouls be create a DropTable object here?
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

    public function execute()
    {
        $db = $this->conn;
        foreach ($this->actions as $name => $action) {
            $db->begin();
            try {
                $db->execute($action->getSql($this->sql));
                $db->commit();
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        }
    }
}
