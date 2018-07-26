<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class TableValidate extends Validate
{
    protected $rule = [
        ['name', 'require', '请输入餐桌名称'],
        ['seats', 'require', '请输入座位数'],
        ['organization_id', 'require', '请选择门店'],
        ['sign', 'require', '请选择餐桌位置'],
    ];

}