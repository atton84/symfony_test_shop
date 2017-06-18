<?php
namespace ShopBundle\Model\Admin;

use ShopBundle\Model\Base\BaseModel;

class VAuth extends BaseModel
{

    public function checkAuth($login,$pass){

        $md_pass=md5($pass);
        $auth_query=$this->connection->prepare("SELECT u.id,u.name,u.email FROM users AS u WHERE u.login=:login AND u.password=:pass LIMIT 1");
        $auth_query->bindParam(":login", $login,\PDO::PARAM_STR);
        $auth_query->bindParam(":pass",$md_pass ,\PDO::PARAM_STR);
        $auth_query->execute();
        $auth=$auth_query->fetchAll();

        return $auth;
    }


    /**
     * @static
     * @return VAuth
     */
    public static function model($doctrine,$className=__CLASS__)
    {
        return parent::model($doctrine,$className);
    }

}