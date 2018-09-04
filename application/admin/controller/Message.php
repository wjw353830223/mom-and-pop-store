<?php

namespace app\admin\controller;

use think\Controller;
class Message extends Controller
{

    protected $message_model;
    protected function _initialize() {
        parent::_initialize();
        $this->message_model = model('Message');
    }
    public function changestatus(){
        if(request()->isAjax()){
            $message_hash = input('post.message_hash','','trim');
            if(empty($message_hash)){
                return json('无效的消息摘要','10030');
            }
            $message = $this->message_model->where(['message_hash'=>$message_hash,'to_uid'=>session('id'),
                'to_role'=>'admin'])->find();
            if(empty($message)){
                return json('消息不存在','10031');
            }
            $status = input('post.status',0,'intval');
            if(!$this->message_model->changeStatus($message->id,$status)){
                return json('消息状态更新失败','10032');
            }
            return json('消息更新成功','200');
        }
    }
}
