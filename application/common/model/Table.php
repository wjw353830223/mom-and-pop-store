<?php

namespace app\common\model;

use think\Model;
use think\Queue;

class Table extends Model
{
    protected $name = 'table';
    public function getStatusAttr($value)
    {
        $data=['未使用', '使用中未满桌', '满桌', '被预订', '禁止使用'];
        return $data[$value];
    }
    /**
     * 查询门店
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getTablesByWhere($where, $offset, $limit)
    {
        $tableifys = $this->where($where)->limit($offset, $limit)->order('id desc')->select();
        $sign = [1=>'大厅',2=>'包间'];
        foreach($tableifys as &$vo){
            $vo['organization'] = model('OrganizationModel')->where(['id'=>$vo['organization_id']])->value('name');
            $vo['sign'] = $sign[$vo['sign']];
        }
        unset($vo);
        return $tableifys;
    }
    /**
     * 根据搜索条件获取所有的门店数量
     * @param $where
     */
    public function getAllTables($where)
    {
        return $this->where($where)->count();
    }
    public function addTable($param){
        try{
            $result = $this->validate('TableValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('table/index'), '添加餐桌成功');
            }
        }catch (\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }
    /**
     * 编辑文章信息
     * @param $param
     */
    public function editTable($param,$id)
    {
        try{

            $result = $this->validate('TableValidate')->save($param, ['id' => $id]);

            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('table/index'), '修改餐桌信息成功');
            }
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 根据文章的id 获取文章的信息
     * @param $id
     */
    public function getOneTable($id)
    {
        $table = $this->where('id', $id)->find();
        return $table;
    }

    /**
     * 删除文章
     * @param $id
     */
    public function delTable($id)
    {
        try{
            $this->where('id', $id)->delete();
            return msg(1, '', '删除餐桌类成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
    public function changeTableStatus($id,$status){
        try{
            $result = $this->save(['status'=>$status], ['id' => $id]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('table/index'), '餐桌状态已更新');
            }

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
    public function qrcode($id,$num,$url){
        try{
            $res = Queue::push('app\admin\queue\TableQrcode@fire',json_encode(['id'=>$id,'num'=>$num,'url'=>$url]),null);
            if(false === $res){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('table/index'), '任务已发布，请稍后刷新页面下载');
            }
        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
}