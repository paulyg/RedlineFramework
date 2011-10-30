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
 * Represents a single element in a form and provides methods for validation
 * and rendering.
 * @package Tiramisu
 * @subpackage Forms
 */
abstract class Tm_Form_ElementAbstract
{
	/**
	 * Name of the form element. This will become the HTML name attribute.
	 * @var string
	 */
	protected $name;

	/**
	 * Type of form element. Can be any of the <input> types, <select>, <textarea>
	 * or 'custom'.
	 * @var string
	 */
	protected $type;

	/**
	 * Label text for the element
	 * @var string
	 */
	public $label;

	/**
	 * Descriptive or help text for the form field
	 * @var string
	 */
	public $help;

	/**
	 * Validation status
	 * @var bool
	 */
	protected $valid = true;

	/**
	 * Is this element required (ie can it be blank)?
	 * @var bool
	 */
	public $required = false;

	/**
	 * Default value if none is submitted with form
	 * @var mixed
	 */
	public $default = '';

	/**
	 * Is this element disabled?
	 * @var bool
	 */
	public $disabled = false;

	/**
	 * Miscellaneous HTML attributes for the element
	 * @var array
	 */
	public $attributes = array();

	/**
	 * Function/method to call to clean user input
	 * @var string|array
	 */
	public $sanitize;

	/**
	 * Function/method to call to validate value of element
	 * @var string|array
	 */
	public $validate;

	/**
	 * Error message to display to user on invalid input, recieved from filter
	 * or overrride with your own
	 * @var array
	 */
	public $error_message;

	/**
	 * The form element value
	 * @var mixed
	 */
	public $value;

	/**
	 * Object properties that may be set though the $info array
	 * @var array
	 */
	protected $allowed_props = array('label', 'help', 'required', 'attribs', 'default',
		'sanitize', 'validate', 'error_message', 'value');

	/**
	 * HTML attributes to be rendered by renderAttribs.
	 * @var array
	 */
	protected $extra_attribs = array('id', 'class', 'lang', 'dir', 'title', 'style',
		'tabindex', 'accesskey');

	/**
	 * Initalize the instance
	 *
	 * @param string $name The name for the field in HTML forms
	 * @param array $props An array containing all the configuration values for the element
	 * @return Tm_Form_Element
	 */
	public function __construct($name, array $props)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Invalid type for $name, must be a string.');
		}
		$this->name = $name;

		foreach ($props as $prop => $value) {
			if (property_exists($this, $prop) && ($prop != 'name')) {
				$this->$prop = $value;
			}
		}
	}

	/**
	 * Set an HTML attribute value for the element, will overwrite if value already set
	 * @param string $name
	 * @param string|int $value
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = (string) $value;
	}

	/**
	 * Render the miscellaneous HTML elements.
	 * @return string
	 */
	public function renderAttributes()
	{
		if (empty($this->attributes)) {
			return '';
		}
		$attributes = ' ';
		if ($this->disabled) {
			$attributes .= "disabled='disabled'";
		}
		foreach ($this->attributes as $name => $value) {
			if (in_array($name, $this->extra_attribs)) {
				$attributes .= " $name='$value'";
			}
		}
		return $attributes;
	}

	/**
	 * Return the name of the element.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Return the stored value of the form element.
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Set a new value for the form element.
	 * @param mixed $value
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Return the translated label used for the form element.
	 * @return string
	 */
	public function getLabel()
	{
		return t($this->label);
	}

	/**
	 * Generate the HTML for the element's label.
	 * @return string
	 */
	public function makeLabel()
	{
		return "<label for='{$this->name}'>" . $this->getLabel() . "</label>\n";
	}

	/**
	 * Does this form element have a validation error?
	 *
	 * Will return FALSE if isValid() has not been called.
	 *
	 * @return bool
	 */
	public function hasError()
	{
		return !$this->valid;
	}

	/**
	 * Return any error messages generated by form element validators.
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->error_message;
	}

	/**
	 * Check if the submitted value passes all validators for this element.
	 * The value is run through any sanitizers first.
	 * @param mixed $value Value from $_POST or other source.
	 * @return bool
	 */
	public function isValid($value)
	{
		if ($this->disabled) {
			return true;
		}
		if (!empty($this->sanitize)) {
			$method = $this->sanitize;
			$this->value = Tm_Filter::$method($value);
		} else {
			$this->value = $value;
		}

		if ($this->required) {
			if (empty($this->value) || ($this->value == $this->default)) {
				$this->error_message = sprintf(t('element_required'), $this->getLabel());
				$this->valid = false;
				return $this->valid;
			}
		}

		if (!empty($this->validate)) {
			if (is_string($this->validate)) {
				$class = 'Tm_Filter';
				$method = $this->validate;
				$args = array($this->value);
			} elseif (is_array($this->validate)) {
				$class = (isset($this->validate['class'])) ? $this->validate['class'] : 'Tm_Filter';
				$method = $this->validate['method'];
				$args = (isset($this->validate['args'])) ? $this->validate['args'] : array();
				array_unshift($args, $this->value);
			}
			$this->valid = call_user_func_array(array($class, $method), $args);
			$this->error_message = call_user_func(array($class, 'getMessage'), $method);
		}

		return $this->valid;
	}

	/**
	 * Creates the common HTML for each form element.
	 * @uses makeElement()
	 * @param bool $wrap Wrap with <p> tag?
	 * @return string
	 */
	public function render($wrap = true)
	{
		if ($wrap) {
			$html = "<p>\n";
		} else {
			$html = '';
		}
		$html .= $this->makeLabel();
		$html .= $this->makeElement();

		if (!$this->valid) {
			$html .= "<br><span class='form-error'>{$this->error_message}</span>\n";
		} elseif (!empty($this->help)) {
			$html .= "<br><span class='form-help'>" . t($this->help) . "</span>\n";
		}
		if ($wrap) {
			$html .=  "</p>\n";
		}

		return $html;
	}

	/**
	 * Element specific rendering logic.
	 * @return string
	 */
	abstract public function makeElement();
}
