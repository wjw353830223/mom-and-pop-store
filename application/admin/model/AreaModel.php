<?php

namespace app\admin\model;

use think\Model;

class AreaModel extends Model
{
    // 确定链接表名
    protected $name = 'area';
    /**
     * 地区三级联动
     * @param $type
     * @param $parent
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function region($type,$parent)
    {
        return $this->where(['area_deep'=>$type,'area_parent_id'=>$parent])->cache(true)->select();
    }
    public function getFullAddressByAreaId($area_id){
        $area = $this->field('area_id,area_name,area_parent_id')->getByAreaId($area_id);
        $city = $this->field('area_id,area_name,area_parent_id')->getByAreaId($area['area_parent_id']);
        $province = $this->field('area_id,area_name,area_parent_id')->getByAreaId($city['area_parent_id']);
        return [
            'area_id'=>$area['area_id'],
            'city_id'=>$city['area_id'],
            'province_id'=>$province['area_id'],
            'address'=>$province['area_name'] . ' ' . $city['area_name'] . ' ' . $area['area_name'] . ' ',
        ];
    }
}
