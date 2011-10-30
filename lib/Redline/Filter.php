<?php
/**
 * @package Tiramisu
 * @package Framework
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
 * Provides routines for sanitizing and validating of user input.
 *
 * Uses PHP's Filter extension where possible.
 *
 * @package Tiramisu
 * @subpackage Framework
 */
class Tm_Filter
{
	/**
	 * Regex for a valid internet hostname, used in various places.
	 * Use with 'i' PCRE switch to make case-insensitive.
	 */
	const HOSTNAME_REGEX = '(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[a-z]{2,4}|museum|travel)';

	/**
	 * Valid URL path characters, don't need 'i' regex switch.
	 */
	const PCHAR_REGEX = '(?\/(?(?:%[[:xdigit:]]{2}|[a-zA-Z0-9\-_.!~*\'()$&+,=;:@])*)?)+';

	/**
	 * Valid characters for query and fragment URL segments.
	 */
	const URIC_REGEX = '(?:%[[:xdigit:]]{2}|[a-zA-Z0-9\-_.!~*\'()$&+,=;:@\/?])';

	/**
	 * List of allowed URL schemes.
	 * @var array
	 */
	public static $allowed_schemes = array('http', 'https', 'ftp', 'ftps', 'news',
		'nntp', 'mailto', 'irc', 'feed', 'webcal', 'cvs', 'svn', 'git',
		'aim', 'msnim', 'ymsgr', 'xmpp', 'itpc', 'itms');

	/**
	 * Validate if the submitted value is a boolean or not.
	 *
	 * Use in conjunction with {@link sanitizeBool()}. If value is not PHP boolean
	 * values TRUE OR FALSE, FALSE is returned.
	 *
	 * @param mixed $value Value to validate 
	 * @return bool
	 */
	public static function validateBool($value)
	{
		if (($value === true) or ($value === false) or
			($value === 1) or ($value === 0)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if the submitted value is a boolean and converts to 0 or 1.
	 *
	 * on, off, yes, no, 'true', 'false' are converted to a 0 or 1 as appropriate.
	 * If value is none of the above NULL is returned. Use in conjunction with
	 * {@link validateBool()}
	 *
	 * @param mixed $value Value to sanitize
	 * @return int|null
	 */
	public static function sanitizeBool($value)
	{
		if (empty($value)) {
			return 0;
		}
		if ($value === false) {
			return $value; // PHP bug #49510, not fixed as of 5.3.2
		}
		$result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if ($result === true) {
			$result = 1;
		} elseif ($result === false) {
			$result = 0;
		}
		return $result;
	}

	/**
	 * Validate if the submitted value is a float value or not.
	 * 
	 * @param mixed $value Value to validate 
	 * @return float|false
	 */
	public static function validateFloat($value)
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT);
	}

	/**
	 * Validate if the submitted value is an integer or not.
	 *
	 * @param mixed $value Value to validate 
	 * @return int|false
	 */
	public static function validateInt($value)
	{
		return filter_var($value, FILTER_VALIDATE_INT);
	}

	/**
	 * Validate if the submitted value is an email address or not
	 *
	 * @param mixed $value Value to validate 
	 * @return bool
	 */
	public static function validateEmail($value)
	{
		$value = (string) $value;

		return preg_match('/^[a-z0-9_%+-](?:\.*[a-z0-9_%+-]+)*@' . self::HOSTNAME_REGEX . '$/i', $value);
	}

