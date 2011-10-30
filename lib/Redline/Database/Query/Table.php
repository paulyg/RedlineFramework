<?php
/**
 * Feathr - A web based application for editing and managing SQLite3 database files,
 * written in PHP 5.3.
 *
 * @package Feathr
 * @author Paul Garvin
 * @copyright Copyright 2009, 2010 Paul Garvin
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GNU General Public License
 */
namespace Feathr;

/**
 * Holds information about and helper methods for a database table.
 * @package Feathr
 */
class Table
{
	/**
	 * Name of the table.
	 * @var string
	 */
	protected $name;

	/**
	 * Database connection.
	 * @var SQLite3
	 */
	protected $con;

	/**
	 * Reference back to Feathr\Database object.
	 * @var Database
	 */
	protected $database;

	/**
	 * Metadata about the table.
	 * @var array
	 */
	protected $metadata;

	/**
	 * List of columns and column metadata for table.
	 * @var array
	 */
	protected $column_data;

	/**
	 * Simple list of column names, used in concert with getRows().
	 * @var array
	 */
	protected $column_names;

	/**
	 * Primary Key column name.
	 * @var string
	 */
	protected $pk_column;

	/**
	 * Flag indicating if the table has an autoincrementing primary key.
	 * @var bool
	 */
	protected $has_autoinc_col = false;

	/**
	 * Number of columns in the table.
	 * @var int
	 */
	protected $num_columns;

	/**
	 * Number of rows in the table.
	 * @var int
	 */
	protected $num_rows;

	/**
	 * Object constructor.
	 * @param string $name
	 * @param SQLite3 $con
	 */
	public function __construct($name, \SQLite3 $con, Database $database)
	{
		$this->name = $name;
		$this->con = $con;
		$this->database = $database;
	}

	/**
	 * Save the meta data from sqlite_master, usually passed from \Feathr\Database.
	 * @param array $data
	 */
	public function setMetaData(array $data)
	{
		$this->metadata = $data;
	}

	/**
	 * Retrieve the table name.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Retrieve the SQL statement used to (re)create the table.
	 * @return string
	 */
	public function getCreateSql()
	{
		$sql = "SELECT sql FROM sqlite_master WHERE type='table' AND name="
		. $this->database->quote($this->name);

		return $this->con->querySingle($sql);
	}
	
	/**
	 * Return a list of columns and column metadata.
	 *
	 * Data retreived from PRAGMA table_info(). Array keys are:
	 *     cid, name, type, affinity, notnull, dflt_value, pk
	 *
	 * @return array
	 */
	public function getColumnMetaData()
	{
		if (!isset($this->column_data)) {
			$this->_populateColumnMetaData();
		}
		return $this->column_data;
	}

	/**
	 * Return the number of columns in the table.
	 * @return int
	 */
	public function getColumnCount()
	{
		if (!isset($this->num_columns)) {
			$sql = "SELECT * FROM \"{$this->name}\" LIMIT 1";
			if ($result = $this->con->query($sql)) {
				$this->num_columns = $result->numColumns();
			} else {
				$this->num_columns = 0;
			}
		}
		return $this->num_columns;
	}

	/**
	 * Return the number of rows in the table.
	 * @return int
	 */
	public function getRowCount()
	{
		if (!isset($this->num_rows)) {
			$sql = "SELECT COUNT(*) FROM \"{$this->name}\"";
			$this->num_rows = $this->con->querySingle($sql);
		}
		return $this->num_rows;
	}

	/**
	 * Retreive the list of columns for the table.
	 * @return void
	 */
	protected function _populateColumnMetaData()
	{
		$sql = "PRAGMA table_info('{$this->name}')";
		$result = $this->con->query($sql);

		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {

			$type = strtolower($row['type']);
			$is_pk = (bool) $row['pk'];
			if (empty($type)) {
				$affinity = 'None';
			} elseif (false !== strpos($type, 'blob')) {
				$affinity = 'None';
			} elseif (false !== strpos($type, 'int'))  {
				$affinity = 'Integer';
			} elseif (false !== strpos($type, 'char')) {
				$affinity = 'Text';
			} elseif (false !== strpos($type, 'clob')) {
				$affinity = 'Text';
			} elseif (false !== strpos($type, 'text')) {
				$affinity = 'Text';
			} elseif (false !== strpos($type, 'real')) {
				$affinity = 'Real';
			} elseif (false !== strpos($type, 'floa')) {
				$affinity = 'Real';
			} elseif (false !== strpos($type, 'doub')) {
				$affinity = 'Real';
			} else {
				$affinity = 'Numeric';
			}
			$row['affinity'] = $affinity;
			if ($is_pk) {
				$this->pk_column = $row['name'];
				if ($type == 'integer') {
					$this->has_autoinc_col = true;
				}
			}				
			$this->column_data[$row['name']] = $row;
		}
		$result->finalize();
	}

