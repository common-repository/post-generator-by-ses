<?php
abstract class Twig_Node_Expression_Call extends Twig_Node_Expression
{
    private $reflector;
    protected function compileCallable(Twig_Compiler $compiler)
    {
        $closingParenthesis = false;
        if ($this->hasAttribute('callable') && $callable = $this->getAttribute('callable')) {
            if (is_string($callable) && false === strpos($callable, '::')) {
                $compiler->raw($callable);
            } else {
                list($r, $callable) = $this->reflectCallable($callable);
                if ($r instanceof ReflectionMethod && is_string($callable[0])) {
                    if ($r->isStatic()) {
                        $compiler->raw(sprintf('%s::%s', $callable[0], $callable[1]));
                    } else {
                        $compiler->raw(sprintf('$this->env->getRuntime(\'%s\')->%s', $callable[0], $callable[1]));
                    }
                } elseif ($r instanceof ReflectionMethod && $callable[0] instanceof Twig_ExtensionInterface) {
                    $compiler->raw(sprintf('$this->env->getExtension(\'%s\')->%s', get_class($callable[0]), $callable[1]));
                } else {
                    $type = ucfirst($this->getAttribute('type'));
                    $compiler->raw(sprintf('call_user_func_array($this->env->get%s(\'%s\')->getCallable(), array', $type, $this->getAttribute('name')));
                    $closingParenthesis = true;
                }
            }
        } else {
            $compiler->raw($this->getAttribute('thing')->compile());
        }
        $this->compileArguments($compiler);
        if ($closingParenthesis) {
            $compiler->raw(')');
        }
    }
    protected function compileArguments(Twig_Compiler $compiler)
    {
        $compiler->raw('(');
        $first = true;
        if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment')) {
            $compiler->raw('$this->env');
            $first = false;
        }
        if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context')) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('$context');
            $first = false;
        }
        if ($this->hasAttribute('arguments')) {
            foreach ($this->getAttribute('arguments') as $argument) {
                if (!$first) {
                    $compiler->raw(', ');
                }
                $compiler->string($argument);
                $first = false;
            }
        }
        if ($this->hasNode('node')) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->subcompile($this->getNode('node'));
            $first = false;
        }
        if ($this->hasNode('arguments')) {
            $callable = $this->hasAttribute('callable') ? $this->getAttribute('callable') : null;
            $arguments = $this->getArguments($callable, $this->getNode('arguments'));
            foreach ($arguments as $node) {
                if (!$first) {
                    $compiler->raw(', ');
                }
                $compiler->subcompile($node);
                $first = false;
            }
        }
        $compiler->raw(')');
    }
    protected function getArguments($callable, $arguments)
    {
        $callType = $this->getAttribute('type');
        $callName = $this->getAttribute('name');
        $parameters = [];
        $named = false;
        foreach ($arguments as $name => $node) {
            if (!is_int($name)) {
                $named = true;
                $name = $this->normalizeName($name);
            } elseif ($named) {
                throw new Twig_Error_Syntax(sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $callType, $callName), $this->getTemplateLine());
            }
            $parameters[$name] = $node;
        }
        $isVariadic = $this->hasAttribute('is_variadic') && $this->getAttribute('is_variadic');
        if (!$named && !$isVariadic) {
            return $parameters;
        }
        if (!$callable) {
            if ($named) {
                $message = sprintf('Named arguments are not supported for %s "%s".', $callType, $callName);
            } else {
                $message = sprintf('Arbitrary positional arguments are not supported for %s "%s".', $callType, $callName);
            }
            throw new LogicException($message);
        }
        $callableParameters = $this->getCallableParameters($callable, $isVariadic);
        $arguments = [];
        $names = [];
        $missingArguments = [];
        $optionalArguments = [];
        $pos = 0;
        foreach ($callableParameters as $callableParameter) {
            $names[] = $name = $this->normalizeName($callableParameter->name);
            if (array_key_exists($name, $parameters)) {
                if (array_key_exists($pos, $parameters)) {
                    throw new Twig_Error_Syntax(sprintf('Argument "%s" is defined twice for %s "%s".', $name, $callType, $callName), $this->getTemplateLine());
                }
                if (count($missingArguments)) {
                    throw new Twig_Error_Syntax(sprintf(
                        'Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".',
                        $name,
                        $callType,
                        $callName,
                        implode(', ', $names),
                        count($missingArguments) > 1 ? 's' : '',
                        implode('", "', $missingArguments)
                    ), $this->getTemplateLine());
                }
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $parameters[$name];
                unset($parameters[$name]);
                $optionalArguments = [];
            } elseif (array_key_exists($pos, $parameters)) {
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $parameters[$pos];
                unset($parameters[$pos]);
                $optionalArguments = [];
                ++$pos;
            } elseif ($callableParameter->isDefaultValueAvailable()) {
                $optionalArguments[] = new Twig_Node_Expression_Constant($callableParameter->getDefaultValue(), -1);
            } elseif ($callableParameter->isOptional()) {
                if (empty($parameters)) {
                    break;
                } else {
                    $missingArguments[] = $name;
                }
            } else {
                throw new Twig_Error_Syntax(sprintf('Value for argument "%s" is required for %s "%s".', $name, $callType, $callName), $this->getTemplateLine());
            }
        }
        if ($isVariadic) {
            $arbitraryArguments = new Twig_Node_Expression_Array([], -1);
            foreach ($parameters as $key => $value) {
                if (is_int($key)) {
                    $arbitraryArguments->addElement($value);
                } else {
                    $arbitraryArguments->addElement($value, new Twig_Node_Expression_Constant($key, -1));
                }
                unset($parameters[$key]);
            }
            if ($arbitraryArguments->count()) {
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $arbitraryArguments;
            }
        }
        if (!empty($parameters)) {
            $unknownParameter = null;
            foreach ($parameters as $parameter) {
                if ($parameter instanceof Twig_Node) {
                    $unknownParameter = $parameter;
                    break;
                }
            }
            throw new Twig_Error_Syntax(sprintf(
                'Unknown argument%s "%s" for %s "%s(%s)".',
                count($parameters) > 1 ? 's' : '',
                implode('", "', array_keys($parameters)),
                $callType,
                $callName,
                implode(', ', $names)
            ), $unknownParameter ? $unknownParameter->getTemplateLine() : $this->getTemplateLine());
        }
        return $arguments;
    }
    protected function normalizeName($name)
    {
        return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $name));
    }
    private function getCallableParameters($callable, $isVariadic)
    {
        list($r) = $this->reflectCallable($callable);
        if (null === $r) {
            return [];
        }
        $parameters = $r->getParameters();
        if ($this->hasNode('node')) {
            array_shift($parameters);
        }
        if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment')) {
            array_shift($parameters);
        }
        if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context')) {
            array_shift($parameters);
        }
        if ($this->hasAttribute('arguments') && null !== $this->getAttribute('arguments')) {
            foreach ($this->getAttribute('arguments') as $argument) {
                array_shift($parameters);
            }
        }
        if ($isVariadic) {
            $argument = end($parameters);
            if ($argument && $argument->isArray() && $argument->isDefaultValueAvailable() && [] === $argument->getDefaultValue()) {
                array_pop($parameters);
            } else {
                $callableName = $r->name;
                if ($r instanceof ReflectionMethod) {
                    $callableName = $r->getDeclaringClass()->name . '::' . $callableName;
                }
                throw new LogicException(sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = array()".', $callableName, $this->getAttribute('type'), $this->getAttribute('name')));
            }
        }
        return $parameters;
    }
    private function reflectCallable($callable)
    {
        if (null !== $this->reflector) {
            return $this->reflector;
        }
        if (is_array($callable)) {
            if (!method_exists($callable[0], $callable[1])) {
                return [null, []];
            }
            $r = new ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable) && !$callable instanceof Closure) {
            $r = new ReflectionObject($callable);
            $r = $r->getMethod('__invoke');
            $callable = [$callable, '__invoke'];
        } elseif (is_string($callable) && false !== $pos = strpos($callable, '::')) {
            $class = substr($callable, 0, $pos);
            $method = substr($callable, $pos + 2);
            if (!method_exists($class, $method)) {
                return [null, []];
            }
            $r = new ReflectionMethod($callable);
            $callable = [$class, $method];
        } else {
            $r = new ReflectionFunction($callable);
        }
        return $this->reflector = [$r, $callable];
    }
}
class_alias('Twig_Node_Expression_Call', 'Twig\Node\Expression\CallExpression', false);
