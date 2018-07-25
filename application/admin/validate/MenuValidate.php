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

class MenuValidate extends Validate
{
    protected $rule = [
        ['name', 'require', '菜品名称不能为空'],
        ['price', 'require', '请输入菜品价格'],
        ['image', 'require', '请上传菜品图片'],
        ['class_id', 'require', '选择菜品分类'],
    ];

}