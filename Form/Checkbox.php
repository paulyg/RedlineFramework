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
 * Represents an HTML form checkbox.
 * @package Tiramisu
 * @subpackage Forms
 */
class Tm_Form_Checkbox extends Tm_Form_ElementAbstract
{
	/**
	 * Default sanitizer function
	 * @var string
	 */
	public $sanitize = 'sanitizeBool';

	/**
	 * Default validator function
	 * @var string
	 */
	public $validate = 'validateBool';

	/**
	 * HTML attributes to be rendered by renderAttribs.
	 * @var array
	 */
	protected $extra_attribs = array('lang', 'dir', 'title', 'style', 'tabindex', 'accesskey');

	/**
	 * Create the checkbox and label optionally wrapped in a P tag.
	 * @uses makeElement()
	 * @param bool $wrap
	 * @return string
	 */
	public function render($wrap = true)
	{
		$html = ($wrap) ? "<p>\n" : '';
		$html .= $this->makeElement() . 
			"<label for='{$this->name}' class='checkbox'>" . $this->getLabel() .
			"</label>\n"; 
		$html .= ($wrap) ?  "</p>\n" : '';
		return $html;
	}

	/**
	 * Build the checkbox element.
	 * @return string
	 */
	public function makeElement()
	{
		$id = (isset($this->attributes['id'])) ? $this->attributes['id'] : $this->name;
		$class = (isset($this->attributes['class'])) ? $this->attributes['class'] : 'checkbox';
		$html = "<input type='checkbox' name='{$this->name}' id='{$id}' class='{$class}'";
		$html .= $this->renderAttributes();
		if ($this->getValue() == true) {
			$html .= " checked='checked'";
		}
		$html .= " />\n";

		return $html;
	}
}
