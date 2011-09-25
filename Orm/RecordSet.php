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
class RecordSet implements \IteratorAggregate, \Countable
{
	protected $table_name;

	protected $active_record_class;

    protected $result;

    protected $keys;

	protected $where_clause;

	protected $data = array();

    public function __construct() {}

	public function count()
	{
		return count($this->data);
	}

	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	public function update(array $input)
	{
	}

	public function delete()
	{
	}
}
