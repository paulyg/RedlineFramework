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
    protected static $statusCodes = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',

        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',

        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',

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

        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );

    protected $status;

    protected $contentType;

    protected $headers;

    protected $content = '';

	/* good way to set these quickly but not sure if I like it */
	public function __construct($content = '', $status = 200, $headers = null);

	/* Headers */
    public function status($code)
    {
		if (isset(self::$statusCodes[$code])) {
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

	public function setHeader()
    {
    }

    public function getHeader()
    {
    }

	public function getHeaders() {} // get all
	public function setHeaders() {} // replace all

    public function setCookie()
    {
    }
 
	/* Content, not sure if we can/should do this */
	public function content(); // add/append
	public function getContent(); // retreive
	public function setContent(); // reset/overwrite

	/* Action methods */
	public function sendHeaders();
	public function sendContent(); // should this be flush() or flushContent() or sendBody() ?

	/* Content helpers */
	public function html($status = 200, $content = '')
    {
    }

	public function xml($status = 200, $content = '')
    {
    }

	public function json($status = 200, $content = '')
    {
    }

    public function text($status = 200, $content = '')
    {
    }
}
