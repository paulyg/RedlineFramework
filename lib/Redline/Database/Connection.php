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

use PDO;

/**
 * Connects to database, performs basic queries, prepares sql queries & statements.
 *
 * @package RedlineFramework
 */
abstract class Connection
{
    /**
     * Holds the PDO object.
     * @var PDO
     */  
    protected $pdo = null;

    /**
     * Holds passed config array.
     * @var array
     */
    protected $config = array();

    /**
     * Holds driver specific options.
     * @var array
     */
    protected $driverOptions;

    /**
     * The error mode.
     * @var integer
     */
    protected $errorMode = PDO::ERRMODE_EXCEPTION;

    /**
     * Array map of error mode strings to PDO constants.
     * @var array
     */
    protected static $errorModes = array(
        'silent' =>     PDO::ERRMODE_SILENT;
        'warnings' =>   PDO::ERRMODE_WARNING;
        'exceptions' => PDO::ERRMODE_EXCEPTION;
    );

    /**
     * Fetch mode for results.
     * @var integer
     */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * Array map of fetch modes strings to PDO fetch mode constants.
     * @var array
     */
    public static $fetchModes = array(
        'assoc'     => PDO::FETCH_ASSOC,
        'both'      => PDO::FETCH_BOTH,
        'default'   => PDO::FETCH_ASSOC,
        'lazy'      => PDO::FETCH_LAZY,
        'num'       => PDO::FETCH_NUM,
        'obj'       => PDO::FETCH_OBJ
    );

    /**
     * Case of column names returned in queries.
     * @var integer
     */
    protected $caseFolding = PDO::CASE_NATURAL;

    /**
     * Array map of case folding mode strings to PDO case folding mode constants.
     * @var array
     */
    protected static $foldingModes = array(
        'lower'     => PDO::CASE_LOWER,
        'natural'   => PDO::CASE_NATURAL,
        'upper'     => PDO::CASE_UPPER
    );

    /**
     * Null and empty string handling mode.
     * @var integer
     */
    protected $nullMode = PDO::NULL_NATURAL;

    /**
     * Array map of null <=> empty string handling modes.
     * @var array
     */
    protected $nullModes = array(
        'natural'        => PDO::NULL_NATURAL,
        'string_to_null' => PDO::NULL_EMPTY_STRING,
        'null_to_string' => PDO::NULL_TO_STRING
    );

    /**
     * Automatic quoting of idenifiers on or off.
     * @var boolean
     */
    protected $autoQuoteIdents = true;

    /**
     * Keep track of number of queries executed by this driver.
     * @var int
     */
    public $queryCount = 0;

