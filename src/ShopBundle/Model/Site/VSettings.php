<?php
namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;

class VSettings extends BaseModel
{
  public $breadcrumbs="Настройки";

  public function getSettings(){
      $settings_array=$this->connection->query("SELECT * FROM settings")->fetchAll();

      $settings=array();

      foreach($settings_array as $key=>$val){
          $settings[$val['key']]=$val['value'];
      }

    return $settings;
  }

  /**
   * @static
   * @return VSettings
   */
  public static function model($doctrine, $className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
