<?php if (!defined('THINK_PATH')) exit();?><html>
<head>
    <meta charset="utf-8">
    <link href="/Public/css/index.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="http://code.jquery.com/jquery-2.0.0.min.js"></script>
    <script type="text/javascript" src="/Public/js/ajaxfileupload.js"></script>
</head>
<body>

<h1 style="font-family:'华文楷体';">处方药接口测试</h1>

<div class="parms_div">
    <span style="font-family:'华文楷体';">接口名称：</span>
    <select id="apipick" autocomplete="off">
        <?php if(is_array($data['api'])): foreach($data['api'] as $k=>$v): ?><option value="<?php echo ($v['url']); ?>"  ><?php echo ($v['url']); ?></option><?php endforeach; endif; ?>
    </select>
</div>

<div class="parms_div">
    <?php if ( 0 == count($data['api']) ) { ?>
    <div class="canshu" style="font-family:'华文楷体';">接口参数：没有参数</div>
    <?php } else { ?>
    <div class="canshu" style="font-family:'华文楷体';">接口参数：</div>
    <?php $i = 1 ?>
    <?php foreach ($data['api'] as $key => $value): ?>

    <div id='<?php echo $i ?>' style="display:block;" class="parms">

        <form action="<?php echo $key ?>">
            <table>
                <?php foreach ($value['request'] as $key => $value): ?>
                <?php
 if (is_string($value) && strpos($value, '{') !== false && strpos($value, '}') !== false){ $letters = array('{', '}'); $fruit = array(' ', ' '); $model = strtoupper(trim(str_replace($letters, $fruit, $value))); if (isset($data['model'][$model])) { echo '<tr><td class="align">'.$model."：</td><td>"; foreach ($data['model'][$model] as $k => $v) { echo trim(str_replace('!', '',$k)).':&nbsp;<input type="text" style="font-family:华文楷体;" name="'.trim(str_replace('!', '',$k)).'" placeholder="'.$v.'" ><br/>'; } echo '</td></tr>'; } }else{ if (is_array($value)){ $value = implode(',', $value); } $pic_arr = array("images"); if(in_array(trim(str_replace('!', '',$key)),$pic_arr)){ echo '<tr><td class="align">'.trim(str_replace('!', '',$key)).'：</td><td><input type="file" style="font-family:华文楷体;" id="'.trim(str_replace('!', '',$key)).'" name="'.trim(str_replace('!', '',$key)).'" placeholder="'.$value.'" ></td></tr>'; }else{ echo '<tr><td class="align">'.trim(str_replace('!', '',$key)).'：</td><td><input type="text" style="font-family:华文楷体;" name="'.trim(str_replace('!', '',$key)).'" placeholder="'.$value.'" ></td></tr>'; } } ?>

                <?php endforeach ?>
            </table>
        </form>

    </div>
</div>

<?php  $i++ ?>
<?php endforeach ?>

<?php }?>

<div class="parms_div">
    <button id="send" style="font-family:华文楷体;">发送</button>
</div>
<div class="parms_div">
    <span style="font-family:华文楷体;">返回结果：</span><br/>
    <div id="request" clsss="result" style="font-family:微软雅黑">
    </div>