	/**
	 * Validate if the submitted value is a URL or not
	 *
	 * @param mixed $value Value to validate 
	 * @return bool
	 */
	public static function validateUrl($value)
	{
		return filter_var($value, FILTER_VALIDATE_URL);

		/*$allowed_schemes = array('http', 'https', 'ftp', 'ftps', 'news', 'nntp',
			'mailto', 'irc', 'feed', 'webcal', 'cvs', 'svn', 'git', 'apt');

		$parts_blank = array('scheme' => '', 'host' => '', 'port' => '', 'user' => '',
			 'pass' => '', 'path' => '', 'query' => '', 'fragment' => '');

		if ($parts = parse_url($value)) {
			extract(array_merge($parts_blank, $parts));
			echo "URL parsed<br />\n";
			var_dump($parts);
		} else {
			return false;
		}

		if (!empty($scheme) && (!in_array($scheme, $allowed_schemes))) {
			return false;
			var_dump($scheme);
		} else {
			echo "URL failed scheme<br />\n";
		}

		if (!empty($scheme)) {
			$valid = preg_match('/^' . self::HOSTNAME_REGEX . '$/i', $host);
			if (!empty($query)) {
				$valid = $valid && preg_match('/^' . self::URIC_REGEX . '$/', $query);
			}
			if (!empty($fragment)) {
				$valid = $valid && preg_match('/^' . self::URIC_REGEX . '$/', $fragment);
			}
			return $valid;

		} else {
			switch ($scheme) {
				case 'mailto':
					if (!empty($host) || !empty($port) || !empty($user) || !empty($pass)
						|| !empty($query) || !empty($fragment)) {
						return false;
					}
					return self::validateEmail($path);
				break;

				case 'irc':
					// IRC may have own URI rules
				break;

				case 'apt':
					// have to research
				break;

				default:
					$valid = preg_match('/^' . self::HOSTNAME_REGEX . '$/', $host);
					if (!empty($query)) {
						$valid = $valid && preg_match('/^' . self::URIC_REGEX . '$/', $query);
					}
					if (!empty($fragment)) {
						$valid = $valid && preg_match('/^' . self::URIC_REGEX . '$/', $fragment);
					}
					return $valid;
			}
		}*/
	}

	/**
	 * Do some light cleanup on URLs. This really doesn't sanitize much.
	 *
	 * We are using the sanitize construct to do some basic altering of URLs
	 * passed in forms and in the page content. If a hostname is present but
	 * a scheme is not http:// is prepended. If the URL contains an absolute
	 * reference to the server Tiramisu is on it is removed.
	 *
	 * @param string $value URL to sanitize
	 * @return Sanitized URL
	 */
	public static function sanitizeUrl($value)
	{
		if (preg_match('/^' . self::HOSTNAME_REGEX . '/i', $value)) {
			$hostname = $_SERVER['SERVER_NAME'];
			if (preg_match("/^$hostname/", $value)) {
				$value = substr($value, strlen($hostname));
			} else {
				$value = 'http://' . $value;
			}
		}

		return $value;
	}

	/**
	 * Check if the value contains only valid page slug (HTTP URL path) characters.
	 *
	 * @param mixed $value Value to validate 
	 * @return bool
	 */
	public static function validatePageSlug($value)
	{
		if (empty($value)) {
			return true;
		}
		$value = (string) $value;

		// allow -_.~!()+:@
		return preg_match('/^[a-zA-Z0-9_][a-zA-Z0-9\-_.~+!():@]*$/', $value);
	}

