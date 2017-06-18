<?php
namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;

class VSlideshow extends BaseModel
{
  //public $breadcrumbs="Карта сайта";

  public function getSlideshowContent(){

    $slideshow=$this->connection->query("SELECT * FROM slider AS s LEFT JOIN pictures AS p ON s.id=p.id WHERE visible='1' ORDER BY p.count ASC");

    $slideshow=$slideshow->fetchAll();

    return $slideshow;
  }

  public function deleteFrame($picture_id){

   return  $this->connection->createQueryBuilder()->delete('slider', 'id=:picture_id',
      array(':picture_id'=>$picture_id,
      ));

  }

  /**
   * @static
   * @return VSitemap
   */
  public static function model($doctrine,$className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
