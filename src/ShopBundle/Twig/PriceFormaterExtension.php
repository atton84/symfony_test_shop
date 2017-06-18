<?php

namespace ShopBundle\Twig;

use ShopBundle\Helpers;

class PriceFormaterExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('parse_price', array($this, 'parse_priceFilter')),
            new \Twig_SimpleFilter('count_sum', array($this, 'count_sumFilter')),
        );
    }

    public function parse_priceFilter($price,$count=1,$discount=0){

        return Helpers\PriceHelper::parse_price($price,$count,$discount);
    }

    public function count_sumFilter($price,$count,$discount=0){

        return Helpers\PriceHelper::count_sum($price,$count,$discount);
    }

    public function getName()
    {
        return 'price_formater_extension';
    }
}