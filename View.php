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
 * Implements templating features.
 * @package RedlineFramework
 */
class View
{
	/**
	 * Holds a reference to the Redline\Application object to pass to Helpers.
	 * @var Redline\Application
	 */
	protected $app;

	/**
	 * Holds a reference to global Redline\Http\Request instance.
	 * @var Redline\Http\Request
	 */
	protected $request;

    /**
	 * Holds template variables to be included in templates/layouts.
	 * @var array
	 */
	protected $vars = array();

	/**
	 * Paths to view scripts (for a controller action).
	 * @var array
	 */
	protected $script_paths = array();

	/**
	 * Name of layout script (template).
	 * @param string
	 */
	protected $layout_file = '';

	/**
	 * Path to view layouts (templates).
	 * @param string
	 */
	protected $layout_path = '';

	/**
	 * Collection of helper objects.
	 * @var array
	 */
	protected $helpers = array();

	/**
	 * Beginning part of all URLs.
	 * @var string
	 */
	protected $base_url = '';

    /**
     * Beginning path part of all relative URLs.
     * @var string
     */
    protected $base_path = '';

	/**
	 * URL to location where theme related files (css, js, images) are stored.
	 * @var string
	 */
	protected $themeUrl = '';

	/**
	 * Collection of CSS files to include when rendering the page.
	 * @var array
	 */
	public $stylesheets = array();

	/**
	 * Collection of Javascript files to include in the head when rendering the page.
	 * @var array
	 */
	public $headJavascripts = array();

	/**
	 * Collection of Javascript files to include in the footer when rendering the page.
	 * @var array
	 */
	public $tailJavascripts = array();

	/**
	 * Collection of Javascript snippits to include in the footer when rendering the page.
	 * @var array
	 */
	public $inlineJavascriptCode = array();

	/**
	 * Collection of meta tags to include when rendering the page.
	 * @var array
	 */
	public $meta = array();

	/**
	 * Collection of link tags to include when rendering page.
	 * @var array
	 */
	public $links = array();

	/**
	 * Object Constructor.
	 * @param Redline_Request $request
	 * @param Redline_Application $app
	 * @return Redline_View
	 */
	public function __construct(Redline_Request $request, Redline_Application $app)
	{
		$this->request = $request;
		$this->app = $app;
	}

	/**
	 * Magic method for view helpers or decorators.
	 * @return string
	 * @throws BadMethodCallException
	 */
	public function __call($helper, $args = array())
	{
		if (!isset($this->helpers[$helper])) {
			$class = 'Tm_Helper_' . ucfirst($helper);

			if (class_exists($class, true)) {
				$object = new $class($this->app);
				$this->helpers[$helper] = $object;
			} else {
				throw new BadMethodCallException("View helper class '$class' does not exist!");
			}
		} else {
			$object = $this->helpers[$helper];
		}

		return call_user_func_array(array($object, $helper), $args);
	}

	/**
	 * Magic method for returning template variables.
	 * @param string $key Template variable name.
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->vars[$key];
	}

	/**
	 * Magic method for setting template variables.
	 * @param string $key Template variable name.
	 * @param mixed $val Template variable value.
	 * @return void
	 */
	public function __set($key, $val)
	{
		$this->vars[$key] = $val;
	}

	/**
	 * Magic method for determining if a template variable is set.
	 * @param string $key Template variable name.
	 * @return boolean
	 */
	public function __isset($key)
	{
		return isset($this->vars[$key]);
	}

	/**
	 * Magic method for unsetting a template variable.
	 * @param string $key Template variable name.
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->vars[$key]);
	}

	/**
	 * Set the path to folder with view scripts to be rendered.
	 * @param string $path
	 */
	public function setViewScriptPath($path)
	{
		$path = rtrim($path, '/\\');
		$this->viewScriptPath = $path . DS;
	}

	/**
	 * Assign multiple template variables at once.
	 *
	 * The parameter may be an associative array of 'variable_name' => 'variable_value'
	 * pairs or an object. If an object is passed all public variables will be assigned
	 * to the template.
	 *
	 * @param array|object $vars
	 * @return bool
	 * @throws InvalidArgumentException on incorrect parameter type or setting private variable.
	 */
	public function assign($vars)
	{
		if (is_array($vars)) {
			foreach ($vars as $key => $val) {
				if (is_string($key)) {
					$this->__set($key, $val);
				}
			}
			return true;
		} elseif (is_object($vars)) {
			foreach (get_object_vars($vars) as $key => $val) {
				$this->__set($key, $val);
			}
			return true;
		} else {
			$type = gettype($vars);
			throw new InvalidArgumentException("Only an array or object may be passed to View:assign(). You passed '$type'.");
		}
	}

	/**
	 * Add a css file to the css queue.
	 * @param string $filename
	 * @param string $media
	 * @return void
	 */
	public function addStylesheet($filename, $media = 'screen')
	{
		$css = '<link rel="stylesheet" type="text/css" media="' . $media . '" href="';
		$css .= $this->themeUrl . 'css/' . $filename . '" />';
		$this->stylesheets[] = $css;
	}

	/**
	 * Echo out all queued stylesheets.
	 * @return void
	 */
	public function stylesheets()
	{
		echo implode("\n", array_reverse($this->stylesheets));
	}

