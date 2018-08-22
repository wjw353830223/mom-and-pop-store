<?php

namespace app\common\model;

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
     * 添加一条消息
     * @param $from_uid
     * @param $from_type
     * @param $to_uid
     * @param $to_type
     * @param array $message
     * @return $this
     */
    public function addMessage($from_uid,$from_type,$to_uid,$to_type,$message=[]){
        $message = json_encode($message);
        $messaga_info = $this->where(['from_uid'=>$from_uid,'to_uid'=>$to_uid,'message'=>$message])->find();
        if(empty($messaga_info)){
            $data = [
                'from_uid'=>$from_uid,
                'from_role'=>$from_type,
                'to_uid'=>$to_uid,
                'to_role'=>$to_type,
                'message'=>$message
            ];
            return $this->create($data);
        }else{
            $this->where(['from_uid'=>$from_uid,'to_uid'=>$to_uid,'message'=>$message])->update(['status'=>self::STATUS_DEFAULT]);
        }
        return true;
    }
    public function changeStatus($mid,$status){
        $res = $this->update(['status'=>$status,'id'=>$mid]);
        if($res===false){
            return false;
        }
        return true;
    }

}