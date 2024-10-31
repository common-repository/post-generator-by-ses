<?php
namespace Pg;
class ProductEnricher
{
    private $enrichers;
    private $errors;
    public function __construct(array $fillers)
    {
        $this->enrichers = $fillers;
        $this->enrichers = $fillers;
    }
    public function enrich(Product $product)
    {
        $this->errors = [];
        foreach ($this->enrichers as $enricher) {
            try {
                $enricher->enrich($product);
            } catch (\Exception $e) {
                $this->errors[get_class($enricher)] = $e->getMessage();
            }
        }
    }
    public function getErrors()
    {
        return $this->errors;
    }
}
