<?php
namespace Pg;
class PgTwigExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('test', function () {
                return 'test function';
            }),
            new \Twig_SimpleFunction('debug', function ($data) {
                return '<pre>' . print_r($data, true) . '</pre>';
            }),
        ];
    }
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('orderProducts', function (array $products) {
                return $products;
            }),
            new \Twig_SimpleFilter('cardinal', function ($number) {
                $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
                if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
                    return $number . 'th';
                } else {
                    return $number . $ends[$number % 10];
                }
            }),
            new \Twig_SimpleFilter('longest', function (array $strings) {
                if (empty($strings)) {
                    return '';
                }
                $lengths = array_map('strlen', $strings);
                $index = array_search(max($lengths), $lengths);
                return $strings[$index];
            }),
        ];
    }
    public function getName()
    {
        return 'twigExt';
    }
}
