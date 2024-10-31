<?php
interface Twig_TemplateInterface
{
    const ANY_CALL = 'any';
    const ARRAY_CALL = 'array';
    const METHOD_CALL = 'method';
    public function render(array $context);
    public function display(array $context, array $blocks = []);
    public function getEnvironment();
}
