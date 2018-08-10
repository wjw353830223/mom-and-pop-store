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
    public function qrcode($id,$num,$url){
        $table = model('TableModel')->find($id);
        $flag = $table->qrcode($id,$num,$url);
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
    public function download($id){
        $zipName = ROOT_PATH ."public".DS."qrcode" .DS."qrcode_".$id.".zip";
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
        @readfile($zipName);
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
            ]
        ];
        $table = model('TableModel')->field('status,seats')->getById($id);
        $status = $table->getData('status');
        $seats = $table['seats'];
        $url = url('index/menu/list','tid='.$id,'html',true);
        $zip = ROOT_PATH ."public".DS."qrcode" .DS."qrcode_".$id.".zip";
        if(is_file($zip)){
            $buttons = array_merge($buttons,[
                '下载' => [
                    'auth' => 'table/download',
                    'href' => "javascript:download(" . $id . ")",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-trash-o'
                ],
                '重新生成' => [
                    'auth' => 'table/qrcode',
                    'href' => "javascript:qrcode($id,$seats,'$url')",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-trash-o'
                ]
            ]);
        }else{
            $buttons = array_merge($buttons,[
                '生成二维码' => [
                    'auth' => 'table/qrcode',
                    'href' => "javascript:qrcode($id,$seats,'$url')",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-trash-o'
                ]
            ]);
        }
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
