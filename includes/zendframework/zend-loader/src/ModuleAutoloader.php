<?php
namespace Zend\Loader;
require_once __DIR__ . '/SplAutoloader.php';
use GlobIterator;
use Phar;
use PharFileInfo;
use SplFileInfo;
use Traversable;
class ModuleAutoloader implements SplAutoloader
{
    protected $paths = [];
    protected $explicitPaths = [];
    protected $namespacedPaths = [];
    protected $pharBasePath = '';
    protected $pharExtensions = [];
    protected $moduleClassMap = [];
    public function __construct($options = null)
    {
        if (extension_loaded('phar')) {
            $this->pharBasePath = Phar::running(true);
            $this->pharExtensions = [
                'phar',
                'phar.tar',
                'tar',
            ];
            if (extension_loaded('zlib')) {
                $this->pharExtensions[] = 'phar.gz';
                $this->pharExtensions[] = 'phar.tar.gz';
                $this->pharExtensions[] = 'tar.gz';
                $this->pharExtensions[] = 'phar.zip';
                $this->pharExtensions[] = 'zip';
            }
            if (extension_loaded('bzip2')) {
                $this->pharExtensions[] = 'phar.bz2';
                $this->pharExtensions[] = 'phar.tar.bz2';
                $this->pharExtensions[] = 'tar.bz2';
            }
        }
        if (null !== $options) {
            $this->setOptions($options);
        }
    }
    public function setOptions($options)
    {
        $this->registerPaths($options);
        return $this;
    }
    public function getModuleClassMap()
    {
        return $this->moduleClassMap;
    }
    public function setModuleClassMap(array $classmap)
    {
        $this->moduleClassMap = $classmap;
        return $this;
    }
    public function autoload($class)
    {
        if (substr($class, -7) !== '\Module') {
            return false;
        }
        if (isset($this->moduleClassMap[$class])) {
            require_once $this->moduleClassMap[$class];
            return $class;
        }
        $moduleName = substr($class, 0, -7);
        if (isset($this->explicitPaths[$moduleName])) {
            $classLoaded = $this->loadModuleFromDir($this->explicitPaths[$moduleName], $class);
            if ($classLoaded) {
                return $classLoaded;
            }
            $classLoaded = $this->loadModuleFromPhar($this->explicitPaths[$moduleName], $class);
            if ($classLoaded) {
                return $classLoaded;
            }
        }
        if (count($this->namespacedPaths) >= 1) {
            foreach ($this->namespacedPaths as $namespace => $path) {
                if (false === strpos($moduleName, $namespace)) {
                    continue;
                }
                $moduleNameBuffer = str_replace($namespace . '\\', '', $moduleName);
                $path .= DIRECTORY_SEPARATOR . $moduleNameBuffer . DIRECTORY_SEPARATOR;
                $classLoaded = $this->loadModuleFromDir($path, $class);
                if ($classLoaded) {
                    return $classLoaded;
                }
                $classLoaded = $this->loadModuleFromPhar($path, $class);
                if ($classLoaded) {
                    return $classLoaded;
                }
            }
        }
        $moduleClassPath   = str_replace('\\', DIRECTORY_SEPARATOR, $moduleName);
        $pharSuffixPattern = null;
        if ($this->pharExtensions) {
            $pharSuffixPattern = '(' . implode('|', array_map('preg_quote', $this->pharExtensions)) . ')';
        }
        foreach ($this->paths as $path) {
            $path = $path . $moduleClassPath;
            if ($path == '.' || substr($path, 0, 2) == './' || substr($path, 0, 2) == '.\\') {
                if (! $basePath = $this->pharBasePath) {
                    $basePath = realpath('.');
                }
                if (false === $basePath) {
                    $basePath = getcwd();
                }
                $path = rtrim($basePath, '\/\\') . substr($path, 1);
            }
            $classLoaded = $this->loadModuleFromDir($path, $class);
            if ($classLoaded) {
                return $classLoaded;
            }
            if ($pharSuffixPattern) {
                foreach (new GlobIterator($path . '.*') as $entry) {
                    if ($entry->isDir()) {
                        continue;
                    }
                    if (! preg_match('#.+\.' . $pharSuffixPattern . '$#', $entry->getPathname())) {
                        continue;
                    }
                    $classLoaded = $this->loadModuleFromPhar($entry->getPathname(), $class);
                    if ($classLoaded) {
                        return $classLoaded;
                    }
                }
            }
        }
        return false;
    }
    protected function loadModuleFromDir($dirPath, $class)
    {
        $modulePath = $dirPath . '/Module.php';
        if (substr($modulePath, 0, 7) === 'phar://') {
            $file = new PharFileInfo($modulePath);
        } else {
            $file = new SplFileInfo($modulePath);
        }
        if (($file->isReadable() && $file->isFile())) {
            $absModulePath = $this->pharBasePath ? (string) $file : $file->getRealPath();
            require_once $absModulePath;
            if (class_exists($class)) {
                $this->moduleClassMap[$class] = $absModulePath;
                return $class;
            }
        }
        return false;
    }
    protected function loadModuleFromPhar($pharPath, $class)
    {
        $pharPath = static::normalizePath($pharPath, false);
        $file = new SplFileInfo($pharPath);
        if (! $file->isReadable() || ! $file->isFile()) {
            return false;
        }
        $fileRealPath = $file->getRealPath();
        if (strpos($fileRealPath, '.phar') !== false) {
            require_once $fileRealPath;
            if (class_exists($class)) {
                $this->moduleClassMap[$class] = $fileRealPath;
                return $class;
            }
        }
        $moduleClassFile = 'phar://' . $fileRealPath . '/Module.php';
        $moduleFile = new SplFileInfo($moduleClassFile);
        if ($moduleFile->isReadable() && $moduleFile->isFile()) {
            require_once $moduleClassFile;
            if (class_exists($class)) {
                $this->moduleClassMap[$class] = $moduleClassFile;
                return $class;
            }
        }
        $pharBaseName = $this->pharFileToModuleName($fileRealPath);
        $moduleClassFile = 'phar://' . $fileRealPath . '/' . $pharBaseName . '/Module.php';
        $moduleFile = new SplFileInfo($moduleClassFile);
        if ($moduleFile->isReadable() && $moduleFile->isFile()) {
            require_once $moduleClassFile;
            if (class_exists($class)) {
                $this->moduleClassMap[$class] = $moduleClassFile;
                return $class;
            }
        }
        return false;
    }
    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }
    public function unregister()
    {
        spl_autoload_unregister([$this, 'autoload']);
    }
    public function registerPaths($paths)
    {
        if (! is_array($paths) && ! $paths instanceof Traversable) {
            require_once __DIR__ . '/Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException(
                'Parameter to \\Zend\\Loader\\ModuleAutoloader\'s '
                . 'registerPaths method must be an array or '
                . 'implement the Traversable interface'
            );
        }
        foreach ($paths as $module => $path) {
            if (is_string($module)) {
                $this->registerPath($path, $module);
            } else {
                $this->registerPath($path);
            }
        }
        return $this;
    }
    public function registerPath($path, $moduleName = false)
    {
        if (! is_string($path)) {
            require_once __DIR__ . '/Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid path provided; must be a string, received %s',
                gettype($path)
            ));
        }
        if ($moduleName) {
            if (in_array(substr($moduleName, -2), ['\\*', '\\%'])) {
                $this->namespacedPaths[substr($moduleName, 0, -2)] = static::normalizePath($path);
            } else {
                $this->explicitPaths[$moduleName] = static::normalizePath($path);
            }
        } else {
            $this->paths[] = static::normalizePath($path);
        }
        return $this;
    }
    public function getPaths()
    {
        return $this->paths;
    }
    protected function pharFileToModuleName($pharPath)
    {
        do {
            $pathinfo = pathinfo($pharPath);
            $pharPath = $pathinfo['filename'];
        } while (isset($pathinfo['extension']));
        return $pathinfo['filename'];
    }
    public static function normalizePath($path, $trailingSlash = true)
    {
        $path = rtrim($path, '/');
        $path = rtrim($path, '\\');
        if ($trailingSlash) {
            $path .= DIRECTORY_SEPARATOR;
        }
        return $path;
    }
}
