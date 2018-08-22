<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\common\model\Order as OrderModel;
class Order extends Controller
{
    protected $order_model,$menu_model,$order_partition_model,$user_model,$message_model;
    protected function _initialize() {
        parent::_initialize();
        $this->order_model = model('Order');
        $this->order_partition_model = model('OrderPartition');
        $this->menu_model = model('Menu');
        $this->user_model = model('User');
        $this->message_model = model('Message');
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['mobile'])) {
                $member_id = model('Member')->where(['member_mobile'=>$param['mobile']])->value('member_id');
                $where['member_id'] = $member_id;
            }
            if (!empty($param['order_sn'])) {
                $where['order_sn'] = $param['order_sn'];
            }
            if (!empty($param['status'])) {
                $where['status'] = $param['status'];
            }
            $selectResult = $this->order_model->getOrdersByWhere($where, $param['timeStart'],$param['timeEnd'],$offset, $limit);
            $total_amount = 0;
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
                if($vo['status_prev'] == OrderModel::STATUS_FINISH){
                    $total_amount += $vo['order_amount'];
                }
            }
            $return['total_amount'] = $total_amount / 100;
            $return['total'] = $this->order_model->getAllOrders($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        $uid = session('id');
        $this->assign('uid',$uid);
        return $this->fetch();
    }
    public function partitions($oid){
        $partitions = model('OrderPartition')->get_menus($oid);
        $data = [];
        foreach($partitions as $key=>$part){
            $data[$key]=[
                'order_partition_id'=>$part['order_partition_id'],
                'spec' => $part['spec_str'],
                'name' => $part['name'],
                'status'=>$part['status'],
            ];
        }
        return json($data);
    }
    public function notice($oid,$order_partition_ids){
        if(empty($order_partition_ids)){
            return json(msg(-1, '', '未选择要通知的菜品'));
        }
        $admin_ids = $this->user_model->column('id');
        $to_uid = $this->order_model->where(['id'=>$oid])->value('member_id');
        $message = [
            'type'=>'notice',
            'oid'=>$oid
        ];
        if(!empty($admin_ids)){
            foreach($admin_ids as $admin_id){
                $this->message_model->addMessage($admin_id,'admin',$to_uid,'member',$message);
            }
        }
        foreach($order_partition_ids as $id) {
            $res = $this->order_partition_model->changeOrderStatus($oid,$id,OrderModel::STATUS_GET);
            if($res['code']== -1){
                return json($res);
            }
        }
        return json($res);
    }
    public function status($mid,$status){
        $order = $this->order_model->find($mid);
        $flag = $order->changeOrderStatus($mid,$status);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    public function no_get($mid){
        $order_partitions = $this->order_partition_model->where(['order_id'=>$mid])->field('id,status')->select();
        foreach($order_partitions as $order) {
            if($order['status'] == OrderModel::STATUS_GET){
                $res = $this->order_partition_model->changeOrderStatus($mid,$order['id'],OrderModel::STATUS_NO_GET);
                if($res['code']== -1){
                    return json($res);
                }
            }
        }
        return json($res);
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($mid)
    {
        $status = $this->order_model->where(['id'=>$mid])->value('status');
        switch ($status){
            case OrderModel::STATUS_ORDER:
                $buttons = [
                    '菜品制作' => [
                        'auth' => 'order/status',
                        'href' => "javascript:statusChange($mid,2)",
                        'btnStyle' => 'primary',
                        'icon' => 'fa fa-paste'
                    ]
                ];
                break;
            case OrderModel::STATUS_MAKE:
                $buttons = [
                    '通知取餐' => [
                        'auth' => 'order/status',
                        'href' => "javascript:notice($mid)",
                        'btnStyle' => 'info',
                        'icon' => 'fa fa-paste'
                    ],
                ];
                break;
            case OrderModel::STATUS_GET:
                $buttons = [
                    '通知取餐' => [
                        'auth' => 'order/status',
                        'href' => "javascript:notice($mid)",
                        'btnStyle' => 'info',
                        'icon' => 'fa fa-paste'
                    ],
                    '无人取餐' => [
                        'auth' => 'order/status',
                        'href' => "javascript:noGet($mid)",
                        'btnStyle' => 'warning',
                        'icon' => 'fa fa-paste'
                    ],
                ];
                $count = $this->order_partition_model->where(['order_id'=>$mid])->count();
                $num = $this->order_partition_model->where(['order_id'=>$mid,'status'=>OrderModel::STATUS_GET])->count();
                if($count == $num){
                    $buttons = array_merge($buttons,[
                        '取餐完毕' => [
                            'auth' => 'order/status',
                            'href' => "javascript:statusChange($mid,5)",
                            'btnStyle' => 'success',
                            'icon' => 'fa fa-paste'
                        ],
                    ]);
                }
                break;
            case OrderModel::STATUS_CANCEL:
                $buttons = [
                    '删除订单' => [
                        'auth' => 'order/delete',
                        'href' => "javascript:statusChange($mid,6)",
                        'btnStyle' => 'danger',
                        'icon' => 'fa fa-trash-o'
                    ]
                ];
                break;
            case OrderModel::STATUS_NO_GET:
                $buttons = [
                    '重新制作' => [
                        'auth' => 'order/reopen',
                        'href' => "javascript:statusChange($mid,1)",
                        'btnStyle' => 'primary',
                        'icon' => 'fa fa-trash-o'
                    ]
                ];
                break;
            default:
                $buttons = [];
                break;
        }
        return $buttons;
    }
}
