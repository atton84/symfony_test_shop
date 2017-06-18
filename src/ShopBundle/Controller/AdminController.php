<?php

namespace ShopBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use ShopBundle\Controller\Base\AdminBaseController;
use ShopBundle\Model\Admin;

class AdminController extends AdminBaseController
{

    public function indexAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else
            return $this->redirect("/admin/catalog/categories/1");
            //return $this->render('ShopBundle:admin:index.html.twig',array());
    }


    public function categoriesAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model=Admin\VCatalog::model($this->doctrine);

            $page=$request->get("page");
            $offset=$page*($page>1?$this->item_per_page:1);

            $categories= $this->model->getCategories($offset,$this->item_per_page);
            $this->model->renameCats();
            $this->model->renameItems();
            $this->model->renamePages();
            return $this->render('ShopBundle:admin:categories_view.html.twig', array("categories_list" => $categories,"curr_page"=>$page));

        }
    }

    public function categoryViewAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model=Admin\VCatalog::model($this->doctrine);

            $catid=$request->get("catid");
            $page=$request->get("page");
            $offset=$page*($page>1?$this->item_per_page:1);
            $items= $this->model->getCategoryItems($catid,$offset,$this->item_per_page);

            return $this->render('ShopBundle:admin:items_list.html.twig', array("items_list" => $items,"curr_page"=>$page,"catid"=>$catid));

        }
    }

    public function categoryEditAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model = Admin\VCatalog::model($this->doctrine);

            if ($request->isMethod('POST')) {
                $this->model->setCategory($_POST);
            }

            $catid = $request->get("catid");
            $category = $this->model->getCategory($catid);

            return $this->render('ShopBundle:admin:category_form.html.twig', array("cat"=>$category,"root_categories"=>$this->root_categories,"action"=>"edit"));

        }
    }

    public function categoryDeleteAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model=Admin\VCatalog::model($this->doctrine);

            $catid=$request->get("catid");
            $this->model->deleteCategory($catid);

            return $this->redirect("/admin/catalog/categories/1");

        }
    }

    public function categoryCreateAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model = Admin\VCatalog::model($this->doctrine);

            if ($request->isMethod('POST')) {
                $this->model->setCategory($_POST);
                return $this->redirect("/admin/catalog/categories/1");
            }

            return $this->render('ShopBundle:admin:category_form.html.twig', array("root_categories"=>$this->root_categories,"action"=>"create"));

        }
    }

    public function itemEditAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model=Admin\VCatalog::model($this->doctrine);

            $itemid=$request->get("itemid");

            if ($request->isMethod('POST')) {

                $this->model->setItem($_POST);
                return $this->redirect("/admin/catalog/item/".$itemid."/edit");
            }

            $item= $this->model->getItem($itemid);

            $cats_by_root=$this->model->getCategoriesByRootCat();

            return $this->render('ShopBundle:admin:item_form.html.twig', array("root_categories"=>$this->root_categories,"item"=>$item,"action"=>"edit","cats_by_root"=>$cats_by_root));

        }
    }

    public function itemDeleteAction(Request $request)
    {
        parent::indexAction($request);

        if($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model=Admin\VCatalog::model($this->doctrine);

            $itemid=$request->get("itemid");
            $this->model->deleteItem($itemid);

            return $this->redirect("/admin/catalog/categories/1");

        }
    }

    public function itemCreateAction(Request $request)
    {
        parent::indexAction($request);

        if ($this->nonAuthorized($request))
            return $this->nonAuthorized($request);
        else {

            $this->model = Admin\VCatalog::model($this->doctrine);

            if ($request->isMethod('POST')) {
                $this->model->setItem($_POST);
                return $this->redirect("/admin/catalog/categories/1");
            }

            $cats_by_root = $this->model->getCategoriesByRootCat();

            return $this->render('ShopBundle:admin:item_form.html.twig', array("root_categories" => $this->root_categories, "action" => "create", "cats_by_root" => $cats_by_root));

        }

    }

}
