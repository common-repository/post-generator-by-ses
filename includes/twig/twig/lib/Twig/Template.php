<?php
abstract class Twig_Template implements Twig_TemplateInterface
{
    protected static $cache = [];
    protected $parent;
    protected $parents = [];
    protected $env;
    protected $blocks = [];
    protected $traits = [];
    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
    }
    public function __toString()
    {
        return $this->getTemplateName();
    }
    abstract public function getTemplateName();
    public function getDebugInfo()
    {
        return [];
    }
    public function getSource()
    {
        @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);
        return '';
    }
    public function getSourceContext()
    {
        return new Twig_Source('', $this->getTemplateName());
    }
    public function getEnvironment()
    {
        @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 1.20 and will be removed in 2.0.', E_USER_DEPRECATED);
        return $this->env;
    }
    public function getParent(array $context)
    {
        if (null !== $this->parent) {
            return $this->parent;
        }
        try {
            $parent = $this->doGetParent($context);
            if (false === $parent) {
                return false;
            }
            if ($parent instanceof self) {
                return $this->parents[$parent->getTemplateName()] = $parent;
            }
            if (!isset($this->parents[$parent])) {
                $this->parents[$parent] = $this->loadTemplate($parent);
            }
        } catch (Twig_Error_Loader $e) {
            $e->setSourceContext(null);
            $e->guess();
            throw $e;
        }
        return $this->parents[$parent];
    }
    protected function doGetParent(array $context)
    {
        return false;
    }
    public function isTraitable()
    {
        return true;
    }
    public function displayParentBlock($name, array $context, array $blocks = [])
    {
        $name = (string) $name;
        if (isset($this->traits[$name])) {
            $this->traits[$name][0]->displayBlock($name, $context, $blocks, false);
        } elseif (false !== $parent = $this->getParent($context)) {
            $parent->displayBlock($name, $context, $blocks, false);
        } else {
            throw new Twig_Error_Runtime(sprintf('The template has no parent and no traits defining the "%s" block.', $name), -1, $this->getSourceContext());
        }
    }
    public function displayBlock($name, array $context, array $blocks = [], $useBlocks = true)
    {
        $name = (string) $name;
        if ($useBlocks && isset($blocks[$name])) {
            $template = $blocks[$name][0];
            $block = $blocks[$name][1];
        } elseif (isset($this->blocks[$name])) {
            $template = $this->blocks[$name][0];
            $block = $this->blocks[$name][1];
        } else {
            $template = null;
            $block = null;
        }
        if (null !== $template && !$template instanceof self) {
            throw new LogicException('A block must be a method on a Twig_Template instance.');
        }
        if (null !== $template) {
            try {
                $template->$block($context, $blocks);
            } catch (Twig_Error $e) {
                if (!$e->getSourceContext()) {
                    $e->setSourceContext($template->getSourceContext());
                }
                if (false === $e->getTemplateLine()) {
                    $e->setTemplateLine(-1);
                    $e->guess();
                }
                throw $e;
            } catch (Exception $e) {
                throw new Twig_Error_Runtime(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $template->getSourceContext(), $e);
            }
        } elseif (false !== $parent = $this->getParent($context)) {
            $parent->displayBlock($name, $context, array_merge($this->blocks, $blocks), false);
        } else {
            @trigger_error(sprintf('Silent display of undefined block "%s" in template "%s" is deprecated since version 1.29 and will throw an exception in 2.0. Use the "block(\'%s\') is defined" expression to test for block existence.', $name, $this->getTemplateName(), $name), E_USER_DEPRECATED);
        }
    }
    public function renderParentBlock($name, array $context, array $blocks = [])
    {
        ob_start();
        $this->displayParentBlock($name, $context, $blocks);
        return ob_get_clean();
    }
    public function renderBlock($name, array $context, array $blocks = [], $useBlocks = true)
    {
        ob_start();
        $this->displayBlock($name, $context, $blocks, $useBlocks);
        return ob_get_clean();
    }
    public function hasBlock($name, array $context = null, array $blocks = [])
    {
        if (null === $context) {
            @trigger_error('The ' . __METHOD__ . ' method is internal and should never be called; calling it directly is deprecated since version 1.28 and won\'t be possible anymore in 2.0.', E_USER_DEPRECATED);
            return isset($this->blocks[(string) $name]);
        }
        if (isset($blocks[$name])) {
            return $blocks[$name][0] instanceof self;
        }
        if (isset($this->blocks[$name])) {
            return true;
        }
        if (false !== $parent = $this->getParent($context)) {
            return $parent->hasBlock($name, $context);
        }
        return false;
    }
    public function getBlockNames(array $context = null, array $blocks = [])
    {
        if (null === $context) {
            @trigger_error('The ' . __METHOD__ . ' method is internal and should never be called; calling it directly is deprecated since version 1.28 and won\'t be possible anymore in 2.0.', E_USER_DEPRECATED);
            return array_keys($this->blocks);
        }
        $names = array_merge(array_keys($blocks), array_keys($this->blocks));
        if (false !== $parent = $this->getParent($context)) {
            $names = array_merge($names, $parent->getBlockNames($context));
        }
        return array_unique($names);
    }
    protected function loadTemplate($template, $templateName = null, $line = null, $index = null)
    {
        try {
            if (is_array($template)) {
                return $this->env->resolveTemplate($template);
            }
            if ($template instanceof self) {
                return $template;
            }
            if ($template instanceof Twig_TemplateWrapper) {
                return $template;
            }
            return $this->env->loadTemplate($template, $index);
        } catch (Twig_Error $e) {
            if (!$e->getSourceContext()) {
                $e->setSourceContext($templateName ? new Twig_Source('', $templateName) : $this->getSourceContext());
            }
            if ($e->getTemplateLine()) {
                throw $e;
            }
            if (!$line) {
                $e->guess();
            } else {
                $e->setTemplateLine($line);
            }
            throw $e;
        }
    }
    public function getBlocks()
    {
        return $this->blocks;
    }
    public function display(array $context, array $blocks = [])
    {
        $this->displayWithErrorHandling($this->env->mergeGlobals($context), array_merge($this->blocks, $blocks));
    }
    public function render(array $context)
    {
        $level = ob_get_level();
        ob_start();
        try {
            $this->display($context);
        } catch (Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean();
    }
    protected function displayWithErrorHandling(array $context, array $blocks = [])
    {
        try {
            $this->doDisplay($context, $blocks);
        } catch (Twig_Error $e) {
            if (!$e->getSourceContext()) {
                $e->setSourceContext($this->getSourceContext());
            }
            if (false === $e->getTemplateLine()) {
                $e->setTemplateLine(-1);
                $e->guess();
            }
            throw $e;
        } catch (Exception $e) {
            throw new Twig_Error_Runtime(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $this->getSourceContext(), $e);
        }
    }
    abstract protected function doDisplay(array $context, array $blocks = []);
    final protected function getContext($context, $item, $ignoreStrictCheck = false)
    {
        if (!array_key_exists($item, $context)) {
            if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                return;
            }
            throw new Twig_Error_Runtime(sprintf('Variable "%s" does not exist.', $item), -1, $this->getSourceContext());
        }
        return $context[$item];
    }
    protected function getAttribute($object, $item, array $arguments = [], $type = self::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        if (self::METHOD_CALL !== $type) {
            $arrayItem = is_bool($item) || is_float($item) ? (int) $item : $item;
            if ((is_array($object) && (isset($object[$arrayItem]) || array_key_exists($arrayItem, $object)))
                || ($object instanceof ArrayAccess && isset($object[$arrayItem]))
            ) {
                if ($isDefinedTest) {
                    return true;
                }
                return $object[$arrayItem];
            }
            if (self::ARRAY_CALL === $type || !is_object($object)) {
                if ($isDefinedTest) {
                    return false;
                }
                if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                    return;
                }
                if ($object instanceof ArrayAccess) {
                    $message = sprintf('Key "%s" in object with ArrayAccess of class "%s" does not exist.', $arrayItem, get_class($object));
                } elseif (is_object($object)) {
                    $message = sprintf('Impossible to access a key "%s" on an object of class "%s" that does not implement ArrayAccess interface.', $item, get_class($object));
                } elseif (is_array($object)) {
                    if (empty($object)) {
                        $message = sprintf('Key "%s" does not exist as the array is empty.', $arrayItem);
                    } else {
                        $message = sprintf('Key "%s" for array with keys "%s" does not exist.', $arrayItem, implode(', ', array_keys($object)));
                    }
                } elseif (self::ARRAY_CALL === $type) {
                    if (null === $object) {
                        $message = sprintf('Impossible to access a key ("%s") on a null variable.', $item);
                    } else {
                        $message = sprintf('Impossible to access a key ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
                    }
                } elseif (null === $object) {
                    $message = sprintf('Impossible to access an attribute ("%s") on a null variable.', $item);
                } else {
                    $message = sprintf('Impossible to access an attribute ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
                }
                throw new Twig_Error_Runtime($message, -1, $this->getSourceContext());
            }
        }
        if (!is_object($object)) {
            if ($isDefinedTest) {
                return false;
            }
            if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                return;
            }
            if (null === $object) {
                $message = sprintf('Impossible to invoke a method ("%s") on a null variable.', $item);
            } elseif (is_array($object)) {
                $message = sprintf('Impossible to invoke a method ("%s") on an array.', $item);
            } else {
                $message = sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
            }
            throw new Twig_Error_Runtime($message, -1, $this->getSourceContext());
        }
        if (self::METHOD_CALL !== $type && !$object instanceof self) {
            if (isset($object->$item) || array_key_exists((string) $item, $object)) {
                if ($isDefinedTest) {
                    return true;
                }
                if ($this->env->hasExtension('Twig_Extension_Sandbox')) {
                    $this->env->getExtension('Twig_Extension_Sandbox')->checkPropertyAllowed($object, $item);
                }
                return $object->$item;
            }
        }
        $class = get_class($object);
        if (!isset(self::$cache[$class])) {
            if ($object instanceof self) {
                $ref = new ReflectionClass($class);
                $methods = [];
                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
                    if ('getenvironment' !== strtolower($refMethod->name)) {
                        $methods[] = $refMethod->name;
                    }
                }
            } else {
                $methods = get_class_methods($object);
            }
            sort($methods);
            $cache = [];
            foreach ($methods as $method) {
                $cache[$method] = $method;
                $cache[$lcName = strtolower($method)] = $method;
                if ('g' === $lcName[0] && 0 === strpos($lcName, 'get')) {
                    $name = substr($method, 3);
                    $lcName = substr($lcName, 3);
                } elseif ('i' === $lcName[0] && 0 === strpos($lcName, 'is')) {
                    $name = substr($method, 2);
                    $lcName = substr($lcName, 2);
                } else {
                    continue;
                }
                if ($name) {
                    if (!isset($cache[$name])) {
                        $cache[$name] = $method;
                    }
                    if (!isset($cache[$lcName])) {
                        $cache[$lcName] = $method;
                    }
                }
            }
            self::$cache[$class] = $cache;
        }
        $call = false;
        if (isset(self::$cache[$class][$item])) {
            $method = self::$cache[$class][$item];
        } elseif (isset(self::$cache[$class][$lcItem = strtolower($item)])) {
            $method = self::$cache[$class][$lcItem];
        } elseif (isset(self::$cache[$class]['__call'])) {
            $method = $item;
            $call = true;
        } else {
            if ($isDefinedTest) {
                return false;
            }
            if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                return;
            }
            throw new Twig_Error_Runtime(sprintf('Neither the property "%1$s" nor one of the methods "%1$s()", "get%1$s()"/"is%1$s()" or "__call()" exist and have public access in class "%2$s".', $item, $class), -1, $this->getSourceContext());
        }
        if ($isDefinedTest) {
            return true;
        }
        if ($this->env->hasExtension('Twig_Extension_Sandbox')) {
            $this->env->getExtension('Twig_Extension_Sandbox')->checkMethodAllowed($object, $method);
        }
        try {
            if (!$arguments) {
                $ret = $object->$method();
            } else {
                $ret = call_user_func_array([$object, $method], $arguments);
            }
        } catch (BadMethodCallException $e) {
            if ($call && ($ignoreStrictCheck || !$this->env->isStrictVariables())) {
                return;
            }
            throw $e;
        }
        if ($object instanceof Twig_TemplateInterface) {
            $self = $object->getTemplateName() === $this->getTemplateName();
            $message = sprintf('Calling "%s" on template "%s" from template "%s" is deprecated since version 1.28 and won\'t be supported anymore in 2.0.', $item, $object->getTemplateName(), $this->getTemplateName());
            if ('renderBlock' === $method || 'displayBlock' === $method) {
                $message .= sprintf(' Use block("%s"%s) instead).', $arguments[0], $self ? '' : ', template');
            } elseif ('hasBlock' === $method) {
                $message .= sprintf(' Use "block("%s"%s) is defined" instead).', $arguments[0], $self ? '' : ', template');
            } elseif ('render' === $method || 'display' === $method) {
                $message .= sprintf(' Use include("%s") instead).', $object->getTemplateName());
            }
            @trigger_error($message, E_USER_DEPRECATED);
            return '' === $ret ? '' : new Twig_Markup($ret, $this->env->getCharset());
        }
        return $ret;
    }
}
class_alias('Twig_Template', 'Twig\Template', false);
