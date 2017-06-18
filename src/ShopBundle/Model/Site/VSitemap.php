<?php

namespace ShopBundle\Model\Site;
use ShopBundle\Model\Site;
use ShopBundle\Model\Base\BaseModel;

class VSitemap extends BaseModel
{

    public function getSitemapContent(){

        $sitemap=array();

        //$pages=Yii::app()->getModule('pages');

        $settings=Site\VSettings::model($this->doctrine)->getSettings();

       // if($pages){
            $sitemap['pages']['title']='Разделы';
            $sitemap['pages']['content']=Site\VMenu::model($this->doctrine)->getMenuById($settings['main_menu']);/*$pages->getMenuById(Yii::app()->params['main_menu']);*/
        //}

        /*$catalog=Yii::app()->getModule('catalog');

        if($catalog){*/
            $sitemap['catalog']['title']='Каталог';
            $sitemap['catalog']['content']=Site\VCatalog::model($this->doctrine)->getSitemap();
       // }

        return $sitemap;
    }

    /**
     * @static
     * @return VSitemap
     */
    public static function model($doctrine, $className=__CLASS__)
    {
        return parent::model($doctrine,$className);
    }

}
