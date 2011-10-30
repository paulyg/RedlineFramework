<?php
/**
 * @package Tiramisu
 * @subpackage Forms
 * @author Paul Garvin <paul@paulgarvin.net>
 * @copyright Copyright 2009, 2010 Paul Garvin. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GNU General Public License
 * @link http://www.tiramisu-cms.org
 * @version @package_version@
 *
 * Tiramisu is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Tiramisu is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tiramisu. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Represents an HTML form hidden element.
 * @package Tiramisu
 * @subpackage Forms
 */
class Tm_Form_Hidden extends Tm_Form_ElementAbstract
{
	/**
	 * Build the hidden element.
	 * @return string
	 */
	public function render($wrap = false)
	{
		return $this->makeElement();
	}

	public function makeElement()
	{
		$id = (isset($this->attributes['id'])) ? $this->attributes['id'] : $this->name;
		$value = h($this->getValue());
		return "<input type='hidden' name='{$this->name}' id='{$id}' value='{$value}' />\n";
	}
}
