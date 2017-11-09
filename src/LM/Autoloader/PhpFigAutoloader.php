<?php

namespace LM\Autoloader;

/**
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 */
class PhpFigAutoloader
{
    // base directory for the namespace prefix
    private $base_dir;

    public function __construct(string $base_dir)
    {
        $this->base_dir = $base_dir;
    }
    /**
    * An example of a project-specific implementation.
    *
    * After registering this autoload function with SPL, the following line
    * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
    * from /path/to/project/src/Baz/Qux.php:
    *
    *      new \Foo\Bar\Baz\Qux;
    * 
    * @see http://www.php-fig.org/psr/psr-4/examples/
    *
    * @param string $class The fully-qualified class name.
    * @return void
    */
    public function autoLoadClass($class) {
        // project-specific namespace prefix
        $prefix = '';


        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }
        
        // get the relative class name
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $this->base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}