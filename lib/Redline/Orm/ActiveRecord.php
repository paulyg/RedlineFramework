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
namespace Redline\Orm;

use Redline\Database as DB;

/**
 * Implements an active record design pattern for interacting with a database resource.
 *
 * @package RedlineFramework
 */
class ActiveRecord implements \ArrayAccess
{
	/**
	 * Default database connection to be used in new objects if none passed into object
	 * constructor.
	 * @var DBALiteAbstract
	 */
	static public $connection;

	/**
	 * Name of the database table to interact with, this is not optional.
	 * @var string
	 */
	static protected $table_name;

	/**
	 * Column name of the database table's primary key. Primary key is assumed to be
	 * autoincrementing.
	 * @var string
	 */
	static protected $pk_column = 'id';

	/**
	 * Array of columns present in the table. For performance reasons this class does not
	 * introspect the database to determine the columns. Therefore you must provide some
	 * values. Key of array is column name, value is psuedo type.
	 * 'name'     => 'text',
	 * 'email'    => 'text',
	 * 'age'      => 'integer',
	 * 'birthday' => 'date'
	 *
	 * @var array
	 */
	static protected $columns = array();

	/**
	 * Array of validation rules for each table column.
	 * @var array
	 */
	static protected $validators = array();

	/**
	 * Configuration information about each column for the Redline\Form class.
	 * @var array
	 */
	static protected $form_fields = array();

	/**
	 * Defines a relationship between this table and another, where the foreign key in
	 * this table links to one record in the other table (the `many` side of a
	 * one-to-many relationship).
	 * @todo Add example and more explanation.
	 * @var array
	 */
	static protected $belongs_to = array();

	/**
	 * Defines a one-to-one relationship between this table and another.
	 * @todo Add example and more explanation.
	 * @var array
	 */
	static protected $has_one = array();

	/**
	 * Defines a relationship between this table and another, where the primary key of
	 * this table appears as a foreign key, possibly multiple times, in the related
	 * table (`one` side of a one-to-many relationship).
	 * @todo Add example and more explanation.
	 * @var array
	 */
	static protected $has_many = array();

	/**
	 * Defines a many-to-many relationship between this table and another. A `join table`
	 * is transparently used to link the records.
	 * @todo Add example and more explanation.
	 * @var array
	 */
	static protected $has_and_belongs_to_many = array();

	/**
	 * A list of methods in the Redline\Database\Select class that may be called on this
	 * class (via the magic __call method) to create complex queries.
	 * @var array
	 */
	static protected $query_methods = array('join', 'where', 'groupBy', 'having', 'orderBy',
		'limit', 'limitPage', 'distinct');

	/**
	 * The database connection used by the object.
	 * @var Database\Connection
	 */
	protected $db;

	/**
	 * Stores the value of the primary key column for each object instance/record.
	 * @var integer|string
	 */
	protected $pk_value;

	/**
	 * Stores all of the database record's values represented by the object instance.
	 * @var array
	 */
	protected $data = array();

	/**
	 * Record of columns who's values were changed via property access and not yet saved.
	 * @var array
	 */
	protected $changed_columns = array();

	/**
	 * List of errors encountered when running value validation.
	 * @var array
	 */
	protected $validation_errors = array();

	/**
	 * Serves as a cache location for objects created & retreived through the
	 * `belongs_to`, `has_one`, `has_many`, and `habtm` relationships. Objects
	 * representing these related records are not created until they are accessed.
	 * Once accessed the object is stored in this array.
	 * @var array
	 */
	protected $related_objects = array();

	/**
	 * Flag reprsenting if the data contained has passed all the validation checks.
	 * New records are invalid until validation is explicity run or implicitly run
	 * by calling create() or save(). Records loaded from the database are valid until
	 * a property is updated. It stays invalid validation is explicity run or implicitly
	 * run by calling update() or save().
	 * @var boolean
	 */
	protected $valid = false;

	/**
	 * @todo Isn't this the opposite of dirty? One needs to get tossed.
	 * @var boolean
	 */
	protected $saved = false;

