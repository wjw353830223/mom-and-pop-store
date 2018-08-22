<?php
namespace app\api\controller;
use think\Controller;

class Message extends Controller
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
        $mids = input('post.mids');
        if(empty($mids)){
            $this->ajax_return('10030','无效的消息id');
        }
        $mids = explode(',',$mids);
        $status = input('post.status',0,'intval');
        foreach($mids as $mid){
            if(!$this->message_model->changeStatus($mid,$status)){
                $this->ajax_return('10031','消息状态更新失败');
            }
        }
        $this->ajax_return('200','success',[]);
    }
    /**
     * 全局中断输出
     * @param $code string 响应码
     * @param $msg string 简要描述
     * @param $result array 返回数据
     */
    public function ajax_return($code = '200',$msg = '',$result = array()){
        $data = array(
            'code'   => (string)$code,
            'msg'    =>  $msg,
            'result' => $result
        );
        ajax_return($data);
    }
}
