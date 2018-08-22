<?php
namespace app\api\controller;
use app\common\model\Menu;
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
            'type'=>'order_menu',
            'order_sn'=>$result
        ];
        if(!empty($admin_ids)){
            foreach($admin_ids as $admin_id){
                $this->message_model->addMessage($from_uid,'member',$admin_id,'admin',$message);
            }
        }
        $this->ajax_return('200','success',$result);
    }
}
