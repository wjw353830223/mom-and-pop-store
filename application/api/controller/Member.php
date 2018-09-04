<?php
namespace app\api\controller;
use app\common\model\Member as MemberModel;
use GatewayClient\Gateway;

class Member extends Apibase
{
    protected $member_model;
    protected function _initialize() {
        parent::_initialize();
        $this->member_model = model('Member');

    }
    //GatewayClient 绑定用户uid和client_id
    public function bind(){
        $client_id = input('post.client_id','','trim');
        if (empty($client_id)) {
            $this->ajax_return('10010','invalid param');
        }
        Gateway::$registerAddress = '127.0.0.1:1236';
        $uid = $this->member_info['member_id'];
        Gateway::bindUid($client_id, $uid);
        if($this->member_info['member_type'] == MemberModel::MEMBER_TYPE_NORMAL){
            Gateway::joinGroup($client_id,'member');
        }
        if($this->member_info['member_type'] == MemberModel::MEMBER_TYPE_WAITER){
            Gateway::joinGroup($client_id,'waiter');
        }
        $this->ajax_return('200','success',['uid'=>$uid,'member_type'=>$this->member_info['member_type']]);
    }
}