	/**
	 * Replace non-allowed characters in page slugs
	 *
	 * @param mixed $value Value to sanitize 
	 * @return string
	 */
	public static function sanitizePageSlug($value)
	{
		$value = (string) $value;
		
		// Do accented character replacement
		$accents = array('À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
			'Ä' => 'A', 'Å' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
			'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
			'Õ' => 'O', 'Ö' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
			'Ü' => 'U', 'Ý' => 'Y', 'ß' => 's', 'à' => 'a', 'á' => 'a',
			'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i',
			'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o',
			'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ù' => 'u',
			'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
			'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A', 'ă' => 'a', 'Ą' => 'A',
			'ą' => 'a', 'Ć' => 'C', 'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c',
			'Ċ' => 'C', 'ċ' => 'c', 'Č' => 'C', 'č' => 'c', 'Ď' => 'D',
			'ď' => 'd', 'Đ' => 'D', 'đ' => 'd', 'Ē' => 'E', 'ē' => 'e',
			'Ĕ' => 'E', 'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e', 'Ę' => 'E',
			'ę' => 'e', 'Ě' => 'E', 'ě' => 'e', 'Ĝ' => 'G', 'ĝ' => 'g',
			'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G', 'ġ' => 'g', 'Ģ' => 'G',
			'ģ' => 'g', 'Ĥ' => 'H', 'ĥ' => 'h', 'Ħ' => 'H', 'ħ' => 'h',
			'Ĩ' => 'I', 'ĩ' => 'i', 'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I',
			'ĭ' => 'i', 'Į' => 'I', 'į' => 'i', 'İ' => 'I', 'ı' => 'i',
			'Ĳ' => 'IJ', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j', 'Ķ' => 'K',
			'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L',
			'ļ' => 'l', 'Ľ' => 'L', 'ľ' => 'l', 'Ŀ' => 'L', 'ŀ' => 'l',
			'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N',
			'ņ' => 'n', 'Ň' => 'N', 'ň' => 'n', 'ŉ' => 'N', 'Ŋ' => 'n',
			'ŋ' => 'N', 'Ō' => 'O', 'ō' => 'o', 'Ŏ' => 'O', 'ŏ' => 'o',
			'Ő' => 'O', 'ő' => 'o', 'Œ' => 'OE', 'œ' => 'oe', 'Ŕ' => 'R',
			'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r',
			'Ś' => 'S', 'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S',
			'ş' => 's', 'Š' => 'S', 'š' => 's', 'Ţ' => 'T', 'ţ' => 't',
			'Ť' => 'T', 'ť' => 't', 'Ŧ' => 'T', 'ŧ' => 't', 'Ũ' => 'U',
			'ũ' => 'u', 'Ū' => 'U', 'ū' => 'u', 'Ŭ' => 'U', 'ŭ' => 'u',
			'Ů' => 'U', 'ů' => 'u', 'Ű' => 'U', 'ű' => 'u', 'Ų' => 'U',
			'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w', 'Ŷ' => 'Y', 'ŷ' => 'y',
			'Ÿ' => 'Y', 'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z',
			'Ž' => 'Z', 'ž' => 'z', 'ſ' => 's');

		$value = strtr($value, $accents);

		$value = mb_strtolower($value);
		
		/* According to RFC1738 & RFC3986 -_.~!$&'()*+,;=:@ are allowed in
		   path part of URL. We exclude ' because it's an HTML special char,
		   = and & because they are used in query strings, * is unix glob,
		   , and ; and $ just don't make sense. That leaves -_.~!()+:@ */
		$special_chars = array('`', '#', '$', '%', '^', '&', '*', '=', '[', ']',
			'{', '}', '|', '\\', ';', '\'', '"', ',', '<', '>', '/', '?');
		$value = str_replace($special_chars, '', $value);

		$value = preg_replace('/\s+/', '-', $value);

		$value = preg_replace('/-{2,}/', '-', $value);

		/* If there is anything greater than 128 left in the string it's UTF-8
		   that needs to be encoded. */
		$encval = '';
		$len = strlen($value);
		for ($i = 0; $i < $len; $i++) {
			$dec = ord($value[$i]);
			if ($dec > 128) {
				$encval .= '%' . dechex($dec);
			} else {
				$encval .= $value[$i];
			}
		}
		return $encval;
	}

	/**
	 * Check to make sure the string is encoded in UTF-8 and is under a certain length
	 *
	 * @param mixed $str Value to validate
	 * @param int $max_len OPTIONAL Maximum string length
	 * @param int $min_len OPTIONAL Minimum string length
	 * @return bool
	 */
	public static function validateString($str, $max_len = 0, $min_len = 0)
	{
		if ($max_len > 0) {
			if (mb_strlen($str) > $max_len) {
				return false;
			}
		}

		if ($min_len > 0) {
			if (mb_strlen($str) < $min_len) {
				return false;
			}
		}

		/* Even though this is a multibyte string we use strlen instead of mb_strlen
		   because we need byte length, not character length. */
		$length = strlen($str);
		$i = 0; $nbytes = 0;
		$oct = 0; $tail = 0;
		while ($i < $length) {
			$oct = ord($str[$i]);
			if ($oct < 128) { $nbytes = 1; } // Hex 00-7F: US-ASCII character
			elseif (($oct > 193) && ($oct < 224)) { $nbytes = 2; } // Hex C2-DF: Two byte sequence
			elseif (($oct > 223) && ($oct < 240)) { $nbytes = 3; } // Hex E0-EF: Three btye sequence
			elseif (($oct > 239) && ($oct < 245)) { $nbytes = 4; } // Hex F0-F4: Four byte sequence
			/* Four byte sequences starting with F5 or greater and five or six byte
			   sequences are not allowed since the Unicode range was capped at
			   U+10FFFF. C0 & C1 are not allowed since they would cause a "non-
			   shortest-form" two byte sequence. Byte-order-mark octets FE & FF
			   are also not allowed in UTF-8. */
			else { return false; }

			for ($j = 1; $j < $nbytes; $j++) {
				$tail = ord($str[$i + $j]);
				if ($j == 1) {
					switch ($oct) {
						case 224:
							// Hex E0: catch overlong form three byte sequence
							if (($tail < 160) || ($tail > 191)) { return false; }
							break;
						case 237:
							// Hex ED: catch surrogate code points, not allowed in UTF-8
							if (($tail < 128) || ($tail > 191)) { return false; }
							break;
						case 240:
							// Hex F0: catch overlong form four byte sequence
							if (($tail < 144) || ($tail > 191)) { return false; }
							break;
						case 244:
							// Hex F4: code points over U+10FFFF not valid
							if (($tail < 128) || ($tail > 143)) { return false; }
							break;
						default:
							// Normal "tail byte" range
							if (($tail < 128) || ($tail > 191)) { return false; }
					}
				} else {
					// Normal "tail byte" range
					if (($tail < 128) || ($tail > 191)) { return false; }
				}
			}
			$i = $i + $nbytes;
		}
		// We made it through the whole string without errors. Must be a valid string.
		return true;
	}

