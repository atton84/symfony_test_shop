<?php

namespace ShopBundle\Model\Base;

abstract class BaseModel
{
  private static $_models=array();			// class name => model
  protected $events=array();
  protected $connection;
  public $breadcrumbs=array();
  public $root_categories;
  public $doctrine;
  //public $notifier;

  public $title;
  public $keywords;
  public $description;
  public $picture_types=array('category'=>1,'item'=>2,'news'=>3,'actions'=>4,'slideshow'=>5,'users'=>6);
  public $em;

  protected $translations=array(
      /***Catalog***/
      'Search'=>'Поиск',
      'Home'=>'Главная',
      'Sitemap'=>'Карта сайта',
      'News'=>'Новости',
      'Actions'=>'Акции',

      'Slideshow'=>'Слайдшоу',
      'Sorry, nothing was found.'=>'По вашему запросу ничего не найдено.',


  );

  protected function __construct($doctrine){
    //parent::__construct();
      $this->doctrine=$doctrine;
      $this->connection=$doctrine->getConnection();
      $this->root_categories=$this->getRootCategories();
      $this->em=$doctrine->getEntityManager();
    /*$this->notifier=new Notifier();
    $this->root_categories=Yii::app()->params['root_categories'];*/
  }


    public function getPageData($params){
        $return=array();
        if(count($params)>0){
            foreach($params as $k=>$v){
                if(empty($v['translit'])&&isset($v['type'])){
                    $text=ucfirst($v['type']);
                    $translation=(isset($this->translations[$text])?$this->translations[$text]:$text);
                    if($translation!=$text/*!in_array($translation,array('catalog'))*/)
                        $return[$v['type']]=array('name'=>$translation,'meta_title'=>$translation,'meta_keywords'=>$translation,'meta_description'=>$translation, 'id'=>$v['id']);
                    else
                        $return[$v['type']]=array('name'=>false);
                }

            }
        }

        return $return;
    }


    public function translit($str){
    $tr = array(
      "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
      "Д"=>"d","Е"=>"e","Ё"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
      "Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
      "О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
      "У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
      "Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
      "Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
      "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"e","ж"=>"j",
      "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
      "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
      "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
      "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
      "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
      " "=> "_", "."=> "", "/"=>"_", ";"=>"", ":"=>""
    );


    $str=strtr($str,$tr);
    #convert case to lower
    $str = strtolower($str);
    #remove special characters
    $str = preg_replace('/[^a-zA-Z0-9]/i',' ', $str);
    #remove white space characters from both side
    $str = trim($str);
    #remove double or more space repeats between words chunk
    $str = preg_replace('/\s+/', ' ', $str);
    #fill spaces with hyphens
    $str = preg_replace('/\s+/', '_', $str);

    return $str;
  }

  public static function model($doctrine,$className=__CLASS__)
  {
    if(isset(self::$_models[$className]))
      return self::$_models[$className];
    else
    {
      $model=self::$_models[$className]=new $className($doctrine);
      //$model->attachBehaviors($model->behaviors());
      /*foreach($model->events as $methodName=>$notifierMethodName){
        $model->$methodName= array($model->notifier, $notifierMethodName);
      }*/

      return $model;
    }
  }

    public function getRootCategories(){
        $root_categories=$this->connection->query("SELECT children.* FROM `category` as root LEFT JOIN category AS children ON children.root_cat=root.id WHERE root.root_cat=0")->fetchAll();

        $root_cats=array();

        foreach($root_categories as $key=>$val){
            $root_cats[$val['alias']]=array('system'=>$val['system'],'id'=>$val['id'],'name'=>$val['name']);
            $root_cats[$val['system']]=array('alias'=>$val['alias'],'id'=>$val['id'],'name'=>$val['name']);
            $root_cats[$val['id']]=array('alias'=>$val['alias'],'system'=>$val['system'],'name'=>$val['name']);
            $root_cats["all"][]=array('alias'=>$val['alias'],'system'=>$val['system'],'name'=>$val['name'],'id'=>$val['id'],);
        }
        $this->root_categories=$root_cats;

        return $root_cats;
    }


}
