<?php
class Twig_Loader_Filesystem implements Twig_LoaderInterface, Twig_ExistsLoaderInterface, Twig_SourceContextLoaderInterface
{
    const MAIN_NAMESPACE = '__main__';
    protected $paths = [];
    protected $cache = [];
    protected $errorCache = [];
    private $rootPath;
    public function __construct($paths = [], $rootPath = null)
    {
        $this->rootPath = (null === $rootPath ? getcwd() : $rootPath) . DIRECTORY_SEPARATOR;
        if (false !== $realPath = realpath($rootPath)) {
            $this->rootPath = $realPath . DIRECTORY_SEPARATOR;
        }
        if ($paths) {
            $this->setPaths($paths);
        }
    }
    public function getPaths($namespace = self::MAIN_NAMESPACE)
    {
        return isset($this->paths[$namespace]) ? $this->paths[$namespace] : [];
    }
    public function getNamespaces()
    {
        return array_keys($this->paths);
    }
    public function setPaths($paths, $namespace = self::MAIN_NAMESPACE)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        $this->paths[$namespace] = [];
        foreach ($paths as $path) {
            $this->addPath($path, $namespace);
        }
    }
    public function addPath($path, $namespace = self::MAIN_NAMESPACE)
    {
        $this->cache = $this->errorCache = [];
        $checkPath = $this->isAbsolutePath($path) ? $path : $this->rootPath . $path;
        if (!is_dir($checkPath)) {
            throw new Twig_Error_Loader(sprintf('The "%s" directory does not exist ("%s").', $path, $checkPath));
        }
        $this->paths[$namespace][] = rtrim($path, '/\\');
    }
    public function prependPath($path, $namespace = self::MAIN_NAMESPACE)
    {
        $this->cache = $this->errorCache = [];
        $checkPath = $this->isAbsolutePath($path) ? $path : $this->rootPath . $path;
        if (!is_dir($checkPath)) {
            throw new Twig_Error_Loader(sprintf('The "%s" directory does not exist ("%s").', $path, $checkPath));
        }
        $path = rtrim($path, '/\\');
        if (!isset($this->paths[$namespace])) {
            $this->paths[$namespace][] = $path;
        } else {
            array_unshift($this->paths[$namespace], $path);
        }
    }
    public function getSource($name)
    {
        @trigger_error(sprintf('Calling "getSource" on "%s" is deprecated since 1.27. Use getSourceContext() instead.', get_class($this)), E_USER_DEPRECATED);
        return file_get_contents($this->findTemplate($name));
    }
    public function getSourceContext($name)
    {
        $path = $this->findTemplate($name);
        return new Twig_Source(file_get_contents($path), $name, $path);
    }
    public function getCacheKey($name)
    {
        $path = $this->findTemplate($name);
        $len = strlen($this->rootPath);
        if (0 === strncmp($this->rootPath, $path, $len)) {
            return substr($path, $len);
        }
        return $path;
    }
    public function exists($name)
    {
        $name = $this->normalizeName($name);
        if (isset($this->cache[$name])) {
            return true;
        }
        try {
            return false !== $this->findTemplate($name, false);
        } catch (Twig_Error_Loader $exception) {
            @trigger_error(sprintf('In %s::findTemplate(), you must accept a second argument that when set to "false" returns "false" instead of throwing an exception. Not supporting this argument is deprecated since version 1.27.', get_class($this)), E_USER_DEPRECATED);
            return false;
        }
    }
    public function isFresh($name, $time)
    {
        return filemtime($this->findTemplate($name)) < $time;
    }
    protected function findTemplate($name)
    {
        $throw = func_num_args() > 1 ? func_get_arg(1) : true;
        $name = $this->normalizeName($name);
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }
        if (isset($this->errorCache[$name])) {
            if (!$throw) {
                return false;
            }
            throw new Twig_Error_Loader($this->errorCache[$name]);
        }
        try {
            $this->validateName($name);
            list($namespace, $shortname) = $this->parseName($name);
        } catch (Twig_Error_Loader $e) {
            if (!$throw) {
                return false;
            }
            throw $e;
        }
        if (!isset($this->paths[$namespace])) {
            $this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);
            if (!$throw) {
                return false;
            }
            throw new Twig_Error_Loader($this->errorCache[$name]);
        }
        foreach ($this->paths[$namespace] as $path) {
            if (!$this->isAbsolutePath($path)) {
                $path = $this->rootPath . '/' . $path;
            }
            if (is_file($path . '/' . $shortname)) {
                if (false !== $realpath = realpath($path . '/' . $shortname)) {
                    return $this->cache[$name] = $realpath;
                }
                return $this->cache[$name] = $path . '/' . $shortname;
            }
        }
        $this->errorCache[$name] = sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths[$namespace]));
        if (!$throw) {
            return false;
        }
        throw new Twig_Error_Loader($this->errorCache[$name]);
    }
    protected function parseName($name, $default = self::MAIN_NAMESPACE)
    {
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                throw new Twig_Error_Loader(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }
            $namespace = substr($name, 1, $pos - 1);
            $shortname = substr($name, $pos + 1);
            return [$namespace, $shortname];
        }
        return [$default, $name];
    }
    protected function normalizeName($name)
    {
        return preg_replace('#/{2,}#', '/', str_replace('\\', '/', (string) $name));
    }
    protected function validateName($name)
    {
        if (false !== strpos($name, "\0")) {
            throw new Twig_Error_Loader('A template name cannot contain NUL bytes.');
        }
        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }
            if ($level < 0) {
                throw new Twig_Error_Loader(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }
    }
    private function isAbsolutePath($file)
    {
        return strspn($file, '/\\', 0, 1)
            || (
                strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === substr($file, 1, 1)
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
        ;
    }
}
class_alias('Twig_Loader_Filesystem', 'Twig\Loader\FilesystemLoader', false);
