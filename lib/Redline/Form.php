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
 * Encapsulates common code for generation and handling of HTML forms.
 * @package Tiramisu
 * @subpackage Forms
 */
class Tm_Form implements ArrayAccess, IteratorAggregate
{
	/**
	 * Message to display on form submission success.
	 *
	 * @var string
	 */
	public $success_message;

	/**
	 * Message to display if form submission failed.
	 *
	 * @var string
	 */
	public $failure_message;

	/**
	 * Validation status for form.
	 *
	 * @var bool
	 */
	protected $valid = false;

	/**
	 * Holds values of the attributes for the form element itself.
	 *
	 * @var array
	 */
	public $attribs = array();

	/**
	 * Default values for the form attriutes.
	 *
	 * @var array
	 */
	protected $default_attribs = array(
		'action' => null,
		'method' => 'post',
		'accept-charset' => 'UTF-8'
	);

	/**
	 * Array containing all of the elements belonging to the form.
	 *
	 * Element key is name.
	 * @see addElement()
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Holds information about submit button(s) for the form.
	 *
	 * @var array
	 */
	public $submit_buttons = array();

	/**
	 * URL to send the user to if they want to cancel the form, leave blank for none.
	 *
	 * @var string|bool
	 */
	public $cancel_link = false;

	/**
	 * Counter for setting 'tabindex' on elements.
	 *
	 * @var int
	 */
	protected $tabindex = 0;

	/**
	 * Render help strings with this form or not.
	 *
	 * @var bool
	 */
	protected $show_help = false;

	/**
	 * Object Constructor.
	 *
	 * @param array $attribs Attributes to set for the form
	 * @return Tm_Form
	 */
	public function __construct(array $attribs = null)
	{
		if (is_null($attribs)) {
			$attribs = array();
		}
		if (!isset($attribs['action'])) {
			$this->default_attribs['action'] = h($_SERVER['REQUEST_URI']);
		}

		$this->attribs = array_merge($this->default_attribs, $attribs);
	}

	/**
	 * Allows easy iteration over form elements.
	 * 
	 * <code>
	 * foreach ($form as $element)
	 * </code>
	 * instead of
	 * <code>
	 * foreach ($form->elements as $element)
	 * </code>
	 * Satisfies IteratorAggregate interface.
	 *
	 * @return Traversable
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->elements);
	}

	/**
	 * Part of ArrayAccess implementation, allow easy access to elements.
	 *
	 * <code>
	 * if (isset($form['password']))
	 * </code>
	 *
	 * @param string $name Element name
	 * @return bool
	 */
	public function offsetExists($name)
	{
		return isset($this->elements[$name]);
	}

	/**
	 * Part of ArrayAccess implementation, allow easy access to elements.
	 *
	 * <code>
	 * $form['title']->setAttrib('class', 'big');
	 * </code>
	 *
	 * @param string $name Element name
	 * @return Tm_Form_ElementAbstract
	 */
	public function offsetGet($name)
	{
		return isset($this->elements[$name]) ? $this->elements[$name] : null;
	}

	/**
	 * Part of ArrayAccess implementation, allow easy access to elements.
	 *
	 * <code>
	 * $form['tag'] = new Tm_Form_Text('tag', $tag_props);
	 * </code>
	 * or
	 * <code>
	 * $form['tag'] = $tag_props;
	 * </code>
	 *
	 * @param string $name Element name
	 * @param array|Tm_Form_ElementAbstract Element array definition or object
	 * @return void
	 */
	public function offsetSet($name, $element)
	{
		if (is_null($name)) {
			throw new LogicException('Form elements must have a name. None passed using ArrayAccess to add a form element.');
		}
		if (is_array($element)) {
			$this->addElement($name, $element);
		} elseif ($element instanceof Tm_Form_ElementAbstract) {
			$this->tabindex++;
			$element->setAttrib('tabindex', $this->tabindex);
			$this->elements[$name] = $element;
		} else {
			throw new LogicException(__METHOD__ . ': Invalid type passed for $element. Must be array or instance of Tm_Form_ElementAbstract.');
		}
	}

	/**
	 * Part of ArrayAccess implementation, allow easy access to elements.
	 *
	 * <code>
	 * unset($form['password']);
	 * </code>
	 *
	 * @param string $name Element name
	 * @return void
	 */
	public function offsetUnset($name)
	{
		unset($this->elements[$name]);
	}

	/**
	 * Set a form attribute.
	 *
	 * @param string $attrib Attribute name
	 * @param string $value Attribute value
	 * @return void
	 */
	public function setAttribute($attrib, $value)
	{
		$valid_attribs = array('action', 'accept', 'accept-charset', 'enctype', 'method',
			'name', 'target', 'id', 'class', 'dir', 'lang', 'style', 'title');
		if (in_array($attrib, $valid_attribs)) {
			$this->attribs[$attrib] = (string) $value;
		} else {
			throw new InvalidArgumentException("Invalid argument: '$attrib' is not a valid <form> attribute.");
		}
	}

	/**
	 * Get a form attribute. If not set returns empty string.
	 * @param string $attrib Attribute name
	 * @return string
	 */
	public function getAttribute($attrib)
	{
		return (isset($this->attribs[$attrib])) ? $this->attribs[$attrib] : '';
	}

	/**
	 * Set multiple attributes at once.
	 * @param array $attribs
	 * @return void
	 */
	public function setAttributes(array $attribs)
	{
		foreach ($attribs as $attrib) {
			$this->setAttrib($attrib);
		}
	}

