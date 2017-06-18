<?php
namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;

class VActions extends BaseModel
{

    public function getPageData($params){

        $arr=array();
        foreach($params as $k=>$v){
            if(isset($v['id'])&&is_numeric($v['id']))
                $arr['actions'][]=$v['id'];
        }

        $return=parent::getPageData($params);

        if(isset($arr['actions'])){
            $data=$this->connection->createQueryBuilder()->select(array('id','translit','name','meta_keywords','meta_description'))->from('category AS c')->where('id IN (:actions)')->setParameter(':actions',$arr['actions'],\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)->execute()->fetchAll();

            foreach($data as $k=>$v){
                $return[$v['translit']]=array('type'=>'actions',
                    'id'=>$v['id'],
                    'name'=>$v['name'],
                    'meta_title'=>$v['name'],
                    'meta_keywords'=>$v['meta_keywords'],
                    'meta_description'=>$v['meta_description']
                );
            }
        }

        return $return;
    }

    public function getActionsContent($id){

        $today=new \DateTime('now');
        $where_id="";
        if(is_numeric($id))
            $where_id="(a.id=:id) AND ";
        $actions_query=$this->connection->prepare("SELECT a.*,p.url,p.id AS picture_id,a.name as title, a.descr as text FROM category AS a LEFT OUTER JOIN pictures AS p ON (p.object_id=a.id AND p.type=:actions_picture_type AND p.visible=1 AND p.main=1)  WHERE $where_id (a.visible=1 AND a.root_cat=:action_root_cat AND a.expired>".$today->format('Y-m-d').") ORDER BY a.modified DESC");
        if(is_numeric($id))
            $actions_query->bindParam(":id",$id,\PDO::PARAM_STR);
        $actions_query->bindParam(":actions_picture_type", $this->picture_types['category'],\PDO::PARAM_STR);
        $actions_query->bindParam(":action_root_cat", $this->root_categories['action']['id'],\PDO::PARAM_STR);
        $actions_query->execute();
        $actions=$actions_query->fetchAll();

        return $actions;
    }

    public function getLastActions($count){

        $today=new \DateTime('now');

        $qb=$this->connection->createQueryBuilder();
        $actions_query=$qb->select('c.*,c.name as title, c.descr as text')->from('category AS c')->where("c.visible=1 AND c.root_cat=:action_root_cat AND c.expired>".$today->format('Y-m-d'))/*->andWhere($qb->expr()->gte('c.expired',':now'))*/->orderBy('c.modified','DESC');
        $actions_query->setParameter(":action_root_cat", $this->root_categories['action']['id'],\PDO::PARAM_STR);
        $actions_query->setMaxResults($count);
        $actions=$actions_query->execute()->fetchAll();

        return $actions;
    }

    /**
     * @static
     * @return VActions
     */
    public static function model($doctrine,$className=__CLASS__)
    {
        return parent::model($doctrine,$className);
    }

}