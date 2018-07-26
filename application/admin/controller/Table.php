<?php

namespace app\admin\controller;

use app\admin\model\OrganizationModel;
use app\admin\model\TableModel;
use think\Controller;
use think\Request;

class Table extends Controller
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

            $table = new TableModel();
            $selectResult = $table->getTablesByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $table->getAllTables($where);  // 总数据
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
            $organization = new OrganizationModel();
            $organizations = $organization->getOrganizations();
            return json(['organizations'=>$organizations]);
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
            $table = new TableModel();
            $flag = $table->addTable($param);
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
        $table = new TableModel();
        $id = input('param.id');
        $this->assign([
            'table' => $table->getOneTable($id)
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
        $table = new TableModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $table->editTable($param,$id);
            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
    }
    public function changestatus($id,$status){
        $table = model('TableModel')->find($id);
        $flag = $table->changeTableStatus($id,$status);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    public function qrcode($id,$num){
        $table = model('TableModel')->find($id);
        $flag = $table->qrcode($id,$num);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
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
        $table = new TableModel();
        $flag = $table->delTable($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        $buttons = [
            '编辑' => [
                'auth' => 'organization/edit',
                'href' => url('table/edit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'table/delete',
                'href' => "javascript:articleDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ],
            '位码' => [
                'auth' => 'table/qrcode',
                'href' => "javascript:qrcode(" . $id . ")",
                'btnStyle' => 'primary',
                'icon' => 'fa fa-trash-o'
            ]
        ];
        $status = model('TableModel')->where(['id'=>$id])->value('status');
        if($status == 0){
            $buttons = array_merge($buttons,[
                '预订' => [
                    'auth' => 'table/changestatus',
                    'href' => "javascript:statusChange($id ,3)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-trash-o'
                ],
                '禁用' => [
                    'auth' => 'table/changestatus',
                    'href' => "javascript:statusChange($id ,4)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-trash-o'
                ],

            ]);
        }
        return $buttons;
    }
}
