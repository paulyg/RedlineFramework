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
class AlterColumn implements Action
{
	/**
	 * Name of the column.
	 * @var string
	 */
	protected $name;

    /**
     * Rename the column to this.
     * @var string
     */
    protected $newName;

    /**
     * The generic type of the column.
     * @var string
     */
    protected $type;

    /**
     * A database native type to set the column too.
     * @var string
     */
    protected $nativeType;

    /**
     * Should this column hold unique values?
     * @var boolean
     */
    protected $unique;

    /**
     * Should null values be allowed in this column.
     * @var boolean
     */
    protected $notNull;

    /**
     * A default value for the column.
     * @var mixed
     */
    protected $default;

    /**
     * A text collation to use for char/text columns.
     * @var string
     */
    protected $collation;

    /**
     * The charset to use for char/text columns.
     * @var string
     */
    protected $charset;

	/**
	 * Define a database column.
	 *
	 * Column configuration options can be omitted.
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
	 */
	public function __construct($name, array $definition = array())
	{
        $this->name = (string) $name;

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
     * Change the name of the column.
     *
     * @param string $new_name
     * @return self
     */
    public function rename($new_name)
    {
        $this->newName = $new_name;
    }

    /**
     * Change the type of the column.
     *
     * @param string $type
     * @return self
     */
    public function changeType($type)
    {
    }

    /**
     * Set the type as an exact native database type.
     *
     * @param string $type
     * @return self
     */
    public function changeNativeType($type)
    {
        $this->nativeType = $type;
        return $this;
    }

    /**
     * Change this column from/to unique index.
     *
     * @param boolean $flag
     * @return self
     */
    public function changeUnique($flag)
    {
        $this->unique = (boolean) $flag;
        return $this;
    }

    /**
     * Set whether this column can accept null values.
     *
     * @param boolean $flag
     * @return self
     */
    public function changeNull($flag)
    {
        $this->notNull = (boolean) $flag;
        return $this;
    }

    /**
     * Change the default value for this column.
     *
     * @param mixed $default
     * @return self
     */
    public function changeDefault($default)
    {
        $this->default = $default;
        return $this;
    }

   	/**
	 * Change the collation sequence for this column.
	 *
	 * @param string $collation.
	 * @return self
	 */
	public function changeCollation($collation)
	{
        $this->collation = $collation;
        return $this;
	}

    /**
     * Change the character set of the column.
     *
     * @param string $charset
     * @return self
     */
    public function changeCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }
}
