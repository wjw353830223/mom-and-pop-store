<?php

namespace app\common\model;

use think\Model;

class OrderPartition extends Model
{
    protected $menu_model,$attribution_model;
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected function initialize(){
        parent::initialize();
        $this->menu_model = model('Menu');
        $this->attribution_model = model('Attribution');
    }
    //获取订单菜单信息
    public function get_menus($order_id){
        $partitions = $this->field('id,menu_id,attr_id,nums')->where(['order_id'=>$order_id])->select();
        if(empty($partitions)){
            return [];
        }
        $data = [];
        foreach($partitions as &$partition){
            $menu = $this->menu_model->field('id,name,image')->getById($partition['menu_id'])->toArray();
            $spec = [];
            $spec_str = '';
            if($partition['attr_id']){
                $attr = $this->attribution_model->where(['id'=>$partition['attr_id']])->value('spec');
                $spec = json_decode($attr,true);
                foreach($spec as $val){
                    $spec_str .= $val['specValue'] . ' ';
                }
            }
            $menu['spec'] = $spec;
            $menu['spec_str'] = $spec_str;
            $menu['order_partition_id'] = $partition['id'];
            $data[] = $menu;
        }
        unset($menu);
        return $data;
    }
}