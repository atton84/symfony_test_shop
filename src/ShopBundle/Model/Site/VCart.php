<?php
namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;
use Symfony\Component\HttpFoundation\Session\Session;

class VCart extends BaseModel
{
  protected $events=array('onSaveOrder'=>'saveOrder');

  private $session_name="cart";
  private $session;

    protected function __construct($doctrine)
    {
        parent::__construct($doctrine);
        $this->session=new Session();

    }

  public function Add($id,$url){



    /*if(!isset(Yii::app()->session[$this->session_name]))
      Yii::app()->session[$this->session_name]=array();*/

    if(!$this->session->has($this->session_name))
        $this->session->set($this->session_name,array());

      $session_data=$this->session->get($this->session_name);

    if(/*!isset(Yii::app()->session[$this->session_name][$id])*/!isset($session_data[$id])){

      $item=VCatalog::model($this->doctrine)->getItemsById($id);

        $session_data+=array(
        $id=>array(
          'translit'=>$item[0]['translit'],
          'name'=>$item[0]['name'],
          'price'=>$item[0]['price'],
          'discount'=>$item[0]['discount'],
          'picture'=>$item[0]['url'],
          'url'=>rtrim($url,'/,#'),
          'count'=>1
        ));

    }else{

      //$session=Yii::app()->session->toArray();
        $session_data[$id]['count']++;
      //Yii::app()->session->add($this->session_name,$session[$this->session_name]);
    }
      $this->session->set($this->session_name,$session_data);

    return $session_data;
  }

  public function Delete($id){
    if($id){
      $session_data=/*Yii::app()->session->toArray()*/$this->session->get($this->session_name);
      unset($session_data[$id]);
        $this->session->set($this->session_name,$session_data);
      //Yii::app()->session->add($this->session_name,$session[$this->session_name]);
    }

    return;
  }

  public function Update($id,$count){

    if($id&&$count){
      $session_data=$this->session->get($this->session_name)/*Yii::app()->session->toArray()*/;
        $session_data[$id]['count']=$count;
      //Yii::app()->session->add($this->session_name,$session[$this->session_name]);
        $this->session->set($this->session_name,$session_data);
    }

    if($session_data&&$session_data[$id]['price']!=''&&$session_data[$id]['price']!=0)
      return $session_data[$id]['price'];
    else
      return false;

  }

  public function Save($attributes){

  $session_data=/*Yii::app()->session->toArray()*/$this->session->get($this->session_name);

  $user=$this->connection->prepare("SELECT id FROM customers WHERE name=:name AND email=:email");
  $user->bindParam(":email",$attributes['email'],\PDO::PARAM_STR);
  $user->bindParam(":name",$attributes['name'],\PDO::PARAM_STR);
  $user->execute();

  $user=$user->fetch();


  $customer_id=$user['id'];
      $today=new \DateTime('now');

  if(!is_numeric($customer_id)){
    $attributes['created']=$today->format('Y-m-d');
    $this->connection->insert('customers',$attributes);
    $customer_id=$this->connection->lastInsertId();
  }else{

    $this->connection->update('customers',array('modified'=>$today->format('Y-m-d'))/*,"id=:customer_id"*/,array("id"=>$customer_id));
  }

    $order=$this->connection->prepare("INSERT INTO orders (created,customer_id) VALUES(DATE_FORMAT(NOW(),'%d.%m.%Y'),:customer_id)");
    $order->bindParam(":customer_id",$customer_id,\PDO::PARAM_INT);
    $order->execute();

    $order_id=$this->connection->lastInsertId();

    $values=array();

    foreach($session_data as $key=>$val){
      $sum=$val['price']*$val['count'];
      if($val['discount']>0)
        $sum=$sum-(($sum/100)*$val['discount']);
      $values[]=array('order_id'=>$order_id,'item_id'=>$key,'name'=>$val['name'],'price'=>$val['price'],'discount'=>intval($val['discount']),'count'=>$val['count'],'sum'=>$sum);
    }



      foreach($values as $k=>$v) {
          //$record = new Record();
         // $collection->add($v);
          $this->connection->insert('order_details',$v);
         // $collection->save();
      }

      $admins_email=$this->connection->query("SELECT email FROM users WHERE role='admin'")->fetchAll();

      $admins_email = array_map(function ($v){ return $v['email']; }, $admins_email);



   // $this->connection->schema->commandBuilder->createMultipleInsertCommand('order_details',$values)->execute();

    /*$admins_email=$this->connection->query("SELECT email FROM users WHERE role='admin'")->fetchAll();

    $admins_email = array_map(function ($v){ return $v['email']; }, $admins_email);

    $event = new CEvent($this,array('order_id'=>$order_id,'admins_email'=>$admins_email,'cart'=>$session[$this->session_name],'user'=>$attributes));
    $this->onSaveOrder($event);*/

    $this->session->set($this->session_name,array());

  return array('order_id'=>$order_id,'admins_email'=>$admins_email,'cart'=>$session_data,'user'=>$attributes);

  }

  /*public function onSaveOrder($event) {
    $this->raiseEvent('onSaveOrder', $event);
  }*/

  public function getCart(){
    return /*Yii::app()->session[$this->session_name]*/$this->session->get($this->session_name);
  }

  /**
   * @static
   * @return VCart
   */
    public static function model($doctrine=null,$className=__CLASS__)
    {
        return parent::model($doctrine,$className);
    }

}