	/**
	 * @todo Isn't this the opposite of saved? One needs to get tossed.
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 * Flag indicating if the object represents a new database record. Every object
	 * is considered a new record until it is loaded with data from the database.
	 * @var boolean
	 */
	protected $new_record = true;

	/**
	 * When this flag is set to true property values can not be written to and the
	 * create(), update(), and save() methods are disabled. The read_only flag gets
	 * set when the object is loaded with data from a query with joins or aggregate/
	 * grouping functions in the SQL. You can also set it manually when you want to
	 * protect the data.
	 * @var boolean
	 */
	protected $read_only = false;

	/**
	 * When query() is used to compose a complex query, an object representing the
	 * select query is saved here until it is executed.
	 * @var Database\Select
	 */
	protected $pending_query;

	/**
	 * Object constructor.
	 * @param DBALiteAbstract $connection Optional connection, overrides connection
	 * set in static $connection property.
	 * @return Active_Record
	 */
	public function __construct(DB\Connection $connection = null)
	{
		if (!is_object($connection)) {
			$this->db = self::$connection;
		}
	}

	/**
	 * Magic method for returning property values.
	 * @param string $property
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function __get($property)
	{
		$property = strtolower($property);
		
		if ($property == $this->pk_column) {

			return $this->pk_value;

		} elseif (array_key_exists($property, static::$columns)) {

			return $this->data[$property];

		} elseif (isset($this->related_objects[$property])) {

			return $this->related_objects[$property];

		} elseif (isset(static::$belongs_to[$property])) {

			$spec = static::$belongs_to[$property];
			$class = isset_or_default($spec, 'class', $property);
			$foreign_key = isset_or_default($spec, 'foreign_key', $property.'_id');
			
			$object = $class::find($this->data[$foreign_key]);
			return $this->related_objects[$property] = $object;

		} elseif (isset(static::$has_one[$property])) {

			$spec = static::$has_one[$property];
			$class = isset_or_default($spec, 'class', $property);
			$foreign_key = isset_or_default($spec, 'foreign_key', $property.'_id');
			
			$object = $class::findBy($foreign_key, $this->id);
			return $this->related_objects[$property] = $object;

		} elseif (isset(static::$has_many[$property])) {
			
			$spec = static::$has_many[$property];
			$class = isset_or_default($spec, 'class', $property);
			$foreign_key = isset_or_default($spec, 'foreign_key', $property.'_id');
			
			$objects = $class::findAll(array('where' => array($foreign_key, '=', $this->id)));
			return $this->related_objects[$property] = $objects;

		} elseif (isset(static::$has_and_belongs_to_many[$property])) {
			// blah
		} else {
			throw new InvalidArgumentException("Property '$property' does not exist in class '" . __CLASS__ . "'.");
		}
	}

	/**
	 * Magic method for setting property values.
	 * @param string $property
	 * @param scalar $value
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function __set($property, $value)
	{
		$property = strtolower($property);

		if ((array_key_exists($property, static::$columns)) {

			$this->data[$property] = $value;
			$this->changed_columns[$property] = $property;
			$this->saved = $this->valid = false;
			$this->dirty = true;

		} elseif (isset(static::$belongs_to[$property])) {

			$this->related_objects[$property] = $value;
			$this->data[static::$belongs_to[$property]['foreign_key']] = $value->pk();
			$this->changed_columns = static::$belongs_to[$column]['foreign_key'];

		} else {

			throw new InvalidArgumentException("Property '$property' does not exist in class '" . __CLASS__ . "'.");

		}
	}

	/**
	 * Magic method for checking if property values are set.
	 * @param $property
	 * @return boolean
	 */
	public function __isset($property)
	{
		$property = strtolower($property);

		return (isset($this->data[$property]) or
			isset($this->related_objects[$property]) or
			isset(static::$belongs_to[$property]) or
			isset(static::$has_one[$property]) or
			isset(static::$has_many[$property]));
	}

