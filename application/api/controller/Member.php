<?php
namespace app\api\controller;
use app\common\model\MemberAuth;
use think\Cache;
use think\View;

class Member extends Apibase
{
    protected $member_model, $token_model, $info_model,$area_model,$seller_model,$sm_model,$mq_model;
    protected $pl_model,$grade_model,$order_model,$member_auth,$cash_model,$pd_model;
    protected function _initialize() {
        parent::_initialize();
        $this->member_model = model('Member');
        $this->token_model = model('MemberToken');
        $this->info_model = model('MemberInfo');
        $this->area_model = model('Area');
        $this->seller_model = model('StoreSeller');
        $this->sm_model = model('SellerMember');
        $this->mq_model = model('MemberQrcode');
        $this->pl_model = model('PointsLog');
        $this->grade_model = model('Grade');
        $this->order_model = model('Order');
        $this->member_auth = model('MemberAuth');
        $this->cash_model = model('PdCash');
        $this->pd_model = model('PdLog');
    }
    /**
     *	会员中心
     */
    public function member_center(){
        $seller_state = ['2','3'];
        $where = [
            'member_id'    => $this->member_info['member_id'],
            'seller_state' => ['in',$seller_state],
            'seller_role'  => ['in',[1,3]]
        ];
        $seller_info = $this->seller_model->where($where)->find();

        $member_info = [
            'ordinary_info' => [],
            'seller_info' => []
        ];
        if(empty($seller_info)){
            if(!empty($this->member_info['member_avatar'])){
                $member_avatar = strexists($this->member_info['member_avatar'],'http') ? $this->member_info['member_avatar'] : config('qiniu.buckets')['images']['domain'] . '/uploads/avatar/'.$this->member_info['member_avatar'];
            }else{
                $member_avatar = '';
            }

            //判断当前用户可升至等级
            $grade_info = Cache::get('grade');
            if(empty($grade_info)){
                $grade_info = $this->grade_model->where(['grade_type'=>1,'grade_state'=>1])->order('grade asc')->select();
                Cache::set('grade', $grade_info);
            }

            $member_data = $this->member_model->where(['member_id'=>$this->member_info['member_id']])->field('experience,member_grade')->find();
            $last_num = $grade_experience = 0;
            if($member_data['member_grade'] == 6){
                $next_num = $this->member_model->where(['parent_id'=>$this->member_info['member_id'],'member_grade'=>['egt',6]])->count();
                foreach($grade_info as $gr_key=>$gr_val){
                    if(6 == $gr_val['grade']){
                        $grade_experience = $gr_val['grade_points'];
                        break;
                    }
                }
                $grade = 'F级消费商';
                if($next_num >= 3){
                    $last_num =  0;
                }else{
                    $last_num =  3 - $next_num;
                }
            }else{
                foreach($grade_info as $gr_key=>$gr_val){
                    if($member_data['experience'] < $gr_val['grade_points']){
                        $grade = $gr_val['grade'];
                        $grade_experience = $gr_val['grade_points'];
                        break;
                    }else{
                        $grade = $member_data['member_grade'];
                        $grade_experience = $gr_val['grade_points'];
                    }
                }
            }

            //购买积分奖励
            $query_condition = [
                'member_id'=>$this->member_info['member_id'],
                'type' => 'order_add'
            ];
            $buy_award = $this->pl_model->whereTime('add_time','month')->where($query_condition)->sum('points');
            $buy_points = !empty($buy_award) ? '+'.$buy_award : 0;

            //等级积分奖励(上一月)
            $query = [
                'member_id'=>$this->member_info['member_id'],
                'type' => 'system_points_award'
            ];
            $grade_points = $this->pl_model->whereTime('add_time','last month')->where($query)->value('points');
            if(empty($grade_points)){
                //获取等级数据

                $member_grade = $this->member_model->where(['member_id'=>$this->member_info['member_id']])->value('member_grade');
                foreach($grade_info as $gr_key=>$gr_val){
                    if($member_grade == $gr_val['grade']){
                        $to_grade_points = $gr_val['grade_expend_present'];
                        break;
                    }
                }

                $to_grade_points = !empty($to_grade_points) ? '-'.$to_grade_points : 0;
            }
            $grade_points = !empty($grade_points) ? '+'.$grade_points : $to_grade_points;

            //分享积分奖励
            $query_condition['type'] = ['in',['grade_return_present','order_parent_present']];
            $share_award = $this->pl_model->whereTime('add_time','month')->where($query_condition)->sum('points');
            $share_points = !empty($share_award) ? '+'.$share_award : 0;

            //用户当前是否具备申请成为消费商的资格
            $member_count = $this->member_model->where(['parent_id'=>$this->member_info['member_id'],'member_grade'=>6])->count();
            $is_apply = $member_data['member_grade'] >= 6 && $member_count >=3 ? 1 : 0;

            $member_info['ordinary_info'] = [
                'member_avatar' => $member_avatar,
                'member_name' => $this->member_info['member_name'],
                'experience' => $member_data['experience'],
                'member_grade' => $member_data['member_grade'],
                'to_grade' => $grade,
                'to_experience' => $grade_experience,
                'last_num' => $last_num,
                'buy_points' => $buy_points,
                'grade_points' => $grade_points,
                'is_apply' => $is_apply,
                'share_points' => $share_points
            ];
        }

        $this->ajax_return('200','success',$member_info);
    }
}
