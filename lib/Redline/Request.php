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
 * Object oriented access to the HTTP Request information normally exposed via PHP superglobals.
 *
 * The functionality in this class is heavily influenced by the respective classes in:
 * Slim Framework, Zend Framework 1, Symfony 2
 */
class Request
{
    /**
     * Parameters present in the URL query string (regardless of HTTP method).
     * @var array
     */
    public $query = array();
    
    /**
     * Form data submitted in a POST or PUT request.
     * @var array
     */
    public $data = array();

    /**
     * HTTP cookies sent in with the current request.
     * @var array
     */
    public $cookies = array();
    
    /**
     * The HTTP method of this request.
     * @var string
     */
    protected $method;
    
    /**
     * HTTP headers sent in with request.
     * @var array
     */
    protected $headers;
    
    /**
     * The server host name.
     * @var string
     */
    protected $hostName;

    /**
     * The path from the webroot to the PHP file being invoked.
     * @var string
     */
    protected $basePath;

    /**
     * The script name invoked by this request.
     * @var string
     */
    protected $scriptName;

    /**
     * Path portion of the URL minus the base path (the part you want to match against routing).
     * @var string
     */
    protected $requestPath;

    /**
     * The raw query string, if present.
     * @var string
     */
    protected $queryString;

    /**
     * The request body's Content-Type, if data was sent with the request.
     * @var string
     */
    protected $contentType;

    /**
     * Is this request an XMLHttpReqest?
     * @var boolean
     */
    protected $isXhr = false;

    /**
     * Is this request under HTTPS protocol?
     * @var boolean
     */
    protected $isSecure = false;

    /**
     * Is the User-Agent a mobile browser?
     * @var boolean
     */
    protected $isMobile;

    /**
     * Headers which don't get the 'HTTP_' prefix in PHP's $_SERVER superglobal.
     * @var array
     */
    protected static $additionalHeaders = array('content-type', 'content-length', 'auth-type', 'php-auth-user', 'php-auth-pw', 'x-requested-with', 'x-http-method-override', 'x-wap-profile');

    /**
     * Object constructor.
     *
     * @param array $get Array representing URL query string parameters.
     * @param array $post Array representing submitted form data.
     * @param array $cookies Array representing HTTP cookies.
     * @param array $server Array representing HTTP headers and other data in PHP's $_SERVER.
     */
    public function __construct(&$get, &$post, &$cookies, &$server)
    {
        $this->query = $get;
        $this->data = $post;
        $this->cookies = $cookies;
        
        $this->method = $server['REQUEST_METHOD'];
         
        $this->setHeaders($server);
        $this->setUrlData($server);
        $this->checkForMethodOverride();
        $this->loadPutParams();
    }
    
    /**
     * Create a new object instance using PHP's superglobals as a base.
     *
     * @return Redline\Request
     */
    public static function createFromGlobals()
    {
        return new static($_GET, $_POST, $_COOKIES, $_SERVER);
    }

    /**
     * Access one of or all the query string parameters.
     *
     * Do not pass a key name to retreive all the query parameters.
     *
     * @param string $name Query param key name.
     * @param string $default A default value if the given name is not present.
     * @return string
     */
    public function query($name = null, $default = null)
    {
        return $this->valueOrAll('query', $name, $default);
    }

    /**
     * Merge in parameters pulled from routed URL into query parameters.
     *
     * @param array
     */
    public function mergeQueryParams(array $params)
    {
        $this->query = array_replace($this->query, $params);
    }

    /**
     * Access one of or all the request body parameters.
     *
     * Do not pass a key name to retreive all the body parameters.
     *
     * @param string $name Param key name.
     * @param string $default A default value if the given name is not present.
     * @return string
     */
    public function data($name = null, $default = null)
    {
        return $this->valueOrAll('data', $name, $default);
    }

    /**
     * Access one of or all the query string and request body parameters.
     *
     * The data array (PHP $_POST) is searched first, then query (PHP $_GET).
     *
     * @param string $name Param key name.
     * @param string $default A default value if the given name is not present.
     * @return string
     */
    public function params($name = null, $default = null)
    {
        return (isset($this->data[$name]) ? $this->data[$name] : (isset($this->query[$name]) ? $this->query[$name] : $default));
    }

