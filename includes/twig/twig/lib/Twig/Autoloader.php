<?php
@trigger_error('The Twig_Autoloader class is deprecated since version 1.21 and will be removed in 2.0. Use Composer instead.', E_USER_DEPRECATED);
class Twig_Autoloader
{
    public static function register($prepend = false)
    {
        @trigger_error('Using Twig_Autoloader is deprecated since version 1.21. Use Composer instead.', E_USER_DEPRECATED);
        if (PHP_VERSION_ID < 50300) {
            spl_autoload_register([__CLASS__, 'autoload']);
        } else {
            spl_autoload_register([__CLASS__, 'autoload'], true, $prepend);
        }
    }
    public static function autoload($class)
    {
        if (0 !== strpos($class, 'Twig')) {
            return;
        }
        if (is_file($file = dirname(__FILE__) . '/../' . str_replace(['_', "\0"], ['/', ''], $class) . '.php')) {
            require $file;
        }
    }
}
