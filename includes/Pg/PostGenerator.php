<?php
namespace Pg;
use Twig_Environment;
class PostGenerator
{
    private $enricher;
    private $twig;
    private $templateFiles;
    private $errors;
    public function __construct(ProductEnricher $enricher, Twig_Environment $twig, array $templateFiles)
    {
        $this->enricher = $enricher;
        $this->twig = $twig;
        $this->templateFiles = $templateFiles;
    }
    public function renderTemplateWithProductObjects(array $urls, $template)
    {
        $this->errors = [];
        $products = [];
        $urls = array_filter(array_map(function ($e) {
            return trim($e, "\n ");
        }, $urls));
        foreach ($urls as $url) {
            try {
                $p = new Product();
                $p->setUrlInput($url);
                $this->enricher->enrich($p);
                $this->errors[$url] = $this->enricher->getErrors();
                $products[] = $p;
            } catch (\Exception $e) {
                echo $e;
            }
        }
        return $this->twig->render($template, [
            'products' => $products,
            'pg' => $this
        ]);
    }
    public function getTemplates()
    {
        return array_map('basename', $this->templateFiles);
    }
    public function getErrors()
    {
        return array_filter($this->errors);
    }
}
