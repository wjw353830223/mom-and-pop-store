<?php

namespace app\common\model;

use think\Model;

class Order extends Model
{
    protected $name = 'order';
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    const STATUS_CANCEL = 7;
    const STATUS_ORDER = 1;
    const STATUS_MAKE = 2;
    const STATUS_GET = 3;
    const STATUS_NO_GET = 4;
    const STATUS_FINISH = 5;
    const STATUS_DELETE = 6;
    protected $order_partition_model,$menu_model,$pay_model;
    protected function initialize(){
        parent::initialize();
        $this->menu_model = model('Menu');
        $this->order_partition_model = model('OrderPartition');
        $this->pay_model = model('Pay');
    }
    public function getStatusAttr($value)
    {
        $status = [0=>'取消',1=>'已下单',2=>'制作中.....',3=>'取餐中....',4=>'无人取餐....',5=>'已完成',6=>'已删除'];
        return $status[$value];
    }
    public function getTypeAttr($value){
        $type = [0=>'用户在线下单',1=>'服务员下单'];
        return $type[$value];
    }
    /**
     * 查询订单
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getOrdersByWhere($where, $offset, $limit)
    {
        $orders = $this->where($where)->limit($offset, $limit)->order('created_at asc')->select();
        foreach($orders as &$vo){
            $vo['table_name'] = model('TableModel')->where(['id'=>$vo['tid']])->value('name');
            $vo['member_mobile'] = model('Member')->where(['member_id'=>$vo['member_id']])->value('member_mobile');
            $menus = $this->order_partition_model->get_menus($vo['id']);
            $order_info = '';
            foreach($menus as $menu){
                $order_info .=  $menu['name'].' '.$menu['spec_str'] . "<br/><br/>";
            }
            $vo['order_info'] = substr($order_info,0,-10);
        }
        unset($vo);
        return $orders;
    }
    /**
     * 根据搜索条件获取所有的订单数量
     * @param $where
     */
    public function getAllOrders($where)
    {
        return $this->where($where)->count();
    }
    public function gen_order($tid,$member_id,$order_param,$type=0)
    {
        if (empty($tid) || empty($member_id) || empty($order_param) || !is_array($order_param)) {
            return false;
        }
        $this->startTrans();
        $this->order_partition_model->startTrans();
        $this->pay_model->startTrans();
        $pay_sn = $this->make_paysn($member_id);
        $pay_result = $this->pay_model->create(['pay_sn' => $pay_sn,'buyer_id' => $member_id]);
        if (empty($pay_result)) {
            return false;
        }
        $data = [
            'tid' => $tid,
            'type' => $type,
            'member_id' => $member_id,
            'order_amount' => $this->menu_model->getTotalAmount($order_param),
            'order_sn' => $this->make_ordersn($pay_result->pay_id),
        ];
        $res = $this->create($data);
        if (is_null($res)) {
            $this->pay_model->rollback();
            return false;
        }
        foreach($order_param as $param){
            $data = [
                'order_id' =>$res->id,
                'order_partition_sn' => $this->make_ordersn($pay_result->pay_id),
                'order_amount' => $this->menu_model->getTotalAmount([$param]),
                'menu_id'=>$param['mid'],
                'attr_id'=>$param['attr_id'],
                'nums'=>$param['nums'],
            ];
            if(!$this->order_partition_model->create($data)){
                $this->pay_model->rollback();
                $this->rollback();
                $this->order_partition_model->rollback();
                return false;
            }
        }
        $this->pay_model->commit();
        $this->commit();
        $this->order_partition_model->commit();
        return $res->order_sn;
    }
    /**
     * 生成支付单编号(两位随机 + 从2000-01-01 00:00:00 到现在的秒数+微秒+会员ID%1000)，该值会传给第三方支付接口
     * 长度 =2位 + 10位 + 3位 + 3位  = 18位
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @return string
     */
    public function make_paysn($member_id) {
        return mt_rand(10,99)
            . sprintf('%010d',time() - 946656000)
            . sprintf('%03d', (float) microtime() * 1000)
            . sprintf('%03d', (int) $member_id % 1000);
    }


    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function make_ordersn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num ++;
        }
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }
    public function changeOrderStatus($mid,$status){
        try{
            $data = ['status'=>$status];
            if($status==5){
                $data['finished_at'] = time();
            }
            $result = $this->save($data, ['id' => $mid]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('order/index'), '订单状态已更新');
            }

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
}