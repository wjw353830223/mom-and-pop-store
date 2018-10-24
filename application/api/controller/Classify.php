<?php
namespace app\api\controller;
use think\Cache;

class Classify extends Apibase
{
    protected $member_model, $token_model, $menu_model,$organization_model,$classify_model;
    protected function _initialize() {
        parent::_initialize();
        $this->member_model = model('Member');
        $this->token_model = model('MemberToken');
        $this->menu_model = model('Menu');
        $this->organization_model = model('Organization');
        $this->classify_model = model('Classify');
    }
    /**
     *	菜单列表
     */
    public function index(){
        $oid = input('post.oid',0,'intval');
        $page = input('post.page',0,'intval');
        if(empty($oid)){
            $this->ajax_return('10001','无效的门店id');
        }
        $classify = $this->classify_model->where(['organization_id'=>$oid,'state'=>0])->field('id,name')->select();
        if(empty($classify)){
            $this->ajax_return('10002','该门店暂无分类');
        }
        $this->ajax_return('200','success',['classify'=>$classify]);
    }
}
