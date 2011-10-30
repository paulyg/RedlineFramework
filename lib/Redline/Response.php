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
 * Potential HTTP Response class interface. Based on discussion for ZF 2.0 at
 * http://framework.zend.com/wiki/display/ZFDEV2/Proposal+for+MVC+Interfaces
 * and my own work.
 *
 * @todo Merge in stuff I like from Tiramisu, Slim & Limonade
 * @package RedlineFramework
 */
class Response
{
    /**
     * List of HTTP reponse status codes and common reason phrases.
     * @var array
     */
    protected static $statusCodes = array(
        // Informational 1xx
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        // Success 2xx
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        // Redirection 3xx
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',
        // Client Error 4xx
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request Uri Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        // Server Error 5xx
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );

    /**
     * This status line for this response.
     * @var string
     */
    protected $status;

    /**
     * Other HTTP response headers to send.
     * @var array
     */
    protected $headers = array();

    /**
     * The reponse body.
     * @var string
     */
    protected $content = '';

	/* good way to set these quickly but not sure if I like it */
	public function __construct($content = '', $status = 200, $headers = null)
    {
        $this->content = $content;
        $this->status($status);
        if (is_array($headers)) {
            $this->setHeaders($headers);
        }
    }

	/**
     * Set the response Status.
     *
     * @param int $code The three digit reponse code.
     * @param string reason An optional alternate reason phrase.
     * @return void
     */
    public function status($code, $reason = null)
    {
		if (is_null($reason) && isset(self::$statusCodes[$code])) {
			$status = " $code " .  self::$statusCodes[$code];
		} else {
			$status = " $code";
		}

		// catches 'cgi' (PHP < 5.3), 'cgi-fcgi' (PHP >= 5.3), & 'fpm-fcgi'
		if (strpos(php_sapi_name(), 'cgi') !== false) {
			$this->status = 'Status:' . $code;
		} else {
			$this->status = 'HTTP/1.1' . $code;
		}
    }

    /**
     * Set the Content-Type of this reponse.
     *
     * @param string $type
     * @return void
     */
    public function contentType($type)
    {
        $this->headers['Content-Type'] = $type;
    }

    /**
     * Set an arbitrary header.
     *
     * @param string $name Header name.
     * @param string $value Header value.
     * @return void
     */
	public function setHeader($name, $value)
    {
        $name = $this->normalizeHeaderName($name);
        $this->headers[$name] = $value;
    }

    /**
     * Set multiple headers at once.
     *
     * @param array $headers Key => value header pairs.
     * @return void
     */
    public function setHeaders($headers)
    {
        foreach ($headers as $name => $val) {
            $this->setHeader($name, $val);
        }
    }

    /**
     * Retrieve a set header value. Returns empty string if header is not set.
     *
     * @param string name
     * @return string
     */
    public function getHeader($name)
    {
        $name = $this->normalizeHeaderName($name);
        return (isset($this->headers[$name])) ? $this->headers[$name] : '';
    }

    /**
     * Retreive all set headers.
     *
     * @return array
     */
	public function getHeaders()
    {
        return $this->headers;
    }
	

    public function setCookie()
    {
    }
 
	/* Content, not sure if we can/should do this */
	public function content($content)
    {
        $this->content .= $content;
    }

	public function getContent()
    {
        return $this->$content;
    }

	public function setContent($content)
    {
        $this->content = $content;
    }

	/**
     * Send HTTP headers to the client.
     *
     * @return void
     */
	public function sendHeaders()
    {
        if (headers_sent()) {
            throw new RuntimeException("Attempting to call Response::sendHeaders() but headers have already been sent.");
        }

        if (!empty($this->status) && $this->status != 200) {
            header($this->status);
        }
        foreach ($this->headers as $name => $value) {
            header($name . ": " . $value);
        }
    }

	public function sendContent()
    {
        echo $this->content;
    }

	/**
     * Helper for setting status and content-type to HTML.
     *
     * @param int $status Code.
     * @param string $content Body.
     * @return void
     */
	public function html($status = 200, $content = '')
    {
        $this->status($status);
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->content = $content;
    }

	/**
     * Helper for setting status and content-type to HTML.
     *
     * @param int $status Code.
     * @param string $content Body.
     * @param string $additionalType Something to go before "+xml" in the content-type.
     * @return void
     */
	public function xml($status = 200, $content = '', $additionalType = '')
    {
        $this->status($status);
        if (!is_null($additionalType)) {
            $type = "application/$additionalType+xml";
        } else {
            $type = "application/xml";
        }
        $this->setHeader('Content-Type', $type);
        $this->content = $content;
    }

	/**
     * Helper for setting status and content-type to JSON.
     *
     * @param int $status Code.
     * @param string $content Body.
     * @return void
     */
	public function json($status = 200, $content = '')
    {
        $this->status($status);
        $this->setHeader('Content-Type', 'application/json');
        $this->content = $content;
    }

	/**
     * Helper for setting status and content-type to plain text.
     *
     * @param int $status Code.
     * @param string $content Body.
     * @return void
     */
    public function text($status = 200, $content = '')
    {
        $this->status($status);
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->content = $content;
    }

    /* Date/Cache helpers */
    public function expire(\DateTime $time) {}

    public function lastModified(\DateTime $time) {}

    public function setPublic() {}

    public function setPrivate() {}

    public function cacheable() {}

    public function notCacheable() {}

    /**
     * CamelCases HTTP header names and removes any spaces and _, converting to -.
     *
     * @param string
     * @return string
     */
    protected function normalizeHeaderName($name)
    {
        return str_replace(' ', '-', ucwords(str_replace(array('-', '_'), ' ', $name)));
    }
}
