WEB_BASE_URL = 'http://www.snake.com';
function fetchs(path, method, params) {
    //处理必传参数和选传参数
    function baseParams(params) {
        let signParams = []
        let mustParams
        if (localStorage.getItem('token') == null) {
            mustParams = {
                'client_type': "wap" ,
                '_timestamp': Math.round(new Date().getTime() / 1000)
            }
        } else {
            mustParams = {
                'client_type': "wap",
                '_timestamp': Math.round(new Date().getTime() / 1000),
                'token': localStorage.getItem('token')
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
        var sha1 = $.sha1($.param(signParams));
        mustParams = Object.assign(mustParams, {
            'signature': sha1
        });
        return mustParams
    }
    //封装ui.request
    let lastParam = baseParams(params)
    return new Promise((resolve, reject) => {
        $.post(path,Object.assign({}, lastParam),function(ret,err){
            resolve(ret)
            //reject(ret)
        })
    })
}