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
class AddIndex implements Action
{
	/**
	 * Name of the index to create.
	 * @var string
	 */
	protected $name;

    /**
     * A qualifier such as PRIMARY KEY, UNIQUE, or FULLTEXT.
     * @var string
     */
    protected $qualifier;

	/**
	 * List of columns to include in the index.
	 * @var array
	 */
	protected $columns = array();

	/**
	 * Define a new index.
     *
     * Index configuration options can be omitted.
     *
	 * Keys for the configuration options are:
     * - name => give the index a name
	 * - qualifier => primary_key|unique|fulltext
     * - columns => simple array of columns to include in index
	 *
	 * @param array $definition Index configuration options.
	 */
	public function __construct(array $definition = array())
	{
        if (!empty($definition)) {
            foreach ($definition as $prop => $value) {
                $prop = TextUtil::camelize($prop);
                if (property_exists($this, $prop)) {
                    $this->$prop($value);
                }
            }
        }
	}

	/**
	 * Set the name of the index.
     *
	 * @param string $name Index name.
	 * @return self
	 */
	public function name($name)
	{
        $this->name = (string) $name;

        return $this;
	}

   	/**
	 * Set a qualifier such as PRIMARY KEY, UNIQUE, or FULLTEXT on the index.
	 *
	 * @param string $qualifier
	 * @return self
	 */
	public function qualifier($qualifier)
	{
        $valid = array('PRIMARY KEY', 'UNIQUE', 'FULLTEXT');
        $qualifier = strtoupper(str_replace('_', ' ', $qualifier));
        if (!in_array($qualifier, $valid)) {
            throw new Exception("'$qualifier' is not a valid index qualifier.");
        }
        $this->qualifier = $qualifier;

        return $this;
	}

    /**
     * Set the columns that will make up the index.
     *
     * @param array $columns
     * @return self
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function execute()
    {
    }
}
