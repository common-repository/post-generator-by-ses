<?php
class Twig_Extension_Sandbox extends Twig_Extension
{
    protected $sandboxedGlobally;
    protected $sandboxed;
    protected $policy;
    public function __construct(Twig_Sandbox_SecurityPolicyInterface $policy, $sandboxed = false)
    {
        $this->policy = $policy;
        $this->sandboxedGlobally = $sandboxed;
    }
    public function getTokenParsers()
    {
        return [new Twig_TokenParser_Sandbox()];
    }
    public function getNodeVisitors()
    {
        return [new Twig_NodeVisitor_Sandbox()];
    }
    public function enableSandbox()
    {
        $this->sandboxed = true;
    }
    public function disableSandbox()
    {
        $this->sandboxed = false;
    }
    public function isSandboxed()
    {
        return $this->sandboxedGlobally || $this->sandboxed;
    }
    public function isSandboxedGlobally()
    {
        return $this->sandboxedGlobally;
    }
    public function setSecurityPolicy(Twig_Sandbox_SecurityPolicyInterface $policy)
    {
        $this->policy = $policy;
    }
    public function getSecurityPolicy()
    {
        return $this->policy;
    }
    public function checkSecurity($tags, $filters, $functions)
    {
        if ($this->isSandboxed()) {
            $this->policy->checkSecurity($tags, $filters, $functions);
        }
    }
    public function checkMethodAllowed($obj, $method)
    {
        if ($this->isSandboxed()) {
            $this->policy->checkMethodAllowed($obj, $method);
        }
    }
    public function checkPropertyAllowed($obj, $method)
    {
        if ($this->isSandboxed()) {
            $this->policy->checkPropertyAllowed($obj, $method);
        }
    }
    public function ensureToStringAllowed($obj)
    {
        if ($this->isSandboxed() && is_object($obj)) {
            $this->policy->checkMethodAllowed($obj, '__toString');
        }
        return $obj;
    }
    public function getName()
    {
        return 'sandbox';
    }
}
class_alias('Twig_Extension_Sandbox', 'Twig\Extension\SandboxExtension', false);
