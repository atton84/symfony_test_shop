<?php

namespace ShopBundle\Model\Admin;

use ShopBundle\Model\Base\BaseModel;
use Doctrine\ORM\Tools\Pagination\Paginator;
use ShopBundle\Helpers;


class VCatalog extends BaseModel
{

    public function getCategoriesCount($limit){

        $categories=$this->connection->prepare("SELECT COUNT(id) as count FROM `category`");
        //$categories->bindParam(":tab",$table,\PDO::PARAM_STR);
        $categories->execute();
        $categories=$categories->fetch();

        return ceil($categories["count"]/$limit);
    }

    public function getItemsCount($catid,$limit){

        $items=$this->connection->prepare("SELECT COUNT(i.id) AS count FROM item AS i INNER JOIN category_link AS cl ON cl.id_category1=i.id AND cl.id_category2=:catid");
        $items->bindParam(":catid",$catid,\PDO::PARAM_INT);
        $items->execute();
        $items=$items->fetch();

        return ceil($items["count"]/$limit);
    }

  public function getCategories($offset,$limit){

      $where_in=implode(",",array_column($this->root_categories["all"],"id"));
      $categories=$this->connection->prepare("SELECT c.id,c.name,c.translit,c.created,c.created_by,c.modified,c.modified_by FROM category AS c WHERE c.root_cat>0 AND c.system=0 ORDER BY c.id ASC LIMIT :offset,:limit");
      $categories->bindParam(":offset",$offset,\PDO::PARAM_INT);
      $categories->bindParam(":limit",$limit,\PDO::PARAM_INT);
      $categories->execute();
      $categories=$categories->fetchAll();

      return ["categories"=>$categories,"count"=>$this->getCategoriesCount($limit)];
  }

    public function getCategoryItems($catid,$offset,$limit){

        $items=$this->connection->prepare("SELECT i.id,i.name, i.price, i.created, i.modified FROM item AS i INNER JOIN category_link AS cl ON cl.id_category1=i.id AND cl.id_category2=:catid LIMIT :offset,:limit");
        $items->bindParam(":offset",$offset,\PDO::PARAM_INT);
        $items->bindParam(":limit",$limit,\PDO::PARAM_INT);
        $items->bindParam(":catid",$catid,\PDO::PARAM_INT);
        $items->execute();
        $items=$items->fetchAll();

        return ["items"=>$items,"count"=>$this->getItemsCount($catid,$limit)];
    }

    public function getCategory($catid){

        $category=$this->connection->prepare("SELECT c.id,c.name,c.root_cat,c.descr,c.meta_keywords,c.meta_description,c.visible FROM category AS c WHERE c.id=:catid");
        $category->bindParam(":catid",$catid,\PDO::PARAM_INT);
        $category->execute();
        $category=$category->fetch();

        return $category;
    }

