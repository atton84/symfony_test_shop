<?php
namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;

class VNews extends BaseModel
{

    public function getPageData($params){

        $arr=array();
        foreach($params as $k=>$v){
            if(isset($v['id'])&&is_numeric($v['id']))
                $arr['news'][]=$v['id'];
        }

        $return=parent::getPageData($params);

        if(isset($arr['news'])){
            $data=$this->connection->createQueryBuilder()->select(array('id','translit','title','meta_keywords','meta_description'))->from('news')->where('id in (:news)')->setParameter(':news',$arr['news'],\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)->execute()->fetchAll();

            foreach($data as $k=>$v){
                $return[$v['translit']]=array('type'=>'news',
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


    public function getNewsContent($id){

        $where_id="";
        if(is_numeric($id))
            $where_id="(n.id=:id) AND ";
        $news=$this->connection->prepare("SELECT n.*,p.url,p.id AS picture_id FROM news AS n LEFT OUTER JOIN pictures AS p ON (p.object_id=n.id)  WHERE $where_id (n.visible=1) AND ((p.type=:news_picture_type AND p.visible=1 AND p.main=1) OR p.id IS NULL) ORDER BY modified DESC");
        if(is_numeric($id))
            $news->bindParam(":id",$id,\PDO::PARAM_STR);
        $news->bindParam(':news_picture_type',$this->picture_types['news'],\PDO::PARAM_STR);
        $news->execute();
        $news=$news->fetchAll();

        return $news;
    }

    public function getLastNews($count){
        $news_query=$this->connection->createQueryBuilder()->select('*')->from('news')->where("visible=1")->orderBy('id','DESC')->setMaxResults($count);
        $news=$news_query->execute()->fetchAll();
        return $news;
    }

    /**
     * @static
     * @return VNews
     */
    public static function model($doctrine,$className=__CLASS__)
    {
        return parent::model($doctrine,$className);
    }

}