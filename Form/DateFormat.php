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
 * Make the select box and provide validation for date formats.
 * @package Tiramisu
 * @subpackage Forms
 */
class Tm_Form_DateFormat extends Tm_Form_Select
{
	/**
	 * Holds the available date formats
	 * @var array
	 */
	public $options = array(
		// Day first formats, Europe
		'j/n/Y',   // 1/8/2009
		'd/m/Y',   // 01/08/2009
		'j-n-Y',   // 1-8-2009
		'd-m-Y',   // 01-08-2009
		'j.n.Y',   // 1.8.2009
		'd.m.Y',   // 01.08.2009
		'j M Y',   // 1 Aug 2009
		'D j M Y', // Sat 1 Aug 2009
		'j F Y',   // 1 August 2009
		'l j F Y', // Saturday 1 August 2009
		// Month first formats, USA
		'n/j/Y',   // 8/1/2009
		'd/m/Y',   // 08/01/2009
		'n-j-Y',   // 8-1-2009
		'm-d-Y',   // 08-01-2009
		'M j, Y',  // Aug 1, 2009
		'D, M j, Y',  // Sat, Aug 1, 2009
		'F jS, Y',    // August 1st, 2009
		'l, F jS, Y', // Saturday, August 1st, 2009
		// Year first formats, ISO-8601 like
		'Y-m-d',   // 2009-08-01
		'Y M j',   // 2009 Aug 1
		'Y M d',   // 2009 Aug 01
		'Y F j',   // 2009 August 1
		'Y F d',   // 2009 August 01
	);

	/**
	 * Build the date format select box
	 * @return string
	 */
	public function makeElement()
	{
		$selected = $this->getValue();
		$now = time();
		$out = "<select class='selectbox' name='{$this->name}' id='{$this->name}' tabindex='{$this->attributes['tabindex']}'>\n";
		if (!$this->required && !$selected) {
			$out .= "\t<option value='0' selected='selected'>" . t('site_default') . "</option>\n";
		}
		foreach ($this->options as $format) {
			$out .= "\t<option value='{$format}'";
			if ($format === $selected) {
				$out .= " selected='selected'";
			}
			$out .= ">" . date($format, $now) . "</option>\n";
		}
		$out .= "</select>\n";

		return $out;
	}

	/**
	 * Check a user submitted date format to make sure it matches one of ours.
	 * @param string $value
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

		$this->valid = in_array($value, $this->options);

		return $this->valid;
	}
}
