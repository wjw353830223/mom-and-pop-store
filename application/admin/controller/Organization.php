<?php

namespace app\admin\controller;

use app\admin\model\AreaModel;
use app\admin\model\OrganizationModel;
use think\Controller;
use think\Request;

class Organization extends Controller
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
                $where['name'] = ['like', '%' . $param['searchText'] . '%'];
            }

            $organization = new OrganizationModel();
            $selectResult = $organization->getOrganizationsByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                //$selectResult[$key]['thumbnail'] = '<img src="' . $vo['thumbnail'] . '" width="40px" height="40px">';
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $organization->getAllOrganizations($where);  // 总数据
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
            $regionModel=new AreaModel();
            $type=input('post.type');
            $parent=input('post.parent');
            $province=$regionModel->region($type,$parent);
            return ['province'=>$province];
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
            $organization = new OrganizationModel();
            $flag = $organization->addOrganization($param);

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
        $organization = new OrganizationModel();
        $id = input('param.id');
        $this->assign([
            'organization' => $organization->getOneOrganization($id)
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
        $organization = new OrganizationModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $organization->editOrganization($param,$id);
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
        $organization = new OrganizationModel();
        $flag = $organization->delOrganization($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '编辑' => [
                'auth' => 'organization/edit',
                'href' => url('organization/edit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'organization/delete',
                'href' => "javascript:articleDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }
}
