<?php

namespace app\admin\controller;

use app\common\model\Member as MemberModel;
use think\Controller;
use think\Request;

class Member extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['member_mobile'] = ['like', '%' . $param['searchText'] . '%'];
            }

            $member = new MemberModel();
            $selectResult = $member->getMembersByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['member_id']));
            }

            $return['total'] = $member->getAllMembers($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        return $this->fetch();
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //城市三级联动
        if(request()->isPost())
        {
            $type=input('post.type');
            switch ($type){
                case 'organization':
                    $data = model('OrganizationModel')->getOrganizations();
                    break;
                case 'class':
                    $organization_id=input('post.organization_id');
                    $data = model('ClassModel')->getClassifiesByOrganizationId($organization_id);
                    break;
                default:
                    break;
            }
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        if(request()->isPost()){
            $param = input('post.');
            $menu = new MenuModel();
            $flag = $menu->addMenu($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $menu = new MenuModel();
        $id = input('param.id');
        $this->assign([
            'menu' => $menu->getOneMenu($id)
        ]);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $menu = new MenuModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $menu->editMenu($param,$id);
            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $id = input('param.id');
        $menu = new MenuModel();
        $flag = $menu->delMenu($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    // 上传缩略图
    public function uploadImg()
    {
        if(request()->isAjax()){

            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            if($info){
                $src =  '/upload' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(msg(0, ['src' => $src], ''));
            }else{
                // 上传失败获取错误信息
                return json(msg(-1, '', $file->getError()));
            }
        }
    }
    public function typechange($member_id,$type){
        $member = new MemberModel();
        $flag = $member->changeMemberType($member_id,$type);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        $status = model('Member')->where(['member_id'=>$id])->value('member_type');
        if($status == MemberModel::MEMBER_TYPE_NORMAL){
            $buttons = [
                '设为服务员' => [
                    'auth' => 'member/typechange',
                    'href' => "javascript:typechange($id,2)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-paste'
                ],
            ];
        }else{
            $buttons = [
                '设为普通会员' => [
                    'auth' => 'member/typechange',
                    'href' => "javascript:typechange($id,1)",
                    'btnStyle' => 'warning',
                    'icon' => 'fa fa-paste'
                ],
            ];
        }

        return $buttons;
    }
}
