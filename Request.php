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
namespace Redline;

/**
 * Potential HTTP Request class interface. Based on discussion for ZF 2.0 at
 * http://framework.zend.com/wiki/display/ZFDEV2/Proposal+for+MVC+Interfaces
 * and my own work.
 *
 * @todo Merge in stuff I like from Tiramisu, Slim & Limonade
 * @package RedlineFramework
 */
class Request
{
    protected $host;

    protected $path;

    protected $script_name;

    protected $request_uri;

    protected $is_mobile;

    public function __construct()
    {
		if (isset($_SERVER['PHP_SELF'])) {
			$base_url = $_SERVER['PHP_SELF'];
		} elseif (isset($_SERVER['SCRIPT_NAME'])) {
			$base_url = $_SERVER['SCRIPT_NAME'];
		} else {
			throw new RuntimeException("Neither of the SERVER vars PHP_SELF or SCRIPT_NAME are set. Please check your webserver configuration.");
		}

		$this->script_name = basename($base_url);
		// Ensure one trailing slash.
		$this->path = rtrim(dirname($base_url), '/') . '/';
	}

	/* Accessors for various Superglobals. */
	public function query($name, $default = '')
    {
        return (isset($_GET[$name]) ? $_GET[$name] : $default);
    }

	public function post($name, $default = '')
    {
        return (isset($_POST[$name]) ? $_POST[$name] : $default);
    }

    public function request($name, $default = '')
    {
        return (isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $default));
    }

	public function cookie($name, $default = '')
    {
        return (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
    }

	public function files($name)
    {
        return (isset($_FILES[$name]) ? $_FILES[$name] : array());
    }

	public function server($name, $default = '') {}
 
	/* URI decomposition */
	public function requestUri()
    {
        if (empty($this->request_uri)) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS 6 Isapi-Rewrite
                $request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
		    } elseif (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) { // IIS 7 Mod-Rewrite
		    	$request_uri = $_SERVER['HTTP_X_ORIGINAL_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) { // Apache, Lighttpd, etc...
                $request_uri = $_SERVER['REQUEST_URI'];
                if (isset($_SERVER['HTTP_HOST']) && strstr($request_uri, $_SERVER['HTTP_HOST'])) {
                    $request_uri = preg_replace('#^[^:]*://[^/]*/#', '/', $request_uri);
                }
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
                $request_uri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $request_uri .= '?' . $_SERVER['QUERY_STRING'];
                }
            }

            $this->request_uri = $request_uri;
        }
        return $this->request_uri;
    }

    public function scheme()
    {
        return ($this->isSecure()) ? 'https://' : 'http://';
    }

    public function host()
    {
        if (empty($this->host)) {
            if (isset($_SERVER['HTTP_HOST'])) {
		    	$this->host = $_SERVER['HTTP_HOST'];
		    } elseif (isset($_SERVER['SERVER_NAME'])) {
		    	$this->host = $_SERVER['SERVER_NAME'];
		    } else {
		    	$this->host = '';
		    }
        }
        return $this->host;
    }

    public function path($with_script_name = false)
    {
        return $this->path . ($with_script_name) ? $this->script_name : '';
    }

	public function scriptName()
    {
        return $this->script_name;
    }

    public function queryString() {}

    public function baseUrl($with_script_name = false)
    {
        return $this->scheme() . $this->host() . $this->path($with_script_name);
    }
 
	/* HTTP Method Tests */
	public function isGet()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'GET');
    }

	public function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'POST');
    }

	public function isPut()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'PUT');
    }

	public function isDelete()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'DELETE');
    }

	public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

	public function isSecure() {
    {
        if (isset($_SERVER['HTTPS']) &&
			($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on')) {
			return true;
		}
		return false;
    }

	public function isXhr()
    {
        return (isset($_SERVER['X_REQUESTED_WITH']) && ($_SERVER['X_REQUESTED_WITH'] == 'XMLHttpRequest'));
    }

    public function isMobile()
    {
        if (empty($this->is_mobile)) {
            $this->is_mobile = false;

		    $platforms = array(
		    	'android'    => 'android',
		    	'blackberry' => 'blackberry',
		    	'iphone'     => '(iphone|ipod)',
		    	'opera'      => 'opera mini',
		    	'palm'       => '(avantgo|blazer|palm)',
		    	'webos'      => 'webos',
		    	'windows'    => 'windows ce; (iemobile|ppc|smartphone)',
		    	'generic'    => '(nokia|symbian|midp|pda|plucker|netfront|treo|up.browser|up.link|wap)'
		    );

		    $user_agent = $_SERVER['HTTP_USER_AGENT'];
		    $accept = $_SERVER['HTTP_ACCEPT'];

		    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
			    $this->is_mobile = true;
		    } elseif (strpos($accept,'text/vnd.wap.wml') > 0 || strpos($accept,'application/vnd.wap.xhtml+xml') > 0) {
		    	$this->is_mobile = true;
		    } else {
			    foreach ($platforms as $platform => $regexp) {
				    if (preg_match("/$regexp/i", $user_agent)) {
                        $this->is_mobile = $platform;
			        }
			    }
		    }
        }
        return $this->is_mobile;
    }

	/* These are all optional. */ 
	public function eTags() {}
	public function preferredLanguage() {}
	public function allLanguages() {}
	public function charsets() {}
	public function contentTypes() {}
}
