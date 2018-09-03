if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
// 如果浏览器不支持websocket，会使用这个flash自动模拟websocket协议，此过程对开发者透明
WEB_SOCKET_SWF_LOCATION = "__PUBLIC__/chat/swf/WebSocketMain.swf";
// 开启flash的websocket debug
WEB_SOCKET_DEBUG = true;

var ws, name, client_list={};
var uid = '';
// 连接服务端
function connect() {
    // 创建websocket
    ws = new WebSocket("ws://127.0.0.1:7272");
    //ws = new WebSocket("ws://1457558ty1.51mypc.cn:49485"); //花生壳内网穿透
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
    var login_data = '{"type":"login","role":"admin"}';
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
            $.post('/admin/user/bind',{'client_id':data['client_id']},function(res){
                uid = res.data.uid;
                ws.send('{"type":"message","role":"admin"}');
            });
            break;
        // 服务端ping客户端
        case 'ping':
            ws.send('{"type":"pong"}');
            break;
        //响应点餐
        case 'order':
            play_music()
            if(confirm('用户下单了，单号：'+data['order_sn'])==true){
                var message_hash = $.sha1(JSON.stringify(JSON.parse(e.data)));
                fetchs('/admin/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res=>{
                    console.log(res)
                }).catch(res=>{
                    console.log(res)
                })
            }
            break;
            //服务端响应统计信息
        case 'statistic':
            $('#order_main_all').highcharts({
                "xAxis":{
                    "categories":data['data'].all.name
                },
                "series":[{"name":"历史已完成点餐","data":data['data'].all.nums}],
                "title":{"text":""},
                "chart":{"type":"line"},
                "colors":["#058DC7","#ED561B","#8bbc21","#0d233a"],
                "credits":{"enabled":false},
                "exporting":{"enabled":false},
                "yAxis":{"title":{"text":""}}
            });
            $('#order_main_today').highcharts({
                "xAxis":{
                    "categories":data['data'].today.name
                },
                "series":[{"name":"今日已完成点餐","data":data['data'].today.nums}],
                "title":{"text":""},
                "chart":{"type":"column"},
                "colors":["#058DC7","#ED561B","#8bbc21","#0d233a"],
                "credits":{"enabled":false},
                "exporting":{"enabled":false},
                "yAxis":{"title":{"text":""}}
            });
            var member = data['data'].member;
            $('#member_online').text(member.member);
            $('#waiter_online').text(member.waiter);
            $('#manager_online').text(member.manager);
            break;
        case 'press':
            play_music();
            if(confirm('用户催单了！订单号'+data['order_sn'])==true){
                var message_hash = $.sha1(JSON.stringify(JSON.parse(e.data)));
                fetchs('/admin/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res=>{
                    console.log(res)
                }).catch(res=>{
                    console.log(res)
                })
            }
            break;
    }
}
//播放音乐提醒
function play_music(){
    var audio = $("audio")[0];
    var src=$("#audioPlayer").attr('src');
    // 开始播放当前点击的音频
    audio.play();
    setTimeout(function(){
        audio.pause()
        $("#audioPlayer").attr('src',src);
    },4000);
}