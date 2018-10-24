<?php

namespace app\admin\controller;

use app\admin\model\AreaModel;
use app\admin\model\MenuModel;
use think\Controller;
use think\Request;

class Menu extends Controller
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

            $menu = new MenuModel();
            $selectResult = $menu->getMenusByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                //$selectResult[$key]['thumbnail'] = '<img src="' . $vo['thumbnail'] . '" width="40px" height="40px">';
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $menu->getAllMenus($where);  // 总数据
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
            $info = $file->move(ROOT_PATH . DS . 'upload');
            if($info){
                $src =  '/upload' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(msg(0, ['src' => $src], ''));
            }else{
                // 上传失败获取错误信息
                return json(msg(-1, '', $file->getError()));
            }
        }
    }
    public function changestatus($id,$status){
        $id = input('param.id');
        $status = input('param.status');
        $menu = new MenuModel();
        $flag = $menu->changeMenuStatus($id,$status);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        $status = model('MenuModel')->where(['id'=>$id])->value('status');
        $buttons = [
            '编辑' => [
                'auth' => 'menu/edit',
                'href' => url('menu/edit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'menu/delete',
                'href' => "javascript:articleDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
        switch($status){
            case 0:
                $buttons = array_merge($buttons,['上架' => [
                    'auth' => 'menu/changestatus',
                    'href' => "javascript:statusChange($id ,1)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-paste'
                ]]);
                break;
            case 1:
                $buttons = array_merge($buttons,['下架' => [
                    'auth' => 'menu/changestatus',
                    'href' => "javascript:statusChange($id ,0)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-paste'
                ],'停售' => [
                    'auth' => 'menu/changestatus',
                    'href' => "javascript:statusChange($id ,2)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-paste'
                ]]);
                break;
            case 2:
                $buttons = array_merge($buttons,['出售' => [
                    'auth' => 'menu/changestatus',
                    'href' => "javascript:statusChange($id ,1)",
                    'btnStyle' => 'primary',
                    'icon' => 'fa fa-paste'
                ]]);
                break;
            default:
                break;
        }
        return $buttons;
    }
}
