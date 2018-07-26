<?php
namespace app\admin\queue;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/26
 * Time: 13:50
 */
use Lib\Qrcode;
use think\Db;
use think\queue\Job;
class TableQrcode{
    public function fire(Job $job, $data)
    {
        $isJobDone = $this->_make_qrcode($data);
        if ($isJobDone) {
            //成功删除任务
            $job->delete();
        } else {
            //任务轮询4次后删除
            if ($job->attempts() > 3) {
                // 第1种处理方式：重新发布任务,该任务延迟10秒后再执行
                //$job->release(10);
                // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
                //$job->failed();
                // 第3种处理方式：删除任务
                $job->delete();
            }
        }
    }
    /*public function failed($data){

        // ...任务达到最大重试次数后，失败了
    }*/
    /**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function _make_qrcode($data)
    {
        $data = json_decode($data,true);
        //$table = Db::name('table')->find($data['id']);
        if($data['num']<=0){
            return false;
        }
        $files = [];
        $save_path = ROOT_PATH ."public".DS."qrcode" .DS.$data['id'].DS;
        for($i=1;$i<$data['num'];$i++){
            $qrcode = new Qrcode();
            $qrcode->content = $data['url'];
            $qrcode->logo_file = false;
            $qrcode->is_save = true;
            $qrcode->save_path = $save_path;
            if(!is_dir($qrcode->save_path)){
                @mkdir($qrcode->save_path,777);
            }
            $qrcode_image = $qrcode->build();
            $files[] = $qrcode->save_path.$qrcode_image;
        }
        $zipName = ROOT_PATH ."public".DS."qrcode" .DS."qrcode_".$data['id'].".zip";
        if(!zip($files,$zipName)){
            return false;
        }
        del_dir_file($save_path, true);
        return true;
    }
}