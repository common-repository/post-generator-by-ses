<?php
class Twig_Loader_Array implements Twig_LoaderInterface, Twig_ExistsLoaderInterface, Twig_SourceContextLoaderInterface
{
    protected $templates = [];
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }
    public function setTemplate($name, $template)
    {
        $this->templates[(string) $name] = $template;
    }
    public function getSource($name)
    {
        @trigger_error(sprintf('Calling "getSource" on "%s" is deprecated since 1.27. Use getSourceContext() instead.', get_class($this)), E_USER_DEPRECATED);
        $name = (string) $name;
        if (!isset($this->templates[$name])) {
            throw new Twig_Error_Loader(sprintf('Template "%s" is not defined.', $name));
        }
        return $this->templates[$name];
    }
    public function getSourceContext($name)
    {
        $name = (string) $name;
        if (!isset($this->templates[$name])) {
            throw new Twig_Error_Loader(sprintf('Template "%s" is not defined.', $name));
        }
        return new Twig_Source($this->templates[$name], $name);
    }
    public function exists($name)
    {
        return isset($this->templates[(string) $name]);
    }
    public function getCacheKey($name)
    {
        $name = (string) $name;
        if (!isset($this->templates[$name])) {
            throw new Twig_Error_Loader(sprintf('Template "%s" is not defined.', $name));
        }
        return $name . ':' . $this->templates[$name];
    }
    public function isFresh($name, $time)
    {
        $name = (string) $name;
        if (!isset($this->templates[$name])) {
            throw new Twig_Error_Loader(sprintf('Template "%s" is not defined.', $name));
        }
        return true;
    }
}
class_alias('Twig_Loader_Array', 'Twig\Loader\ArrayLoader', false);
