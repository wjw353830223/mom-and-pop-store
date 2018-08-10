<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use app\common\model\Order as OrderModel;
class Order extends Controller
{
    protected $order_model,$menu_model;
    protected function _initialize() {
        parent::_initialize();
        $this->order_model = model('Order');
        $this->menu_model = model('Menu');
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
            $selectResult = $this->order_model->getOrdersByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $this->order_model->getAllOrders($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        return $this->fetch();
    }
    public function partitions($oid){
        $partitions = model('OrderPartition')->get_menus($oid);
        $data = [];
        foreach($partitions as $key=>$part){
            $data[$key]=[
                'attr_id'=>$part['order_partition_id'],
                'spec' => $part['spec_str'],
                'name' => $part['name']
            ];
        }
        return json($data);
    }
    public function status($mid,$status){
        $order = $this->order_model->find($mid);
        $flag = $order->changeOrderStatus($mid,$status);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
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
                    '无人取餐' => [
                        'auth' => 'order/status',
                        'href' => "javascript:statusChange($mid,4)",
                        'btnStyle' => 'warning',
                        'icon' => 'fa fa-paste'
                    ],
                    '取餐完毕' => [
                        'auth' => 'order/status',
                        'href' => "javascript:statusChange($mid,5)",
                        'btnStyle' => 'success',
                        'icon' => 'fa fa-paste'
                    ],
                ];
                break;
            case OrderModel::STATUS_CANCEL:
            case OrderModel::STATUS_NO_GET:
                $buttons = [
                    '删除订单' => [
                        'auth' => 'table/delete',
                        'href' => "javascript:statusChange($mid,6)",
                        'btnStyle' => 'danger',
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
