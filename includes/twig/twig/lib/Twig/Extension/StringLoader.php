<?php
class Twig_Extension_StringLoader extends Twig_Extension
{
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('template_from_string', 'twig_template_from_string', ['needs_environment' => true]),
        ];
    }
    public function getName()
    {
        return 'string_loader';
    }
}
function twig_template_from_string(Twig_Environment $env, $template)
{
    return $env->createTemplate((string) $template);
}
class_alias('Twig_Extension_StringLoader', 'Twig\Extension\StringLoaderExtension', false);
