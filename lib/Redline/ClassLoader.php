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
 * Provides a PSR-0 compliant class autoloader supporting traditional PEAR/Zend style naming
 * and namespaces. Each "root/vendor" namespace can have it's own path prefix, as well as a
 * prefix path for all non-namepsaced code.
 *
 * Usage examples:
 * <code>
 * $loader = new Redline\ClassLoader();
 * $loader->add('Redline', '/path/to/project/system/');
 * $loader->add('MyApp', '/path/to/project/app');
 * $loader->add('Zend', '/path/to/project/plugins/Zend');
 * $loader->register();
 * </code>
 *
 * @see http://groups.google.com/group/php-standards/web/psr-0-final-proposal?pli=1
 * @package RedlineFramework
 */
class ClassLoader
{
	/**
	 * Map of namespaces to directory paths
     * @var array
	 */
	private $namespaces = array();

    /**
     * Map of class name prefixes to directory paths
     * @var array
     */
    private $prefixes = array();

    /**
     * Base path from which to try and find class files where there is no namespace or
     * prefix match. Use as a fallback mechanism.
     * @var string
     */
    private $fallbackPath = '';

    /**
     * Attempt to load classes from include_path as a last resort?
     * @var bool
     */
    private $useIncludePath = false;

	/**
	 * Add a namespace/directory path pair.
	 *
     * Given:
	 * <code>
     * $loader->addNamespace('Zend', '/opt/lib/php/Zend');
     * </code>
	 * This class will try to load the class <tt>Zend\Ldap\Ldap</tt> from the file
     * <tt>/opt/lib/php/Zend/Ldap/Ldap.php</tt>
	 *
	 * @param string $namespace
	 * @param string $path
	 * @return ClassLoader
	 */
	public function addNamespace($namespace, $path)
	{
        $namespace = rtrim($namespace, '\\') . '\\';
        $this->namespaces[$namespace] = $path;
        return $this;
	}

    /**
     * Add a class prefix/directory path pair.
     *
     * Given:
     * <code>
     * $loader->addPrefix('Cache', '/opt/lib/php/Pear/Cache');
     * </code>
     * This class will try to load the class <tt>Cache_Lite</tt> from the file
     * <tt>/opt/lib/php/Pear/Cache/Lite.php</tt>
     *
     * @param string $prefix
     * @param string $path
     * @return ClassLoader
     */
    public function addPrefix($prefix, $path)
    {
        $prefix = rtrim($prefix, '_') . '_';
        $this->prefixes[$prefix] = $path;
        return $this;
    }

    /**
     * Add a fallback base path to attempt to autoload from is no namespace or prefix
     * match is found.
     *
     * Given:
     * <code>
     * $loader->setBasePath('/opt/lib/php');
     * </code>
     * This class will try to load the class <tt>Symfony\Components\Yaml\Parser</tt> from the
     * file <tt>/opt/lib/php/Symfony/Components/Yaml/Parser.php</tt>
     *
     * @param string $path
     * @return ClassLoader
     */
    public function setFallbackPath($path)
    {
        $this->fallbackPath = $path;
        return $this;
    }

    /**
     * Register this instance with the SPL autoloader stack.
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Unregister this instance from the SPL autoloader stack.
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

	public function loadClass($class_name)
	{
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }

        $pos = strrpos($class, '\\');

        if ($pos !== false) {
            $namespace = substr($class, 0, $pos);
            $className = substr($class, $pos + 1);
            $normalizedClass = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) .
                               DIRECTORY_SEPARATOR .
                               str_replace('_', DIRECTORY_SEPARATOR, $className) .
                               '.php';

            foreach ($this->namespaces as $ns => $dir) {
                if (strpos($namepsace, $ns) === 0) {
                    $file = $dir . DIRECTORY_SEPARATOR . $normalizedClass;
                    if (is_file($file)) {
                        require $file;
                        return true;
                    }
                }
            }
        } else {
            $normalizedClass = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

            foreach ($this->prefixes as $prefix) {
                if (strpos($class, $prefix) === 0) {
                    $file = $dir . DIRECTORY_SEPARATOR . $normalizedClass;
                    if (is_file($file)) {
                        require $file;
                        return true;
                    }
                }
            }
        }
        if (!empty($this->fallbackPath) {
            $file = $this->fallbackPath . DIRECTORY_SEPARATOR . $normalizedClass;
            if (is_file($file)) {
                require $file;
                return true;
            }
        }

        if ($this->useIncludePath && $file = stream_resolve_include_path($normalizedClass)) {
            require $file;
            return true;
        }

        return false;
	}
}
