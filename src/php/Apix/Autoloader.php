<?php
namespace Apix;

class Autoloader
{

    public $early_paths = array();

    public $late_paths = array();

    /**
     * Implodes an array of paths.
     *
     * @param  array  $paths
     * @return string Returns a string.
     */
    public static function implode(array $paths)
    {
        return implode(PATH_SEPARATOR, $paths);
    }

    /**
     * Registers a new autoloader.
     *
     * @param boolean $prepend Wether to prepend the autoloader on the autoload
     *                          stack instead of appending it.
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function register($prepend=false)
    {
        $str_path = '';

        if (!empty($this->early_paths)) {
            $str_path .= self::implode($this->early_paths) . PATH_SEPARATOR;
        }

        $str_path .= get_include_path();

        if (!empty($this->late_paths)) {
            $str_path .= PATH_SEPARATOR . self::implode($this->late_paths);
        }

        set_include_path($str_path);

        return spl_autoload_register(array('static', 'load'), $prepend);
    }

    /**
     * Loads a class.
     *
     * @param  string  $class
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public static function load($class)
    {
        $class = self::normalise($class);

        $paths = explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $class;

            if ( is_file($file) ) {
                include $file;

                return true;
            }
        }

        return false;
    }

    /**
     * Normalises a class name.
     *
     * @param  string $class
     * @param  string $ext   The file extension for the file.
     * @return string
     */
    public static function normalise($class, $ext='.php')
    {
        $file = '';
        $last = strripos($class, '\\');

        if ($last != false) {
            $ns = substr($class, 0, $last);
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $ns)
                    . DIRECTORY_SEPARATOR;
            $class = substr($class, $last+1);
        }

        return $file . str_replace('_', DIRECTORY_SEPARATOR, $class) . $ext;
    }

    /**
     * Prepends a new path
     *
     * @param  string $path
     * @return self   Returns a fluent interface
     */
    public function prepend($path)
    {
        $this->early_paths[] = $path;

        return $this;
    }

    /**
     * Appends a new path
     *
     * @param  string $path
     * @return self   Returns a fluent interface
     */
    public function append($path)
    {
        $this->late_paths[] = $path;

        return $this;
    }

    /**
     * Initialise a new autoloader (static shortcut).
     *
     * @param array   $early   The paths to prepend to the include path.
     * @param array   $late    The paths to append to the include path.
     * @param boolean $prepend Wether to prepend the autoloader on the autoload
     *                         stack instead of appending it.
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public static function init(array $early=null, array $late=null, $prepend=false)
    {
        $loader = new self;
        if (null != $early) {
            $loader->early_paths = $early;
        }
        if (null != $late) {
            $loader->late_paths = $late;
        }

        return $loader->register($prepend);
    }

}
