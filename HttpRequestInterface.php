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
interface HttpRequestInterface
{
	/* accessors for various superglobals */
	public function query($name = null, $default = null);
	public function post($name = null, $default = null);
	public function cookie($name = null, $default = null);
	public function file($name = null);
	public function server($name = null, $default = null);
	public function env($name = null, $default = null);
	public function headers($name = null);
 
	/* URI decomposition */
	public function requestUri();
    public function scheme();
    public function host();
    public function path();
    public function queryString();
    public function baseUrl();
 
	/* script name, is this necessary */
	public function scriptName();
 
	/* method tests */
	public function isHead();
	public function isGet();
	public function isPost();
	public function isPut();
	public function isDelete();
	public function method();

	public function isSecure();
	public function isXhr();
    public function isMobile();

	/* These are all optional. */ 
	public function eTags();
	public function preferredLanguage();
	public function allLanguages();
	public function charsets();
	public function contentTypes();
}
