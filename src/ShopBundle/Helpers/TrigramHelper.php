<?php

namespace ShopBundle\Helpers;

class TrigramHelper
{
  public static function createTrigram($text)
  {
    $str=mb_convert_case($text, MB_CASE_UPPER, "UTF-8");
    $str=str_replace(array("[","]","(",")","{","}","^","\\",".","$","|","*","+","?",","),"",$str);
    $str=preg_replace("/[^A-za-zА-Яа-яЁё0-9\s]*$/iu","",$str);

    $trigram="";
    if(strlen($str)>0){
      $parts=split(" ",$str);
      $str="(".implode(")(",$parts).")";
      //$v1=$str;
      foreach($parts as $k1=>$v1){
        if(strlen($v1)>3){
          $trigram.=(!empty($trigram)?",":"")."__".mb_substr($v1, 0, 1,'UTF8')."_,";
          $trigram.=" _".mb_substr($v1, 0, 1,'UTF8').mb_substr($v1, 1, 1,'UTF8')."_,";
          $len=strlen($v1);
          $k=0;
          for($i=2;$i<$len;$i++){
            if(mb_substr($v1, $i, 1,'UTF8')!=''){
              $trigram.=" ".mb_substr($v1, $i-2, 1,'UTF8').mb_substr($v1, $i-1, 1,'UTF8').mb_substr($v1, $i, 1,'UTF8')."_,";
              $k=$i;
            }
          }
          $trigram.=" ".mb_substr($v1, $k-1, 1,'UTF8').mb_substr($v1, $k, 1,'UTF8')."__,";
          $trigram.=" ".mb_substr($v1, $k, 1,'UTF8')."___ ";
        }
      }
    }

    return $trigram;
  }

}
