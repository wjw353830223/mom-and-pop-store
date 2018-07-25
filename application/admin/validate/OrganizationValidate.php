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

class OrganizationValidate extends Validate
{
    protected $rule = [
        ['name', 'require', '门店名称不能为空'],
        ['telphone', 'require', '门店电话不能为空'],
        ['area_id', 'require', '请选择乡/县'],
        ['address', 'require', '请输入详细地址']
    ];

}