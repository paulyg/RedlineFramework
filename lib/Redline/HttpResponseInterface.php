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
interface HttpResponseInterface
{
	/* good way to set these quickly but not sure if I like it */
	public function __construct($content = '', $status = 200, $headers = null);

	/* action methods */
	public function sendHeaders();
	public function sendContent(); // should this be flush() or flushContent() or sendBody() ?

	/* content helpers */
	public function html($status = 0);
	public function xml($status = 0);
	public function json($status = 0);
    public function text($status = 0);
 
	/* headers */
	public function getHeader($name); // get one
    public function setHeader($name, $value); // set one
	public function getHeaders(); // get all
	public function setHeaders(array $headers); // replace all
 
	/* content, not sure if we can/should do this */
	public function content($content); // add/append
	public function getContent(); // retreive
	public function setContent($content); // reset/overwrite
}