	/**
	 * Retrieve all data in a table, optionally using a limit and offset and sorting.
	 * @param int $limit
	 * @param int $offset
	 * @param string $sortby
	 * @param string $sortdir
	 * @return array
	 */
	public function getRows($limit = 0, $offset = 0, $sortby = null, $sortdir = 'ASC')
	{
		$sql = "SELECT rowid, * FROM \"{$this->name}\"";
		if (!empty($sortby) && is_string($sortby) && 
			($sortdir == 'ASC' || $sortdir = 'DESC')) {
			$sql .= " ORDER BY \"$sortby\" $sortdir";
		}
		if ((is_int($limit)) && ($limit > 0)) {
			$sql .= " LIMIT $limit";
		}
		if ((is_int($offset)) && ($offset > 0)) {
			$sql .= " OFFSET $offset";
		}
		//var_dump($sql);
		$result = $this->con->query($sql);

		$return = array();
		$this->column_names = array();
		$col_count = $result->numColumns();
		// Column 1 is rowid or a duplicate of an INTEGER PRIMARY KEY, and can be skipped.
		for ($i = 1; $i < $col_count; $i++) {
			$this->column_names[] = $result->columnName($i);
		}

		while ($row = $result->fetchArray(SQLITE3_NUM)) {
			$rowid = $row[0];
			$return_row = array();
			// Column 1 is rowid or a duplicate of an INTEGER PRIMARY KEY, and can be skipped.
			for ($i = 1; $i < $col_count; $i++) {
				$return_row[] = new Datapoint($row[$i], $result->columnType($i), $result->columnName($i));
			}
			$return[$rowid] = $return_row;
		}
		$result->finalize();

		if (array_search('rowid', $this->column_names) !== false) {
			$this->pk_column = 'rowid';
		} else {
			$this->_populateColumnMetaData();
		}

		return $return;
	}

	/**
	 * Retrieve just the names of the columns. Used in concert with getRows().
	 * @return array
	 */
	public function getColumnNames()
	{
		return $this->column_names;
	}

	/**
	 * Retreive the name of the Primary Key column.
	 * @return string
	 */
	public function getPrimaryKey()
	{
		if (!isset($this->pk_column)) {
			$this->getColumnMetaData();
		}
		return $this->pk_column;
	}

	/**
	 * Determine if the table has an autoincrementing primary key column.
	 * @return bool
	 */
	public function hasAutoincPk()
	{
		// $this->has_autoinc_col defaults to false so we can't isset() it.
		if (!isset($this->pk_column)) {
			$this->getColumnMetaData();
		}
		return $this->has_autoinc_col;
	}

	/**
	 * Drop the table.
	 * @return bool
	 */
	public function drop()
	{
		$sql = "DROP TABLE {$this->name}";
		return $this->con->exec($sql);
	}

	/**
	 * Create a new table.
	 * @param array $columns
	 * @param bool $ifnotexists
	 * @param string $constraint
	 * @return bool
	 */
	public function create(array $columns, $ifnotexists = false, $constraint = '')
	{
		$sql = "CREATE TABLE ";
		if ($ifnotexists) {
			$sql .= "IF NOT EXISTS ";
		}
		$sql .= "\"{$this->name}\" (\n";
		foreach ($columns as $column) {
			$sql .= $this->createColumnStatement($column) . ",\n";
		}
		if (!empty($constraint)) {
			$sql .= "CONSTRAINT $constraint\n)";
		} else {
			$sql = rtrim($sql);
			$sql = rtrim($sql, ',');
			$sql .= "\n)";
		}
		return $this->con->exec($sql);
	}