    /**
     * Parses connection string/parameters, sets options, creates connection to database.
     *
     * @param array $config Database connection parameters and options, see documentation.
     * @return DBALite_DriverAbstract
     */
    public function __construct(array $config)
    {
        if (isset($config['options']) && is_array($config['options'])) {
            $options = $config['options'];
            unset($config['options']);
            foreach ($options as $key => $val) {
                $this->setOption($key, $val);
            }
        }

        if (isset($config['driver_options']) && is_array($config['driver_options'])) {
            $this->driverOptions = $config['driver_options'];
            unset($config['driver_options']);
        }

        $this->config = $config;

        $this->_connect($this->config);

        $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('Redline\\Database\\Statement'));
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $this->fetchMode);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);
        $this->pdo->setAttribute(PDO::ATTR_CASE, $this->caseFolding);
        $this->pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, $this->nullMode);
    }

    /**
     * Safely closes the connection to the database.
     */
    public function __destruct() 
    {
        $this->pdo = null;
    }

    /**
     * Magic method to call PDO methods that don't have a specific DBALite method.
     *
     * @method mixed getAttribute() proxy for PDO::getAttribute()
     * @method bool setAttribute() proxy for PDO::setAttribute()
     * @method mixed errorCode() proxy for PDO::errorCode()
     * @method array errorInfo() proxy for PDO::errorInfo()
     *
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws Exception If method doesn't exist.
     */
    public function __call($method, $params)
    {
        switch ($method) {
            case 'getAttribute':
            case 'setAttribute':
            case 'errorCode':
            case 'errorInfo':
                return call_user_func_array(array($this->pdo, $method), $params);
            default:
                throw new Exception("Call to undefined method: $method. Not a valid PDO or DBALite method.");
        }
    }

    /**
     * Return the name of the driver in use.
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->driver;
    }

    /**
     * Set a DBALite option.
     *
     * @param string $key Option name.
     * @param mixed  $val Option value.
     * @throws Exception On an invalid option name or value.
     */
    public function setOption($key, $val)
    {
        $key = strtolower($key);
        $val = strtolower($val);
        switch ($key) {
            case 'autoquoteidentifiers':
                $this->autoQuoteIdents = (($val) ? true : false);
                break;
            case 'fetchmode':
                if (isset(self::$fetchModes[$val])) {
                    $this->fetchMode = self::$fetchModes[$val];
                } elseif (in_array($val, self::$fetchModes, true)) {
                    $this->fetchMode = $val;
                } else {
                    throw new Exception("Fetch mode '$val' not supported or unknown.");
                }
                break;
            case 'casefolding':
                if (isset(self::$foldingModes[$val])) {
                    $this->caseFolding = self::$foldingModes[$val];
                } elseif (array_key_exists($val, self::$foldingModes)) {
                    $this->caseFolding = $val;
                } else {
                    throw new Exception("Fetch mode '$val' not supported or unknown.");
                }
                if (isset($this->pdo)) {
                    $this->pdo->setAttribute(PDO::ATTR_CASE, $this->caseFolding);
                }
                break;
            case 'errormode':
                if (isset(self::$errorModes[$val])) {
                    $this->errorMode = self::$errorModes[$val];
                } elseif (in_array($val, self::$errorModes, true)) {
                    $this->errorMode = $val;
                } else {
                    throw new Exception("Error mode '$val' not supported or unknown.");
                }
                break;
            case 'nullhandling':
                if (isset(self::$nullModes[$val])) {
                    $this->nullMode = self::$nullModes[$val];
                } elseif (in_array($val, self::$nullModes, true)) {
                    $this->nullMode = $val;
                } else {
                    throw new Exception("Null handling mode '$val' not supported or unknown.");
                }
                break;
            default:
                throw new Exception("Option '$key' not supported.");
        }
    }

    /**
     * Return the value of a DBALite option.
     *
     * @param $key Option name.
     * @return mixed
     * @throws Exception On an invalid option name.
     */
    public function getOption($key)
    {
        $key = strtolower($key);
        switch ($key) {
            case 'autoquoteidentifiers':
                return $this->autoQuoteIdents;
                break;
            case 'fetchmode':
                return array_search($this->fetchMode, self::$fetchModes);
                break;
            case 'casefolding':
                return array_search($this->caseFolding, self::$foldingModes);
                break;
            case 'errormode':
                return array_search($this->errorMode, self::$errorModes);
                break;
            case 'nullhandling':
                return array_search($this->nullMode, self::$nullModes);
                break;
            default:
                throw new Exception("Option '$key' not supported.");
        }
    }

    /**
     * Build an INSERT statement from parts and optionally execute it.
     *
     * @param string $table Name of table to insert into.
     * @param array $data Associative array of the form 'column_name' => 'data'.
     * @param boolean $execute Execute the query immediatly or return a prepared statement.
     * @return int|bool|Statement Number of affected rows, false on error, or prepared statement.
     */
    public function insert($table, array $data, $execute = true)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $map = array_flip($columns);

        $cols = $vals = array();
        foreach ($columns as $col) {
            $cols[] = $this->quoteIdentifier($col);
            $vals[] = '?';
        }

        $sql = 'INSERT INTO '
             . $this->quoteIdentifier($table)
             . ' (' . implode(', ', $cols) . ')'
             . ' VALUES (' . implode(', ', $vals) . ')';

        $stmt = $this->prepare($sql, $map);

        if ($execute) {
            $result = $stmt->execute($values);
            return ($result) ? $stmt->rowCount() : false;
        }

        return $stmt;       
    }

    /**
     * Build an UPDATE statement from parts and optionally execute it.
     *
     * @param string $table Name of table to update.
     * @param array $columns Associative array of the form 'column_name' => 'data'.
     * @param string|array $where A where clause, will be passed to where().
     * @param boolean $execute Execute the query immediatly or return a prepared statement.
     * @return int|bool|Statement Number of affected rows, false on error, or prepared statement.
     */
    public function update($table, array $data, $where = '', $execute = true)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $map = array_flip($columns);

        $set = array();
        foreach ($columns as $col) {
            $set[] = $this->quoteIdentifier($col) . " = ?";
        }

        $where = $this->where($where);
        $wph = substr_count($where, '?');
        if ($wph > 0) {
            for ($i = 1; $i <= $wph; $i++) {
                $map[] = count($map);
            }
        }

        $sql = 'UPDATE '
             . $this->quoteIdentifier($table)
             . ' SET ' . implode(', ', $set)
             . (($where) ? " WHERE $where" : '');

        $stmt = $this->prepare($sql, $map);

        if ($execute) {
            $result = $stmt->execute($values);
            return ($result) ? $stmt->rowCount() : false;
        }

        return $stmt;
    }

    /**
     * Build a DELETE statement from parts and optionally execute it.
     *
     * @param string $table Name of table to delete from.
     * @param string|array $where A where clause, will be passed to where().
     * @param boolean $execute Execute the query immediatly or return a prepared statement.
     * @return int|bool|Statement Number of affected rows, false on error, or prepared statement.
     */
    public function delete($table, $where = '', $execute = true)
    {
        $where = $this->where($where);
        
        $sql = 'DELETE FROM '
             . $this->quoteIdentifier($table)
             . (($where) ? " WHERE $where" : '');

        $stmt = $this->prepare($sql);

        if ($execute) {
            $result = $stmt->execute();
            return ($result) ? $stmt->rowCount() : false;
        }

        return $stmt;
    }

    /**
     * Create a SELECT query using the Select class.
     *
     * @return Select
     */
    public function select()
    {
        return new Select($this);
    }

    /**
     * Assist in building a where clause.
     *
     * This function accepts an array with three values:
     *     1) A column name or alias for the clause. The column name will be
     *        quoted via quoteIdentifier().
     *     2) An expression such as =, >, <, etc. This value will be put into the
     *        clause unquoted.
     *     3) A data value. The value will be quoted via quote().
     * If a string is passed it will be returned unaltered.
     * 
     * @param string|array $spec
     * @return string
     */
    public function where($spec)
    {
        if (!is_array($spec)) {
            return $spec;
        }

        list($col, $expr, $val) = $spec;
        if (strpos($col, '(') === false) {
            $col = $this->quoteIdentifier($col);
        }
        if (!is_null($val)) {
            $val = $this->quote($val);
        }
        $where = "$col $expr $val";

        return $where;
    }

    /**
     * Determine if array is associative (string keys) or indexed (numeric keys).
     * @param array $array
     * @return int DBALite::ARRAY_* constant.
     */
    public function arrayType(array $array)
    {
        $keys = array_keys($array);
        // Grab 1st key and determine type
        $first = array_shift($keys);
        if (is_int($first)) {
            foreach ($keys as $check) {
                if (!is_int($check)) {
                    return DBALite::ARRAY_MIXED;
                }
            }
            return DBALite::ARRAY_INDEXED;
        } elseif (is_string($first)) {
            foreach ($keys as $check) {
                if (!is_string($check)) {
                    return DBALite::ARRAY_MIXED;
                }
            }
            return DBALite::ARRAY_ASSOC;
        } else {
            return DBALite::ARRAY_MIXED;
        }
    }

    /**
     * Prepare a raw SQL query or statement.
     *
     * @param string $sql SQL query or statement
     * @param array $map Optional map of parameter names to positions.
     * @return Statement
     */
    public function prepare($sql, array $map = array())
    {
        try {
            $stmt = $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            throw new Exception('Error executing PDO->prepare().', $e, $sql);
        }

        if (!empty($map)) {
            $stmt->setParamMap($map);
        }

        return $stmt;
    }

    /**
     * Execute a raw SQL query and return the results.
     *
     * @param string $sql SQL query to execute.
     * @param array $data Optional values to be bound into the query.
     * @return Statement
     */
    public function query($sql, array $data = array())
    {
        if (!empty($data)) {
            $stmt = $this->prepare($sql);
            $stmt->execute($data);
        } else {
            try {
                $stmt = $this->pdo->query($sql);
            } catch (PDOException $e) {
                throw new Exception('Error executing PDO->query().', $e, $sql);
            }
        }

        static::$queryCount++;
        return $stmt;
    }

    /**
     * Convenience method for query() + fetchAll().
     *
     * @param string $sql SQL Select query.
     * @param mixed $data Optional values to be bound into query.
     * @param string $mode Override the default fetch mode.
     * @return array
     */
    public function queryAll($sql, array $data = array(), $fetch_mode = null)
    {
        $stmt = $this->query($sql, $data);
        return $stmt->fetchAll($fetch_mode);
    }

    /**
     * Convenience method for running a query and returning a single value.
     *
     * Returns value of first row and first column if query actually returns
     * more than one row and/or column.
     *
     * @param string $sql SQL Select query.
     * @param mixed $data Optional values to be bound into query.
     * @return mixed
     */
    public function queryOne($sql, array $data = array())
    {
        $stmt = $this->query($sql, $data);
        return $stmt->fetchColumn(0);
    }

    /**
     * Execute a raw SQL statement.
     *
     * @param string $sql SQL statement to execute.
     * @return boolean|int Number of rows affected or false if error.
     */
    public function execute($sql)
    {
        static::$queryCount++;
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new Exception('Error running PDO->exec().', $e, $sql);
        }
    }

    /**
     * Properly escapes a string, places delimiters around it for use in a query.
     *
     * Prepared statement placeholders (? or :string) are not escaped and returned as-is.
     *
     * @param mixed $data
     * @return mixed
     */
    public function quote($data)
    {
        if (is_int($data) || is_float($data) || ($data == '?') || ($data[0] == ':')) {
            return $data;
        }

        $data = str_replace("\x00", '', $data);
        return $this->pdo->quote($data);
    }

    /**
     * Place the proper delimiters around table and column names.
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        if (! $this->autoQuoteIdents) {
            return $identifier;
        }

        $q = $this->quoteIdentChar;

        $idents = explode('.', $identifier);

        foreach ($idents as $key => $ident) {
            $idents[$key] = $q . str_replace("$q", "$q$q", $ident) . $q;
        }

        $quoted = implode('.', $idents);

        return $quoted;
    }

    /**
     * Begin an SQL transaction.
     *
     * @return void
     */
    public function begin()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current SQL transaction.
     *
     * @return void
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Cancels the current SQL transaction.
     *
     * @return void
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Returns the PDO object for direct manipulation.
     *
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Creates the driver specific PDO connection.
     *
     * @param array $config Connection parameters.
     * @return void
     */
    abstract protected function _connect(array $config);

    /**
     * Retrieve the ID of the last record inserted into an auto-incrementing column.
     *
     * Some RDBMS' will return the value of the auto-increment column, on others
     * you need to pass a sequence name for it to work.
     *
     * @return int
     */
    abstract public function lastInsertId($seq = '');

    /**
     * Adds the SQL needed to do a limit query.
     *
     * @param string  $sql    SQL statement.
     * @param integer $limit  Number of rows to return.
     * @param integer $offset Offset number of rows.
     * @return string
     */
    abstract public function limit($sql, $limit, $offset = 0);
}
