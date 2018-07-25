<?php

namespace app\admin\model;

use think\Model;

class OrganizationModel extends Model
{
    protected $name = 'organization';
    /**
     * 查询门店
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getOrganizationsByWhere($where, $offset, $limit)
    {
        $organizations = $this->where($where)->limit($offset, $limit)->order('id desc')->select();
        foreach($organizations as &$vo){
            $address = model('AreaModel')->getFullAddressByAreaId($vo['area_id']);
            $vo['address'] = $address['address'] . $vo['address'];
        }
        unset($vo);
        return $organizations;
    }
    public function getOrganizations(){
        $organizations = $this->field('id,name')->select();
        return $organizations;
    }
    /**
     * 根据搜索条件获取所有的门店数量
     * @param $where
     */
    public function getAllOrganizations($where)
    {
        return $this->where($where)->count();
    }
    public function addOrganization($param){
        try{
            $result = $this->validate('OrganizationValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('organization/index'), '添加门店成功');
            }
        }catch (\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }
    /**
     * 编辑文章信息
     * @param $param
     */
    public function editOrganization($param,$id)
    {
        try{

            $result = $this->validate('OrganizationValidate')->save($param, ['id' => $id]);

            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('organization/index'), '修改门店信息成功');
            }
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 根据文章的id 获取文章的信息
     * @param $id
     */
    public function getOneOrganization($id)
    {
        $organization = $this->where('id', $id)->find();
        $organization['city_id'] = model('AreaModel')->where(['area_id'=>$organization['area_id']])->value('area_parent_id');
        $organization['province_id'] = model('AreaModel')->where(['area_id'=>$organization['city_id']])->value('area_parent_id');
        return $organization;
    }

    /**
     * 删除文章
     * @param $id
     */
    public function delOrganization($id)
    {
        try{
            $this->where('id', $id)->delete();
            return msg(1, '', '删除门店成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
}