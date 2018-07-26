<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 压缩文件夹
 * @param $files
 * @param $zipName
 */
function zip($files,$zipName){

    $zip = new \ZipArchive;//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
    if ($zip->open($zipName, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE)!==TRUE) {
       return false;
    }
    foreach($files as $val){
        if(file_exists($val)){
            //$zip->addFile($val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
            $zip->addFile($val);//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
        }
    }
    $zip->close();//关闭
    if(!file_exists($zipName)){
        return false; //即使创建，仍有可能失败
    }
    return true;
    //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
    /*header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
    header("Content-Type: application/zip"); //zip格式的
    header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
    header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
    @readfile($zipName);*/

}

/**
 * 删除文件夹
 * @param $path
 * @param bool $delDir
 * @return bool
 */
function del_dir_file($path, $delDir = FALSE) {
    if (is_array($path)) {
        foreach ($path as $subPath)
            delDirAndFile($subPath, $delDir);
    }
    if (is_dir($path)) {
        $handle = opendir($path);
        if ($handle) {
            while (false !== ( $item = readdir($handle) )) {
                if ($item != "." && $item != "..")
                    is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
            }
            closedir($handle);
            if ($delDir)
                return rmdir($path);
        }
    } else {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return FALSE;
        }
    }
    clearstatcache();
}