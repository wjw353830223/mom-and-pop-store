<?php

namespace app\admin\controller;

use app\admin\model\OrganizationModel;
use app\admin\model\ClassModel;
use think\Controller;
use think\Request;

class Classify extends Controller
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

            $classify = new ClassModel();
            $selectResult = $classify->getClassifysByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $classify->getAllClassifys($where);  // 总数据
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
        if(request()->isPost())
        {
            $id=input('post.id');
            $class = new ClassModel();
            $selectResult = $class->getClasses($id);
            $organization = new OrganizationModel();
            $organizations = $organization->getOrganizations();
            return json(['classify'=>$selectResult,'organizations'=>$organizations]);
        }
        return $this->fetch();
    }
    public function get_organization()
    {
        if(request()->isPost())
        {
            $id=input('post.id');
            $class = model('ClassModel')->getById($id);
            $organization = new OrganizationModel();
            $data = $organization->field('id,name')->where(['id'=>$class['organization_id']])->select();
            return json(['organizations'=>$data]);
        }
    }
    public function organization_search(){
        if(request()->isPost())
        {
            $classify_id=input('post.classify_id');
            $class = model('ClassModel')->getById($classify_id);
            $organization = new OrganizationModel();
            $data = $organization->field('id,name')->where(['id'=>$class['organization_id']])->select();
            return json(['organizations'=>$data]);
        }
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
            $classify = new ClassModel();
            $flag = $classify->addClassify($param);
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
        $classify = new ClassModel();
        $id = input('param.id');
        $this->assign([
            'classify' => $classify->getOneClassify($id)
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
        $organization = new ClassModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $organization->editClassify($param,$id);
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
        $classify = new ClassModel();
        $flag = $classify->delClassify($id);
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
                'href' => url('classify/edit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'classify/delete',
                'href' => "javascript:articleDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }
}
