<?php
@trigger_error('The Twig_Loader_String class is deprecated since version 1.18.1 and will be removed in 2.0. Use Twig_Loader_Array instead or Twig_Environment::createTemplate().', E_USER_DEPRECATED);
class Twig_Loader_String implements Twig_LoaderInterface, Twig_ExistsLoaderInterface, Twig_SourceContextLoaderInterface
{
    public function getSource($name)
    {
        @trigger_error(sprintf('Calling "getSource" on "%s" is deprecated since 1.27. Use getSourceContext() instead.', get_class($this)), E_USER_DEPRECATED);
        return $name;
    }
    public function getSourceContext($name)
    {
        return new Twig_Source($name, $name);
    }
    public function exists($name)
    {
        return true;
    }
    public function getCacheKey($name)
    {
        return $name;
    }
    public function isFresh($name, $time)
    {
        return true;
    }
}
