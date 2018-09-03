<?php
namespace app\api\controller;
use think\Controller;
class Message extends Apibase
{
    protected $message_model;
    protected function _initialize() {
        parent::_initialize();

        $this->message_model = model('Message');
    }
    /**
     *	下单
     */
    public function changestatus(){
        $message_hash = input('post.message_hash','','trim');
        if(empty($message_hash)){
            $this->ajax_return('10030','无效的消息摘要');
        }
        $message = $this->message_model->where(['message_hash'=>$message_hash,'to_uid'=>$this->member_info['member_id'],
            'to_role'=>'member'])->find();
        if(empty($message)){
            $this->ajax_return('10031','消息不存在');
        }
        $status = input('post.status',0,'intval');
        if(!$this->message_model->changeStatus($message->id,$status)){
            $this->ajax_return('10031','消息状态更新失败');
        }
        $this->ajax_return('200','success',[]);
    }
}
