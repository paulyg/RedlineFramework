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
 * Lets you work with multiple records from a database table at once.
 *
 * @package RedlineFramework
 */
class RecordSet implements \Iterator, \Countable
{
	protected $className;

    protected $results;

    protected $position = 0;

	protected $current;

    public function __construct($results, $className, $count)
    {
        $results->setFetchMode(PDO::FETCH_CLASS, $className);
        $this->results = $results;
        $this->className = $className;
        $this->count = $count;
    }

    public function current()
    {
        return $this->current;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->current = $this->results->fetchRow(PDO::FETCH_CLASS);
        $this->position++;
    }

    public function rewind()
    {
        // no op! Can't rewind PDOStatement
    }

    public function valid()
    {
        return (bool) $this->current;
    }

    public function count()
    {
        return $this->count;
    }


	public function delete()
	{
	}

    public function toArray()
    {
        return $this->results->fetchAll(PDO::FETCH_ASSOC);
    }
}
