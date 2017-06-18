<?php

namespace ShopBundle\Controller;

use ShopBundle\Controller\Base\SiteBaseController;
use ShopBundle\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;

class SiteController extends SiteBaseController
{
    public function indexAction()
    {
        parent::indexAction();
        $this->model=Site\VPages::model($this->doctrine);
        $main_page_content=$this->model->getPageContent(array('id'=>$this->settings['main_page']));

     return $this->render('ShopBundle:site:index.html.twig',array('main_page_content'=>$main_page_content));
    }

    public function pagesAction($id)
    {
        parent::indexAction();

        $this->model=Site\VPages::model($this->doctrine);
        $page_content=$this->model->getPageContent(array('id'=>$id));

        return $this->render('ShopBundle:site:catalog_page.html.twig',array('page_content'=>$page_content));
    }

    public function mainCategoriesAction($id){

        parent::indexAction();

        $this->model=Site\VCatalog::model($this->doctrine);

        $categories= $this->model->getMainCategories($id);

        $root_cat_alias=array_shift(array_keys($categories));

        if($root_cat_alias){
            $view='catalog_'.$root_cat_alias;
            return $this->render('ShopBundle:site:'.$view.'.html.twig',array('content'=>$categories));
        }else
            return $this->render('ShopBundle:site:catalog_item_list.html.twig',array('content'=>array(),'pages'=>array()));
    }

    public function getSecondaryCategoriesAction($id,$page=1){

        parent::indexAction();

        $this->model=Site\VCatalog::model($this->doctrine);

        $categories= $this->model->getSecondaryCategories($id,$page,$this->item_per_page);

        return $this->render('ShopBundle:site:catalog_item_list.html.twig',array('content'=>$categories,'pages'=>ceil($categories['pages']/$this->item_per_page),'current_page'=>$page));
    }

    public function getSecondLevelAction($parent_id,$second_id,$page=1){

        parent::indexAction();

        $this->model=Site\VCatalog::model($this->doctrine);
        $categories=$this->model->getLinkedCategoryContent($parent_id,$second_id,$page,$this->item_per_page);
        return $this->render('ShopBundle:site:catalog_item_list.html.twig',array('content'=>$categories,'pages'=>ceil($categories['pages']/$this->item_per_page),'current_page'=>$page));
    }

    public function getPriceGroupAction($id,$page=1){

        parent::indexAction();

        $this->model=Site\VCatalog::model($this->doctrine);

        $categories= $this->model->getPriceGroup($id,$page,$this->item_per_page);

        return $this->render('ShopBundle:site:catalog_item_list.html.twig',array('content'=>$categories,'pages'=>ceil($categories['pages']/$this->item_per_page),'current_page'=>$page));
    }


    public function itemAction($item_id,$parent_id=null,$second_id=null)
    {
        parent::indexAction();

        $this->model=Site\VCatalog::model($this->doctrine);
        $item=$this->model->getItemContent($item_id,$parent_id,$second_id);

        return $this->render('ShopBundle:site:catalog_item.html.twig',array('content'=>$item));
    }

    public function newsAction($id='all'){

        parent::indexAction();

        $this->model=Site\VNews::model($this->doctrine);
        $news=$this->model->getNewsContent($id);

        return $this->render('ShopBundle:site:news_index.html.twig',array('content'=>array('news'=>$news,'id'=>$id)));
    }

    public function actionsAction($id='all')
    {
        parent::indexAction();

        $this->model=Site\VActions::model($this->doctrine);
        $actions=$this->model->getActionsContent($id);

        return $this->render('ShopBundle:site:actions_index.html.twig',array('content'=>array('actions'=>$actions,'id'=>$id)));
    }

    public function sitemapAction()
    {
        parent::indexAction();

        $this->model=Site\VSitemap::model($this->doctrine);
        $sitemap=$this->model->getSitemapContent();

        return $this->render('ShopBundle:site:sitemap_index.html.twig',array('content'=>array('sitemap'=>$sitemap)));
    }

    public function searchAction($page=1){

        parent::indexAction();

        $request = $this->get('request');
        $session=$request->getSession();
        $session->start();

        if ($request->getMethod() == 'POST') {
            $name=$request->get("name");

            $trigram=\ShopBundle\Helpers\TrigramHelper::createTrigram($name);

            $session->set('search',array('value'=>$name,'trigram'=>$trigram));
        }elseif($session->has('search')){
            $session=$session->get('search');

            $trigram=$session['trigram'];
            $name=$session['value'];
        }

        $this->model=Site\VCatalog::model($this->doctrine);
        $items=$this->model->searchItemsByTrigram($trigram,$page);

        return $this->render('ShopBundle:site:catalog_item_list.html.twig',array('content'=>$items,'pages'=>ceil($items['pages']/$this->item_per_page),'current_page'=>$page));

    }


    public function change_currencyAction()
    {
        parent::indexAction();

        $request = $this->get('request');

        if($request->getMethod() == 'POST'){
            $money=$request->get("money");

            if($money){

                $currency_model=Site\VCurrency::model($this->doctrine);

                $currency_model->setCurrency($money);

                $items=$request->get("prices");
                $counts=$request->get("counts");

                $currency=$currency_model->getCurrencyByName($money);

                $catalog=Site\VCatalog::model($this->doctrine);
                $new_prices=$catalog->recalculatePrices($items,$counts);


                header('Content-type: application/json');

                return new JsonResponse($new_prices);

            }
        }

    }


}
