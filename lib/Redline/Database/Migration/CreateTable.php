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

/**
 * Description of class.
 *
 * @package RedlineFramework
 */
class CreateTable
{
	/**
	 * Name of the table to create.
	 * @var string
	 */
	protected $name;

	/**
	 * List of columns to add to the table.
	 * @var array
	 */
	protected $columns = array();
    
    /**
     * List of indexes to add to the table.
     * @var array
     */
    protected $indexes = array();

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
        if (isset($this->columns[$name])) {
            throw new InvalidArgumentException("Column '$name' already defined for table '{$this->name}'.");
        }

        return $this->columns[$name] = new AddColumn($name, $definition);
	}

   	/**
	 * Add an index to the table definition.
	 *
	 * This method allows you to define the index by passing an array with all of the
     * index options. It also returns a new AddIndex object that you can call methods
     * on in a fluent interface to define the index. The options argument may be
     * omitted or you can use both methods.
	 * 
	 * Keys for the configuration options are:
     * - name => give the index a name
	 * - qualifier => primary_key|unique|fulltext
     * - columns => simple array of columns to include in index
	 *
	 * @param array $definition Index configuration options.
	 * @return Redline\Database\Migration\AddIndex
	 */
	public function addIndex(array $definition = array())
	{
        if (isset($definition['name']) {
            $name = $definition['name'];
            if (isset($this->indexes[$name])) {
                throw new InvalidArgumentException("Index '$name' already defined for table '{$this->name}'.");
            }
            $index = $this->indexes[$name] = new AddIndex($definition);
        } else {
            $index = $this->indexes[] = new AddIndex($definition);
        }

        return $index;
	}

    public function getSql($generator)
    {
        // Get create table head or opening
        // get sql from each column
        // get sql table closing statement
        // get sql from each index
        // MySQL is only one of three that supports creating index during create table,
        // other two use CREATE INDEX statement
    }
}
