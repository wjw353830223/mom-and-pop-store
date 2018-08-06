<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/3
 * Time: 14:35
 */
if(!file_exists(__DIR__ . '/data/install.lock')){
    define('BIND_MODULE', 'install');
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
// 定义应用缓存目录
define('RUNTIME_PATH', __DIR__ . '/runtime/');
// 开启调试模式
define('APP_DEBUG', true);
// 加载框架引导文件
require './thinkphp/start.php';
