let websocket_base_path = 'ws://1457558ty1.51mypc.cn:57899';//'ws://192.168.1.100:7272';
//处理必传参数和选传参数
function baseParams(params) {
  let signParams = []
  let mustParams
  if (ui.getStorageSync('token') == null || ui.getStorageSync('token') == undefined || ui.getStorageSync('token') == '') {
    mustParams = {
      'client_type': ui.IS_ANDROID ? "android" : "ios",
      '_timestamp': Math.round((new Date().getTime() + Math.random() * 100000) / 1000)
    }
  } else {
    mustParams = {
      'client_type': ui.IS_ANDROID ? "android" : "ios",
      '_timestamp': Math.round((new Date().getTime() + Math.random() * 100000) / 1000),
      'token': ui.getStorageSync('token')
    }
  }

  // 判断传参是否未定义，否则判断_timestamp是否未定义(主要用于支付接口中form表单的_timestamp)
  if (params !== undefined) {
    if (Object.keys(params).length > 0) {
      if (params._timestamp !== undefined) {
        mustParams._timestamp = params._timestamp
      }
    }
  }
  mustParams = Object.assign(mustParams, params)
  for (let [name, value] of Object.entries(mustParams)) {
    signParams.push({
      name,
      value
    })
  }

  signParams.sort(function (a, b) {
    return a.name > b.name ? 1 : a.name < b.name ? -1 : 0;
  });

  var sha2 = $.sha1($.param(signParams));

  mustParams = Object.assign(mustParams, {
    'signature': sha2
  });

  return mustParams
}
/**
 * 请求数据的方法
 * @param {string} path 接口地址 {/api/...}
 * @param {string} method 访问方式 {POST,GET...}
 * @param {object} params 传递的参数 {client_type,_timestamp,signature...}
 */
function fetchs(path, method, params) {
  //封装ui.request
  let lastParam = baseParams(params)

  return new Promise((resolve, reject) => {
    ui.request({
      url: `${path}`,
      method: method,
      data: Object.assign({}, lastParam),
      header: {
        'content-type': 'application/x-www-form-urlencoded'
      },
      success: resolve,
      fail: reject
    })

  })
}
/**
 * 建立websocket链接
 */
function estiblashWs(){
  ui.connectSocket({
    url:websocket_base_path,
    success:function(){
      ui.onSocketOpen(res=>{
        ui.sendSocketMessage({
          data:JSON.stringify({type:'login',role:'member'})
        });
      });
      ui.onSocketError(res => {
        ui.showToast({
          title: 'WebSocket连接打开失败，请检查！'
        })
      });
      ui.onSocketClose(res=>{
        ui.showToast({
          title: 'WebSocket 已关闭！'
        })
        estiblashWs();
      });
    }
  });
  ui.onSocketMessage(function (res) {
    let data = JSON.parse(res.data);
      switch(data.type){
      //发送请求将当前用户和client_id绑定 并获取用户历史消息
        case 'init':
          fetchs('/api/member/bind','POST',{'client_id':data.client_id}).then(res1=>{
              let member_type = res1.data.result.member_type;
              ui.setStorageSync('member_type',member_type);
              ui.sendSocketMessage(
                {
                  data:JSON.stringify({
                    type:'message',role:'member'
                  })
                }
              );
          }).catch(res1=>{
            console.log(res1)
          })
          break;
        // 服务端ping客户端
        case 'ping':
            ui.sendSocketMessage({data:JSON.stringify({type:'pong',test:'test'})});
            break;
        //通知取餐
        case 'notice':
            ui.showConfirm({
              content: '您点的菜已经做好了，请到前台领餐',
              confirmButtonText: '去领餐',
              cancelButtonText: '返回',
              success (result) {
                if(result.confirm){
                  let message_hash = $.sha1(JSON.stringify(data));
                  fetchs('/api/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res1=>{
                      console.log(res1)
                  }).catch(res1=>{
                      console.log(res1)
                  })
                }
                location.reload()
              }
            })
            break;
        //呼叫服务员
        case 'waiter':
            ui.showConfirm({
              content: data.table+'用户呼叫服务员，请你协助！',
              confirmButtonText: '马上去协助',
              cancelButtonText: '返回',
              success (result) {
                if(result.confirm){
                  location.reload();
                  let message_hash = $.sha1(res.data);
                  fetchs('/api/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res1=>{
                      console.log(res1)
                  }).catch(res=>{
                      console.log(res1)
                  })
                }
                location.reload()
              }
            })
            break;
          case 'order':
            ui.showConfirm({
              content: '用户下单了，单号：'+data.order_sn,
              confirmButtonText: '确定',
              cancelButtonText: '返回',
              success (result) {
                if(result.confirm){
                  let message_hash = $.sha1(JSON.stringify(data));
                  fetchs('/api/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res1=>{
                     console.log(res1)
                  }).catch(res1=>{
                      console.log(res1)
                  })
                }
                location.reload()
              }
            })
            break;
        case 'cancel':
            ui.showConfirm({
              content: '用户取消订单，单号：'+data.order_sn,
              confirmButtonText: '确定',
              cancelButtonText: '返回',
              success (result) {
                if(result.confirm){
                  let message_hash = $.sha1(JSON.stringify(data));
                  fetchs('/api/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res1=>{
                    console.log(res1)
                  }).catch(res1=>{
                    console.log(res1)
                  })
                }
                location.reload()
              }
            })
            break;
        case 'press':
            ui.showConfirm({
              content: '用户催单了！，单号：'+data.order_sn,
              confirmButtonText: '确定',
              cancelButtonText: '返回',
              success (result) {
                if(result.confirm){
                  let message_hash = $.sha1(JSON.stringify(data));
                  fetchs('/api/message/changestatus','POST',{'message_hash':message_hash,'status':1}).then(res1=>{
                    console.log(res1)
                  }).catch(res1=>{
                    console.log(res1)
                  })
                }
                location.reload()
              }
            })
            break;
        default:
            break;
      }
    })
  }
/**
 * 前后台切换
 * @param {*} page 
 */
function visibilitychange(){
  document.addEventListener('visibilitychange', function() { 
    let isHidden = document.hidden; 
    let token = ui.getStorageSync('token');
    if(isHidden===false && token){
      estiblashWs();
    }
  });
}
export {
  fetchs,
  estiblashWs,
  visibilitychange,
  websocket_base_path
}