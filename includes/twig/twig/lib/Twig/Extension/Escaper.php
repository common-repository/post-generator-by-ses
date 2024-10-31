<?php
class Twig_Extension_Escaper extends Twig_Extension
{
    protected $defaultStrategy;
    public function __construct($defaultStrategy = 'html')
    {
        $this->setDefaultStrategy($defaultStrategy);
    }
    public function getTokenParsers()
    {
        return [new Twig_TokenParser_AutoEscape()];
    }
    public function getNodeVisitors()
    {
        return [new Twig_NodeVisitor_Escaper()];
    }
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('raw', 'twig_raw_filter', ['is_safe' => ['all']]),
        ];
    }
    public function setDefaultStrategy($defaultStrategy)
    {
        if (true === $defaultStrategy) {
            @trigger_error('Using "true" as the default strategy is deprecated since version 1.21. Use "html" instead.', E_USER_DEPRECATED);
            $defaultStrategy = 'html';
        }
        if ('filename' === $defaultStrategy) {
            @trigger_error('Using "filename" as the default strategy is deprecated since version 1.27. Use "name" instead.', E_USER_DEPRECATED);
            $defaultStrategy = 'name';
        }
        if ('name' === $defaultStrategy) {
            $defaultStrategy = ['Twig_FileExtensionEscapingStrategy', 'guess'];
        }
        $this->defaultStrategy = $defaultStrategy;
    }
    public function getDefaultStrategy($name)
    {
        if (!is_string($this->defaultStrategy) && false !== $this->defaultStrategy) {
            return call_user_func($this->defaultStrategy, $name);
        }
        return $this->defaultStrategy;
    }
    public function getName()
    {
        return 'escaper';
    }
}
function twig_raw_filter($string)
{
    return $string;
}
class_alias('Twig_Extension_Escaper', 'Twig\Extension\EscaperExtension', false);