	/** 
	 * Generate the SQL to create a table column.
	 *
	 * The passed array should have the following keys:
	 * 'name', 'type', 'pk', 'autoinc', 'notnull', 'unique', 'default', 'collate', 'check'
	 *
	 * @param array $def
	 * @return string
	 */
	public function createColumnStatement(array $def)
	{
		$defaults = array(
			'pk' => '',
			'autoinc' => '',
			'notnull' => '',
			'unique' => '',
			'default' => '',
			'collate' => '',
			'check' => ''
		);
		$def += $defaults;
		extract($def, EXTR_OVERWRITE);
		$type = strtoupper($type);
		$stmt = "\"$name\" $type ";
		if ($pk) {
			$stmt .= "PRIMARY KEY ";
			if ($autoinc) {
				if ($type != 'INTEGER') {
					throw new Exception("The type of a PRIMARY KEY AUTOINCREMENT column must be INTEGER.");
				}
				$stmt .= "AUTOINCREMENT ";
			}
		}
		if ($notnull) {
			$stmt .= "NOT NULL ";
		}
		if ($unique) {
			$stmt .= "UNIQUE ";
		}
		if (!empty($default)) {
			$stmt .= "DEFAULT $default ";
		}
		if ($check) {
			$stmt .= "CHECK ($check) ";
		}
		if ($collate) {
			$stmt .= "COLLATE $collate";
		}

		return $stmt;
	}

	/**
	 * Rename the table.
	 * @param string $newname
	 * @return bool
	 */
	public function rename($newname)
	{
		$sql = "ALTER TABLE \"{$this->name}\" RENAME TO \"{$newname}\"";
		return $this->con->exec($sql);
	}

	/** 
	 * Add a column to the table.
	 *
	 * The passed array should have the following keys:
	 * 'name', 'type', 'notnull', 'default', 'collate', 'check'
	 *
	 * @param array $def
	 * @return bool
	 */
	public function addColumn(array $def)
	{
		$defaults = array(
			'notnull' => false,
			'default' => '',
			'collate' => '',
			'check' => ''
		);
		$def += $defaults;
		extract($def, EXTR_OVERWRITE);
		$type = strtoupper($type);
		$sql = "ALTER TABLE \"{$this->name}\" ADD COLUMN ";
		$sql .= "\"$name\" $type ";
		if ($notnull) {
			if (empty($column['default'])) {
				throw new Exception("You must supply a default value if using the NOT NULL clause while adding a column.");
			}
			$sql .= "NOT NULL ";
		}
		if (!empty($default)) {
			$timestamp_keywords = array('CURRENT_TIME', 'CURRENT_DATE', 'CURRENT_TIMESTAMP');
			if (in_array($default, $timestamp_keywords)) {
				throw new Exception("When adding a column, the default value can not be one of the special keywords CURRENT_TIME, CURRENT_DATE, or CURRENT_TIMESTAMP.");
			} elseif (substr($default, 0, 1) == '(') {
				throw new Exception("An expression in parentheses is not allowed as a default value when adding a column.");
			}
			$sql .= "DEFAULT $default ";
		}
		if (!empty($check)) {
			$sql .= "CHECK ($check) ";
		}

		if (!empty($collate)) {
			$sql .= "COLLATE $collate";
		}
		return $this->con->exec($sql);
	}

	/**
	 * Drop a column from the table.
	 * @param string $column Name
	 * @return bool
	 */
	public function dropColumn($column)
	{
		$cols = $this->getColumnMetaData();
		if (!isset($cols[$column])) {
			throw new Exception("Column &quot;$column&quot; does not exist in the table.");
		}
		$col_quoted = preg_quote($column, '/');

		$create_stmt = $this->getCreateSql();
		$pos = strpos($create_stmt, '(');
        // $pos + 1 grabs '(' char
		$head = substr($create_stmt, 0, $pos + 1);
		// -1 length strips off ending ')'
		$inner = substr($create_stmt, $pos + 1, -1);
		
        $len = strlen($inner);
        $i = 0;
        $last = 0;
        $in_parens = false;
        $parts = array();

        while ($i < $len) {
            $char = $inner[$i];
            switch ($char) {
                case '(':
                    $in_parens = true;
                    break;
                case ')':
                    $in_parens = false;
                    break;
                case ',':
                    if (!$in_parens) {
                        $parts[] = substr($inner, $last, $i - $last);
                        $last = $i + 1;
                    }
                    break;
            }
            $i++;
        }
        // grab final segment, above loop doesn't do it.
        $parts[] = substr($inner, $last); // go to end this time

        $parts = array_map('trim', $parts);

		$tbl_constr = "(CONSTRAINT|PRIMARY|UNIQUE|CHECK|FOREIGN)";
		foreach ($parts as $i => $part) {
			if (preg_match("/^[\"]?$col_quoted/", $part)) {
				unset($parts[$i]);
			} elseif (preg_match("/^$tbl_constr.+$col_quoted/", $part)) {
				unset($parts[$i]);
			}
		}
		$new_sql = "$head\n" . implode(",\n", $parts) . "\n)";
        Logger::log($col_quoted);

		unset($cols[$column]);
		$new_cols = array_keys($cols);
		$addquotes = function($c) { return "\"$c\""; };
		$new_cols = array_map($addquotes, $new_cols);
		$new_cols = implode(', ', $new_cols);
		$bu_tbl = '"' . $this->name . '_backup"';

		$this->database->begin();
		$this->con->exec("CREATE TEMPORARY TABLE $bu_tbl ($new_cols)");
        Logger::log("CREATE TEMPORARY TABLE $bu_tbl ($new_cols)");
		$this->con->exec("INSERT INTO $bu_tbl SELECT $new_cols FROM \"{$this->name}\"");
        Logger::log("INSERT INTO $bu_tbl SELECT $new_cols FROM \"{$this->name}\"");
		$this->con->exec("DROP TABLE \"{$this->name}\"");
        Logger::log("DROP TABLE \"{$this->name}\"");
		$this->con->exec($new_sql);
        Logger::log($new_sql);
		$this->con->exec("INSERT INTO \"{$this->name}\" SELECT $new_cols FROM $bu_tbl");
        Logger::log("INSERT INTO \"{$this->name}\" SELECT $new_cols FROM $bu_tbl");
		$this->con->exec("DROP TABLE $bu_tbl");
        Logger::log("DROP TABLE $bu_tbl");
		if (!$this->database->commit()) {
			$this->database->rollback();
			return false;
		}
		unset($this->column_data[$column]);
		return true;
	}	

