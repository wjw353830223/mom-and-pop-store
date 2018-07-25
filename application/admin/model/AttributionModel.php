<?php

namespace app\admin\model;

use think\Model;

class AttributionModel extends Model
{
    protected $name = 'attribution';
    public function setPriceAttr($value)
    {
        return $value * 100;
    }
    public function setPreferentialPriceAttr($value)
    {
        return $value * 100;
    }
    public function getPriceAttr($value)
    {
        return $value / 100;
    }
    public function getPreferentialPriceAttr($value)
    {
        return $value / 100;
    }
    public function getSpecAttr($value){
        return json_decode($value,true);
    }
    public function setSpecAttr($value){
        return json_encode($value);
    }
}