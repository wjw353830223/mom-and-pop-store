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

        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                if(isset($message_data['role']) && $message_data['role'] == 'admin'){
                    // 给当前后台用户发送数据
                    $new_message = [];
                    for($i=0;$i<=11;$i++){
                        $new_message[]=random_int(0,50);
                    }
                    $data = ['type'=>'login','data'=>$new_message];
                    Gateway::sendToCurrentClient(json_encode($data));
                }
                Gateway::bindUid($client_id, $message_data['uid']);
                return;
                //用户订餐
            case 'order':
                // 给后台用户发送实时数据
                $new_message = [];
                for($i=0;$i<=11;$i++){
                    $new_message[]=random_int(0,50);
                }
                $data = ['type'=>'order','data'=>$new_message];
                Gateway::sendToUid(1,json_encode($data));
                return;
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
