<?php
namespace Pg\ProductEnricher;
use Pg\Product;
interface ProductEnricherInterface
{
    public function enrich(Product $pg);
}
