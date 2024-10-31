<?php
use Psr\Container\ContainerInterface;
class Twig_ContainerRuntimeLoader implements Twig_RuntimeLoaderInterface
{
    private $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    public function load($class)
    {
        if ($this->container->has($class)) {
            return $this->container->get($class);
        }
    }
}
class_alias('Twig_ContainerRuntimeLoader', 'Twig\RuntimeLoader\ContainerRuntimeLoader', false);
