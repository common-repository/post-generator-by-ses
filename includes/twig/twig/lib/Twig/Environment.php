<?php
class Twig_Environment
{
    const VERSION = '1.36.0';
    const VERSION_ID = 13600;
    const MAJOR_VERSION = 1;
    const MINOR_VERSION = 36;
    const RELEASE_VERSION = 0;
    const EXTRA_VERSION = 'DEV';
    protected $charset;
    protected $loader;
    protected $debug;
    protected $autoReload;
    protected $cache;
    protected $lexer;
    protected $parser;
    protected $compiler;
    protected $baseTemplateClass;
    protected $extensions;
    protected $parsers;
    protected $visitors;
    protected $filters;
    protected $tests;
    protected $functions;
    protected $globals;
    protected $runtimeInitialized = false;
    protected $extensionInitialized = false;
    protected $loadedTemplates;
    protected $strictVariables;
    protected $unaryOperators;
    protected $binaryOperators;
    protected $templateClassPrefix = '__TwigTemplate_';
    protected $functionCallbacks = [];
    protected $filterCallbacks = [];
    protected $staging;
    private $originalCache;
    private $bcWriteCacheFile = false;
    private $bcGetCacheFilename = false;
    private $lastModifiedExtension = 0;
    private $extensionsByClass = [];
    private $runtimeLoaders = [];
    private $runtimes = [];
    private $optionsHash;
    private $loading = [];
    public function __construct(Twig_LoaderInterface $loader = null, $options = [])
    {
        if (null !== $loader) {
            $this->setLoader($loader);
        } else {
            @trigger_error('Not passing a Twig_LoaderInterface as the first constructor argument of Twig_Environment is deprecated since version 1.21.', E_USER_DEPRECATED);
        }
        $options = array_merge([
            'debug' => false,
            'charset' => 'UTF-8',
            'base_template_class' => 'Twig_Template',
            'strict_variables' => false,
            'autoescape' => 'html',
            'cache' => false,
            'auto_reload' => null,
            'optimizations' => -1,
        ], $options);
        $this->debug = (bool) $options['debug'];
        $this->charset = strtoupper($options['charset']);
        $this->baseTemplateClass = $options['base_template_class'];
        $this->autoReload = null === $options['auto_reload'] ? $this->debug : (bool) $options['auto_reload'];
        $this->strictVariables = (bool) $options['strict_variables'];
        $this->setCache($options['cache']);
        $this->addExtension(new Twig_Extension_Core());
        $this->addExtension(new Twig_Extension_Escaper($options['autoescape']));
        $this->addExtension(new Twig_Extension_Optimizer($options['optimizations']));
        $this->staging = new Twig_Extension_Staging();
        if (is_string($this->originalCache)) {
            $r = new ReflectionMethod($this, 'writeCacheFile');
            if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                @trigger_error('The Twig_Environment::writeCacheFile method is deprecated since version 1.22 and will be removed in Twig 2.0.', E_USER_DEPRECATED);
                $this->bcWriteCacheFile = true;
            }
            $r = new ReflectionMethod($this, 'getCacheFilename');
            if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                @trigger_error('The Twig_Environment::getCacheFilename method is deprecated since version 1.22 and will be removed in Twig 2.0.', E_USER_DEPRECATED);
                $this->bcGetCacheFilename = true;
            }
        }
    }
    public function getBaseTemplateClass()
    {
        return $this->baseTemplateClass;
    }
    public function setBaseTemplateClass($class)
    {
        $this->baseTemplateClass = $class;
        $this->updateOptionsHash();
    }
    public function enableDebug()
    {
        $this->debug = true;
        $this->updateOptionsHash();
    }
    public function disableDebug()
    {
        $this->debug = false;
        $this->updateOptionsHash();
    }
    public function isDebug()
    {
        return $this->debug;
    }
    public function enableAutoReload()
    {
        $this->autoReload = true;
    }
    public function disableAutoReload()
    {
        $this->autoReload = false;
    }
    public function isAutoReload()
    {
        return $this->autoReload;
    }
    public function enableStrictVariables()
    {
        $this->strictVariables = true;
        $this->updateOptionsHash();
    }
    public function disableStrictVariables()
    {
        $this->strictVariables = false;
        $this->updateOptionsHash();
    }
    public function isStrictVariables()
    {
        return $this->strictVariables;
    }
    public function getCache($original = true)
    {
        return $original ? $this->originalCache : $this->cache;
    }
    public function setCache($cache)
    {
        if (is_string($cache)) {
            $this->originalCache = $cache;
            $this->cache = new Twig_Cache_Filesystem($cache);
        } elseif (false === $cache) {
            $this->originalCache = $cache;
            $this->cache = new Twig_Cache_Null();
        } elseif (null === $cache) {
            @trigger_error('Using "null" as the cache strategy is deprecated since version 1.23 and will be removed in Twig 2.0.', E_USER_DEPRECATED);
            $this->originalCache = false;
            $this->cache = new Twig_Cache_Null();
        } elseif ($cache instanceof Twig_CacheInterface) {
            $this->originalCache = $this->cache = $cache;
        } else {
            throw new LogicException(sprintf('Cache can only be a string, false, or a Twig_CacheInterface implementation.'));
        }
    }
    public function getCacheFilename($name)
    {
        @trigger_error(sprintf('The %s method is deprecated since version 1.22 and will be removed in Twig 2.0.', __METHOD__), E_USER_DEPRECATED);
        $key = $this->cache->generateKey($name, $this->getTemplateClass($name));
        return !$key ? false : $key;
    }
    public function getTemplateClass($name, $index = null)
    {
        $key = $this->getLoader()->getCacheKey($name) . $this->optionsHash;
        return $this->templateClassPrefix . hash('sha256', $key) . (null === $index ? '' : '_' . $index);
    }
    public function getTemplateClassPrefix()
    {
        @trigger_error(sprintf('The %s method is deprecated since version 1.22 and will be removed in Twig 2.0.', __METHOD__), E_USER_DEPRECATED);
        return $this->templateClassPrefix;
    }
    public function render($name, array $context = [])
    {
        return $this->loadTemplate($name)->render($context);
    }
    public function display($name, array $context = [])
    {
        $this->loadTemplate($name)->display($context);
    }
    public function load($name)
    {
        if ($name instanceof Twig_TemplateWrapper) {
            return $name;
        }
        if ($name instanceof Twig_Template) {
            return new Twig_TemplateWrapper($this, $name);
        }
        return new Twig_TemplateWrapper($this, $this->loadTemplate($name));
    }
    public function loadTemplate($name, $index = null)
    {
        $cls = $mainCls = $this->getTemplateClass($name);
        if (null !== $index) {
            $cls .= '_' . $index;
        }
        if (isset($this->loadedTemplates[$cls])) {
            return $this->loadedTemplates[$cls];
        }
        if (!class_exists($cls, false)) {
            if ($this->bcGetCacheFilename) {
                $key = $this->getCacheFilename($name);
            } else {
                $key = $this->cache->generateKey($name, $mainCls);
            }
            if (!$this->isAutoReload() || $this->isTemplateFresh($name, $this->cache->getTimestamp($key))) {
                $this->cache->load($key);
            }
            if (!class_exists($cls, false)) {
                $loader = $this->getLoader();
                if (!$loader instanceof Twig_SourceContextLoaderInterface) {
                    $source = new Twig_Source($loader->getSource($name), $name);
                } else {
                    $source = $loader->getSourceContext($name);
                }
                $content = $this->compileSource($source);
                if ($this->bcWriteCacheFile) {
                    $this->writeCacheFile($key, $content);
                } else {
                    $this->cache->write($key, $content);
                    $this->cache->load($key);
                }
                if (!class_exists($mainCls, false)) {
                    eval('?>' . $content);
                }
            }
            if (!class_exists($cls, false)) {
                throw new Twig_Error_Runtime(sprintf('Failed to load Twig template "%s", index "%s": cache is corrupted.', $name, $index), -1, $source);
            }
        }
        if (!$this->runtimeInitialized) {
            $this->initRuntime();
        }
        if (isset($this->loading[$cls])) {
            throw new Twig_Error_Runtime(sprintf('Circular reference detected for Twig template "%s", path: %s.', $name, implode(' -> ', array_merge($this->loading, [$name]))));
        }
        $this->loading[$cls] = $name;
        try {
            $this->loadedTemplates[$cls] = new $cls($this);
            unset($this->loading[$cls]);
        } catch (\Exception $e) {
            unset($this->loading[$cls]);
            throw $e;
        }
        return $this->loadedTemplates[$cls];
    }
    public function createTemplate($template)
    {
        $name = sprintf('__string_template__%s', hash('sha256', $template, false));
        $loader = new Twig_Loader_Chain([
            new Twig_Loader_Array([$name => $template]),
            $current = $this->getLoader(),
        ]);
        $this->setLoader($loader);
        try {
            $template = $this->loadTemplate($name);
        } catch (Exception $e) {
            $this->setLoader($current);
            throw $e;
        } catch (Throwable $e) {
            $this->setLoader($current);
            throw $e;
        }
        $this->setLoader($current);
        return $template;
    }
    public function isTemplateFresh($name, $time)
    {
        if (0 === $this->lastModifiedExtension) {
            foreach ($this->extensions as $extension) {
                $r = new ReflectionObject($extension);
                if (file_exists($r->getFileName()) && ($extensionTime = filemtime($r->getFileName())) > $this->lastModifiedExtension) {
                    $this->lastModifiedExtension = $extensionTime;
                }
            }
        }
        return $this->lastModifiedExtension <= $time && $this->getLoader()->isFresh($name, $time);
    }
    public function resolveTemplate($names)
    {
        if (!is_array($names)) {
            $names = [$names];
        }
        foreach ($names as $name) {
            if ($name instanceof Twig_Template) {
                return $name;
            }
            if ($name instanceof Twig_TemplateWrapper) {
                return $name;
            }
            try {
                return $this->loadTemplate($name);
            } catch (Twig_Error_Loader $e) {
            }
        }
        if (1 === count($names)) {
            throw $e;
        }
        throw new Twig_Error_Loader(sprintf('Unable to find one of the following templates: "%s".', implode('", "', $names)));
    }
    public function clearTemplateCache()
    {
        @trigger_error(sprintf('The %s method is deprecated since version 1.18.3 and will be removed in Twig 2.0.', __METHOD__), E_USER_DEPRECATED);
        $this->loadedTemplates = [];
    }
    public function clearCacheFiles()
    {
        @trigger_error(sprintf('The %s method is deprecated since version 1.22 and will be removed in Twig 2.0.', __METHOD__), E_USER_DEPRECATED);
        if (is_string($this->originalCache)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->originalCache), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
                if ($file->isFile()) {
                    @unlink($file->getPathname());
                }
            }
        }
    }
    public function getLexer()
    {
        @trigger_error(sprintf('The %s() method is deprecated since version 1.25 and will be removed in 2.0.', __FUNCTION__), E_USER_DEPRECATED);
        if (null === $this->lexer) {
            $this->lexer = new Twig_Lexer($this);
        }
        return $this->lexer;
    }
    public function setLexer(Twig_LexerInterface $lexer)
    {
        $this->lexer = $lexer;
    }
    public function tokenize($source, $name = null)
    {
        if (!$source instanceof Twig_Source) {
            @trigger_error(sprintf('Passing a string as the $source argument of %s() is deprecated since version 1.27. Pass a Twig_Source instance instead.', __METHOD__), E_USER_DEPRECATED);
            $source = new Twig_Source($source, $name);
        }
        if (null === $this->lexer) {
            $this->lexer = new Twig_Lexer($this);
        }
        return $this->lexer->tokenize($source);
    }
    public function getParser()
    {
        @trigger_error(sprintf('The %s() method is deprecated since version 1.25 and will be removed in 2.0.', __FUNCTION__), E_USER_DEPRECATED);
        if (null === $this->parser) {
            $this->parser = new Twig_Parser($this);
        }
        return $this->parser;
    }
    public function setParser(Twig_ParserInterface $parser)
    {
        $this->parser = $parser;
    }
    public function parse(Twig_TokenStream $stream)
    {
        if (null === $this->parser) {
            $this->parser = new Twig_Parser($this);
        }
        return $this->parser->parse($stream);
    }
    public function getCompiler()
    {
        @trigger_error(sprintf('The %s() method is deprecated since version 1.25 and will be removed in 2.0.', __FUNCTION__), E_USER_DEPRECATED);
        if (null === $this->compiler) {
            $this->compiler = new Twig_Compiler($this);
        }
        return $this->compiler;
    }
    public function setCompiler(Twig_CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }
    public function compile(Twig_NodeInterface $node)
    {
        if (null === $this->compiler) {
            $this->compiler = new Twig_Compiler($this);
        }
        return $this->compiler->compile($node)->getSource();
    }
    public function compileSource($source, $name = null)
    {
        if (!$source instanceof Twig_Source) {
            @trigger_error(sprintf('Passing a string as the $source argument of %s() is deprecated since version 1.27. Pass a Twig_Source instance instead.', __METHOD__), E_USER_DEPRECATED);
            $source = new Twig_Source($source, $name);
        }
        try {
            return $this->compile($this->parse($this->tokenize($source)));
        } catch (Twig_Error $e) {
            $e->setSourceContext($source);
            throw $e;
        } catch (Exception $e) {
            throw new Twig_Error_Syntax(sprintf('An exception has been thrown during the compilation of a template ("%s").', $e->getMessage()), -1, $source, $e);
        }
    }
    public function setLoader(Twig_LoaderInterface $loader)
    {
        if (!$loader instanceof Twig_SourceContextLoaderInterface && 0 !== strpos(get_class($loader), 'Mock_')) {
            @trigger_error(sprintf('Twig loader "%s" should implement Twig_SourceContextLoaderInterface since version 1.27.', get_class($loader)), E_USER_DEPRECATED);
        }
        $this->loader = $loader;
    }
    public function getLoader()
    {
        if (null === $this->loader) {
            throw new LogicException('You must set a loader first.');
        }
        return $this->loader;
    }
    public function setCharset($charset)
    {
        $this->charset = strtoupper($charset);
    }
    public function getCharset()
    {
        return $this->charset;
    }
    public function initRuntime()
    {
        $this->runtimeInitialized = true;
        foreach ($this->getExtensions() as $name => $extension) {
            if (!$extension instanceof Twig_Extension_InitRuntimeInterface) {
                $m = new ReflectionMethod($extension, 'initRuntime');
                if ('Twig_Extension' !== $m->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Defining the initRuntime() method in the "%s" extension is deprecated since version 1.23. Use the `needs_environment` option to get the Twig_Environment instance in filters, functions, or tests; or explicitly implement Twig_Extension_InitRuntimeInterface if needed (not recommended).', $name), E_USER_DEPRECATED);
                }
            }
            $extension->initRuntime($this);
        }
    }
    public function hasExtension($class)
    {
        $class = ltrim($class, '\\');
        if (!isset($this->extensionsByClass[$class]) && class_exists($class, false)) {
            $class = new ReflectionClass($class);
            $class = $class->name;
        }
        if (isset($this->extensions[$class])) {
            if ($class !== get_class($this->extensions[$class])) {
                @trigger_error(sprintf('Referencing the "%s" extension by its name (defined by getName()) is deprecated since 1.26 and will be removed in Twig 2.0. Use the Fully Qualified Extension Class Name instead.', $class), E_USER_DEPRECATED);
            }
            return true;
        }
        return isset($this->extensionsByClass[$class]);
    }
    public function addRuntimeLoader(Twig_RuntimeLoaderInterface $loader)
    {
        $this->runtimeLoaders[] = $loader;
    }
    public function getExtension($class)
    {
        $class = ltrim($class, '\\');
        if (!isset($this->extensionsByClass[$class]) && class_exists($class, false)) {
            $class = new ReflectionClass($class);
            $class = $class->name;
        }
        if (isset($this->extensions[$class])) {
            if ($class !== get_class($this->extensions[$class])) {
                @trigger_error(sprintf('Referencing the "%s" extension by its name (defined by getName()) is deprecated since 1.26 and will be removed in Twig 2.0. Use the Fully Qualified Extension Class Name instead.', $class), E_USER_DEPRECATED);
            }
            return $this->extensions[$class];
        }
        if (!isset($this->extensionsByClass[$class])) {
            throw new Twig_Error_Runtime(sprintf('The "%s" extension is not enabled.', $class));
        }
        return $this->extensionsByClass[$class];
    }
    public function getRuntime($class)
    {
        if (isset($this->runtimes[$class])) {
            return $this->runtimes[$class];
        }
        foreach ($this->runtimeLoaders as $loader) {
            if (null !== $runtime = $loader->load($class)) {
                return $this->runtimes[$class] = $runtime;
            }
        }
        throw new Twig_Error_Runtime(sprintf('Unable to load the "%s" runtime.', $class));
    }
    public function addExtension(Twig_ExtensionInterface $extension)
    {
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to register extension "%s" as extensions have already been initialized.', $extension->getName()));
        }
        $class = get_class($extension);
        if ($class !== $extension->getName()) {
            if (isset($this->extensions[$extension->getName()])) {
                unset($this->extensions[$extension->getName()], $this->extensionsByClass[$class]);
                @trigger_error(sprintf('The possibility to register the same extension twice ("%s") is deprecated since version 1.23 and will be removed in Twig 2.0. Use proper PHP inheritance instead.', $extension->getName()), E_USER_DEPRECATED);
            }
        }
        $this->lastModifiedExtension = 0;
        $this->extensionsByClass[$class] = $extension;
        $this->extensions[$extension->getName()] = $extension;
        $this->updateOptionsHash();
    }
    public function removeExtension($name)
    {
        @trigger_error(sprintf('The %s method is deprecated since version 1.12 and will be removed in Twig 2.0.', __METHOD__), E_USER_DEPRECATED);
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to remove extension "%s" as extensions have already been initialized.', $name));
        }
        $class = ltrim($name, '\\');
        if (!isset($this->extensionsByClass[$class]) && class_exists($class, false)) {
            $class = new ReflectionClass($class);
            $class = $class->name;
        }
        if (isset($this->extensions[$class])) {
            if ($class !== get_class($this->extensions[$class])) {
                @trigger_error(sprintf('Referencing the "%s" extension by its name (defined by getName()) is deprecated since 1.26 and will be removed in Twig 2.0. Use the Fully Qualified Extension Class Name instead.', $class), E_USER_DEPRECATED);
            }
            unset($this->extensions[$class]);
        }
        unset($this->extensions[$class]);
        $this->updateOptionsHash();
    }
    public function setExtensions(array $extensions)
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }
    public function getExtensions()
    {
        return $this->extensions;
    }
    public function addTokenParser(Twig_TokenParserInterface $parser)
    {
        if ($this->extensionInitialized) {
            throw new LogicException('Unable to add a token parser as extensions have already been initialized.');
        }
        $this->staging->addTokenParser($parser);
    }
    public function getTokenParsers()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->parsers;
    }
    public function getTags()
    {
        $tags = [];
        foreach ($this->getTokenParsers()->getParsers() as $parser) {
            if ($parser instanceof Twig_TokenParserInterface) {
                $tags[$parser->getTag()] = $parser;
            }
        }
        return $tags;
    }
    public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
    {
        if ($this->extensionInitialized) {
            throw new LogicException('Unable to add a node visitor as extensions have already been initialized.');
        }
        $this->staging->addNodeVisitor($visitor);
    }
    public function getNodeVisitors()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->visitors;
    }
    public function addFilter($name, $filter = null)
    {
        if (!$name instanceof Twig_SimpleFilter && !($filter instanceof Twig_SimpleFilter || $filter instanceof Twig_FilterInterface)) {
            throw new LogicException('A filter must be an instance of Twig_FilterInterface or Twig_SimpleFilter.');
        }
        if ($name instanceof Twig_SimpleFilter) {
            $filter = $name;
            $name = $filter->getName();
        } else {
            @trigger_error(sprintf('Passing a name as a first argument to the %s method is deprecated since version 1.21. Pass an instance of "Twig_SimpleFilter" instead when defining filter "%s".', __METHOD__, $name), E_USER_DEPRECATED);
        }
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add filter "%s" as extensions have already been initialized.', $name));
        }
        $this->staging->addFilter($name, $filter);
    }
    public function getFilter($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }
        foreach ($this->filters as $pattern => $filter) {
            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);
            if ($count) {
                if (preg_match('#^' . $pattern . '$#', $name, $matches)) {
                    array_shift($matches);
                    $filter->setArguments($matches);
                    return $filter;
                }
            }
        }
        foreach ($this->filterCallbacks as $callback) {
            if (false !== $filter = call_user_func($callback, $name)) {
                return $filter;
            }
        }
        return false;
    }
    public function registerUndefinedFilterCallback($callable)
    {
        $this->filterCallbacks[] = $callable;
    }
    public function getFilters()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->filters;
    }
    public function addTest($name, $test = null)
    {
        if (!$name instanceof Twig_SimpleTest && !($test instanceof Twig_SimpleTest || $test instanceof Twig_TestInterface)) {
            throw new LogicException('A test must be an instance of Twig_TestInterface or Twig_SimpleTest.');
        }
        if ($name instanceof Twig_SimpleTest) {
            $test = $name;
            $name = $test->getName();
        } else {
            @trigger_error(sprintf('Passing a name as a first argument to the %s method is deprecated since version 1.21. Pass an instance of "Twig_SimpleTest" instead when defining test "%s".', __METHOD__, $name), E_USER_DEPRECATED);
        }
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add test "%s" as extensions have already been initialized.', $name));
        }
        $this->staging->addTest($name, $test);
    }
    public function getTests()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->tests;
    }
    public function getTest($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        if (isset($this->tests[$name])) {
            return $this->tests[$name];
        }
        foreach ($this->tests as $pattern => $test) {
            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);
            if ($count) {
                if (preg_match('#^' . $pattern . '$#', $name, $matches)) {
                    array_shift($matches);
                    $test->setArguments($matches);
                    return $test;
                }
            }
        }
        return false;
    }
    public function addFunction($name, $function = null)
    {
        if (!$name instanceof Twig_SimpleFunction && !($function instanceof Twig_SimpleFunction || $function instanceof Twig_FunctionInterface)) {
            throw new LogicException('A function must be an instance of Twig_FunctionInterface or Twig_SimpleFunction.');
        }
        if ($name instanceof Twig_SimpleFunction) {
            $function = $name;
            $name = $function->getName();
        } else {
            @trigger_error(sprintf('Passing a name as a first argument to the %s method is deprecated since version 1.21. Pass an instance of "Twig_SimpleFunction" instead when defining function "%s".', __METHOD__, $name), E_USER_DEPRECATED);
        }
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add function "%s" as extensions have already been initialized.', $name));
        }
        $this->staging->addFunction($name, $function);
    }
    public function getFunction($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }
        foreach ($this->functions as $pattern => $function) {
            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);
            if ($count) {
                if (preg_match('#^' . $pattern . '$#', $name, $matches)) {
                    array_shift($matches);
                    $function->setArguments($matches);
                    return $function;
                }
            }
        }
        foreach ($this->functionCallbacks as $callback) {
            if (false !== $function = call_user_func($callback, $name)) {
                return $function;
            }
        }
        return false;
    }
    public function registerUndefinedFunctionCallback($callable)
    {
        $this->functionCallbacks[] = $callable;
    }
    public function getFunctions()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->functions;
    }
    public function addGlobal($name, $value)
    {
        if ($this->extensionInitialized || $this->runtimeInitialized) {
            if (null === $this->globals) {
                $this->globals = $this->initGlobals();
            }
            if (!array_key_exists($name, $this->globals)) {
                @trigger_error(sprintf('Registering global variable "%s" at runtime or when the extensions have already been initialized is deprecated since version 1.21.', $name), E_USER_DEPRECATED);
            }
        }
        if ($this->extensionInitialized || $this->runtimeInitialized) {
            $this->globals[$name] = $value;
        } else {
            $this->staging->addGlobal($name, $value);
        }
    }
    public function getGlobals()
    {
        if (!$this->runtimeInitialized && !$this->extensionInitialized) {
            return $this->initGlobals();
        }
        if (null === $this->globals) {
            $this->globals = $this->initGlobals();
        }
        return $this->globals;
    }
    public function mergeGlobals(array $context)
    {
        foreach ($this->getGlobals() as $key => $value) {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }
        return $context;
    }
    public function getUnaryOperators()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->unaryOperators;
    }
    public function getBinaryOperators()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }
        return $this->binaryOperators;
    }
    public function computeAlternatives($name, $items)
    {
        @trigger_error(sprintf('The %s method is deprecated since version 1.23 and will be removed in Twig 2.0.', __METHOD__), E_USER_DEPRECATED);
        return Twig_Error_Syntax::computeAlternatives($name, $items);
    }
    protected function initGlobals()
    {
        $globals = [];
        foreach ($this->extensions as $name => $extension) {
            if (!$extension instanceof Twig_Extension_GlobalsInterface) {
                $m = new ReflectionMethod($extension, 'getGlobals');
                if ('Twig_Extension' !== $m->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Defining the getGlobals() method in the "%s" extension without explicitly implementing Twig_Extension_GlobalsInterface is deprecated since version 1.23.', $name), E_USER_DEPRECATED);
                }
            }
            $extGlob = $extension->getGlobals();
            if (!is_array($extGlob)) {
                throw new UnexpectedValueException(sprintf('"%s::getGlobals()" must return an array of globals.', get_class($extension)));
            }
            $globals[] = $extGlob;
        }
        $globals[] = $this->staging->getGlobals();
        return call_user_func_array('array_merge', $globals);
    }
    protected function initExtensions()
    {
        if ($this->extensionInitialized) {
            return;
        }
        $this->parsers = new Twig_TokenParserBroker([], [], false);
        $this->filters = [];
        $this->functions = [];
        $this->tests = [];
        $this->visitors = [];
        $this->unaryOperators = [];
        $this->binaryOperators = [];
        foreach ($this->extensions as $extension) {
            $this->initExtension($extension);
        }
        $this->initExtension($this->staging);
        $this->extensionInitialized = true;
    }
    protected function initExtension(Twig_ExtensionInterface $extension)
    {
        foreach ($extension->getFilters() as $name => $filter) {
            if ($filter instanceof Twig_SimpleFilter) {
                $name = $filter->getName();
            } else {
                @trigger_error(sprintf('Using an instance of "%s" for filter "%s" is deprecated since version 1.21. Use Twig_SimpleFilter instead.', get_class($filter), $name), E_USER_DEPRECATED);
            }
            $this->filters[$name] = $filter;
        }
        foreach ($extension->getFunctions() as $name => $function) {
            if ($function instanceof Twig_SimpleFunction) {
                $name = $function->getName();
            } else {
                @trigger_error(sprintf('Using an instance of "%s" for function "%s" is deprecated since version 1.21. Use Twig_SimpleFunction instead.', get_class($function), $name), E_USER_DEPRECATED);
            }
            $this->functions[$name] = $function;
        }
        foreach ($extension->getTests() as $name => $test) {
            if ($test instanceof Twig_SimpleTest) {
                $name = $test->getName();
            } else {
                @trigger_error(sprintf('Using an instance of "%s" for test "%s" is deprecated since version 1.21. Use Twig_SimpleTest instead.', get_class($test), $name), E_USER_DEPRECATED);
            }
            $this->tests[$name] = $test;
        }
        foreach ($extension->getTokenParsers() as $parser) {
            if ($parser instanceof Twig_TokenParserInterface) {
                $this->parsers->addTokenParser($parser);
            } elseif ($parser instanceof Twig_TokenParserBrokerInterface) {
                @trigger_error('Registering a Twig_TokenParserBrokerInterface instance is deprecated since version 1.21.', E_USER_DEPRECATED);
                $this->parsers->addTokenParserBroker($parser);
            } else {
                throw new LogicException('getTokenParsers() must return an array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances.');
            }
        }
        foreach ($extension->getNodeVisitors() as $visitor) {
            $this->visitors[] = $visitor;
        }
        if ($operators = $extension->getOperators()) {
            if (!is_array($operators)) {
                throw new InvalidArgumentException(sprintf('"%s::getOperators()" must return an array with operators, got "%s".', get_class($extension), is_object($operators) ? get_class($operators) : gettype($operators) . (is_resource($operators) ? '' : '#' . $operators)));
            }
            if (2 !== count($operators)) {
                throw new InvalidArgumentException(sprintf('"%s::getOperators()" must return an array of 2 elements, got %d.', get_class($extension), count($operators)));
            }
            $this->unaryOperators = array_merge($this->unaryOperators, $operators[0]);
            $this->binaryOperators = array_merge($this->binaryOperators, $operators[1]);
        }
    }
    protected function writeCacheFile($file, $content)
    {
        $this->cache->write($file, $content);
    }
    private function updateOptionsHash()
    {
        $hashParts = array_merge(
            array_keys($this->extensions),
            [
                (int) function_exists('twig_template_get_attributes'),
                PHP_MAJOR_VERSION,
                PHP_MINOR_VERSION,
                self::VERSION,
                (int) $this->debug,
                $this->baseTemplateClass,
                (int) $this->strictVariables,
            ]
        );
        $this->optionsHash = implode(':', $hashParts);
    }
}
class_alias('Twig_Environment', 'Twig\Environment', false);
