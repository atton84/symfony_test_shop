<?php

namespace ShopBundle\Controller\Base;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use ShopBundle\Model\Site;
use ShopBundle\Model\Admin;
/*use Doctrine\ORM\Query;*/


class AdminBaseController extends Controller
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
    public $item_per_page=16;
    public $auth=[];

    public function authAction(Request $request)
    {
        $this->indexAction($request);

        $auth=$request->cookies->get("auth");
        if($auth)
            return $this->redirect("/admin");
        else {
            $login = $request->get("login");
            $pass = $request->get("pass");
            if($login&&$pass) {
                $this->model = Admin\VAuth::model($this->doctrine);

                $auth = $this->model->checkAuth($login, $pass);
                if ($auth && count($auth) > 0) {
                    $response = new Response();
                    $response->headers->setCookie(new Cookie('auth', serialize($auth)));
                    $response->send();
                    return $this->redirect("/admin");

                } else {
                    return $this->render('ShopBundle:admin:login.html.twig', array());
                }
            }else
                return $this->render('ShopBundle:admin:login.html.twig', array());
        }
    }

    public function nonAuthorized(Request $request){
        $auth=$request->cookies->get("auth");
        if($auth) {
            $this->auth = unserialize($auth);
            return false;
        }
        else
            return $this->redirect("/admin/auth");
    }

    public function logoutAction(){
        $response = new Response();
        $response->headers->clearCookie('auth');
        $response->send();

        return $this->redirect("/admin");
    }

    public function indexAction(Request $request){

        $this->doctrine=$this->getDoctrine();

        $this->root_categories=Site\VCatalog::model($this->doctrine)->getRootCategories();

        $this->render_vars=array(
            'root_categories'=>$this->root_categories,
            );


        $auth=$request->cookies->get("auth");
        if($auth) {
            $this->auth = unserialize($auth);
            return;
        }
        else
            return $this->redirect("/admin/auth");
    }



    public function render($view, array $parameters = Array(), \Symfony\Component\HttpFoundation\Response $response = NULL){


            return parent::render($view, array_merge($this->render_vars,$parameters),$response);
    }


}
