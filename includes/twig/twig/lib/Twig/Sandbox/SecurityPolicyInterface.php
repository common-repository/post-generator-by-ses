<?php
interface Twig_Sandbox_SecurityPolicyInterface
{
    public function checkSecurity($tags, $filters, $functions);
    public function checkMethodAllowed($obj, $method);
    public function checkPropertyAllowed($obj, $method);
}
class_alias('Twig_Sandbox_SecurityPolicyInterface', 'Twig\Sandbox\SecurityPolicyInterface', false);
