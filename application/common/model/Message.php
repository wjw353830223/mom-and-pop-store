<?php

namespace app\common\model;

use GatewayClient\Gateway;
use think\Model;

class Message extends Model
{
    protected $name = 'message';
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    const STATUS_SUCCESS = 1;
    const STATUS_DEFAULT = 0;
    public function getStatusAttr($value)
    {
        $status = [0=>'已发送', 1=>'已接收'];
        return $status[$value];
    }

    /**
     * 添加一条消息记录
     * @param $from_uid
     * @param $from_type
     * @param $to_uid
     * @param $to_type
     * @param array $message
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addMessage($from_uid,$from_type,$to_uid,$to_type,$message=[]){
        $message = json_encode($message);
        $message_hash = md5($message.time());
        $messaga_info = $this->where(['from_uid'=>$from_uid,'to_uid'=>$to_uid,'message_hash'=>$message_hash])->find();
        if(empty($messaga_info)){
            $data = [
                'from_uid'=>$from_uid,
                'from_role'=>$from_type,
                'to_uid'=>$to_uid,
                'to_role'=>$to_type,
                'message'=>$message,
                'message_hash'=>$message_hash
            ];
            $res = $this->create($data);
        }else{
            $res = $this->where(['from_uid'=>$from_uid,'to_uid'=>$to_uid,'message_hash'=>$message_hash])->update(['status'=>self::STATUS_DEFAULT]);
        }
        if($res === false){
            return false;
        }
        return $res->id;
    }

    /**
     * 改变消息发送状态
     * @param $mid
     * @param $status
     * @return bool
     */
    public function changeStatus($mid,$status){
        $res = $this->update(['status'=>$status,'id'=>$mid]);
        if($res===false){
            return false;
        }
        return true;
    }

    /**
     * @param $uid
     * @param $message
     * @return bool
     */
    public function push_message_to_uid($uid,$message){
        Gateway::$registerAddress = '127.0.0.1:1236';
        Gateway::sendToUid($uid,$message);
        return true;
    }

    /**
     *
     * @param $message
     * @param string $group
     * @throws \Exception
     */
    public function push_message_to_group($message,$group='order_manager'){
        Gateway::$registerAddress = '127.0.0.1:1236';
        Gateway::sendToGroup($group, $message);
    }

    /**
     * 推送消息
     * @param $from_uid
     * @param $from_type
     * @param $to_uid
     * @param $to_type
     * @param $message
     * @param bool $save_message  是否保存信息到数据库
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function push_message($from_uid,$from_type,$to_uid,$to_type,$message,$save_message=true){
        if($to_type=='admin'){
            $to_uid = 'admin:'.$to_uid;
        }
        $res = $this->push_message_to_uid($to_uid,$message);
        if($save_message){
            $res = $this->addMessage($from_uid,$from_type,$to_uid,$to_type,$message);
        }
        return $res;
    }
}