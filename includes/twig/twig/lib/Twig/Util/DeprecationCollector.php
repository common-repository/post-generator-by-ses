<?php
class Twig_Util_DeprecationCollector
{
    private $twig;
    private $deprecations;
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }
    public function collectDir($dir, $ext = '.twig')
    {
        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '{' . preg_quote($ext) . '$}'
        );
        return $this->collect(new Twig_Util_TemplateDirIterator($iterator));
    }
    public function collect(Traversable $iterator)
    {
        $this->deprecations = [];
        set_error_handler([$this, 'errorHandler']);
        foreach ($iterator as $name => $contents) {
            try {
                $this->twig->parse($this->twig->tokenize(new Twig_Source($contents, $name)));
            } catch (Twig_Error_Syntax $e) {
            }
        }
        restore_error_handler();
        $deprecations = $this->deprecations;
        $this->deprecations = [];
        return $deprecations;
    }
    public function errorHandler($type, $msg)
    {
        if (E_USER_DEPRECATED === $type) {
            $this->deprecations[] = $msg;
        }
    }
}
class_alias('Twig_Util_DeprecationCollector', 'Twig\Util\DeprecationCollector', false);
