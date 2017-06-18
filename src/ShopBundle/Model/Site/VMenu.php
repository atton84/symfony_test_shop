<?php
namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;

class VMenu extends BaseModel
{
  public $breadcrumbs="Меню";

  public function getMenuById($id){
    $menu=$this->connection->prepare("SELECT md.title,md.page_id,p.translit FROM (menu AS m LEFT JOIN menu_details AS md ON md.menu_id=m.id) LEFT JOIN pages AS p ON md.page_id=p.id  WHERE m.visible='yes' AND m.id=:id ORDER BY position ASC");
    $menu->bindParam(":id",$id,\PDO::PARAM_STR);
    $menu->execute();
    $menu=$menu->fetchAll();
    return $menu;
  }

  /**
   * @static
   * @return VMenu
   */
  public static function model($doctrine,$className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
