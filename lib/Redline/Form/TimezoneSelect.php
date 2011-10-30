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
 * Make the Timezone select boxes and validate submitted timezone.
 * @package Tiramisu
 * @subpackage Forms
 */
class Tm_Form_TimezoneSelect extends Tm_Form_ElementAbstract
{
	protected static $regions = array(
		'Africa' => DateTimeZone::AFRICA,
		'America' => DateTimeZone::AMERICA,
		'Antarctica' => DateTimeZone::ANTARCTICA,
		'Aisa' => DateTimeZone::ASIA,
		'Atlantic' => DateTimeZone::ATLANTIC,
		'Europe' => DateTimeZone::EUROPE,
		'Indian' => DateTimeZone::INDIAN,
		'Pacific' => DateTimeZone::PACIFIC
		);

	/**
	 * Create the timezone select box.
	 * @return string
	 */
	public function makeElement()
	{
		$selected = $this->splitTz($this->value);
		$tzlist = array();
		$out = "<select name='timezone[]' id='tz_region' class='selectbox' tabindex='{$this->attributes['tabindex']}'>\n";
		$out .= "\t<option></option>\n";
		if ($selected[0] == 'UTC') {
			$out .= "\t<option value='UTC' selected='selected'>UTC</option>\n";
		} else {
			$out .= "\t<option value='UTC'>UTC</option>\n";
		}
		foreach (self::$regions as $name => $mask) {
			$tzlist[$name] = DateTimeZone::listIdentifiers($mask);
			if ($selected[0] == $name) {
				$out .= "\t<option value='$name' selected='selected'>$name</option>\n";
			} else {
				$out .= "\t<option value='$name'>$name</option>\n";
			}
			foreach ($tzlist[$name] as &$tz) {
				$tz = substr($tz, strpos($tz, '/') + 1);
			}
			unset($tz);
		}
		$out .= "</select>\n";
		$jslist = json_encode($tzlist);

$js = <<<EOHD
<select name="timezone[]" id="tz_city" disabled="disabled" class="selectbox"></select>
<script type="text/javascript">
var tzmatrix = $jslist;
var currcity = "{$selected[1]}";
$(document).ready(function() {
	var selectedregion = $("#tz_region").val();
	if (selectedregion != 'UTC' || selectedregion != '') {
		var citydd = $("#tz_city");
		var citylist = tzmatrix[selectedregion];
		jQuery.each(citylist, function(key, val) {
			if (val == currcity) {
				citydd.append("<option value=\"" + val + "\" selected=\"selected\">" + val + "</option>");
			} else {
				citydd.append("<option value=\"" + val + "\">" + val + "</option>");
			}
		});
		citydd.removeAttr("disabled");
	}
});
$("#tz_region").change(function() {
	var selectedregion = $("#tz_region").val();
	var citydd = $("#tz_city");
	if (selectedregion == 'UTC' || selectedregion == '') {
		citydd.html('');
		citydd.Attr("disabled", "disabled");
	} else {
		var citylist = tzmatrix[selectedregion];
		citydd.html('');
		jQuery.each(citylist, function(key, val) {
			citydd.append("<option value=\"" + val + "\">" + val + "</option>");
		});
		citydd.removeAttr("disabled");
	}
});
</script>
EOHD;

		return $out . $js;
	}

	/**
	 * Check a user submitted timezone is valid.
	 * @param array $submitted
	 * @return bool
	 */
	public function isValid($submitted)
	{
		$this->error_message = t('timezone_validation_failure');

		if (empty($submitted)) {
			if ($this->required) {
				$this->valid = false;
			} else {
				$this->valid = true;
			}
			return $this->valid;
		}

		$submitted = implode('/', $submitted);
				
		try {
			$tz = new DateTimeZone($submitted);
			$this->valid = true;
		} catch (Exception $e) {
			$this->valid = false;
		}
		return $this->valid;
	}

	/**
	 * Split a timezone into it's parts.
	 * @param string $tz_str
	 * @return array
	 */
	protected function splitTz($tz_str)
	{
		$tz_arr = explode('/', $tz_str);
		$city = '';
		foreach ($tz_arr as $k => $part) {
			switch ($k) {
				case 0:
					continue;
					break;
				case 1:
					$city = $part;
					break;
				default:
					$city .= '/' . $part;
			}
		}
		return array($tz_arr[0], $city);
	}
}
