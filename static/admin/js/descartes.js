/**
 * 笛卡尔积换算
 * @param {array} [arguments] 多个数组, 如：[[1, 2, 3, 4], [11, 22, 33, 44]]
 * @author Flc
 * @example
 *     descartes([[1, 2, 3, 4], [11, 22, 33, 44], [111, 222, 333, 444]]);
 */
function descartes(args){
    //var args = arguments;
    var rs   = [];

    // A. 校验并转换为JS数组
    for (var i = 0; i < args.length; i++) {
        if (!$.isArray(args[i])) {
            return false;  // 参数必须为数组
        }
    }

    // 两个笛卡尔积换算
    var bothDescartes = function (m, n){
        var r = []
        for (var i = 0; i < m.length; i++) {
            for (var ii = 0; ii < n.length; ii++) {
                var t = [];
                if ($.isArray(m[i])) {
                    t = m[i].slice(0);  //此处使用slice目的为了防止t变化，导致m也跟着变化
                } else {
                    t.push(m[i]);
                }
                t.push(n[ii]);
                r.push(t);
            }
        }
        return r;
    }

    // 多个笛卡尔基数换算
    for (var i = 0; i < args.length; i++) {
        if (i == 0) {
            rs = args[i];
        } else {
            rs = bothDescartes(rs, args[i]);
        }
    }

    return rs;
}
/*var spec1 = [
    {"spec_name":"颜色","spec_value":"红色"},
    {"spec_name":"颜色","spec_value":"蓝色"},
    {"spec_name":"颜色","spec_value":"黄色"},
];
var spec2 = [
    {"spec_name":"尺寸","spec_value":"1m"},
    {"spec_name":"尺寸","spec_value":"2m"},
    {"spec_name":"尺寸","spec_value":"3m"},
];
var spec3 = [
    {"spec_name":"重量","spec_value":"100g"},
    {"spec_name":"重量","spec_value":"200g"},
    {"spec_name":"重量","spec_value":"300g"},
];
var rs = descartes([spec1, spec2, spec3]);
console.log(rs)*/