	/**
	 * Remove ASCII control characters and HTML tags from a string
	 *
	 * @param mixed $value Value to sanitize 
	 * @return string
	 */
	public static function sanitizeString($value)
	{
		$value = self::stripControlChars($value);
		$value = strip_tags($value);
		return $value;
	}

	/**
	 * Check for characters not allowed in filenames.
	 *
	 * In addition to not causing problems with the filesystem or shell, the
	 * filenames must be valid URL characters too.
	 *
	 * @param mixed $value Value to validate 
	 * @return bool
	 */
	public static function validateFilename($value)
	{
		$value = (string) $value;

		return preg_match('/^[a-zA-Z1-9\-_.+@]+$/', $value);
	}

	/**
	 * Remove characters not allowed in filenames.
	 *
	 * In addition to not causing problems with the filesystem or shell, the
	 * filenames must be valid URL characters too. Only -_.+%@ allowed.
	 *
	 * @param mixed $value Value to sanitize 
	 * @return string
	 */
	public static function sanitizeFilename($value)
	{
		$value = (string) $value;
		$value = self::stripControlChars($value);
		
		$special_chars = array('~', '`', '!', '#', '$', '^', '&', '*', '(', ')', '=', '{',
			'}', '[', ']', '\\', '|', ':', ';', '\'', '"', '<', '>', ',', '/', '?');
		$value = str_replace($special_chars, '', $value);
		$value = preg_replace('/\s+/', '-', $value);
		return trim($value, '-_.+');
	}

	/**
	 * Validate a username
	 *
	 * @param mixed $value Value to validate 
	 * @return bool
	 */
	public static function validateUsername($value)
	{
		// Not allowed `'"\/&<>
		$spl_chars = "~!@#$%*() -_+=[]{}|.?;:,^";
		$spl_chars = preg_quote($spl_chars, '/');

		return preg_match('/^[\w' . $spl_chars . ']{3,30}$/u', $value);
	}

	/**
	 * Validate a password
	 *
	 * @param string $value Value to validate
	 * @return bool
	 */
	public static function validatePassword($value)
	{
		return (strlen($value) > 5);
	}

	/**
	 * Check for only basic ASCII characters. A-Z, a-z, 0-9 and _.
	 * @param string $value
	 * @return bool
	 */
	public static function validateAscii($value)
	{
		return preg_match('/^[a-zA-Z0-9_]*$/', $value);
	}

	/**
	 * Strip control characters from a string.
	 * @param string $value
	 * @return string
	 */
	public static function stripControlChars($value)
	{
		return preg_replace('/[\x00-\x1F\x7F]/', '', $value);
	}

	/**
	 * Strip any "null byte" charaters from the string.
	 * @param string $value
	 * @return string
	 */
	public static function stripNulls($value)
	{
		return str_replace(chr(0), '', $value);
	}

	/**
	 * A generic check to see if the supplied value is in a list of allowed values
	 *
	 * @param mixed $value Value to validate
	 * @param array $list
	 * @return bool
	 */
	public static function validateInList($value, array $list)
	{
		return in_array($value, $list);
	}

	/**
	 * Get the error message for failing a validation check.
	 *
	 * @param string $testname Name of validation method failed.
	 * @return string Translated message.
	 */
	public static function getMessage($testname)
	{
		// Take 'validate' off front of string.
		$key = strtolower(substr($testname, 8));
		$key = $key . '_validation_failure';
		return t($key);
	}
}
