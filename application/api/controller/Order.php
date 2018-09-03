<?php
namespace app\api\controller;
use app\common\model\Menu;
use app\common\model\Order as OrderModel;
use GatewayClient\Gateway;
use think\Cache;
use app\common\model\Member;

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
                $uid = 'admin:'.$admin_id;
                if(Gateway::isUidOnline($uid)){
                    $this->message_model->addMessage($from_uid,'member',$admin_id,'admin',$message);
                    $uids[] = $uid;
                }
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
    public function call_waiter(){
        $tid = input('post.tid',0,'intval');
        if(empty($tid)){
            $this->ajax_return('10050','餐桌id无效');
        }
        //随机分配一个服务员并推送呼叫信息到该服务员
        $waiters = model('Member')->where(['member_state'=>1,'member_type'=>Member::MEMBER_TYPE_WAITER])->select();
        $table = model('Table')->where(['id'=>$tid])->value('name');
        $message = [
            'type'=>'waiter',
            'table'=>$table
        ];
        if(empty($waiters)){
            $this->ajax_return('10051','系统暂未分配服务员角色！');
        }
        $waiters_online = [];
        foreach($waiters as $waiter){
            if(Gateway::isUidOnline($waiter['member_id'])){
                $waiters_online[] = $waiter->toArray();
            }
        }
        if(empty($waiters_online)){
            $this->ajax_return('10052','服务员都不在线！');
        }
        $index= random_int(0,count($waiters_online)-1);
        $to_uid = $waiters_online[$index]['member_id'];
        $waiter_mobile = $waiters_online[$index]['member_mobile'];
        $this->message_model->addMessage($this->member_info['member_id'],'member',$to_uid,'member',$message);
        //通知服务员
        Gateway::sendToUid($to_uid,json_encode($message));
        $this->ajax_return('200','已通知服务员'.$waiter_mobile.',请耐心等待...',[]);
    }

    /**
     * 催单
     */
    public function order_press(){
        $oid = input('post.oid',0,'intval');
        if(empty($oid)){
            $this->ajax_return('10060','订单id无效');
        }
        $order_sn = model('Order')->where(['id'=>$oid])->value('order_sn');
        $message = [
            'type'=>'press',
            'order_sn'=>$order_sn
        ];
        $admin_ids = $this->user_model->column('id');
        $from_uid = $this->member_info['member_id'];
        $uids = [];
        if(!empty($admin_ids)){
            foreach($admin_ids as $admin_id){
                $uid = 'admin:'.$admin_id;
                if(Gateway::isUidOnline($uid)){
                    $this->message_model->addMessage($from_uid,'member',$admin_id,'admin',$message);
                    $uids[] = $uid;
                }
            }
            Gateway::sendToUid($uids,json_encode($message));
        }
        $this->ajax_return('200','催单成功！',[]);
    }
}
