<?php
namespace ShopBundle\Controller;
use ShopBundle\Controller\Base\SiteBaseController;
use ShopBundle\Model\Site;
use ShopBundle\Helpers;
use Symfony\Component\HttpFoundation\Response;


class CartController extends SiteBaseController
{
  protected $render_vars=array();

  public function indexAction(){

      parent::indexAction();

    $cart_data=Site\VCart::model($this->doctrine)->getCart();

      return $this->render('ShopBundle:site:cart.html.twig',array('cart'=>$cart_data));
    /*echo $this->renderPartial('cart',array('cart'=>$cart_data),true);

    return;*/
  }

  public function addAction($id){

      parent::indexAction();

      $request = $this->get('request');
    //$this->renderPartial('cart',array('cart'=>VCart::model()->Add($id,$request->get('url'))),true);
      return $this->render('ShopBundle:site:cart.html.twig',array('cart'=>Site\VCart::model($this->doctrine)->Add($id,$request->get('url'))));
  }

  public function deleteAction($id){

      parent::indexAction();

    Site\VCart::model($this->doctrine)->Delete($id);
    return self::indexAction();
    //return;
  }

  public function updateAction($id,$count,$discount){

      parent::indexAction();

    $price=Site\VCart::model($this->doctrine)->Update($id,$count);
    //echo ((is_numeric($price))? Helpers\PriceHelper::count_sum($price,$count,$discount):'-');

    return new Response((is_numeric($price))? Helpers\PriceHelper::count_sum($price,$count,$discount):'-');
  }


  public function sendAction(){

      parent::indexAction();

      $request = $this->get('request');

    if($request->getMethod() == 'POST') {
       $session_data= Site\VCart::model($this->doctrine)->getCart();

      if(count($session_data)>0){
        $post['name']=$request->get("name");
        $post['surename']=$request->get("surename");
        $post['patr']=$request->get("patr");
        $post['adres']=$request->get("adres");
        $post['phone']=$request->get("phone");
        $post['email']=$request->get("email");

        if(!empty($post['email'])&&!empty($post['name'])) {
            $params=Site\VCart::model($this->doctrine)->Save($post);
            if ($params) {

                $message = \Swift_Message::newInstance()
                    ->setSubject("shop(simfony):заказ № " . $params['order_id'])
                    ->setFrom('shop@simfony.tst')
                    ->setTo($params['admins_email'])
                    ->setBody(
                        $this->renderView(
                        // app/Resources/views/Emails/registration.html.twig
                            'ShopBundle:site:cart(mail).html.twig',
                            array('name' => 'uu')
                        ),
                        'text/html'
                    );
                $this->get('mailer')->send($message);
                $this->get('session')->getFlashBag()->set('message', 'Поздравляем! Ваш заказ принят.');
            }
        }
      }
    }

    return $this->render('ShopBundle:site:messages.html.twig',array());
  }

}