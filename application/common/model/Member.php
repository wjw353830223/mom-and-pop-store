<?php
namespace app\common\model;
use think\Model;
class Member extends Model
{
    protected $order_model;
    const AUTH_SUCCESS = 1;//实名认证通过
    const AUTH_DEFAULT = 0;//未实名认证
    const MEMBER_TYPE_NORMAL = 1;//普通会员
    const MEMBER_TYPE_WAITER = 2;//服务员
    const MEMBER_TYPE_CHIEF = 3;//厨师
    protected function initialize(){
        parent::initialize();
        $this->order_model = model('Order');
    }
    /**
	* 会员详细关联
	*/
    public function info(){
        return $this->hasOne('MemberInfo','member_id','member_id');
    }

    public function has_info($mobile){
    	$member_id = $this->where(['member_mobile' => $mobile])->value('member_id');
    	$result = model('Member')->where(['member_id' => $member_id])->value('member_name');

    	if (!empty($result)) {
    		return true;
    	}else{
    		return false;
    	}
    }
    public function getLoginTimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }
    /*public function getMemberTypeAttr($value){
        $type = [1=>'普通会员',2=>'服务员'];
        return $type[$value];
    }*/
    public function getMembersByWhere($where, $offset, $limit)
    {
        $members = $this->where($where)->limit($offset, $limit)->order('member_time desc')->select();
        $status = [0=>'关闭',1=>'开启'];
        foreach($members as &$member){
            $amount = $this->order_model->where(['member_id'=>$member->member_id,'status'=>Order::STATUS_FINISH])->sum('order_amount');
            $member['amount'] = $amount/100 . '元';
            $member['member_state'] = $status[$member['member_state']];
        }
        unset($member);
        return $members;
    }
    /**
     * 根据搜索条件获取所有的门店数量
     * @param $where
     */
    public function getAllMembers($where)
    {
        return $this->where($where)->count();
    }
    public function changeMemberType($member_id,$type){
        try{
            $result = $this->save(['member_type'=>$type], ['member_id' => $member_id]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('member/index'), '会员角色已更新');
            }

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
}