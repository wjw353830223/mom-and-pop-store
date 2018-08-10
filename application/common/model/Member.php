<?php
namespace app\common\model;
use think\Model;
class Member extends Model
{
	const AUTH_SUCCESS = 1;//实名认证通过
    const AUTH_DEFAULT = 0;//未实名认证
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
}