	/**
	 * Add a javascript file to the js queue.
	 * @param string $filename
	 * @param string $headortail
	 * $param string $min_suffix
	 * @return void
	 */
	public function addJavascript($file, $headortail = 'tail', $min_suffix = '')
	{
		if ($min_suffix && !DEBUG_MODE) {
			$file = substr($file, 0, -2) . $min_suffix . '.js';
		}
		$js = '<script type="text/javascript" src="';
		$js .= $this->themeUrl . 'js/' . $file . '"></script>';

		switch ($headortail) {
			case 'head':
				$this->headJavascripts[] = $js;
				break;
			case 'tail':
				$this->tailJavascripts[] = $js;
				break;
			default:
				throw new InvalidArgumentException('Invalid value for $headortail.');
		}
	}

	/**
	 * Add an inline javascript snippit.
	 * @param string $content
	 * @return void
	 */
	public function addJavascriptInline($content)
	{
		$this->inlineJavascriptCode .= "<script type=\"text/javascript\">\n//<![CDATA[\n{$content}\n//]]>\n</script>\n";
	}

	/**
	 * Echo queued javascript files.
	 * @param string $headortail
	 * @return void
	 */
	public function javascripts($headortail)
	{
		switch ($headortail) {
			case 'head':
				echo implode("\n", array_reverse($this->headJavascripts));
				break;
			case 'tail':
				echo implode("\n", array_reverse($this->tailJavascripts));
				break;
			default:
				throw new InvalidArgumentException('Invalid value for $headortail.');
		}
	}

	/**
	 * Echo queued inline javascript code.
	 * @return void
	 */
	public function inlineJavascript()
	{
		echo implode("\n", $this->inlineJavascriptCode);
	}

	/**
	 * Add a meta tag (non http-equiv) to the meta tag queue.
	 * @param string $name
	 * @param string $content
	 * @return void
	 */
	public function addMeta($name, $content)
	{
		$meta = '<meta name="' . $name . '" content="' . $content . '" />';
		$this->meta[] = $meta;
	}

	/**
	 * Make the meta tag for a delayed redirect.
	 * @param string $url
	 * @param int $delay Time delay in seconds
	 * @return void
	 */
	public function metaRefresh($url, $delay = 20)
	{
		$url = Tm_Filter::sanitizeUrl($url);
		$meta = '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
		$this->meta[] = $meta;
	}

	/**
	 * Echo out all queued meta tags.
	 * @return void
	 */
	public function metaTags()
	{
		echo implode("\n", $this->meta);
	}

	/**
	 * Add a link tag to be displayed in the head of the page.
	 * @param string $rel
	 * @param string $type
	 * @param string $href
	 * @param string $hreflang
	 * @param string $charset
	 * @param string $media
	 * @return void
	 */
	public function addLinkTag($rel, $type, $href, $hreflang = null, $charset = null, $media = null)
	{
		$href = Tm_Filter::sanitizeUrl($href);
		$link = '<link rel="' . $rel . '" type="' . $type . '" href="' . $href;
		if (!is_null($hreflang)) {
			$link .= '" hreflang="' . $hreflang;
		}
		if (!is_null($charset)) {
			$link .= '" charset="' . $charset;
		}
		if (!is_null($media)) {
			$link .= '" media="' . $media;
		}
		$link .= '" />';
		$this->links[] = $link;
	}

	/**
	 * Echo out all queued link tags.
	 * @return void
	 */
	public function linkTags()
	{
		echo implode("\n", $this->links);
	}

	/**
	 * Set the name of the layout script to render.
	 * @param string $file Layout script file name.
	 * @return void
	 */
	public function setLayout($file)
	{
		$this->layoutFile = $file;
	}

	public function urlFor($route, $args)
	{
	}

	public function linkFor($route, $args, $relative = true)
	{
	}

	public function pathFor($route, $args)
	{
	}

	public function img($filename)
	{
	}

	/**
	 * Renders a view script/template file.
	 *
	 * @param string $script The name of the view script to render.
	 * @param string $varName Name of the placeholder var to store the output in.
	 * @return void
	 * @throws LogicException if the view script does not exist.
	 */
	public function render($__file__ = null, $__vars__ = array(), $__into__ = '')
	{
		if (substr($__file__, -4) != '.php') {
			$__file__ = $file . '.php';
		}
		$__file__ = $this->findViewScript($__file__);
		
		if (!file_exists($__file__)) {
			throw new LogicException("The view script '$__file__' does not exist.");
		}

        if (empty($__vars__)) {
            $__vars__ = $this->vars;
        }

		ob_start();
		extract($__vars__, EXTR_SKIP);
		include $__file__;
        if (!empty($__into__)) {
            $this->vars[$__into__] = ob_get_clean();
            return;
        }
        return ob_get_clean();
	}

	/**
	 * Render a view layout, sending output to the user.
	 * @return void
	 */
	public function renderLayout()
	{
		$file = $this->layoutFile;

		if (substr($file, -4) != '.php') {
			$file = $file . '.php';
		}
		$file = $this->layoutPath . $file;

		if (!file_exists($file)) {
			throw new LogicException("The layout script '$file' does not exist.");
		}

		$this->setCommon();
		extract($this->vars, EXTR_OVERWRITE);
		include $file;
	}
}