	/**
	 * Add an element to the form.
	 * @param string $name Name of the form element
	 * @param array $props Properties of the element
	 * @return void
	 */
	public function addElement($name, array $props)
	{
		$type = $props['type'];
		$type = 'Tm_Form_' . ucfirst($type);

		$element = new $type($name, $props);

		$this->tabindex++;
		$element->setAttribute('tabindex', $this->tabindex);

		$this->elements[$name] = $element;
	}

	/**
	 * Add multiple elements to the form.
	 * @param array $list Array of form elements with the form 'name' => array()
	 * where the array contains the element properties.
	 * @return void
	 */
	public function addElements($list)
	{
		foreach ($list as $name => $props) {
			$this->addElement($name, $props);
		}
	}

	/**
	 * Adds a submit button to the form
	 * @param string $value What the button will say
	 * @param string $name Optional, Field name of the button, use with multiple submits
	 * @param array $attribs Optional, other HTML attributes
	 * @return void
	 */
	public function addSubmit($value, $name = null, $attribs = array())
	{
		if (!is_string($value)) {
			throw new InvalidArgumentException('Invalid type for value, must be a string.');
		}
		if (empty($name)) {
			if (count($this->submit_buttons) > 0) {
				throw new LogicException('Multiple submit buttons can not be added to a form without assigning them a name.');
			} else {
				$this->submit_buttons[] = array('value' => t($value), 'attribs' => $attribs);
			}
		} else {
			$this->submit_buttons[$name] = array('name' => $name, 'value' => t($value), 'attribs' => $attribs);
		}
	}

    /**
     * Returns the form element values as an array.
     * @return array An associative array of element values.
     */
    public function getValues()
    {
        $values = array();
        foreach ($this->elements as $name => $element) {
            $values[$name] = $element->getValue();
		}
 
		return $values;
     }

	 /**
	  * Set the current values for all elements in the form.
	  * @param array|object $values
	  */
	 public function setValues(&$values)
	 {
		 if (is_array($values)) {
			 foreach ($this->elements as $name => $element) {
				 if (isset($values[$name])) {
					 $element->setValue($values[$name]);
				 }
			 }
		 } elseif (is_object($values)) {
			 foreach ($this->elements as $name => $element) {
				 if (isset($values->$name)) {
					 $element->setValue($values->$name);
				 }
			 }
		 }
	 }

	/**
	 * Validate each form element and return the result.
	 * If any elements return a false validation the entire form returns false.
	 * Input can be the $_POST array, a decoded JSON array from AJAX, or an array
	 * of values from an XML-RPC call.
	 * @param array|object $input Form input values.
	 * @return bool
	 */
	public function isValid($input)
	{
		if (!$this->checkFormToken()) {
			$this->failure_message = t('invalid_form_token');
			return false;
		}

		$valid = true;
		foreach ($this->elements as $name => $element) {
			$value = (isset($input[$name])) ? $input[$name] : $element->default;
			$valid = $element->isValid($value) && $valid;
		/*	echo "$name : $valid ";
			var_dump($value);
			echo "<br />";  */
		}

		$this->valid = $valid;
        return $this->valid;
	}

	/**
	 * Generate XHTML for the form.
	 * @return string
	 */
	public function render()
	{
		$attributes = '';
		foreach ($this->attribs as $name => $value) {
			$attributes .= " $name='$value'";
		}
		$out = "<form$attributes>\n";

		$out .= $this->generateFormToken() . "\n";

		foreach ($this->elements as $element) {
			$out .= $element->render();
		}

		$out .= "<p>\n";
		$out .= $this->renderSubmits();

		if ($this->cancel_link) {
			$cancel = t('cancel');
			$out .= "<a href='{$this->cancel_link}'>$cancel<a/>\n";
		}
		$out .= "</p>\n</form>\n";

		return $out;
	}

	/**
	 * Get list of errors from each invalid element.
	 * @return string
	 */
	public function getErrors()
	{
		$msgs = '';
		foreach ($this->elements as $element) {
			if (!$element->isValid()) {
				$msgs .= $element->getLabel() . ': ' . $element->getErrorMessage();
			}
		}
		return $msgs;
	}

	/**
	 * Create a form token to verify that posted data is coming from where we think it is.
	 * @return string
	 */
	public static function generateFormToken()
	{
		$token = md5(uniqid(mt_rand(), true));
		$_SESSION['form_token'] = $token;
		return "<input type='hidden' name='form-token' value='$token' />\n";
	}

	/**
	 * Check a returned form token to see if it is valid.
	 * Automatically pulls form token from $_POST, no need to pass it.
	 * @return bool
	 */
	public static function checkFormToken()
	{
		if (isset($_POST['form-token']) && isset($_SESSION['form_token']) && 
			($_POST['form-token'] == $_SESSION['form_token'])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create the HTML for a submit button.
	 * @param array $props
	 * @return string
	 */
	public function renderSubmit($props)
	{
		$out = "<input type='submit' ";
		if (isset($props['name'])) {
			$out .= "name='{$props['name']}' ";
		}
		foreach ($props['attribs'] as $name => $value) {
			$out .= "$name='$value' ";
		}
		$out .= "value='{$props['value']}' />\n";

		return $out;
	}

	/**
	 * Render all submit buttons at once.
	 * @return string
	 */
	public function renderSubmits()
	{
		$out = '';
		foreach ($this->submit_buttons as $button) {
			$out .= $this->renderSubmit($button);
		}
		return $out;
	}
}
