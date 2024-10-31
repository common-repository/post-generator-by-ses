<?php
class Twig_Extension_Debug extends Twig_Extension
{
    public function getFunctions()
    {
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
                        && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump'))
                                    && (false === ini_get('html_errors') || ini_get('html_errors'))
            || 'cli' === PHP_SAPI
        ;
        return [
            new Twig_SimpleFunction('dump', 'twig_var_dump', ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => true, 'needs_environment' => true]),
        ];
    }
    public function getName()
    {
        return 'debug';
    }
}
function twig_var_dump(Twig_Environment $env, $context)
{
    if (!$env->isDebug()) {
        return;
    }
    ob_start();
    $count = func_num_args();
    if (2 === $count) {
        $vars = [];
        foreach ($context as $key => $value) {
            if (!$value instanceof Twig_Template) {
                $vars[$key] = $value;
            }
        }
        var_dump($vars);
    } else {
        for ($i = 2; $i < $count; ++$i) {
            var_dump(func_get_arg($i));
        }
    }
    return ob_get_clean();
}
class_alias('Twig_Extension_Debug', 'Twig\Extension\DebugExtension', false);
