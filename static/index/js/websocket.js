if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
// 如果浏览器不支持websocket，会使用这个flash自动模拟websocket协议，此过程对开发者透明
WEB_SOCKET_SWF_LOCATION = "__PUBLIC__/chat/swf/WebSocketMain.swf";
// 开启flash的websocket debug
WEB_SOCKET_DEBUG = true;

var ws, name, client_list={};
var uid = "{:$uid}";

// 连接服务端
function connect() {
    // 创建websocket
    ws = new WebSocket("ws://127.0.0.1:7272");
    //ws = new WebSocket("ws://1457558ty1.51mypc.cn:49485");
    // 当socket连接打开时，输入用户名
    ws.onopen = onopen;
    // 当有消息时根据消息类型显示不同信息
    ws.onmessage = onmessage;
    ws.onclose = function() {
        console.log("连接关闭，定时重连");
        connect();
    };
    ws.onerror = function() {
        console.log("出现错误");
    };
}

// 连接建立时发送登录信息
function onopen()
{
    var login_data = '{"type":"login","role":"member"}';
    ws.send(login_data);
}
// 服务端发来消息时
function onmessage(e)
{
    console.log(e.data);
    var data = eval("("+e.data+")");
    switch(data['type']){
        //发送请求将当前用户和client_id绑定
        case 'init':
            fetchs('/api/member/bind','POST',{'client_id':data['client_id']}).then(res=>{
                uid = res.data.uid;
                ws.send('{"type":"message","role":"member"}');
            }).catch(
            )
            break;
        // 服务端ping客户端
        case 'ping':
            ws.send('{"type":"pong"}');
            break;
        case 'notice':
            play_music();
            alert('您点的菜已经做好了，请到前台领餐');
            break;
        case 'waiter':
            play_music();
            layer.open({
                title: '信息提示'
                ,content: data['table_name']+'号桌呼叫服务员，请你协助！',
                yes:function(index, layero){
                    fetchs('/api/message/changestatus','POST',{mids:""+data['mids'],status:1}).then(res=>{
                        if('200' == res.code){
                            layer.alert('消息已确认！', {title: '友情提示', icon: 1, closeBtn: 0});
                        }
                    }).catch(
                    )
                    layer.close(index);
                }
            });
            break;
    }
}
//制作
function ws_notice(mid){
    var order_data = '{"type":"notice","oid":'+mid+'}';
    ws.send(order_data);
}
//催单
function ws_press(oid){
    var press_data = '{"type":"press","oid":'+oid+'}';
    console.log("发送催单数据:"+press_data);
    ws.send(press_data);
}
//呼叫服务员
function ws_waiter(){
    var tid = localStorage.getItem('table_id');
    var waiter_data = '{"type":"waiter","uid":'+uid+',"tid":'+tid+'}';
    console.log("发送呼叫服务员数据:"+waiter_data);
    ws.send(waiter_data);
}