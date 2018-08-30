<?php
namespace app\index\controller;


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
use GatewayWorker\Lib\Db;
use \GatewayWorker\Lib\Gateway;

class Push
{
    /**
     * 有消息时
     * @param int $client_id
     * @param mixed $message
     */
    public static function onMessage($client_id, $message)
    {
        // debug
        //echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";

        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        $db = Db::instance('db');
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                $time = strtotime(date('Y-m-d'));
                if(isset($message_data['role']) && $message_data['role'] == 'admin'){
                    if($message_data['uid'] == 1){
                        $new_message = [];
                        for($i=0;$i<=11;$i++){
                            $new_message[]=random_int(0,50);
                        }
                        $data = ['type'=>'login','data'=>$new_message];
                        Gateway::sendToCurrentClient(json_encode($data));
                    }
                    Gateway::bindUid($client_id, 'admin:'.$message_data['uid']);
                    $messages = $db->query('SELECT `message`,`id` FROM `snake_message` WHERE `to_uid`='.$message_data['uid'].' 
                    AND `status`=0  AND `to_role`="admin" AND `create_time` >'.$time);
                    if(!empty($messages)){
                        foreach($messages as $message){
                            Gateway::sendToCurrentClient($message['message']);
                            $db->query('UPDATE `snake_message` set `status`=1 WHERE `id`='.$message['id']);
                        }
                    }
                }
                if(isset($message_data['role']) && $message_data['role'] == 'user'){
                    Gateway::bindUid($client_id, $message_data['uid']);
                    $messages = $db->query('SELECT `message`,`id` FROM `snake_message` WHERE `to_uid`='.$message_data['uid'].' 
                    AND `status`=0  AND `to_role`="member" AND `from_uid`=1 AND `create_time` >'.$time);
                    if(!empty($messages)){
                        foreach($messages as $message){
                            Gateway::sendToCurrentClient($message['message']);
                        }
                        $db->query('UPDATE `snake_message` set `status`=1 WHERE `to_uid`='.$message_data['uid'].' 
                    AND `status`=0  AND `to_role`="member" AND `create_time` >'.$time);
                    }
                }

                return;
                //用户订餐
            case 'order':
                // 给后台用户发送实时数据
                $new_message = [];
                for($i=0;$i<=11;$i++){
                    $new_message[]=random_int(0,50);
                }
                $data = ['type'=>'order','data'=>$new_message];
                Gateway::sendToUid('admin:'.$message_data['uid'],json_encode($data));
                return;
            case 'notice':
                //通知前端用户取餐
                $oid = $message_data['oid'];
                $member = $db->query('SELECT `member_id` FROM `snake_order` WHERE `id`='.$oid);
                $message = json_encode([
                    'type'=>'notice',
                    'oid'=>(string)$oid
                ]);
                $mes = $db->query("SELECT `id` from `snake_message` WHERE `to_uid`=".$member[0]['member_id']." 
                 AND `to_role`='member' AND `message`='$message'");
                $mids = [];
                if(!empty($mes)){
                    foreach($mes as $val){
                        $mids[]=$val['id'];
                    }
                }
                $data = ['type'=>'notice','data'=>$oid,'mids'=>$mids];
                Gateway::sendToUid($member[0]['member_id'],json_encode($data));
                return;
            case 'order_menu':
                //通知后台已下单
                $order_sn =  number_format($message_data['order_sn'], 0, '', '');
                $message = json_encode([
                    'type'=>'order_menu',
                    'order_sn'=>$order_sn
                ]);
                $from_uid = $message_data['from_uid'];
                $mes = $db->query("SELECT `id` from `snake_message` WHERE `from_uid`=".$from_uid." 
                 AND `to_role`='admin' AND `message`='$message'");
                $mids = [];
                if(!empty($mes)){
                    foreach($mes as $val){
                        $mids[]=$val['id'];
                    }
                }
                $data = ['type'=>'order_menu','order_sn'=>$order_sn,'mids'=>$mids];
                $admin_ids = $db->query('SELECT `id` from `snake_user` WHERE `status`=1');
                if(!empty($admin_ids)){
                    foreach($admin_ids as $uid){
                        Gateway::sendToUid('admin:'.$uid['id'],json_encode($data));
                    }
                }
                return;
            case 'press':
                //催单
                $order = $db->query('SELECT `member_id`,`order_sn` FROM `snake_order` WHERE `id`='.$message_data['oid']);
                $message = json_encode([
                    'type'=>'press',
                    'oid'=>$message_data['oid']
                ]);
                $time = time();
                $mids = [];
                $admin_ids = $db->query('SELECT `id` FROM `snake_user` WHERE `status`=1');
                if(!empty($admin_ids)) {
                    foreach($admin_ids as $uid){
                        $res = $db->query("SELECT * FROM `snake_message` WHERE `from_uid`=".$order[0]['member_id']." AND 
                        `to_uid`=".$uid['id']." AND `message`='$message'");
                        if(empty($res)){
                            $db->query("INSERT INTO `snake_message` set `from_uid`=".$order[0]['member_id'].",`from_role`='member',
                `to_uid`=".$uid['id'].",`to_role`='admin',`message`='$message',`create_time`=$time");
                            $mids[] = $db->lastInsertId();
                        }else{
                            $db->query("UPDATE `snake_message` set `status`=0 WHERE `from_uid`=".$order[0]['member_id']." AND 
                        `to_uid`=".$uid['id']." AND `message`='$message'");
                            $mids[] = $res[0]['id'];
                        }
                    }
                    //催单
                    $db->query('UPDATE `snake_order` set `press_status`=1 WHERE id='.$message_data['oid']);
                    $data = ['type'=>'press','order_sn'=>$order[0]['order_sn'],'mids'=>$mids];
                    foreach($admin_ids as $uid){
                        Gateway::sendToUid('admin:'.$uid['id'],json_encode($data));
                    }
                }
                break;
            case 'waiter':
                //随机分配一个服务员并推送呼叫信息到该服务员
                $waiters = $db->query('SELECT * FROM `snake_member` WHERE `member_state`=1 AND `member_type`=2');
                $message = json_encode([
                    'type'=>'waiter',
                    'uid'=>$message_data['uid'],
                    'tid'=>$message_data['tid']
                ]);
                $time = time();
                $mids = [];
                if(!empty($waiters)){
                    $index= random_int(0,count($waiters)-1);
                    $to_uid = $waiters[$index]['member_id'];
                    $res = $db->query('SELECT * FROM `snake_message` WHERE `from_uid`='.$message_data['uid']." AND
                    `from_role`='member' AND `to_role`='member' AND `to_uid`=".$to_uid);
                    if(empty($res)){
                        $db->query("INSERT INTO `snake_message` set `from_uid`=".$message_data['uid'].",`from_role`='member',
                `to_uid`=".$to_uid.",`to_role`='member',`message`='$message',`create_time`=$time");
                        $mids[] = $db->lastInsertId();
                    }else{
                        $db->query("UPDATE `snake_message` set `status`=0 WHERE `from_uid`=".$message_data['uid']." AND 
                        `to_uid`=".$to_uid." AND `message`='$message'");
                        $mids[] = $res[0]['id'];
                    }
                    $table = $db->query('SELECT `name` FROM `snake_table` WHERE `id`='.$message_data['tid']);
                    //通知服务员
                    $data = ['type'=>'waiter','table_name'=>$table[0]['name'],'mids'=>$mids];
                    Gateway::sendToUid($to_uid,json_encode($data));
                }
                break;
            default:
                break;
        }
    }

    /**
     * 当客户端断开连接时
     * @param integer $client_id 客户端id
     */
    public static function onClose($client_id)
    {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";

        // 从房间的客户端列表中删除
        if(isset($_SESSION['room_id']))
        {
            $room_id = $_SESSION['room_id'];
            $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
            Gateway::sendToGroup($room_id, json_encode($new_message));
        }
    }

}
