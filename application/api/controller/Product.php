<?php
namespace app\api\controller;
use think\Cache;

class Product extends Apibase
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
        $cid = input('post.cid',0,'intval');
        if(empty($oid)){
            $this->ajax_return('10001','无效的门店id');
        }
        if($cid){
            $classify = $this->classify_model->where(['organization_id'=>$oid,'state'=>0,'id'=>$cid])->column('id,name');
            if(empty($classify)){
                $this->ajax_return('10004','该分类不存在或已下架');
            }
            $cids = [$cid];
        }else{
            $classify = $this->classify_model->where(['organization_id'=>$oid,'state'=>0])->column('id,name');
            if(empty($classify)){
                $this->ajax_return('10002','该门店暂无分类和菜单');
            }
            $cids = array_keys($classify);
            $classify = $this->classify_model->where(['organization_id'=>$oid,'state'=>0])->field('id,name')->select();
        }
        $menus = $this->menu_model->get_menu_by_cids($cids,$page);
        if($menus === false){
            $this->ajax_return('10003','菜单查询出错');
        }
        $data['classify'] = $classify;
        $data['menus'] = $menus;
        $this->ajax_return('200','success',$data);
    }
    /**
     *	菜单列表2
     */
    public function products(){
        $oid = input('post.oid',0,'intval');
        if(empty($oid)){
            $this->ajax_return('10001','无效的门店id');
        }

        $classify = $this->classify_model->where(['organization_id'=>$oid,'state'=>0])->column('id,name');
        if(empty($classify)){
            $this->ajax_return('10002','该门店暂无分类和菜单');
        }
        $cids = array_keys($classify);
        $menus = $this->menu_model->get_menus($cids);
        $classify = $this->classify_model->whereIn('id',$cids)->field('id,name')->select();
        if($menus === false){
            $this->ajax_return('10003','菜单查询出错');
        }
        $data['classify'] = $classify;
        $data['menus'] = $menus;
        $this->ajax_return('200','success',$data);
    }
    /**
     * 菜单详细
     */
    public function detail(){
        $mid = input('post.mid',0,'intval');
        if(empty($mid)){
            $this->ajax_return('10010','无效的菜单id');
        }
        $menu = $this->menu_model->get_menu_detail($mid);
        $this->ajax_return('200','success',$menu);
    }
}
