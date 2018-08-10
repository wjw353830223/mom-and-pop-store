<?php

namespace app\common\model;

use think\Model;

class Classify extends Model
{
    protected $name = 'class';
    /**
     * 查询门店
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getClassifysByWhere($where, $offset, $limit)
    {
        $classifys = $this->where($where)->limit($offset, $limit)->order('id desc')->select();
        foreach($classifys as &$vo){
            $vo['organization'] = model('OrganizationModel')->where(['id'=>$vo['organization_id']])->value('name');
        }
        unset($vo);
        return $classifys;
    }
    public function getClasses($id){
        $query = $this->field('id,name,pid,deep');
        if($id){
            $classify = $this->getById($id);
            $query->where('id','neq',$id)->where('deep','lt',$classify['deep']);
        }
        $classifys = $query->select();
        return $classifys;
    }
    public function getClassifiesByOrganizationId($organization_id){
        $classify = $this->parse_classify($organization_id,0,1);
        foreach($classify as &$value){
            if($value['deep']>1){
                $value['name'] = '|' . str_repeat('-',$value['deep']-1) .  $value['name'];
            }
        }
        unset($value);
        return $classify;
    }
    public function parse_classify($organization_id,$pid=0,$deep=1){
        $classify = $this->where(['organization_id'=>$organization_id,'deep'=>$deep,'pid'=>$pid])->select();
        $return = [];
        if(!empty($classify)){
            foreach($classify  as $key=>$vo){
                $return[$vo['id']] = $vo->toArray();
                $classify_deep = $this->parse_classify($organization_id,$vo['id'],$deep+1);
                if(!empty($classify_deep)){
                    foreach($classify_deep as $kk=>$vv){
                        $return[$vv['id']] = $vv;
                    }
                }
            }
            $return = array_values($return);
        }
        return $return;
    }
    public function parse_classify1($classify){
        foreach($classify as &$value){
            $value = $value->toArray();
            if($value['deep']>1){
                $value['name'] = str_repeat('|',$value['deep']-1) . '-' . $value['name'];
            }
        }
        unset($value);
        $sort = array(
            'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
            'field'     => 'deep',       //排序字段
        );
        $arrSort = array();
        foreach($classify as $uniqid => $row){
            foreach($row as $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }
        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $classify);
        }
        $classify = $this->sort_classify($classify);
        return $classify;
    }
    /**
     * 根据搜索条件获取所有的门店数量
     * @param $where
     */
    public function getAllClassifys($where)
    {
        return $this->where($where)->count();
    }
    public function addClassify($param){
        try{
            $deep = 1;
            if($param['pid']){
                $parent = $this->getById($param['pid']);
                $deep = $parent['deep']+1;
            }
            $param['deep'] = $deep;
            $result = $this->validate('ClassValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('classify/index'), '添加菜单分类成功');
            }
        }catch (\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }
    /**
     * 编辑文章信息
     * @param $param
     */
    public function editClassify($param,$id)
    {
        try{

            $result = $this->validate('ClassValidate')->save($param, ['id' => $id]);

            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{
                return msg(1, url('classify/index'), '修改菜单分类信息成功');
            }
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 根据文章的id 获取文章的信息
     * @param $id
     */
    public function getOneClassify($id)
    {
        $classify = $this->where('id', $id)->find();
        return $classify;
    }

    /**
     * 删除文章
     * @param $id
     */
    public function delClassify($id)
    {
        try{
            $classifys = $this->where(['pid'=>$id])->count();
            if($classifys > 0){
                return msg(-2, '', '请先删除子菜单分类');
            }
            $this->where('id', $id)->delete();
            return msg(1, '', '删除菜单分类成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }
}