	/**
	 * Retrieve a single row from the table.
	 * @param int $rowid
	 * @return array
	 */
	public function selectRow($rowid)
	{
		if (!is_int($rowid)) {
			throw new Exception("Non-integer supplied for rowid. Only integers allowed.");
		}
		$sql = "SELECT * FROM \"{$this->name}\" WHERE rowid = $rowid";
		$result = $this->con->query($sql);

		if (!($result instanceof \SQLite3Result)) {
			throw new Exception("Database error retrieving row: " . $this->con->lastErrorMsg());
		}

		$row = $result->fetchArray(SQLITE3_NUM);
		$this->num_columns = count($row);
		$return = array();
		for ($i = 0; $i < $this->num_columns; $i++) {
			$val = $row[$i];
			$name = $result->columnName($i);
			$type = $result->columnType($i);
			$return[] = new Datapoint($val, $type, $name, 0);
		}

		// Just in case it actually happened
		if ($row = $result->fetchArray(SQLITE3_NUM)) {
			throw new Exception("Multiple rows returned when only expecting one. This indicated possible data corruption.");
		}

		$result->finalize();

		return $return;
	}

	/**
	 * Insert a new row into the table.
	 * @param array $data
	 * @return bool
	 */
	public function insertRow(array $data)
	{
		$cols = $vals = array();
		foreach ($data as $col => $val) {
			$cols[] = '"' . $col . '"';
			$vals[] = $this->database->quote($val);
		}
		$sql = "INSERT INTO \"{$this->name}\" (";
		$sql .= implode(', ', $cols) . ') VALUES (';
		$sql .= implode(', ', $vals) . ')';

		if (!$this->con->exec($sql)) {
			throw new Exception("Database error on row insert: " . $this->con->lastErrorMsg());
		}
		return true;
	}

	/**
	 * Save (update) the values of a row.
	 * @param int $rowid
	 * @param array $data
	 * @return bool
	 */
	public function updateRow($rowid, array $data)
	{
		if (!is_int($rowid)) {
			throw new Exception("Non-integer supplied for rowid. Only integers allowed.");
		}
		$set = array();
		foreach ($data as $col => $val) {
			$set[] = '"' . $col . '" = ' . $this->database->quote($val);
		}

		$sql = "UPDATE \"{$this->name}\" SET ";
		$sql .= implode(', ', $set);
		$sql .= "WHERE rowid = $rowid";
		var_dump($sql);
		if (!$this->con->exec($sql)) {
			throw new Exception("Database error on row update: " . $this->con->lastErrorMsg());
		}
		return true;
	}

	/**
	 * Delete a row from the database table.
	 * @param int $rowid
	 * @return bool
	 */
	public function deleteRow($rowid)
	{
		if (!is_int($rowid)) {
			throw new Exception("Non-integer supplied for rowid. Only integers allowed.");
		}

		$sql = "DELETE FROM \"{$this->name}\" WHERE rowid = $rowid";

		if (!$this->con->exec($sql)) {
			throw new Exception("Database error on row delete: " . $this->con->lastErrorMsg());
		}
		return true;
	}
}
