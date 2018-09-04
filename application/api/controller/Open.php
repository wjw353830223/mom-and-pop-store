<?php
namespace app\api\controller;
use Lib\Sms;
use think\Cache;
class Open extends Apibase
{
	protected $member_model,$token_model,$info_model,$seller_member,$sm_model;
	protected function _initialize(){
    	parent::_initialize();
    	$this->member_model = model('Member');
    	$this->token_model = model('MemberToken');
    }
   
	/**
	* 会员登录
	*/ 
	public function login(){

		$mobile = input('post.mobile','','trim');
		$client_type = input('param.client_type');
		if (empty($mobile)) {
			$this->ajax_return('10010','invalid param');
		}

		//严格验证手机号
		if (!is_mobile($mobile)) {
			$this->ajax_return('10011','invalid mobile');
		}


		$member_info = $this->member_model->where('member_mobile',$mobile)->find();

		if (!empty($member_info)) {

			//锁定用户无法登录
			$member_info = $member_info->toArray();
			if ($member_info['member_state'] < 1) {
				$this->ajax_return('10014','the user is locked');
			}

			//更新登录信息
			$this->member_model->where(['member_mobile' => $mobile])
                ->update(['login_time' => time(),'login_ip' => get_client_ip(0,true),'login_num' => ['exp','login_num+1']]);

			//创建token
			$token = $this->token_model->create_token($mobile,$client_type);
			if (is_null($token)) {
				$this->ajax_return('10013','failed to login');
			}else{
				$this->ajax_return('200','success',['token' => $token]);
			}
		}else{
			//创建会员
			$data = ['member_mobile' => $mobile,'login_num' => 1,'member_time' => time(),'login_time' => time(),'login_ip' => get_client_ip(0,true)];
			$result = $this->member_model->save($data);
			if ($result === false) {
				$this->ajax_return('10013','failed to login');
			}

			//获取添加会员ID
			$member_id = $this->member_model->member_id;
			
			//创建token
			$token = $this->token_model->create_token($mobile,$client_type);
			if (is_null($token)) {
				$this->ajax_return('10013','failed to login');
			}else{
				$this->ajax_return('200','success',['token' => $token]);
			}
		}
	}

    /**
     * 获取服务器时间
     */
    public function get_timestamp(){
        $this->ajax_return('200','success',['timestamp'=>time()]);
    }

    /**
     * 餐桌存在判断
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tid_judge(){
        $tid = input('post.tid',0,'intval');
        $table = model('Table')->where(['id'=>$tid])->find();
        if(!$table){
            $this->ajax_return('10070','该餐桌不存在');
        }
        $this->ajax_return('200','success',[]);
    }

    /**
     * 获取所有的餐桌
     */
    public function tables(){
        $tables = model('Table')->field('id,sign,name')->select();
        if(!$tables){
            $this->ajax_return('10080','餐桌不存在');
        }
        $sign = [1=>'大厅',2=>'包间'];
        foreach($tables as &$vo){
            $vo['sign'] = $sign[$vo['sign']];
        }
        unset($vo);
        $this->ajax_return('200','success',['tables'=>$tables]);
    }
}
