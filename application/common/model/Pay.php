<?php

namespace app\common\model;

use think\Model;

class Pay extends Model
{
    protected $name = 'pay';
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'created_at';

    protected $order_partition_model,$menu_model;
    protected function initialize(){
        parent::initialize();
        $this->menu_model = model('Menu');
        $this->order_partition_model = model('OrderPartition');
    }
}