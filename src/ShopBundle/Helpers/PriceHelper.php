<?php

namespace ShopBundle\Helpers;
use Symfony\Component\HttpFoundation\Session\Session;

class PriceHelper
{
    public static function parse_price($price,$count=1,$discount=0)
    {
        if(($price != '' && $price != 0)){

            $session = new Session();

            $currency=$session->get('currency');

            $result="";

            $delim=";";

            $price=preg_replace('/[^0-9.,;]/','',$price);

            $price=preg_split('/[;]/', $price, -1, PREG_SPLIT_NO_EMPTY);

            if(count($price)>0){

                $discount=intval($discount);

                foreach($price as $key=>$val){
                    $new_price=($val*$count);

                    if($discount>0)
                        $new_price=$new_price-(($new_price/100)*$discount);

                    $new_price=$new_price/$currency['rate'];

                    $result.=(!empty($result)?$delim:"").number_format($new_price, 2, '.', ',').' '.$currency['currency'];
                }
            }

        }
        else
            $result=/*Yii::app()->params['price_not_defined']*/'Цена не указана.';


        return $result;

    }

    public static function count_sum($price,$count,$discount=0)
    {
        $result=self::parse_price($price,$count,$discount);
        $result=($result=='Цена не указана.'?'-':$result);
        return $result;
    }

}