</div>
<script type="text/javascript" charset="utf-8">
    $(function(){
        $("#apipick").change(function(){
            $("#request").html('');
            var api = $(this).val();

            if (api == '') {
                return false;
            }
            $(".parms").hide();
            var index = $("#apipick :selected").index();
            $("#"+index).show();
        });
        $("#send").click(function(){
            var url;
            var a = $("#apipick :selected").val();
            var b = '/index.php/Home';
            var parm = {};
            $(".parms :visible tr").each(function(){
                if($(this).find('input').length > 1){
                    name = $(this).find('td:first').text();
                    name = name.toLowerCase();
                    parm[name] = {};
                    $(this).find('input').each(function(){
                        var n = $(this).attr('name');
                        parm[name][n] = $(this).val();
                    });
                } else {
                    var input = $(this).find('input');
                    var name = input.attr('name');
                    parm[name] = input.val();
                };
            });
            if (a=='') {
                alert('请选择接口');
                return false;
            }
            if (b=='') {
                alert('请选择接口地址');
                return false;
            }

            //要上传图片的接口地址数组
            var arrlist = new Array("/tStatus/create","/CurrentStatus/create");
            if(arrlist.indexOf(a)){
                var upload_images = true;
            }else{
                var upload_images = false;
            }

            /*ajax请求url*/
            url = b+a;

            //添加上传图片接口
            if(upload_images){
                $.ajaxFileUpload({
                    url: url,
                    secureuri: false,
                    fileElementId: ['images'],
                    dataType: 'json',
                    'data':{'json': JSON.stringify(parm)},
                    'type' : 'post',
                    'success':function(data){
                        if (data.session !== undefined) {
                            $('input[name=sid]').val(data.session.sid);
                            $('input[name=uid]').val(data.session.uid);
                        }
                        $("#request").html(JsonUti.convertToString(data));
                    },
                    'beforeSend' : function(){
                        $("#request").html('加载数据中...');
                    },
                    'error' : function(i, data){
                        $("#request").html(i.responseText);
                    }
                });
            }else{
                $.ajax({
                    'url': url,
                    'data':{'json': JSON.stringify(parm)},
                    'dataType': 'json',
                    'type' : 'post',
                    'success':function(data){
                        if (data.session !== undefined) {
                            $('input[name=sid]').val(data.session.sid);
                            $('input[name=uid]').val(data.session.uid);
                        }
                        $("#request").html(JsonUti.convertToString(data));
                    },
                    'beforeSend' : function(){
                        $("#request").html('加载数据中...');
                    },
                    'error' : function(i, data){
                        $("#request").html(i.responseText);
                    }
                });
            }

        });
    });






    var JsonUti = {

        //定义换行符

        n: "\n",

        //定义制表符

        t: "\t",

        //转换String

        convertToString: function (obj) {

            return JsonUti.__writeObj(obj, 1);

        },

        //写对象

        __writeObj: function (obj    //对象

                , level             //层次（基数为1）

                , isInArray) {       //此对象是否在一个集合内

            //如果为空，直接输出null

            if (obj == null) {

                return "null";

            }

            //为普通类型，直接输出值

            if (obj.constructor == Number || obj.constructor == Date || obj.constructor == String || obj.constructor == Boolean) {

                var v = obj.toString();

                var tab = isInArray ? JsonUti.__repeatStr(JsonUti.t, level - 1) : "";

                if (obj.constructor == String || obj.constructor == Date) {

                    //时间格式化只是单纯输出字符串，而不是Date对象

                    return tab + ("\"" + v + "\"");

                }

                else if (obj.constructor == Boolean) {

                    return tab + v.toLowerCase();

                }

                else {

                    return tab + (v);

                }

            }



            //写Json对象，缓存字符串

            var currentObjStrings = [];

            //遍历属性

            for (var name in obj) {

                var temp = [];

                //格式化Tab

                var paddingTab = JsonUti.__repeatStr(JsonUti.t, level);

                temp.push(paddingTab);

                //写出属性名

                temp.push(name + " : ");



                var val = obj[name];

                if (val == null) {

                    temp.push("null");

                }

                else {

                    var c = val.constructor;



                    if (c == Array) { //如果为集合，循环内部对象

                        temp.push(JsonUti.n + paddingTab + "[" + JsonUti.n);

                        var levelUp = level + 2;    //层级+2



                        var tempArrValue = [];      //集合元素相关字符串缓存片段

                        for (var i = 0; i < val.length; i++) {

                            //递归写对象

                            tempArrValue.push(JsonUti.__writeObj(val[i], levelUp, true));

                        }



                        temp.push(tempArrValue.join("," + JsonUti.n));

                        temp.push(JsonUti.n + paddingTab + "]");

                    }

                    else if (c == Function) {

                        temp.push("[Function]");

                    }

                    else {

                        //递归写对象

                        temp.push(JsonUti.__writeObj(val, level + 1));

                    }

                }

                //加入当前对象“属性”字符串

                currentObjStrings.push(temp.join(""));

            }

            return (level > 1 && !isInArray ? JsonUti.n : "")                       //如果Json对象是内部，就要换行格式化

                    + JsonUti.__repeatStr(JsonUti.t, level - 1) + "{" + JsonUti.n     //加层次Tab格式化

                    + currentObjStrings.join("," + JsonUti.n)                       //串联所有属性值

                    + JsonUti.n + JsonUti.__repeatStr(JsonUti.t, level - 1) + "}";   //封闭对象

        },

        __isArray: function (obj) {

            if (obj) {

                return obj.constructor == Array;

            }

            return false;

        },

        __repeatStr: function (str, times) {

            var newStr = [];

            if (times > 0) {

                for (var i = 0; i < times; i++) {

                    newStr.push(str);

                }

            }

            return newStr.join("");

        }

    };

    /*打印odject对象*/
    function obj2string(o){
        var r=[];
        if(typeof o=="string"){
            return "\""+o.replace(/([\'\"\\])/g,"\\$1").replace(/(\n)/g,"\\n").replace(/(\r)/g,"\\r").replace(/(\t)/g,"\\t")+"\"";
        }
        if(typeof o=="object"){
            if(!o.sort){
                for(var i in o){
                    r.push(i+":"+obj2string(o[i]));
                }
                if(!!document.all&&!/^\n?function\s*toString\(\)\s*\{n?s*[native code]n?s*}\n?\s*$/.test(o.toString)){
                    r.push("toString:"+o.toString.toString());
                }
                r="{"+r.join()+"}";
            }else{
                for(var i=0;i<o.length;i++){
                    r.push(obj2string(o[i]))
                }
                r="["+r.join()+"]";
            }
            return r;
        }
        return o.toString();
    }
</script>
</body>
</html>