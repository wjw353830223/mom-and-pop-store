<?php

namespace app\common\model;

use think\Model;

class Menu extends Model
{
    protected $name = 'menu';
    const SALE_STATE_ON_SALE = 1;
    const SALE_STATE_OFF_SALE = 0;
    const SALE_STATE_SALE_OUT = 2;
    /**
     * 查询门店
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getMenusByWhere($where, $offset, $limit)
    {
        $menus = $this->where($where)->limit($offset, $limit)->order('id desc')->select();
        foreach($menus as &$menu){
            $class = model('ClassModel')->field('organization_id,name')->getById($menu['class_id']);
            $menu['classify'] = $class['name'];
            $menu['organization'] = model('OrganizationModel')->where(['id'=>$class['organization_id']])->value('name');
            $menu['image'] = "<img src='$menu[image]' style='width:40px;height: 40px;'>";
            $menu['recommend'] = $menu['recommend'] . '星';
        }
        unset($menu);
        return $menus;
    }
    public function specs(){
        return $this->hasMany('AttributionModel','menu_id');
    }
    public function getMenus(){
        $menus = $this->field('id,name')->select();
        return $menus;
    }
    public function getPriceAttr($value)
    {
        return $value / 100;
    }
    public function setPriceAttr($value)
    {
        return $value * 100;
    }
    public function setPreferentialPriceAttr($value)
    {
        return $value * 100;
    }
    public function getPreferentialPriceAttr($value)
    {
        return $value / 100;
    }
    public function getStatusAttr($value)
    {
        $status = [0=>'下架',1=>'上架',2=>'停售'];
        return $status[$value];
    }
    /**
     * 根据搜索条件获取所有的门店数量
     * @param $where
     */
    public function getAllMenus($where)
    {
        return $this->where($where)->count();
    }
    public function addMenu($param){
        try{
            $this->startTrans();
            $has_attr = false;
            if(!empty($param['attr_price'])){
                $attr_price = array_values(array_filter($param['attr_price']));
                if(!empty($attr_price)){
                    $has_attr = true;
                    foreach($param['attrValue'] as $key=>$value){
                        foreach($value as $kk=>$val){
                            $param['attrValue'][$key][$kk] = json_decode($val,true);
                        }
                    }
                    $param['attributions'] = json_encode($param['attrValue']);
                }
            }
            $result = $this->validate('MenuValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }
            if($has_attr){
                model('AttributionModel')->startTrans();
                foreach($attr_price as $key=>$value){
                    $data = [
                        'menu_id'=>$this->id,
                        'price'=>$value,
                        'preferential_price' =>$param['attr_preferential_price'][$key],
                        'spec' => json_decode($param['attr_spec'][$key],true)
                    ];
                    $res = model('AttributionModel')->create($data);
                    if($res === false){
                        $this->rollback();
                        // 验证失败 输出错误信息
                        return msg(-1, '', $this->getError());
                    }
                }
            }
            $has_attr && model('AttributionModel')->commit();
            $this->commit();
            return msg(1, url('menu/index'), '添加菜单成功');
        }catch (\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }
    /**
     * 编辑文章信息
     * @param $param
     */
    public function editMenu($param,$id)
    {
        try{
            $this->startTrans();
            $has_attr = false;
            if(!empty($param['attr_price'])){
                $attr_price = array_values(array_filter($param['attr_price']));
                if(!empty($attr_price)){
                    $has_attr = true;
                    foreach($param['attrValue'] as $key=>$value){
                        foreach($value as $kk=>$val){
                            $param['attrValue'][$key][$kk] = json_decode($val,true);
                        }
                    }
                    $param['attributions'] = json_encode($param['attrValue']);
                }
            }
            $result = $this->validate('MenuValidate')->save($param,['id' => $id]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }
            if($has_attr){
                model('AttributionModel')->startTrans();
                $res = model('AttributionModel')->where(['menu_id'=>$id])->delete();
                if($res === false){
                    $this->rollback();
                    return msg(-1, '', $this->getError());
                }
                foreach($attr_price as $key=>$value){
                    $data = [
                        'menu_id'=>$id,
                        'price'=>$value,
                        'preferential_price' =>$param['attr_preferential_price'][$key],
                        'spec' => json_decode($param['attr_spec'][$key],true)
                    ];
                    $res = model('AttributionModel')->create($data);
                    if($res === false){
                        $this->rollback();
                        // 验证失败 输出错误信息
                        return msg(-1, '', $this->getError());
                    }
                }
            }
            $has_attr && model('AttributionModel')->commit();
            $this->commit();
            return msg(1, url('menu/index'), '添加菜单成功');
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 根据文章的id 获取文章的信息
     * @param $id
     */
    public function getOneMenu($id)
    {
        $menu = $this->where('id', $id)->find();
        $organization_id = model('ClassModel')->where(['id'=>$menu['class_id']])->value('organization_id');
        $menu['organization_id'] = $organization_id;
        $menu['spec'] = json_decode($menu['attributions'],true);
        return $menu;
    }

    /**
     * 删除文章
     * @param $id
     */
    public function delMenu($id)
    {
        try{
            $this->where('id', $id)->delete();
            return msg(1, '', '删除菜单成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
    public function changeMenuStatus($id,$status){
        try{
            $result = $this->save(['status'=>$status], ['id' => $id]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('menu/index'), '销售状态已更新');
            }

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
    public function get_menu_by_cids($cids,$page=0){
        if(empty($cids)){
            return [];
        }
        $query = $this->field('id,name,price,image,detail,introduction,class_id,sale_nums,recommend,preferential_price,attributions')
            ->where(['status'=>self::SALE_STATE_ON_SALE])
            ->whereIn('class_id',$cids);
        $page && $query->page($page)->limit(10);;
        $menus = $query->select();
        if(!empty($menus)){
            foreach($menus as &$menu){
                $menu = $this->parse_menu_detail($menu);
            }
            unset($menu);
        }
        return $menus;
    }
    public function get_menu_detail($mid){
        if(empty($mid)){
            return [];
        }
        $menu = $this->field('id,name,price,image,detail,introduction,class_id,sale_nums,recommend,preferential_price,attributions')
            ->where(['status'=>self::SALE_STATE_ON_SALE,'id'=>$mid])
            ->find();
        if(empty($menu)){
            return [];
        }
        $menu = $this->parse_menu_detail($menu);
        return $menu;
    }
    public function parse_menu_detail($menu){
        $menu['attributions']= json_decode($menu['attributions'],true);
        $attrs = model('Attribution')
            ->field('id,spec,preferential_price,price,sale_nums')
            ->where(['menu_id'=>$menu->id])
            ->select();
        if(!empty($attrs)){
            foreach($attrs as &$val){
                $val['spec'] = json_decode(json_encode($val['spec']),true);
            }
            unset($val);
        }else{
            $attrs = [];
        }
        $menu['spec'] = $attrs;
        return $menu;
    }

    /**
     * @param $order_param
     * 获取商品总价格  单位 分
     */
    public function getTotalAmount($order_param){
        if(empty($order_param)){
            return 0;
        }
        $order_amount = 0;
        foreach($order_param as $val){
            if($val['attr_id']){
                $price = model('Attribution')->field('price,preferential_price')->getById($val['attr_id']);
            }else{
                $price = $this->field('price,preferential_price')->getById($val['mid']);
            }
            $order_amount += $val['nums'] * $price->preferential_price;
        }
        return $order_amount;
    }
}