<?php

namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;
use Symfony\Component\HttpFoundation\Session\Session;

class VCurrency extends BaseModel
{
  private $session_name='currency';
  private $session;

  protected function __construct($doctrine){

      parent::__construct($doctrine);
    /*$this->session_name=Yii::app()->getEndName()."[currency]";*/

      $this->session = new Session();

      //$this->session->start();

    if(!isset($this->session)/*->has($this->session_name)*/)
        $this->session->set($this->session_name,array('currency'=>'UAH','rate'=>1));

      return $this;
  }

  public function getCurrencyList(){
    $currency_array=$this->connection->query("SELECT id,name,value FROM currency")->fetchAll();

      $currency=array();

      foreach($currency_array as $key=>$val){
          $currency[$val['id']]=$val['name'];
      }

    //$this->breadcrumbs=$page[0]['title'];

    return $currency;
  }


  public function getCurrencyByName($name){
    $currency=$this->connection->prepare("SELECT name,value FROM currency WHERE name=:name LIMIT 1");
    $currency->bindParam(":name",$name,\PDO::PARAM_STR);
      $currency->execute();
    $currency=$currency->fetchAll();

    //$this->breadcrumbs=$page[0]['title'];

    return $currency;
  }

  public function setCurrency($name){

    $currency=$this->getCurrencyByName($name);
      $this->session->set($this->session_name,array('currency'=>$name,'rate'=>$currency[0]['value']));

      //var_dump($this->session);
      //$_SESSION[$this->session_name]=array('currency'=>$name,'rate'=>$currency[0]['value']);
   /* Yii::app()->session[$this->session_name]=array('currency'=>$name,'rate'=>$currency[0]['value']);*/
  }


  public function getCurrentCurrency(){
    return  $this->session->get($this->session_name);
  }

  /**
   * @static
   * @return VCurrency
   */
  public static function model($doctrine,$className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
