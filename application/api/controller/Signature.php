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
        $param = input('get.');
        unset($param['/api/signature/get_sign']);
        ksort($param);
        unset($param['signature']);
        $sort_str = http_build_query($param);
        $signature = sha1($sort_str);
        $param['signature'] = $signature;
        $param['sort_str'] = $sort_str;
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