	/**
	 * Magic method for unsetting property values.
	 * @param string $property
	 * @return void
	 */
	public function __unset($property)
	{
		$property = strtolower($property);

		if ((array_key_exists($property, static::$columns)) {
			$this->data[$property] = '';
			$this->changed_columns[$property] = $property;
			$this->saved = $this->valid = false;
			$this->dirty = true;
		} elseif (isset($this->related_objects[$property])) {
			unset($this->related_objects[$property]);
		}
	}

	/**
	 * Magic method that allows composing a select query via method chaining by
	 * proxying method calls to the Database\Select class.
	 * @param string $method
	 * @param array $args
	 * @return Active_Record allows method chaining (fluent interface)
	 */
	public function __call($method, $args)
	{
		if (!array_key_exists(self::$query_methods)) {
			throw new BadMethodCallException("Method `$method` not supported by Active_Record class.");
		}

		$this->pending_query[$method] = $args;

		return $this;
	}

	/**
	 * Magic method that allows for static `finder` methods.
	 *
	 * <code>User::findByEmail('joe@aol.com');</code>
	 * Only one column may be used for the search criteria, that column must exist in the
	 * static $columns property and be all lowercase ASCII characters.
	 * @param string $name
	 * @param array args
	 * @throws BadMethodCallException|InvalidArgumentException
	 * @return ActiveRecord
	 */
	public static function __callStatic($name, $args)
	{
		$nargs = count($args);
		if ($nargs != 1) {
			throw new InvalidArgumentException("ActiveRecord dynamic finders only accept one argument, the value to search for. You passed $nargs.");
		}
		$value = array_shift($args);

		if (strpos($name, 0, 6) != 'findBy') {
			throw new BadMethodCallException("ActiveRecord dynamic finders must begin with `findBy`. You called `$name`.");
		}

		$class = get_called_class();
		$column = strtolower(substr($name, 6));
		if (!isset(static::$columns[$column])) {
			throw new BadMethodCallException("Failure on call to ActiveRecord dynamic finder. Column `$column` does not exist in class $class.");
		}

		return $class::findBy($column, $value);
	}

	/**
     * Required by the ArrayAccess implementation, @see __isset
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Required by the ArrayAccess implementation, @see __get
     * @param string $offset
     * @return mixed
     */
     public function offsetGet($offset)
     {
         return $this->__get($offset);
     }

     /**
      * Required by the ArrayAccess implementation, @see __set
      * @param string $offset
      * @param mixed $value
	  * @return void
      */
     public function offsetSet($offset, $value)
     {
         $this->__set($offset, $value);
     }

     /**
      * Required by the ArrayAccess implementation, @see __unset
      * @param string $offset
	  * @return void
      */
     public function offsetUnset($offset)
     {
         return $this->__unset($offset);
     }

	/**
	 * Retrieve database record(s) by primary key value(s).
	 *
	 * 1. Retrieve a single record by it's id:
	 *    <code>Model::find(4);</code>
	 *
	 * 2. Retrieve multiple records at once. Pass an array of id values.
	 *    An IN query will be used to pull the records at once instead of 
	 *    multiple queries.
	 *    <code>Model::find(array(1, 2, 3, 4));</code>
	 *
	 * For more complicated queries see findBy() or query().
	 *
	 * @param integer|array $ids
	 * @returns Active_Record|array
	 */
	public static function find($ids)
	{
		$class = get_called_class();
		$db = ActiveRecord::$connection;

		if (is_array($ids)) {
			$where = static::$pk_column.' IN (';
			$where .= implode(', ', array_map(array($db, 'quote'), $ids)).')';
		} else {
			$where = static::$pk_column.' = '.$db->quote($ids);
		}
		$sql = "SELECT * FROM ".static::$table_name." WHERE $where";
		$result = $db->query($sql);

		if (is_array($ids)) {
			$records = array();
			foreach ($result as $row) {
				$records[] = $row->fetchObject($class);
			}
		} else {
			$records = $row->fetchObject($class);
		}

		return $records;
	}

	public static function findBy($column, $value)
	{
		$class = get_called_class();
		$db = ActiveRecord::$connection;

		if (!isset(static::$columns[$column])) {
			throw new Exception("Can not execute findBy(). Column `$column` does not exist in table `".static::$table_name."`.");
		}

		$where =  $db->quoteIdentifier($column).' = '.$db->quote($ids);
        $sqlc = "SELECT COUNT(*) FROM ".static::$table_name."WHERE $where";
		$sql = "SELECT * FROM ".static::$table_name." WHERE $where";

        $count = $db->queryOne($sqlc);
		$results = $db->query($sql);

		if ($count == 1) {
			$results->setFetchMode(PDO::FETCH_CLASS, $class);
			$return = $results->fetchRow();
            unset($results);
		} else {
			$return = new RecordSet($results, $class, $count);
		}

		return $return;
	}

	public static function findAll()
	{
		$class = get_called_class();
		$db = ActiveRecord::$connection;

		$results = $db->query("SELECT * FROM ".static::$table_name);

		$return = array();
		foreach ($results as $result) {
			$record = new $class;
			$record->loadValues($result);
			$return[] = $record;
		}

		return $return;
	}

	public static function query(array $spec)
	{
		$class = get_called_class();
		$db = ActiveRecord::$connection;

		$select = $db->select();
		$select->from(static::$table_name);

		if (is_array($spec)) {
			foreach ($spec as $method => $args) {
				if (!is_array($args)) {
					$args = (array) $args;
				}
				call_user_func_array(array($select, $method), $args);
			}
		}

        $results = $this->db->query($select->build());
		return new RecordSet($results, $class, -1);
	}

	/**
	 * Creates a new record in the database table.
	 *
	 * Create() accepts an associative array of column_name => values to save, such as
	 * from $_POST. The passed values are merged with any that were set prior via property
	 * access, with the passed values overwriting the existing if both are present for a
	 * given column.
	 *
	 * A LogicException is thrown if the record is not a `new record` (ie loaded from the
	 * database) or is set as `read only`. Any validation rules defined in the $validators
	 * property will be run prior to data being inserted into the database, with false being
	 * returned if any validations fail. The beforeCreate() and afterCreate() hooks are also
	 * run.
	 *
	 * @param array $input
	 * @throws LogicException
	 * @return boolean
	 */
	public function create(array $input)
	{
		if (!$this->new_record) {
			throw new LogicException("Attempting to call create() on a record that already exists.");
		}

		if ($this->read_only) {
			throw new LogicException("Attempting to call create() on a read only object.");
		}

		$this->beforeCreate();

		$newdata = array_merge(
			array_intersect_key($this->data, $this->changed),
			array_intersect_key($input, static::$columns)
		);

		if (empty($newdata)) {
			return;
		}

		$this->db->insert(static::$table_name, $newdata);

		$nulls = array_combine(
			array_keys(static::$columns),
			array_fill(0, count(static::$columns), null)
		);

		$this->data = array_merge($nulls, $newdata);
		$this->pk_value = $this->db->lastInsertId();
		$this->changed = array();
		$this->new_record = false;
		$this->saved = true;

		$this->afterCreate();
	}

	/**
	 * Updates values for a record in the database table.
	 *
	 * Update() accepts an associative array of column_name => values to save, such as
	 * from $_POST. The passed values are merged with any that were set prior via property
	 * access, with the passed values overwriting the existing if both are present for a
	 * given column.
	 *
	 * A LogicException is thrown if the record is a `new record` (ie not loaded from the
	 * database) or is set as `read only`. Any validation rules defined in the $validators
	 * property will be run prior to data being saved in the database, with false being
	 * returned if any validations fail. The beforeUpdate() and afterUpdate() hooks are also
	 * run.
	 *
	 * @param array $input
	 * @throws LogicException
	 * @return boolean
	 */
	public function update(array $input)
	{
		if ($this->new_record) {
			throw new LogicException("Attempting to call update() on a record that does not exist.");
		}

		if ($this->read_only) {
			throw new LogicException("Attempting to call update() on a read only object.");
		}

		$this->beforeUpdate();

		$newdata = array_merge(
			array_intersect_key($this->data, $this->changed),
			array_intersect_key($input, static::$columns)
		);

		if (empty($newdata)) {
			return;
		}

		$this->db->update(static::$table_name, $newdata, array($this->pk_column, '=', $this->pk_value));

		$this->data = array_replace($this->data, $newdata);
		$this->pk_value = $this->db->lastInsertId();
		$this->changed = array();
		$this->saved = true;

		$this->afterUpdate();
	}
	
	/**
	 * Deletes a record from the database table.
	 *
	 * A LogicExcpetion will be thrown if the record is set as `read only`. After deleting
	 * all the data values are cleared from the object. The beforeDelete() and afterDelete()
	 * hooks are run before and after the delete operation.
	 *
	 * @throws LogicException
	 * @return boolean
	 */
	public function delete()
	{
		if ($this->read_only) {
			throw new LogicException("Attempting to call delete() on a read only object.");
		}

		$this->beforeDelete();

		$this->db->delete(static::$table_name, array($this->pk_column, '=', $this->pk_value));

		$this->data = array_combine(
			array_keys(static::$columns),
			array_fill(0, count(static::$columns), null)
		);
		$this->changed = array();
		$this->read_only = true;

		$this->afterDelete();
	}

	/**
	 * Creates a new record or updates values for an existing record after values are
	 * set using property access.
	 *
	 * Use this method when you are working only with property/array access. Unlike
	 * create() and update() this method does not accept an array of input values.
	 * Validation is run prior to saving and appropriate before*() and after*() hooks
	 * are run.
	 *
 	 * @throws LogicException
	 * @return boolean
	 */
	public function save()
	{
		if ($this->new_record) {
			return $this->create(array());
		} else {
			return $this->update(array());
		}
	}

	/**
	 * Implement this method in descendant classes with any logic you want run before
	 * creating a new record.
	 */
	public function beforeCreate() { }

	/**
	 * Implement this method in descendant classes with any logic you want run after
	 * creating a new record.
	 */
	public function afterCreate() { }

	/**
	 * Implement this method in descendant classes with any logic you want run before
	 * updating an existing record.
	 */
	public function beforeUpdate() { }

	/**
	 * Implement this method in descendant classes with any logic you want run after
	 * updating an existing record.
	 */
	public function afterUpdate() { }

	/**
	 * Implement this method in descendant classes with any logic you want run before
	 * deleting a record.
	 */
	public function beforeDelete() { }

	/**
	 * Implement this method in descendant classes with any logic you want run after
	 * deleting a record.
	 */
	public function afterDelete() { }

	/**
	 * Implement this method in descendant classes with any logic you want run after
	 * a successful search instanciates a new object.
	 */
	public function afterFind() { }

	/**
	 * Returns an associative array representing the record where keys as the column names.
	 * @return array
	 */
	public function toArray()
	{
		return array_merge($this->data, array(static::$pk_column => $this->pk_value));
	}

	public function fromArray(array $input)
	{
		if (isset($input[static::$pk_column])) {
			$this->pk_value = $input[static::$pk_column];
		}
		$this->data = array_intersect_key($input, static::$columns);
		$this->frozen = true;
	}

	public function asArray()
	{
		if (!$this->pending_query instanceof DB\Select) {
			throw new LogicException("Attemping to call asArray() when no query is pending.");
		}
		$result = $this->db->query($this->pending_query->build());
		$this->pending_query = null;
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}

	public function asObject()
	{
		if (!$this->pending_query instanceof DB\Select) {
			throw new LogicException("Attemping to call asObject() when no query is pending.");
		}
		$result = $this->db->query($this->pending_query->build());
		$result->setFetchMode(PDO::FETCH_CLASS, get_class($this));
		$this->pending_query = null;
		return $result->fetchAll(PDO::FETCH_CLASS);
	}

	/**
	 * Return string representation of object, useful for debugging.
	 * @return string
	 */
	public function __toString()
	{
		$class = get_class($this);
		$ret = "Active Record object of class ".__CLASS__;
		if ($this->new_record) {
			$ret .= " new record";
		} else {
			$ret .= " with $this->pk_column = $this->pk_value (";

			if ($this->saved) {	$ret .= " saved"; }
			if ($this->valid) { $ret .= " valid"; }
			if ($this->frozen) { $ret .= " frozen";	}
			$ret .= ")";
		}
		return $ret;
	}
}
