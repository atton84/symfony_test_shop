<?php

namespace ShopBundle\Twig;


class ImageHelperExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('getItemThumbnail', array($this, 'getItemThumbnail')),
            new \Twig_SimpleFilter('getItemImage', array($this, 'getItemImage')),
            new \Twig_SimpleFilter('getCategoryThumbnail', array($this, 'getCategoryThumbnail')),
        );
    }


    private function getImage($file,$dir='/pictures/chapters/',$show_thumbnail=true){

        $result="";

        $dir_path=explode('/',$dir);
        $dir=implode('/',array_filter($dir_path)).'/';

        if(isset($file)&&isset($dir))
            if(is_dir($dir.'thumbs/')&&is_file($dir.'thumbs/'.$file)&&$show_thumbnail)
                $result=$dir.'thumbs/'.$file;
            else if(is_file($dir.$file))
                $result=$dir.$file;

        return $result;

    }

    public function getItemThumbnail($file,$dir='/pictures/chapters/')
    {
        $result="pictures/noimage/no-image.gif";

        $image=self::getImage($file,$dir);
        $result=!empty($image)?$image:$result;
        $result=(!empty($result)?"/":"").$result;

        return $result;

    }

    public function getItemImage($file,$dir='/pictures/chapters/')
    {
        $result="pictures/noimage/no-image.gif";

        $image=self::getImage($file,$dir,false);
        $result=!empty($image)?$image:$result;
        $result=(!empty($result)?"/":"").$result;

        return $result;
    }


    public function getCategoryThumbnail($file,$dir='/pictures/chapters/')
    {
        $result="";

        $image=self::getImage($file,$dir);
        $result=!empty($image)?$image:$result;
        $result=(!empty($result)?"/":"").$result;

        return $result;
    }

    public function getName()
    {
        return 'image_helper_extension';
    }
}
