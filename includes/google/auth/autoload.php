<?php
function oauth2client_php_autoload($className)
{
    $classPath = explode('_', $className);
    if ($classPath[0] != 'Google') {
        return;
    }
    if (count($classPath) > 3) {
        $classPath = array_slice($classPath, 0, 3);
    }
    $filePath = dirname(__FILE__) . '/src/' . implode('/', $classPath) . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}
spl_autoload_register('oauth2client_php_autoload');
