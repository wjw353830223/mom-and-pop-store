<?php
namespace app\service\controller;
use Workerman\MySQL\Connection;

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose
 */
use \GatewayWorker\Lib\Gateway;

class Push
{
    public static $db;
    //进程启动时 实例化数据库连接 将db实例存储在全局变量中(也可以存储在某类的静态成员中)
    public static function onWorkerStart($worker){
        $db_config = config('database');
        self::$db = new Connection($db_config['hostname'], $db_config['hostport'], $db_config['username'],
            $db_config['password'], $db_config['database'], $db_config['charset']);
    }
    // 当有客户端连接时，将client_id返回，让mvc框架判断当前uid并执行绑定
    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, json_encode(array(
            'type'      => 'init',
            'client_id' => $client_id
        )));
    }
    // 收到消息时
    public static function onMessage($client_id, $message)
    {
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 后台登录 发送订单统计信息
            case 'login':
                if(isset($message_data['role']) && $message_data['role'] == 'admin'){
                    $time = strtotime(date('Y-m-d'));
                    $orders_all = self::$db->query('SELECT sum(p.nums) as nums,p.name FROM (SELECT o.nums,m.name FROM `snake_order_partition` as o 
                    INNER JOIN `snake_menu` as m ON m.id=o.menu_id WHERE o.status=5) as p GROUP BY p.name');
                    $orders_today = self::$db->query('SELECT sum(p.nums) as nums,p.name FROM (SELECT o.nums,m.name FROM `snake_order_partition` as o 
                    INNER JOIN `snake_menu` as m ON m.id=o.menu_id WHERE o.status=5 AND o.created_at > '.$time.') as p GROUP BY p.name');
                    $all = [];
                    $today = [];
                    if(!empty($orders_all)){
                        foreach($orders_all as $val){
                            $all['name'][] = $val['name'];
                            $all['nums'][] = (int)$val['nums'];
                        }
                    }
                    if(!empty($orders_today)){
                        foreach($orders_today as $val){
                            $today['name'][] = $val['name'];
                            $today['nums'][] = (int)$val['nums'];
                        }
                    }
                    //当前在线人数
                    $group_user_count = Gateway::getAllGroupUidCount();
                    $data = ['type'=>'statistic','data'=>['all'=>$all,'today'=>$today,'member'=>$group_user_count]];
                    Gateway::sendToCurrentClient(json_encode($data));
                }
                return;
                //登录成功发送历史消息
            case 'message':
                $time = strtotime(date('Y-m-d'));
                $uid = Gateway::getUidByClientId($client_id);
                if(!Gateway::isUidOnline($uid)){
                    break;
                }
                $role = 'member';
                if(!is_numeric($uid)){
                    $uid = explode(':',Gateway::getUidByClientId($client_id))[1];
                    $role = 'admin';
                }
                $messages = self::$db->query('SELECT `message`,`id` FROM `snake_message` WHERE `to_uid`='.$uid.' 
                    AND `status`=0  AND `to_role`="'.$role.'" AND `create_time` >'.$time.' AND `from_uid` <>'.$uid);
                if(!empty($messages)){
                    foreach($messages as $message){
                        Gateway::sendToCurrentClient($message['message']);
                    }
                }
                break;
            default:
                break;
        }
    }

}
