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
 * Make the select box and provide validation for time formats.
 * @package Tiramisu
 * @subpackage Forms
 */
class Tm_Form_Select extends Tm_Form_ElementAbstract
{
	/**
	 * Override parent allowed properties to add options property.
	 * @var array
	 */
	protected $allowed_props = array('label', 'help', 'required', 'attribs', 'default',
		'sanitize', 'validate', 'error_message', 'value', 'options');

	/**
	 * List of options to include in the select form element.
	 * @var array
	 */
	public $options = array();

	/**
	 * Set the options for the select dropdown. Use this when you can't pass options in at construction time.
	 * @param array $options
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
	}

	/**
	 * Set a default value for the select box.
	 *
	 * If the value is not in the options and the second arguement is passed the
	 * value and text will be added. Otherwise the value should match an existing
	 * option key.
	 *
	 * @param mixed
	 * @return void
	 * @throws InvalidArgumentExcpetion
	 */
	public function setDefault($value, $text = null)
	{
		if (!is_null($text) && !isset($this->options[$value])) {
			if ($value == 0) {
				array_unshift($this->options, $text);
			} else {
				$this->options = array_merge(array($value => $text), $this->options);
			}
		}
		$this->default = $value;
	}

	/**
	 * Build the select box.
	 * @return string
	 */
	public function makeElement()
	{
		$selected = $this->value;
		if (empty($selected)) {
			$selected = $this->default;
		}
		$id = (isset($this->attributes['id'])) ? $this->attributes['id'] : $this->name;
		$class = (isset($this->attributes['class'])) ? $this->attributes['class'] : 'selectbox';
		$out = "<select name='{$this->name}' id='{$id}' class='{$class}'";
		$out .= $this->renderAttributes() . ">\n";
		foreach ($this->options as $value => $option) {
			$out .= "\t<option value='$value'";
			// Triple equals required here. 0 == 'some string' evaluates to true in PHP.
			if ($value === $selected) {
				$out .= " selected='selected'";
			}
			$out .= ">$option</option>\n";
		}
		$out .= "</select>\n";

		return $out;
	}

	/**
	 * Check user submitted value to make sure it matches one of the options, or perform another check.
	 * @return bool
	 */
	public function isValid($value)
	{
		$this->value = $value;
		$this->valid = true;

		if ($value == $this->default) {
			if ($this->required) {
				$this->error_message = sprintf(t('element_required'), $this->getLabel());
				$this->valid = false;
				return $this->valid;
			} else {
				return true;
			}
		}

		if (!isset($this->options[$value])) {
			$this->valid = false;
		}

		return $this->valid;
	}
}