    public function setCategory($data){

        $category=null;
        if($data["id"]>0){
            $category=$this->connection->prepare("UPDATE category SET
                                                                 `name`=:name,
                                                                 `root_cat`=:root_cat,
                                                                 `descr`=:descr,
                                                                 `meta_keywords`=:meta_keywords,
                                                                 `meta_description`=:meta_description,
                                                                 `visible`=:visible,
                                                                 `translit`=:translit
                                                                 WHERE id=:id");
            $category->bindParam(":id",$data["id"],\PDO::PARAM_INT);

        }else {
            $category=$this->connection->prepare("INSERT INTO category (`name`,`root_cat`,`descr`,`meta_keywords`,`meta_description`,`visible`,`translit`) VALUES (:name,:root_cat,:descr,:meta_keywords,:meta_description,:visible,:translit)");
        }

        $category->bindParam(":root_cat",$data["root_cat"],\PDO::PARAM_INT);
        $category->bindParam(":name",$data["name"],\PDO::PARAM_STR);
        $category->bindParam(":descr",$data["descr"],\PDO::PARAM_STR);
        $category->bindParam(":meta_keywords",$data["meta_keywords"],\PDO::PARAM_STR);
        $category->bindParam(":meta_description",$data["meta_description"],\PDO::PARAM_STR);
        $category->bindParam(":visible",$data["visible"],\PDO::PARAM_STR);
        $category->bindParam(":translit",$this->translit($data["name"]),\PDO::PARAM_STR);
        $category->execute();

    }

    public function deleteCategory($catid){

        $category=$this->connection->prepare("DELETE FROM category_link WHERE id_category2=:catid");
        $category->bindParam(":catid",$catid,\PDO::PARAM_INT);
        $category->execute();

        $category=$this->connection->prepare("DELETE FROM category WHERE id=:catid");
        $category->bindParam(":catid",$catid,\PDO::PARAM_INT);
        $category->execute();

    }

    public function getCategoriesByRootCat(){

        $where_in=implode(",",array_column($this->root_categories["all"],"id"));
        $categories=$this->connection->prepare("SELECT c.id,c.name,c.root_cat FROM category AS c WHERE c.root_cat IN ($where_in) ORDER BY c.id ASC");
        $categories->execute();
        $categories=$categories->fetchAll();

        $cats_by_root=[];
        foreach($categories as $k=>$v){
            $cats_by_root[$v["root_cat"]][]=$v;
        }

        return $cats_by_root;
    }

    public function getItem($itemid){

        $item=$this->connection->prepare("SELECT i.id,i.name,i.article,i.descr,i.meta_keywords,i.meta_description,i.visible,i.size,i.price,GROUP_CONCAT(CONCAT(cl.system,':',cl.id_category2) SEPARATOR ',') AS relations FROM item AS i INNER JOIN category_link AS cl ON cl.id_category1=i.id WHERE i.id=:itemid GROUP BY i.id ");
        $item->bindParam(":itemid",$itemid,\PDO::PARAM_INT);
        $item->execute();
        $item=$item->fetch();

        $relations = [];
        if($item["relations"]) {
            $rel = explode(",", $item["relations"]);
            foreach ($rel as $k => $v) {
                $r = explode(":", $v);
                $relations[$r[0]][$r[1]] = $r[1];
            }
        }

        return ["item"=>$item,"relations"=>$relations];
    }

    public function addRelation($id,$catid,$system){

        $relation = $this->connection->prepare("INSERT INTO category_link (`id_category1`,`id_category2`,`system`,`type1`) VALUES (:itemid,:catid,:system,1)");
        $relation->bindParam(":itemid", $id, \PDO::PARAM_INT);
        $relation->bindParam(":catid", $catid, \PDO::PARAM_INT);
        $relation->bindParam(":system", $system, \PDO::PARAM_INT);
        $relation->execute();
    }

    public function clearRelations($itemid){

        $relation=$this->connection->prepare("DELETE FROM category_link WHERE id_category1=:itemid");
        $relation->bindParam(":itemid",$itemid,\PDO::PARAM_INT);
        $relation->execute();
    }

    public function setItem($data){

        $category=null;
        if($data["id"]>0){
            $this->clearRelations($data["id"]);

            $category=$this->connection->prepare("UPDATE item SET
                                                                 `name`=:name,
                                                                 `descr`=:descr,
                                                                 `meta_keywords`=:meta_keywords,
                                                                 `meta_description`=:meta_description,
                                                                 `size`=:size,
                                                                 `price`=:price,
                                                                 `visible`=:visible,
                                                                 `translit`=:translit
                                                                 WHERE id=:id");
            $category->bindParam(":id",$data["id"],\PDO::PARAM_INT);

        }else {
            $category=$this->connection->prepare("INSERT INTO item (`name`,`descr`,`meta_keywords`,`meta_description`,`size`,`price`,`visible`,`translit`) VALUES (:name,:descr,:meta_keywords,:meta_description,:size,:price,:visible,:translit)");
        }

        $category->bindParam(":price",$data["price"],\PDO::PARAM_STR);
        $category->bindParam(":size",$data["size"],\PDO::PARAM_STR);
        $category->bindParam(":name",$data["name"],\PDO::PARAM_STR);
        $category->bindParam(":descr",$data["descr"],\PDO::PARAM_STR);
        $category->bindParam(":meta_keywords",$data["meta_keywords"],\PDO::PARAM_STR);
        $category->bindParam(":meta_description",$data["meta_description"],\PDO::PARAM_STR);
        $category->bindParam(":translit",$this->translit($data["name"]),\PDO::PARAM_STR);
        $category->execute();

        if(!$data["id"])
            $data["id"]=$this->connection->lastInsertId();


        foreach($data["relations"] as $k=>$v){
            if(is_array($v)){
                foreach($v as $k1=>$v1){
                    $this->addRelation($data["id"],$v1,$this->root_categories[$k]['system']);
                }
            }else {
                $this->addRelation($data["id"],$v,$this->root_categories[$k]['system']);
            }
        }

    }

    public function deleteItem($itemid){

        $category=$this->connection->prepare("DELETE FROM category_link WHERE id_category1=:itemid");
        $category->bindParam(":itemid",$itemid,\PDO::PARAM_INT);
        $category->execute();

        $category=$this->connection->prepare("DELETE FROM item WHERE id=:itemid");
        $category->bindParam(":itemid",$itemid,\PDO::PARAM_INT);
        $category->execute();

    }


    public function renameCats(){

        $cats=$this->connection->createQueryBuilder()->select('*')->from('category')->execute()->fetchAll();

        foreach($cats as $k=>$v) {
            if(isset($this->root_categories[$v['root_cat']])) {
                $new_cat_name=$this->root_categories[$v['root_cat']]["alias"]." ".rand(0,100000);
                $text=str_repeat("Lorem ipsum dolor sit amet,", rand(0,100));
                $this->connection->query("UPDATE `category` SET name='" . $new_cat_name . "',descr='".$text."',meta_description='Page description ".$new_cat_name."',meta_keywords='Page keywords ".$new_cat_name."',translit='" . $this->translit($new_cat_name) . "' WHERE id=" . $v['id'])->execute();
            }
        }
    }

    public function renameItems(){

        $items=$this->connection->createQueryBuilder()->select('*')->from('item')->execute()->fetchAll();

        foreach($items as $k=>$v) {
            //if(isset($this->root_categories[$v['root_cat']])) {
                $new_item_name="item ".rand(0,100000);
                $text=str_repeat("Lorem ipsum dolor sit amet,", rand(0,100));
                $this->connection->query("UPDATE `item` SET name='" . $new_item_name . "',descr='".$text."',meta_description='Page description ".$new_item_name."',meta_keywords='Page keywords ".$new_cat_name."',translit='" . $this->translit($new_item_name) . "' WHERE id=" . $v['id'])->execute();
            //}
        }
    }

    public function renamePages(){

        $items=$this->connection->createQueryBuilder()->select('*')->from('pages')->execute()->fetchAll();

        foreach($items as $k=>$v) {
            $new_page_name="page ".rand(0,100000);
            $text=str_repeat("Lorem ipsum dolor sit amet,", rand(0,100));
            $this->connection->query("UPDATE `pages` SET title='" . $new_page_name . "',text='".$text."',meta_description='Page description ".$new_page_name."',meta_keywords='Page keywords ".$new_page_name."',translit='" . $this->translit($new_page_name) . "' WHERE id=" . $v['id'])->execute();

            $this->connection->query("UPDATE `menu_details` SET title='" . $new_page_name . "' WHERE page_id=" . $v['id'])->execute();
        }


    }

    public static function model($doctrine=null,$className=__CLASS__)
  {
    return parent::model($doctrine,$className);
  }

}
