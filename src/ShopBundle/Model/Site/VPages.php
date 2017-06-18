<?php

namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;

class VPages extends BaseModel
{
    public function getPageData($params){

        $arr=array();
        foreach($params as $k=>$v){
            if(isset($v['id'])&&is_numeric($v['id']))
                $arr['pages'][]=$v['id'];
        }

        $return=parent::getPageData($params);

        if(isset($arr['pages'])){
            $data=$this->connection->createQueryBuilder()->select(array('id','translit','title','meta_keywords','meta_description'))
                ->from('pages')
                ->where(/*array('in','id',$arr['pages'])*/'id IN (:pages)')
                ->setParameter('pages',$arr['pages'],\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->execute()
                ->fetchAll();

            foreach($data as $k=>$v){
                $return[$v['translit']]=array('type'=>'pages',
                    'id'=>$v['id'],
                    'name'=>$v['title'],
                    'meta_title'=>$v['title'],
                    'meta_keywords'=>$v['meta_keywords'],
                    'meta_description'=>$v['meta_description']
                );
            }
        }

        return $return;
    }

  public function getPageContent($params){

      /*$page1=$this->connection->createQueryBuilder()->select('*')->from('category')->execute()->fetchAll();

      foreach($page1 as $k=>$v)
          $this->connection->query("UPDATE `category` SET translit='".$this->translit($v['name'])."' WHERE id=".$v['id'])->execute();


      $page1=$this->connection->createQueryBuilder()->select('*')->from('item')->execute()->fetchAll();

      foreach($page1 as $k=>$v)
          $this->connection->query("UPDATE `item` SET translit='".$this->translit($v['name'])."' WHERE id=".$v['id'])->execute();*/



    $page1=$this->connection->createQueryBuilder()->select('*')->from('pages')->execute()->fetchAll();

    foreach($page1 as $k=>$v)
        $this->connection->query("UPDATE `pages` SET translit='".$this->translit($v['title'])."' WHERE id=".$v['id'])->execute();

    $page=$this->connection->createqueryBuilder()->select('*')->from('pages');//"SELECT * FROM pages WHERE visible='yes' AND id=:id");
    $page->where("visible='yes'");

    foreach($params as $key=>$val)
      $page->andWhere("$key='$val'"/*,array(":$key"=>$val)*/);

    $page->orderBy('id','DESC')->setMaxResults(1);
      $page=$page->execute()->fetch();
      $this->breadcrumbs=array($page['title']=>rtrim($_SERVER['REQUEST_URI'],"/"));

    /*$this->keywords=$page['meta_keywords'];
    $this->description=$page['meta_description'];
    $this->title=$page['title'];*/

    return $page;
  }

  /**
   * @static
   * @return VPages
   */
  public static function model($doctrine,$className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
