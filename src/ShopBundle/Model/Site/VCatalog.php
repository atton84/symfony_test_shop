<?php

namespace ShopBundle\Model\Site;

use ShopBundle\Model\Base\BaseModel;
use Doctrine\ORM\Tools\Pagination\Paginator;
use ShopBundle\Helpers;


class VCatalog extends BaseModel
{

    public function getPageData($params){

        $arr=array();
        foreach($params as $k=>$v){
            if(isset($v['type'])){
                $index=preg_replace('/^(tm|tg|pg|collection|gt|action)$/','category',$v['type']);
                if(isset($v['id'])&&is_numeric($v['id'])){
                    $arr[$index][]=$v['id'];
                }elseif(isset($v['translit']))/*if($index=='keyword')*/
                    $arr[$index]=$v['translit'];
            }
        }


        $return=parent::getPageData($params);


        if(isset($arr['category'])){

            $data=$this->connection->createQueryBuilder()->select(array('root_cat','id','translit','name','meta_keywords','meta_description'))
                ->from('category')->where('id IN(:categories)')
                ->setParameter('categories',$arr['category'],\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->execute()
                ->fetchAll();

            foreach($data as $k=>$v){
                $return[$this->root_categories[$v['root_cat']]['alias'].'_'.$v['translit']]=array('type'=>$this->root_categories[$v['root_cat']]['alias'],
                    'id'=>$v['id'],
                    'name'=>$v['name'],
                    'meta_title'=>$v['name'],
                    'meta_keywords'=>$v['meta_keywords'],
                    'meta_description'=>$v['meta_description']);
            }
        }


        if(isset($arr['keyword'])){

            $data=$this->connection->createQueryBuilder()->select(array('id','keywords_translit','keywords','meta_keywords','meta_description'))
                ->from('item')->where("keywords_translit LIKE :keyword",array(':keyword'=>'%'.$arr['keyword'].'%'))
                ->setMaxResults(1)
                ->execute()
                ->fetchAll();

            $data=$data[0];

            $kt=explode(',',$data['keywords_translit']);
            $name=explode(',',$data['keywords']);

            $keyword="";

            foreach($kt as $k=>$v){
                if($v==$arr['keyword'])
                    $keyword=$name[$k];
            }

            $return['keyword_'.$arr['keyword']]=array('type'=>'keyword',
                'id'=>false,
                'name'=>$keyword,
                'meta_title'=>$keyword,
                'meta_keywords'=>$data['meta_keywords'],
                'meta_description'=>$data['meta_description']
            );
        }


        if(isset($arr['item'])){

            $data=$this->connection->createQueryBuilder()->select(array('id','translit','name','meta_keywords','meta_description','article'))
                ->from('item')->where(/*array('in','id',$arr['item'])*/'id in (:items)')
                ->setParameter('items',$arr['item'],\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->execute()
                ->fetchAll();

            foreach($data as $k=>$v){
                $return['item_'.$v['translit']]=array('type'=>'item',
                    'id'=>$v['id'],
                    'name'=>$v['name'].' '.$v['article'],
                    'meta_title'=>$v['name'].' '.$v['article'],
                    'meta_keywords'=>$v['meta_keywords'],
                    'meta_description'=>$v['meta_description']
                );
            }
        }

        return $return;
    }

    public function getMainCategories($id){

        $cat=array();

        $sql="SELECT c.root_cat,cl.id_category2, cl.id_category1,
    IF(cl.system,cl.system,(SELECT system FROM category AS parent WHERE parent.id=c.root_cat)) as system,
    c.name,c.translit,c.descr,c.meta_keywords,c.meta_description,p.url AS image,c.id,
    IF(cl.id_category2=:id,1,0) AS ord
    FROM (category AS c LEFT JOIN
                        (SELECT cl1.id_category2, cl1.id_category1,cl1.system
                             FROM category_link cl1
                             LEFT JOIN category_link cl2 ON cl1.id_category1=cl2.id_category1
                             WHERE cl2.id_category2=:id and cl1.type1='1' and cl2.type1='1' and cl1.invalid=0 and cl2.invalid=0 AND ((cl1.id_category2 IS NOT NULL OR cl1.id_category2=:id))
                             ORDER BY RAND()
                         ) AS cl on cl.id_category2=c.id
          )
          LEFT JOIN pictures AS p on cl.id_category1=p.object_id AND ((p.type=:item_picture_type AND p.main=1 AND p.visible=1) /*OR p.id IS NULL*/)
          WHERE /*(cl.id_category2 IS NOT NULL OR (c.id=:id)) AND*/ c.visible=1
          GROUP BY cl.id_category2
          ORDER BY ord DESC,c.name";

        $categories=$this->connection->prepare($sql);
        $categories->bindParam(":id",$id,\PDO::PARAM_STR);
        $categories->bindParam(":item_picture_type", $this->picture_types['item'],\PDO::PARAM_STR);

        $categories->execute();
        $categories=$categories->fetchAll();

        foreach($categories as $key=>$val){
            if(isset($this->root_categories[$val['system']]))
                $cat[$this->root_categories[$val['system']]['alias']][]=$val;
        }


        return $cat;
    }

    public function getSecondaryCategories($id,$page=1,$item_per_page=16){

        $cat=$this->connection->prepare('SELECT * FROM category WHERE id=:id');
        $cat->bindParam(":id",$id,\PDO::PARAM_STR);
        $cat->execute();
        $cat=$cat->fetchAll();

        $count=$this->connection->prepare("SELECT count(i.id) AS count FROM item AS i LEFT JOIN category_link AS cl ON cl.id_category1=i.id
                                                                               WHERE cl.id_category2=:id_category AND i.visible=1");
        $count->bindParam(":id_category",$id,\PDO::PARAM_STR);
        $count->execute();
        $count=$count->fetch();

        $start=($page-1)*$item_per_page;

        $querySQL=$this->connection->prepare("SELECT i.id,i.name,i.price,i.translit,i.article,p.url,c.discount FROM item AS i
                                                                     LEFT JOIN category_link AS cl ON cl.id_category1=i.id
                                                                     LEFT JOIN pictures AS p ON p.object_id=i.id AND p.type=:item_picture_type AND p.visible=1 AND p.main=1
                                                                     LEFT JOIN category_link as cl3 ON cl3.id_category1=i.id
                                                                     LEFT JOIN category AS c ON cl3.id_category2=c.id AND c.root_cat=:root_action AND c.visible=1 AND c.expired>NOW()
                                                                     WHERE cl.id_category2=:id_category AND i.visible=1
                                                                     GROUP BY i.id LIMIT :start,$item_per_page");

        $querySQL->bindParam(":id_category",$id,\PDO::PARAM_STR);
        $querySQL->bindParam(":root_action",$this->root_categories['action']['id'],\PDO::PARAM_STR);
        $querySQL->bindParam(":item_picture_type",$this->picture_types['item'],\PDO::PARAM_STR);
        $querySQL->bindParam(":start",$start,\PDO::PARAM_INT);
        $querySQL->execute();
        $items=$querySQL->fetchAll();

        return array('items'=>$items,'pages'=>$count['count'],'category'=>$cat);
    }


    public function getLinkedCategoryContent($main_category_id,$inner_category_id,$page=1,$item_per_page=16){

        $cat=$this->connection->prepare('SELECT * FROM category WHERE id=:id');
        $cat->bindParam(":id",$inner_category_id,\PDO::PARAM_STR);
        $cat->execute();
        $cat=$cat->fetchAll();

        $count=$this->connection->prepare("SELECT count(i.id) AS count FROM category_link cl1
                                                                LEFT JOIN category_link cl2 ON cl1.id_category1=cl2.id_category1
                                                                LEFT JOIN item AS i ON i.id= cl1.id_category1
                                                                WHERE cl1.id_category2=:main_id and cl2.id_category2=:inner_id and cl1.type1='1' and cl2.type1='1' AND cl1.invalid=0 AND cl2.invalid=0 AND i.visible=1
                                                                ");

        $count->bindParam(":inner_id",$inner_category_id,\PDO::PARAM_STR);
        $count->bindParam(":main_id",$main_category_id,\PDO::PARAM_STR);
        $count->execute();

        $count=$count->fetch();

        $start=($page-1)*$item_per_page;

        $main_query=$this->connection->prepare("SELECT i.id,i.name,i.price,i.translit,i.article,c.discount,
              (SELECT url FROM pictures AS p WHERE p.object_id=i.id AND p.visible=1 AND p.type=:item_picture_type LIMIT 1) AS url
               FROM ((category_link cl1
               LEFT JOIN category_link cl2 ON cl1.id_category1=cl2.id_category1)
               LEFT JOIN item i ON i.id=cl1.id_category1)
               LEFT JOIN category_link as cl3 ON cl3.id_category1=i.id
               LEFT JOIN category as c ON cl3.id_category2=c.id AND c.root_cat=:root_action AND c.visible=1 AND c.expired>NOW()
               WHERE cl1.id_category2=:main_id and cl2.id_category2=:inner_id and cl1.type1='1' and cl2.type1='1' AND cl1.invalid=0 AND cl2.invalid=0 AND i.visible=1
               GROUP BY i.id
               ORDER BY CAST(i.price AS SIGNED) DESC, i.id LIMIT :start,$item_per_page");

        $main_query->bindParam(":inner_id",$inner_category_id,\PDO::PARAM_STR);
        $main_query->bindParam(":main_id",$main_category_id,\PDO::PARAM_STR);
        $main_query->bindParam(":root_action",$this->root_categories['action']['id'],\PDO::PARAM_STR);
        $main_query->bindParam(":item_picture_type",$this->picture_types['item'],\PDO::PARAM_STR);
        $main_query->bindParam(":start",$start,\PDO::PARAM_INT);
        $main_query->execute();
        $items=$main_query->fetchAll();

        $current=$this->connection->prepare("SELECT * FROM category WHERE id=:main_id OR id=:inner_id");

        $current->bindParam(":main_id",$main_category_id,\PDO::PARAM_STR);
        $current->bindParam(":inner_id",$inner_category_id,\PDO::PARAM_STR);

        $current=$current->fetchAll();

        return array('items'=>$items,'pages'=>/*$sqlDataProvider->pagination*/$count['count']);

    }

    public function paginate($dql, $page = 1, $limit = 5)
    {
        $paginator = new Paginator($dql/*,$fetchJoinCollection = true*/);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }

    public function getInputType(/*$id*/$params){

        $return=array();
        $input=$this->connection->createCommand()->select(array('root_cat','id','translit'))->from('category')->where("translit IN ('".implode("','",$params)."')")->queryAll();

        foreach($input as $k=>$v){
            $return[$v['translit']]=array('type'=>$this->root_categories[$v['root_cat']]['alias'],'id'=>$v['id']);
        }

        if(!$input||count($params)==3){
            $input=$this->connection->createCommand()->select(array('id','translit'))->from('item')->where("translit IN ('".implode("','",$params)."')")->queryAll();
            foreach($input as $k=>$v){
                $return[$v['translit']]=array('type'=>'item','id'=>$v['id']);
            }
        }


        return $return;
    }

    public function getPriceGroup($id,$page=1,$item_per_page=16){

        $cat=$this->connection->prepare('SELECT * FROM category WHERE id=:id');
        $cat->bindParam(":id",$id,\PDO::PARAM_STR);
        $cat->execute();
        $cat=$cat->fetchAll();

        $min_price=(isset($cat[0]['min_price'])?$cat[0]['min_price']:0);
        $max_price=(isset($cat[0]['max_price'])?$cat[0]['max_price']:0);

        $max_price=$max_price-1;

        $count=$this->connection->prepare("SELECT count(i.id) AS count FROM item as i
                                                                WHERE i.visible=1 AND CAST(i.price AS UNSIGNED)>0 AND CAST(i.price AS UNSIGNED) BETWEEN :min_price AND :max_price");
        $count->bindParam(":min_price",$min_price,\PDO::PARAM_INT);
        $count->bindParam(":max_price",$max_price,\PDO::PARAM_INT);
        $count->execute();
        $count=$count->fetch();

        $count=$count['count'];

        $start=($page-1)*$item_per_page;

        $item_query=$this->connection->prepare("SELECT i.id,i.name,i.price,i.translit,i.article, p.url,c.discount FROM item as i
                                                                      LEFT JOIN pictures as p ON i.id=p.object_id AND p.type=:item_picture_type AND p.visible=1 AND p.main=1
                                                                      LEFT JOIN category_link as cl3 ON cl3.id_category1=i.id
                                                                      LEFT JOIN category AS c ON cl3.id_category2=c.id AND c.root_cat=:root_action AND c.visible=1 AND c.expired>NOW()
                                                                      WHERE i.visible=1 AND CAST(i.price AS UNSIGNED)>0 AND CAST(i.price AS UNSIGNED) BETWEEN :min_price AND :max_price
                                                                      GROUP BY i.id
                                                                      ORDER BY CAST(i.price AS UNSIGNED) ASC LIMIT :start,$item_per_page");

        $item_query->bindParam(":min_price",intval($min_price),\PDO::PARAM_INT);
        $item_query->bindParam(":max_price",intval($max_price),\PDO::PARAM_INT);
        $item_query->bindParam(":root_action",$this->root_categories['action']['id'],\PDO::PARAM_STR);
        $item_query->bindParam(":item_picture_type",$this->picture_types['item'],\PDO::PARAM_STR);
        $item_query->bindParam(":start",$start,\PDO::PARAM_INT);
        $item_query->execute();
        $items=$item_query->fetchAll();


       /* $sqlDataProvider = new CSqlDataProvider($querySQL, array
        (
            'totalItemCount'=>$count,
            'pagination'=>array('pageSize'=>Yii::app()->params['item_per_page']),
            'params'=>array(':min_price'=>intval($min_price),
                ':max_price'=>intval($max_price),
                ':root_action'=>$this->root_categories['action']['id'],
                ':item_picture_type'=> $this->picture_types['item'],
            ),
        ));*/

        //$items=$sqlDataProvider->getData();
        return array('items'=>$items,'pages'=>$count);

    }

    public function getTrademarkContent($id){

        $page1=$this->connection->createCommand()->select()->from('category')->queryAll();

        foreach($page1 as $k=>$v)
            Yii::app()->db->createCommand("UPDATE `category` SET translit='".$this->translit($v['name'])."' WHERE id=".$v['id'])->execute();

        $cat=array();

        $sql="SELECT c.root_cat,cl.id_category2, cl.id_category1,cl.system,c.name,c.translit,c.descr,c.meta_keywords,c.meta_description, p.url AS image,c.alias FROM (category AS c LEFT JOIN (SELECT cl1.id_category2, cl1.id_category1,cl1.system FROM category_link cl1 LEFT JOIN category_link cl2 on cl1.id_category1=cl2.id_category1 WHERE cl2.id_category2=:id and cl1.type1='1' and cl2.type1='1' and cl1.invalid=0 and cl2.invalid=0 AND (cl1.system=2 OR cl1.system=3 OR (cl1.system=1 AND cl1.id_category2=:id)) ORDER BY RAND()) AS cl on cl.id_category2=c.id) LEFT JOIN pictures AS p on cl.id_category1=p.object_id AND ((p.type=2 AND p.main=1 AND p.visible=1) OR p.type IS NULL) WHERE (cl.id_category2 IS NOT NULL OR c.id=:id) GROUP BY cl.id_category2 ORDER BY cl.system ASC,c.name";

        $categories=$this->connection->createCommand($sql);

        $categories->bindParam(":id",$id,PDO::PARAM_STR);

        $categories=$categories->queryAll();

        foreach($categories as $key=>$val){
            if($val['id_category2']==$id||count($categories)==1){
                // $cat['current']=$val;
                if(Yii::app()->session['sitemap'])
                    $this->breadcrumbs['Карта сайта']='/pages/sitemap';

                $this->breadcrumbs[$val['name']]=rtrim(Yii::app()->request->requestUri,"/");
                $this->keywords=$val['meta_keywords'];
                $this->description=$val['meta_description'];
                $this->title=$val['name'];
            }
            $cat[count($categories)>1?$val['system']:$this->root_categories['tm']['system']][]=$val;
        }

        return $cat;
    }

    public function getTradegroupContent($id){

        $cat=array();

        $sql="SELECT c.root_cat,cl.id_category2, cl.id_category1,cl.system,c.name,c.translit,c.descr,c.meta_keywords,c.meta_description,p.url AS image FROM (category AS c LEFT JOIN (SELECT cl1.id_category2, cl1.id_category1,cl1.system FROM category_link cl1 LEFT join category_link cl2 on cl1.id_category1=cl2.id_category1 WHERE cl2.id_category2=:id and cl1.type1='1' and cl2.type1='1' and cl1.invalid=0 and cl2.invalid=0 AND (cl1.system=1 OR cl1.system=6 OR (cl1.system=2 AND cl1.id_category2=:id)) ORDER BY RAND()) AS cl on cl.id_category2=c.id ) LEFT JOIN pictures AS p on cl.id_category1=p.object_id AND ((p.type=2 AND p.main=1 AND p.visible=1) /*OR (p.type=2 AND p.count=(SELECT min(p1.count) FROM pictures AS p1 WHERE p1.type=2 AND p1.object_id=cl.id_category1 AND p1.visible=1))*/ OR p.id IS NULL) WHERE (cl.id_category2 IS NOT NULL OR c.id=:id) AND c.visible=1 GROUP BY cl.id_category2 ORDER BY cl.system ASC,c.name";

        $categories=$this->connection->createCommand($sql);

        $categories->bindParam(":id",$id,PDO::PARAM_STR);

        $categories=$categories->queryAll();

        $cat['current']=$categories[0];

        foreach($categories as $key=>$val){
            if($val['id_category2']==$id||count($categories)==1){
                if(Yii::app()->session['sitemap'])
                    $this->breadcrumbs['Карта сайта']='/pages/sitemap';

                $this->breadcrumbs[$val['name']]=rtrim(Yii::app()->request->requestUri,"/");

                $this->keywords=$val['meta_keywords'];
                $this->description=$val['meta_description'];
                $this->title=$val['name'];
            }
            // $cat['current']=$val;
            $cat[count($categories)>1?$val['system']:$this->root_categories['tg']['system']][]=$val;
        }

        return $cat;
    }

    public function getItemContent($id,$id1=false,$id2=false){

        $item=$this->connection->prepare("SELECT i.*,p.url,p.zoom,c.discount FROM item AS i
                                                                               LEFT JOIN category_link as cl ON cl.id_category1=i.id
                                                                               LEFT JOIN category as c ON cl.id_category2=c.id AND c.root_cat=:root_action AND c.visible=1 AND c.expired>NOW()
                                                                               LEFT JOIN pictures AS p ON p.object_id=i.id AND p.visible=1 AND p.type=:item_picture_type  WHERE i.id=:id
                                                                               GROUP BY p.id
                                                                               ORDER BY p.main DESC,p.count ASC");
        $item->bindParam(":id",$id,\PDO::PARAM_STR);
        $item->bindParam(':root_action',$this->root_categories['action']['id'],\PDO::PARAM_STR);
        $item->bindParam(':item_picture_type',$this->picture_types['item'],\PDO::PARAM_STR);
        $item->execute();
        $item=$item->fetchAll();

        $categories=$this->connection->prepare("SELECT cl.system,c.id,c.name,p.url,c.root_cat,
                                                         c.meta_keywords,c.meta_description,c.translit
                                                         FROM category_link AS cl
                                                         LEFT JOIN category AS c ON (cl.id_category2=c.id)
                                                         LEFT JOIN pictures AS p ON (p.object_id=cl.id_category2 AND p.visible=1 AND p.type=1 AND (p.main=1))
                                                         WHERE (((cl.id_category1=:id) AND (cl.type1=1)) AND (cl.invalid=0) AND (c.root_cat<>:root_action OR (c.root_cat=:root_action AND c.expired>NOW()))) AND c.visible=1
                                                         ORDER BY cl.system ASC");
        $categories->bindParam(":id",$id,\PDO::PARAM_STR);
        $categories->bindParam(':root_action',$this->root_categories['action']['id'],\PDO::PARAM_STR);
        $categories->execute();

        $categories=$categories->fetchAll();

        $categories_array=array();

        foreach($categories as $key=>$val){
            $categories_array[$val['system']][]=$val;
        }

        $where_interesting=null;

        if(isset($id2))
            $where_interesting=" AND cl3.id_category2=:id2 ";

        $interesting_items=$this->connection->prepare("SELECT i.id,i.name,p.url,p.visible,p.type,i.translit
                                                         FROM category_link AS cl1
                                                         LEFT JOIN category_link AS cl2 ON cl2.id_category2=cl1.id_category2 ".
            (isset($where_interesting)?" LEFT JOIN category_link AS cl3 ON cl3.id_category1=cl2.id_category1":"").
            " LEFT JOIN item as i ON cl2.id_category1=i.id
                                                             LEFT JOIN pictures AS p ON p.object_id=cl2.id_category1 AND p.visible=1 AND p.type=:item_picture_type AND p.main=1
                                                             WHERE i.visible='1'  AND cl1.type1='1' AND cl2.type1='1'  and cl1.system='1' and cl2.system=1 AND cl1.id_category1=:id ".$where_interesting." AND  cl2.id_category1<>:id
                                                             GROUP BY ".(isset($where_interesting)?"cl3.id_category1":"cl2.id_category1")." ORDER BY RAND() LIMIT 4");


        $interesting_items->bindParam(":id",$id,\PDO::PARAM_STR);
        $interesting_items->bindParam(':item_picture_type',$this->picture_types['item'],\PDO::PARAM_STR);

        if(isset($where_interesting))
            $interesting_items->bindParam(":id2",$id2,\PDO::PARAM_STR);

        $interesting_items->execute();

        $interesting_items=$interesting_items->fetchAll();

        $price_groups=array();

        if(!empty($item)&&isset($item[0]['price'])&&is_numeric($item[0]['price'])){
            $price_groups=$this->connection->prepare("SELECT * FROM category WHERE root_cat=:root_cat AND min_price<:price AND max_price>:price AND visible=1");
            $price_groups->bindParam(":root_cat",$this->root_categories['pg']['id'],\PDO::PARAM_STR);
            $price_groups->bindParam(":price",$item[0]['price'],\PDO::PARAM_STR);
            $price_groups->execute();
            $price_groups=$price_groups->fetchAll();
        }

        return array('item'=>$item,'categories'=>$categories_array,'interesting_items'=>$interesting_items,'price_groups'=>$price_groups);
    }

    public function getItemsById($id){

        $item=array();

        if(is_numeric($id)||is_array($id)){

            $id=is_numeric($id)?array($id):$id;
            /*if(is_array($id)){
                $where="";
                $params=array();
                for($i=0;$i<count($id);$i++){
                   // $where.=(!empty($where)?" OR ":"")."i.id=:id_$i";
                    $params[":id_$i"]=$id[$i];
                }


            }else{
                $where="i.id=:id";
                $params=array($id);
            }*/
            //var_dump($id);
            $item=$this->connection->executeQuery("SELECT i.id,i.price,i.name,i.translit,p.url FROM item AS i LEFT JOIN pictures AS p ON p.object_id=i.id WHERE i.id IN (?) AND i.visible='1' AND p.type='2' AND p.main='1' AND p.visible='1'",
                array($id),array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
                );
                /*->select("i.id,i.price,i.name,i.translit,p.url")
                ->from("item AS i")
                ->leftJoin("pictures AS p",'p.object_id=i.id')*/
            //i.id IN (?) AND
            //$item->bindValue(1, array(2680,2685));
            //$item->bindParams();

            //Yii::getLogger()->flush(true);
            //$item->execute();

            $item=$item->fetchAll();
        }
       // var_dump($item);
        return $item;
    }

    public function recalculatePrices($items,$counts){

        $calc_sums=count($counts)>0;

        if(count($items)>0){
            $item=$this->getItemsById($items);

            $prices=array();

            $price_not_defined=/*Yii::app()->params['price_not_defined']*/'не указана';

            foreach($item as $key=>$val){
                $prices[$val['id']]['price']=(($val['price']!=''&&$val['price']!=0)?Helpers\PriceHelper::parse_price($val['price']):$price_not_defined);
                if($calc_sums)
                    $prices[$val['id']]['sum']=(($val['price']!=''&&$val['price']!=0)?Helpers\PriceHelper::count_sum($val['price'],$counts[$val['id']]):"-");
            }
        }

        return $prices;
    }

    public function searchItemsByTrigram($trigram,$page=1,$item_per_page=16){

        $max_score=$this->connection->prepare("SELECT MAX(MATCH(trigramm) AGAINST(:trigram IN BOOLEAN MODE)) AS max_score FROM item AS i LEFT JOIN pictures AS p on i.id=p.object_id WHERE MATCH(trigramm) AGAINST(:trigram IN BOOLEAN MODE) AND i.visible=1 AND i.invalid=0 AND ((p.type=2 AND p.main=1 AND p.visible=1) OR p.id IS NULL) ");
        $max_score->bindParam(":trigram",$trigram,\PDO::PARAM_STR);
        $max_score->execute();
        $max_score=$max_score->fetch();

        $max_score=intval($max_score['max_score']);
        $max_score=$max_score/3;

        $count=$this->connection->prepare("SELECT count(i.id) AS count FROM item AS i LEFT JOIN pictures AS p on i.id=p.object_id WHERE (MATCH(trigramm) AGAINST(:trigram IN BOOLEAN MODE))>$max_score AND i.visible=1 AND i.invalid=0 AND ((p.type=2 AND p.main=1 AND p.visible=1) OR p.id IS NULL)");
        $count->bindParam(":trigram",$trigram,\PDO::PARAM_STR);
        $count->execute();
        $count=$count->fetch();

        $start=($page-1)*$item_per_page;

        $main_query=$this->connection->prepare("SELECT i.id,i.name,i.translit,p.url,i.price,MATCH(trigramm) AGAINST(:trigram IN BOOLEAN MODE) AS score FROM item AS i LEFT JOIN pictures AS p on i.id=p.object_id WHERE (MATCH(trigramm) AGAINST(:trigram IN BOOLEAN MODE))>$max_score AND i.visible=1 AND i.invalid=0 AND ((p.type=2 AND p.main=1 AND p.visible=1) OR p.id IS NULL) ORDER BY score DESC LIMIT :start, $item_per_page");
        $main_query->bindParam(":trigram",$trigram,\PDO::PARAM_STR);
        $main_query->bindParam(":start",$start,\PDO::PARAM_INT);

        /*$sqlDataProvider = new CSqlDataProvider($querySQL, array
        (
            'totalItemCount'=>$count,
            'pagination'=>array('pageSize'=>Yii::app()->params['item_per_page']),
            'params'=>array(':trigram'=>$trigram),
        ));*/
        $main_query->execute();

        $items=$main_query->fetchAll()/*$sqlDataProvider->getData()*/;
        $this->breadcrumbs=array("Поиск"=>rtrim($_SERVER['REQUEST_URI'],"/"));

        $this->keywords="Поиск";
        $this->description="Поиск";
        $this->title="Поиск";

        echo $count['count'];
        return array('items'=>$items,'pages'=>$count['count']);
    }


    public function getTrademarks(){

        //$categories=$this->connection->createCommand("SELECT c.id,c.name,(SELECT url FROM pictures AS p WHERE p.object_id=c.id AND p.visible=1 AND p.type=1 ORDER BY p.main DESC, p.count DESC LIMIT 1) AS url FROM category AS c WHERE ((c.root_cat=:tmid) AND (c.visible=1)) ORDER BY c.position ASC");
        //$categories=$this->connection->prepare("SELECT c.id,c.name,c.translit,p.url FROM category AS c LEFT JOIN pictures AS p ON p.object_id=c.id AND p.visible=1 AND p.type=1 AND p.main=1/*(p.main=1 OR (p.main=0 AND p.count=(SELECT min(p1.count) FROM pictures AS p1 WHERE p1.object_id=c.id AND p1.visible=1 AND p1.type=1)))*/ WHERE ((c.root_cat=:tmid) AND (c.visible=1)) GROUP BY c.id ORDER BY c.position ASC");
        $categories=$this->connection->prepare("SELECT c.id,c.name,c.translit,GROUP_CONCAT(DISTINCT cl1.id_category2 SEPARATOR  ',') AS contains FROM category AS c INNER JOIN category_link AS cl ON cl.id_category2=c.id AND cl.system=1 INNER JOIN category_link AS cl1 ON cl1.id_category1=cl.id_category1 AND cl1.system=2 WHERE c.root_cat=:tmid GROUP BY c.id ORDER BY cl1.system ASC,c.position");

        $categories->bindParam(":tmid",$this->root_categories['tm']['id'],\PDO::PARAM_STR);
        $categories->execute();
        $categories=$categories->fetchAll();

        return $categories;
    }

    public function getTradegroups(){

        $categories=$this->connection->prepare("SELECT c.id,c.name,c.translit,GROUP_CONCAT(DISTINCT cl1.id_category2 SEPARATOR  ',') AS contains FROM category AS c INNER JOIN category_link AS cl ON cl.id_category2=c.id AND cl.system=2 INNER JOIN category_link AS cl1 ON cl1.id_category1=cl.id_category1 AND cl1.system=1 WHERE c.root_cat=:tgid GROUP BY c.id ORDER BY cl1.system ASC,c.position");
        //$categories=$this->connection->prepare("SELECT (SELECT p.url FROM category_link AS cl LEFT JOIN pictures as p on cl.id_category1=p.object_id WHERE cl.id_category2=c.id AND cl.invalid=0 AND ((p.main=1 AND p.visible=1 AND p.type=2) /*OR (p.type=1 AND p.count=(SELECT min(p1.count) FROM pictures AS p1 WHERE p1.type=1 AND p1.object_id=cl.id_category1 AND p1.visible=1))*/ OR p.id IS NULL) ORDER BY RAND() LIMIT 1) as url,c.id,c.name,c.translit FROM category AS c WHERE c.root_cat=:tgid and c.visible='1' order by c.position ASC");
        $categories->bindParam(":tgid",$this->root_categories['tg']['id'],\PDO::PARAM_STR);
        $categories->execute();
        $categories=$categories->fetchAll();

        return $categories;
    }


    public function getSitemap(){

        $sitemap=$this->connection->prepare("SELECT c.id AS parent_id,c.translit AS parent_translit,
                                                      IF((c.root_cat=:tmid AND cl2.system<>:tmsys) OR (c.root_cat=:tgid AND cl2.system<>:tgsys),1,0) AS level,
                                                      c1.id,cl2.system,cl.system AS parent_system,c1.name, c1.translit
                                                      FROM `category` AS c
                                                      LEFT JOIN category_link AS cl ON c.id=cl.id_category2
                                                      LEFT JOIN category_link AS cl2 ON cl.id_category1=cl2.id_category1
                                                      LEFT JOIN category AS c1 ON cl2.id_category2=c1.id
                                                      WHERE (((c.root_cat=:tmid AND ((cl2.system=:tmsys AND c.id=c1.id) OR cl2.system=:tgsys OR cl2.system=:collsys))) OR (c.root_cat=:tgid AND ((cl2.system=:tgsys AND c.id=c1.id) OR cl2.system=:tmsys OR cl2.system=:gtsys)))
                                                            AND c.visible=1  AND cl.type1=1 AND cl.invalid=0 AND cl2.type1=1 AND cl2.invalid=0
                                                      GROUP BY cl2.id_category2,c.id
                                                      ORDER BY c.root_cat,c.id,level,cl2.system");

        $sitemap->bindParam(":tmid",$this->root_categories['tm']['id'],\PDO::PARAM_STR);
        $sitemap->bindParam(":tgid",$this->root_categories['tg']['id'],\PDO::PARAM_STR);
        $sitemap->bindParam(":tmsys",$this->root_categories['tm']['system'],\PDO::PARAM_STR);
        $sitemap->bindParam(":tgsys",$this->root_categories['tg']['system'],\PDO::PARAM_STR);
        $sitemap->bindParam(":collsys",$this->root_categories['collection']['system'],\PDO::PARAM_STR);
        $sitemap->bindParam(":gtsys",$this->root_categories['gt']['system'],\PDO::PARAM_STR);

        $sitemap->execute();

        return $sitemap->fetchAll();

    }

    public function getRandomItems($count=16){

        $main_query=$this->connection->prepare("SELECT i.id,i.name,i.translit,p.url,i.price,c.discount,i.article FROM item AS i LEFT JOIN pictures AS p ON i.id=p.object_id  LEFT JOIN category_link as cl3 ON cl3.id_category1=i.id
                                                                     LEFT JOIN category AS c ON cl3.id_category2=c.id AND c.visible=1 AND c.expired>NOW() WHERE i.visible=1 ORDER BY RAND() LIMIT :count");
        $main_query->bindParam(":count",$count,\PDO::PARAM_INT);

        /*$sqlDataProvider = new CSqlDataProvider($querySQL, array
        (
            'totalItemCount'=>$count,
            'pagination'=>array('pageSize'=>Yii::app()->params['item_per_page']),
            'params'=>array(':trigram'=>$trigram),
        ));*/
        $main_query->execute();

        $items=$main_query->fetchAll()/*$sqlDataProvider->getData()*/;

        return $items;

    }


  public static function model($doctrine=null,$className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
