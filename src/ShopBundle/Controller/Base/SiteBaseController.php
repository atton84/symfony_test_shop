<?php

namespace ShopBundle\Controller\Base;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ShopBundle\Model\Site;
/*use Doctrine\ORM\Query;*/


class SiteBaseController extends Controller
{

    public $menu=array();
    public $breadcrumbs=array();
    public $url_without_pages;
    public $prev_url;
    public $url_url_referer;

    protected $render_vars=array();
    public $model;
    public $settings;
    public $root_categories;
    public $assetsBaseUrl;

    public $title;
    public $keywords;
    public $description;
    public $doctrine;
    public $item_per_page=15;

    public function indexAction(){

        $base_params=array();

        $this->doctrine=$this->getDoctrine();

        $this->url_without_pages=preg_replace("/(\/page.*)$/iu","/",$_SERVER['REQUEST_URI']);
        $this->url_without_pages=implode('/',array_filter(explode('/',$this->url_without_pages)));

        $this->prev_url=trim($this->url_without_pages,'/');
        $this->prev_url='/'.implode('/',array_filter(explode('/',$this->prev_url)));

        $this->prev_url=substr( $this->prev_url,0,strrpos( $this->prev_url,'/'));



        $this->settings=Site\VSettings::model($this->doctrine)->getSettings();

        $this->root_categories=Site\VCatalog::model($this->doctrine)->getRootCategories();

        $main_menu=Site\VMenu::model($this->doctrine)->getMenuById($this->settings['main_menu']);

        $trademarks=Site\VCatalog::model($this->doctrine)->getTrademarks();

        $tradegroups=Site\VCatalog::model($this->doctrine)->getTradegroups();

        $rand_items=Site\VCatalog::model($this->doctrine)->getRandomItems(6);

        $contacts_homepage=Site\VPages::model($this->doctrine)->getPageContent(array('alias'=>'contacts[main]'));
        $contacts_homepage1=Site\VPages::model($this->doctrine)->getPageContent(array('alias'=>'contacts'));

        $contacts_homepage['contacts_id']=$contacts_homepage1['id'];

        $slideshow_frames=Site\VSlideshow::model($this->doctrine)->getSlideshowContent();

        $about_homepage=Site\VPages::model($this->doctrine)->getPageContent(array('alias'=>'about[main]'));

        $news_block=Site\VNews::model($this->doctrine)->getLastNews(1);

        $currency_model=Site\VCurrency::model($this->doctrine);

        $currency=$currency_model->getCurrencyList();

        $current_currency=$currency_model->getCurrentCurrency();

        if(!isset($current_currency)){
            Site\VCurrency::model($this->doctrine)->setCurrency($currency[$this->settings['default_currency']]);
        }

        $actions_block=Site\VActions::model($this->doctrine)->getLastActions(1);

        $this->render_vars=array(
            'url_without_pages'=>$this->url_without_pages,
            'url_referer'=>$this->url_url_referer,
            'prev_url'=>$this->prev_url,
            'root_categories'=>$this->root_categories,
            'contacts_homepage'=>$contacts_homepage,
            'cur_currency'=>$current_currency,
            'currency_list'=> $currency,
            'main_menu'=>$main_menu,
            'trademarks'=>$trademarks,
            'tradegroups'=>$tradegroups,
            'slideshow_frames'=>$slideshow_frames,
            'about_homepage'=>$about_homepage,
            'news_block'=>$news_block,
            "rand_items"=>$rand_items,
            'actions_block'=>$actions_block);

        //$root_categories=$doctrine->getRepository('ShopBundle:Category')->getRootCategories();
        return;
    }

    private function setBreadcrumbs($params){
        $breadcrumb="";
        foreach($params as $k=>$v){
            $breadcrumb.='/'.$k.(isset($v['id'])&&is_numeric($v['id'])?'-'.$v['id']:'');
            if($v['name'])
                $this->breadcrumbs[" ".$v['name']]=$breadcrumb;
        }
    }

    private function setMetaTags($params){

        $this->title="";
        $this->keywords="";
        $this->description="";

        foreach($params as $k=>$v){
            if(isset($v['meta_title']))
                $this->title.=(!empty($this->title)?' | ':'').$v['meta_title'];
            if(isset($v['meta_keywords']))
                $this->keywords.=(!empty($this->keywords)?', ':'').$v['meta_keywords'];
            if(isset($v['meta_description']))
                $this->description.=(!empty($this->description)?', ':'').$v['meta_description'];
        }

    }

    private function getPageData(){

        if(isset($this->model)){

            $url=preg_replace('/(\/page\/).+/','',$_SERVER['REQUEST_URI']);
            $arr=array_filter(explode('/',$url));

            $params=array();
            foreach($arr as $k=>$v){
                $m=array();
                preg_match('/^([^_-]*)_?([^-]*)-?([\d]*)$/',$v,$m);
                if(count($m)>0)
                    $params[$v]=array('type'=>$m[1],'translit'=>$m[2],'id'=>$m[3]);
                else
                    $params[$v]=array('type'=>$v);
            }

            $page_data=$this->model->getPageData($params);

            //var_dump($page_data);

            $this->setBreadcrumbs($page_data);
            $this->setMetaTags($page_data);

            //echo $this->keywords;

        }
    }

    public function render($view, array $parameters = Array(), \Symfony\Component\HttpFoundation\Response $response = NULL){

        $this->getPageData();

        $this->render_vars=array_merge($this->render_vars,array(
            'title'=>$this->title,
            'keywords'=>$this->keywords,
            'description'=>$this->description,
            'breadcrumbs'=>array_merge(array('Главная'=>'/'),$this->breadcrumbs),
        ));

        return parent::render($view, array_merge($this->render_vars,$parameters),$response);
    }
}
