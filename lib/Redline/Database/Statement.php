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

use PDOException, PDOStatement, PDO;

/**
 * Description of class.
 *
 * @package RedlineFramework
 */
class Statement extends PDOStatement
{
   	/**
	 * The fetch mode to use for returning results.
	 * @var integer
	 */
	protected $fetchMode = PDO::FETCH_ASSOC;

	/**
	 * Map of parameter name to position.
	 * @var array
	 */
	protected $paramMap;

	/**
	 * Constant representing type of parameter used on this query, positional or named.
	 * @var integer
	 */
	protected $paramType;

    /**
     * Set the parameter map property.
     *
     * @param array $map
     */
    public function setParamMap(array $map)
    {
        $this->paramMap = $map;
    }

    /**
     * Bind a query parameter by reference.
     *
     * @param string|integer $parameter Identifier of the parameter, either integer or string.
     * @param mixed $variable The PHP variable containing the value to bind.
     * @param integer $type Optional PDO::PARAM_* Type hint.
     * @return boolean
     */
    public function bindParam($parameter, &$variable, $type = null)
    {
		$parameter = $this->checkParam($parameter);

        try {
            if (is_null($type)) {
                return parent::bindParam($parameter, $value);
            } else {
                return parent::bindParam($parameter, $value, $type);
            }
        } catch (PDOException $e) {
            throw new Exception("Error while binding parameter '$parameter' to query.", $e);
        }
    }

    /**
     * Bind a query parameter by value.
     * @param string|integer $parameter Identfier of the parameter, string if named placeholders are used, or the 1-indexed position of the ? in the query.
     * @param mixed $value Scalar value to bind to the parameter.
     * @param integer $type Optional PDO::PARAM_* Type hint.
     * @return boolean
     */
    public function bindValue($parameter, $value, $type = null)
    {
        $parameter = $this->checkParam($parameter);

        try {
            if (is_null($type)) {
                return parent::bindValue($parameter, $value);
            } else {
                return parent::bindValue($parameter, $value, $type);
            }
        } catch (PDOException $e) {
            throw new Exception("Error while binding value of '$parameter' to query.", $e);
        }
    }

	/**
	 * Execute the statement and return resutls.
	 *
	 * @param mixed $bind Value(s) to bind into the query.
	 * @return bool Success or failure.
     * @throws Excpetion on error.
	 */
	public function execute($bind = null)
	{
		if (!is_null($bind) && !is_array($bind)) {
			$bind = (array) $bind;
		}

        if (is_array($bind)) {
			foreach ($bind as $name => $value) {
				$newName =  $this->checkParam($name);
				if ($newName != $name) {
					unset($bind[$name]);
					$bind[$newName] = $value;
				}
			}
		}

		try {
			if (is_array($bind)) {
				return parent::execute($bind);
			} else {
				return parent::execute();
			}
		} catch (PDOException $e) {
			throw new Exception('Error while executing statement.', $e, $this->queryString);
		}
	}

    /**
     * Sets the fetch mode to be used when returning rows.
     *
     * This method overloads the PDO native one to allow simpler string fetch modes
     * instead of the PDO::FETCH_* constants.
     *
     * @param string|integer $mode
     * @param string|integer $col_or_class
     * @param array $ctor_args
     */
    public function setFetchMode($mode, $col_or_class = null, array $ctor_args = array())
    {
        $mode = $this->checkFetchMode($mode);

        if (($mode === PDO::FETCH_COLUMN && is_int($col_or_class)) ||
                ($mode === PDO::FETCH_INTO && is_object($col_or_class))) {
            parent::setFetchMode($mode, $col_or_class);
        } elseif ($mode === PDO::FETCH_CLASS && is_string($col_or_class)) {
            parent::setFetchMode($mode, $col_or_class, $ctor_args);
        } else {
            parent::setFetchMode($mode);
        }
    }

	/**
	 * Returns the current fetch mode.
	 *
	 * @return string
	 */
	public function getFetchMode()
	{
		return array_search($this->fetchMode, Connection::$fetchModes);
	}

    /**
     * Retreive a row of query results as an associative array.
     *
     * @return array
     */
	public function fetchAssoc()
	{
		return $this->fetch(PDO::FETCH_ASSOC);
	}

    /**
     * Retreive a row of query results as a numerically ordered array.
     *
     * @return array
     */
	public function fetchNum()
	{
		return $this->fetch(PDO::FETCH_NUM);
	}

	/**
	 * Return all of the query results in an array.
	 *
	 * @param string $mode Override the default return type.
	 * @return array
	 */
	public function fetchAll($mode = null)
	{
		$mode = $this->checkFetchMode($mode);

		try {
			return $this->_stmt->fetchAll($mode);
		} catch (PDOException $e) {
			throw new Exception($e->getMessage(), $e);
		}
	}

	/**
	 * Closes the cursor on the result set and resets the statement to be executed again.
     *
     * Alias of closeCursor().
	 *
	 * @return boolean
	 */
	public function reset()
	{
		return parent::closeCursor();
	}

    /**
     * Internal function for checking fetch modes for string args (vs PDO's constants).
     *
     * @param string|integer $mode
     * @return integer
     * @throws Exception If fetch mode is invalid.
     */
    protected function checkFetchMode($mode)
    {
        if (isset(Connection::$fetchModes[$mode])) {
			return Connection::$fetchModes[$mode];
		} elseif (in_array($mode, Connection::$fetchModes)) {
			return $mode;
		} else {
			throw new Exception("Fetch mode '$mode' not supported or unknown.");
		}
    }
}