    /**
     * Access one of or all the HTTP cookies.
     *
     * Do not pass a cookie name to retrieve all the cookies.
     *
     * @param string $name Cookie name.
     * @param string $default A default value if the given name is not present.
     * @return string
     */
    public function cookies($name = null, $default = null)
    {
        return $this->valueOrAll('cookies', $name, $default);
    }

    public function files($name)
    {
        return (isset($_FILES[$name]) ? $_FILES[$name] : array());
    }
   
    /**
     * Access one of or all the HTTP headers.
     *
     * Do not pass a header name to retrieve all headers.
     *
     * @param string $name Header name. Case insensitive.
     * @param string $default A default value if the given name is not present.
     * @return string
     */
    public function headers($name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->headers;
        }
        return $this->valueOrAll('headers', $this->normalizeHeaderName($name), $default);
    }
    
    /**
     * Access the raw request body.
     *
     * @return string
     */
    public function getBody() {
        return file_get_contents('php://input');
    }
 
    /**
     * The scheme, HTTP or HTTPS.
     *
     * @return string
     */
    public function scheme()
    {
        return ($this->isSecure()) ? 'https://' : 'http://';
    }

    /**
     * The server name.
     *
     * @return string
     */
    public function hostName()
    {
        return $this->hostName;
    }

    /**
     * The path from the web root to the application root.
     *
     * @return string
     */
    public function basePath($with_script_name = false)
    {
        return $this->basePath . ($with_script_name) ? $this->scriptName : '';
    }

    /**
     * The name of the script serving as the entry point.
     *
     * @return string
     */
    public function scriptName()
    {
        return $this->scriptName;
    }

    /**
     * The path portion of the URL after the base path.
     *
     * This is what you want to match for routing.
     * 
     * @return string
     */
    public function rewrittenPath()
    {
        return $this->requestPath;
    }

    /**
     * Access the raw query string.
     *
     * @return string
     */
    public function queryString()
    {
        return $this->queryString;
    }

    /**
     * The full URL (scheme, host name) and base path.
     *
     * @return string
     */
    public function baseUrl($with_script_name = false)
    {
        return $this->scheme() . $this->hostName() . $this->basePath($with_script_name);
    }
 
    /**
     * Is the request a GET request?
     *
     * @return boolean
     */
    public function isGet()
    {
        return ($this->method == 'GET');
    }

    /**
     * Is the request a POST request?
     *
     * @return boolean
     */
    public function isPost()
    {
        return ($this->method == 'POST');
    }

    /**
     * Is the request a PUT request?
     *
     * @return boolean
     */
    public function isPut()
    {
        return ($this->method == 'PUT');
    }

    /**
     * Is the request a DELETE request?
     *
     * @return boolean
     */
    public function isDelete()
    {
        return ($this->method == 'DELETE');
    }

    /**
     * Return the HTTP method used.
     *
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Is the request under HTTPS?
     *
     * @return boolean
     */
    public function isSecure() {
    {
        return $this->isSecure;
    }

    /**
     * Is the request an XMLHttpRequest
     *
     * @return boolean
     */
    public function isXhr()
    {
        return $this->isXhr;
    }

    /**
     * Is the request being made from a mobile client browser?
     *
     * @return boolean
     */
    public function isMobile()
    {
        if (empty($this->isMobile)) {
            $this->isMobile = false;

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

            $user_agent = $this->header('user-agent');
            $accept = $this->header('accept');

            if (!is_null($this->header('x-wap-profile')) {
                $this->isMobile = true;
            } elseif (strpos($accept,'text/vnd.wap.wml') > 0 || strpos($accept,'application/vnd.wap.xhtml+xml') > 0) {
                $this->isMobile = true;
            } else {
                foreach ($platforms as $platform => $regexp) {
                    if (preg_match("/$regexp/i", $user_agent)) {
                        $this->isMobile = $platform;
                    }
                }
            }
        }
        return $this->isMobile;
    }

    /**
     * Get HTTP request content type
     * @return string
     */
    public function contentType() {
        if (empty($this->contentType)) {
            $content_type = 'application/x-www-form-urlencoded';
            $header = $this->headers('content-type');
            if (!is_null($header)) {
                $header_parts = preg_split('/\s*;\s*/', $header);
                $content_type = $header_parts[0];
            }
            $this->contentType = $content_type;
        }
        return $this->contentType;
    }

    /* These are all optional. */ 
    public function acceptTypes() {}
    public function acceptCharsets() {}
    public function acceptLanguages() {}

    /**
     * Return all values if key is null, value if key exists, or a default value.
     *
     * @param string $store Name of array property of this object.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function valueOrAll($store, $key, $default)
    {
        if (is_null($key)) {
            return $this->$store;
        }
        if (isset($this->$store[$key])) {
            return $this->$store[$key];
        }
        return $default;
    }
    
    /**
     * Loads HTTP headers from an array represnting PHP's $_SERVER superglobal.
     *
     * @param array $server
     * @return void
     */
    protected function setHeaders(&$server)
    {
        foreach ($server as $key => $value) {
            $key = $this->normalizeHeaderName($key);
            if (strpos($key, 'http-') === 0 || in_array($key, self::$additionalHeaders)) {
                $name = substr($key, 5);
                $this->headers[$name] = $value;
            }
        }
        $this->isXhr = (isset($this->headers['x-requested-with']) && ($this->headers['x-requested-with'] == 'XMLHttpRequest'));
        $this->isSecure = ((isset($server['HTTPS']) && ($server['HTTPS'] == 1 || strtolower($server['HTTPS']) == 'on'));
        }
    }

    /**
     * Sets values for all of the URL related properties.
     *
     * @param array $server An array like PHP's $_SERVER superglobal.
     * @return void
     */
    protected function setUrlData(&$server)
    {
        if (isset($server['SERVER_NAME'])) {
            $this->hostName = $server['SERVER_NAME'];
        } elseif (isset($server['HTTP_HOST'])) {
            $this->hostName = $server['HTTP_HOST'];
        } else {
            $this->hostName = '';
        }

        if (isset($server['SCRIPT_NAME'])) {
            $script_path = $server['SCRIPT_NAME'];
        } elseif (isset($server['PHP_SELF'])) {
            $script_path = $server['PHP_SELF'];
        } else {
            throw new RuntimeException("Neither of the SERVER vars PHP_SELF or SCRIPT_NAME are set. Please check your webserver configuration.");
        }

        $this->scriptName = basename($script_path);
        // Ensure one trailing slash.
        $this->basePath = rtrim(dirname($script_path), '/') . '/';

        if (isset($server['HTTP_X_REWRITE_URL'])) { // IIS 6 Isapi-Rewrite
            $request_uri = $server['HTTP_X_REWRITE_URL'];
        } elseif (isset($server['HTTP_X_ORIGINAL_URL'])) { // IIS 7 Mod-Rewrite
            $request_uri = $server['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($server['REQUEST_URI'])) { // Apache, Lighttpd, etc...
            $request_uri = $server['REQUEST_URI'];
            if (!empty($this->hostName && strstr($request_uri, $this->hostName)) {
                $request_uri = preg_replace('#^[^:]*://[^/]*/#', '/', $request_uri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $request_uri = $_SERVER['ORIG_PATH_INFO'];
        }

        $query_string = '';
        if (strpos($request_uri, '?')) {
            $parts = explode('?', $request_uri);
            $request_uri = $parts[0];
            $query_string = $parts[1];
        }

        if (empty($server['QUERY_STRING']) && !empty($query_string)) {
            $this->queryString = $query_string;
        } else {
            $this->queryString = $server['QUERY_STRING'];
        }

        $rewritten_path = substr($request_uri, strlen($this->basePath));
        $this->rewrittenPath = '/' . ltrim($rewritten_path, '/');
    }

    /**
     * Lowercase HTTP header names and replace _ with -.
     *
     * @param string $name
     * @return string
     */
    protected function normalizeHeaderName($name)
    {
        return str_replace('_', '-', strtolower($name));
    }

    /**
     * Check for common methods of simulating a PUT request over POST.
     *
     * @return void
     */
    protected function checkForMethodOverride()
    {
        if ($this->method != 'POST') {
            return;
        }
        if ($method = $this->header('x-http-method-override')) {
            $this->method = strtoupper($method);
            return;
        }
        if (isset($this->data['_METHOD'])) {
            $this->method = strtoupper($this->data['_METHOD']);
        }
    }

    /**
     * Loads request data for put requests.
     *
     * @return void
     */
    protected function loadPutParams()
    {
        if ($this->method == 'PUT' && empty($this->data)) {
            $this->data = array();
            parse_str($this->getBody(), $this->post);
        }
    }
}
