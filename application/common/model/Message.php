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
        $message_hash = sha1($message);
        $message_info = $this->where(['from_uid'=>$from_uid,'to_uid'=>$to_uid,'message_hash'=>$message_hash])->find();
        if(empty($message_info)){
            $data = [
                'from_uid'=>$from_uid,
                'from_role'=>$from_type,
                'to_uid'=>$to_uid,
                'to_role'=>$to_type,
                'message'=>$message,
                'message_hash'=>$message_hash
            ];
            $message = $this->create($data);
            if($message === false){
                return false;
            }
        }else{
            $message = $this->where(['from_uid'=>$from_uid,'to_uid'=>$to_uid,'message_hash'=>$message_hash])->update(['status'=>self::STATUS_DEFAULT]);
            if($message === false){
                return false;
            }
            $message = $message_info;
        }

        return $message->id;
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
        Gateway::$registerAddress = config('gateway.register_address');
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
        Gateway::$registerAddress = config('gateway.register_address');
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

    /**
     * 给在线点餐管理员推送并保存消息
     * @param $from_uid
     * @param $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function push_message_to_manager($from_uid,$message){
        $admin_ids = model('User')->where(['status'=>1])->where('role_id','in',[1,2])->column('id');
        if(empty($admin_ids)){
            return false;
        }
        $uids = [];
        Gateway::$registerAddress = config('gateway.register_address');
        foreach($admin_ids as $admin_id){
            $uid = 'admin:'.$admin_id;
            if(Gateway::isUidOnline($uid)){
                $this->addMessage($from_uid,'member',$admin_id,'admin',$message);
                $uids[] = $uid;
            }
        }
        Gateway::sendToUid($uids,json_encode($message));
        return true;
    }

    /**
     * 随机选择在线的服务员推送并保存信息
     * @param $waiters_online
     * @param $from_uid
     * @param $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function push_message_to_random_online_waiter($waiters_online,$from_uid,$message){
        if(empty($waiters_online)){
            return false;
        }
        $index= random_int(0,count($waiters_online)-1);
        $to_uid = $waiters_online[$index]['member_id'];
        $this->addMessage($from_uid,'member',$to_uid,'member',$message);
        //通知服务员
        Gateway::$registerAddress = config('gateway.register_address');
        Gateway::sendToUid($to_uid,json_encode($message));
        return $waiters_online[$index];
    }

    /**
     * 给会员推送并保存消息
     * @param $from_uid
     * @param $to_uid
     * @param $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function push_message_to_member($from_uid,$to_uid,$message){
        if(empty($from_uid) || empty($to_uid)){
            return false;
        }
        $this->addMessage($from_uid,'admin',$to_uid,'member',$message);
        Gateway::$registerAddress = config('gateway.register_address');
        Gateway::sendToUid($to_uid,json_encode($message));
        return true;
    }
}