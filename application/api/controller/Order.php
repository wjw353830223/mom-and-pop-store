<?php
namespace app\api\controller;
use app\common\model\Menu;
use app\common\model\Order as OrderModel;
use GatewayClient\Gateway;
use think\Cache;

class Order extends Apibase
{
    protected $order_model,$menu_model,$user_model,$message_model;
    protected function _initialize() {
        parent::_initialize();
        $this->order_model = model('Order');
        $this->menu_model = model('Menu');
        $this->user_model = model('User');
        $this->message_model = model('Message');
    }
    /**
     *	下单
     */
    public function index(){
        $tid = input('post.tid',0,'intval');
        if(empty($tid)){
            $this->ajax_return('10020','无效的餐桌id');
        }
        $type = $this->member_info['member_type'] == 2 ? 1 : 0;
        $order_param = input('post.order_param','','trim');
        $order_param = json_decode($order_param,true);
        if(empty($order_param)){
            $this->ajax_return('10021','无效的菜单信息');
        }
        foreach($order_param as $param){
            $menu = $this->menu_model->where(['id'=>$param['mid']])->find();
            $status = $menu->getData('status');
            if(empty($menu) || $status != Menu::SALE_STATE_ON_SALE){
                $this->ajax_return('10023','菜单已下架或已售罄');
            }
        }
        $result = $this->order_model->gen_order($tid,$this->member_info['member_id'],$order_param,$type);
        if($result === false){
            $this->ajax_return('10022','点餐失败');
        }
        $admin_ids = $this->user_model->column('id');
        $from_uid = $this->member_info['member_id'];
        $message = [
            'type'=>'order',
            'order_sn'=>$result
        ];
        $uids = [];
        if(!empty($admin_ids)){
            foreach($admin_ids as $admin_id){
                $mid = $this->message_model->addMessage($from_uid,'member',$admin_id,'admin',$message);
                $uids[] = 'admin:'.$admin_id;
            }
            Gateway::sendToUid($uids,json_encode($message));
        }
        $this->ajax_return('200','success',$result);
    }

    /**
     * 订单列表
     */
    public function order_list(){
        $status = input('post.status',1,'intval');
        $page = input('post.page',0,'intval');
        $member_id = $this->member_info['member_id'];
        if(($orders = $this->order_model->order_list($member_id,$page,$status))===false){
            $this->ajax_return('10030','订单查询错误');
        }
        $this->ajax_return('200','success',$orders);
    }
    public function order_cancel(){
        $oid = input('post.oid',0,'intval');
        if(empty($oid)){
            $this->ajax_return('10040','订单id无效');
        }
        $res = $this->order_model->changeOrderStatus($oid,OrderModel::STATUS_CANCEL);
        if($res['code']==-1){
            $this->ajax_return('10041',$res['msg']);
        }
        $this->ajax_return('200','success',[]);
    }
}
