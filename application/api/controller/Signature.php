<?php
namespace app\api\controller;
use think\Controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/16
 * Time: 11:21
 */
class Signature extends Controller {
    public function get_sign(){
        /*$param = array_filter($_GET,function($val){
            if($val===''){
                return false;
            }
            return true;
        });*/
        array_shift($_GET);
        $param = $_GET;
        unset($param['signature']);
        ksort($param);
        $sort_str = http_build_query($param);
        $signature = sha1($sort_str);
        $param['signature'] = $signature;
        echo  json_encode($param);
    }
    public function get_sign_new(){
        $param = $_POST;
        ksort($param);
        unset($param['signature']);
        $sort_str = http_build_query($param);
        $signature = sha1($sort_str);
        $param['signature'] = $signature;
        echo  json_encode($param);
    }
}
