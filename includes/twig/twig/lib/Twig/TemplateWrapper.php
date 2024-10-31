<?php
final class Twig_TemplateWrapper
{
    private $env;
    private $template;
    public function __construct(Twig_Environment $env, Twig_Template $template)
    {
        $this->env = $env;
        $this->template = $template;
    }
    public function render($context = [])
    {
        return $this->template->render($context);
    }
    public function display($context = [])
    {
        $this->template->display($context);
    }
    public function hasBlock($name, $context = [])
    {
        return $this->template->hasBlock($name, $context);
    }
    public function getBlockNames($context = [])
    {
        return $this->template->getBlockNames($context);
    }
    public function renderBlock($name, $context = [])
    {
        $context = $this->env->mergeGlobals($context);
        $level = ob_get_level();
        ob_start();
        try {
            $this->template->displayBlock($name, $context);
        } catch (Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean();
    }
    public function displayBlock($name, $context = [])
    {
        $this->template->displayBlock($name, $this->env->mergeGlobals($context));
    }
    public function getSourceContext()
    {
        return $this->template->getSourceContext();
    }
}
class_alias('Twig_TemplateWrapper', 'Twig\TemplateWrapper', false);
