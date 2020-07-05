<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>draw and guess 1.0</title>
    <link rel="stylesheet" href="/static/bootstrap-3.3.7-dist/css/bootstrap.css">
    <script type="application/javascript" src="/static/js/jquery-3.2.1.js"></script>
    <script type="application/javascript" src="/static/bootstrap-3.3.7-dist/js/bootstrap.js"></script>
    <script type="application/javascript" src="/static/js/layer/layer.js"></script>
    <style>
        .panel-body{
            padding: 0;
        }
        #draw-container {
            height:400px;
        }
        /*画板*/
        /*.draw-broad{
            width:500px;
            margin:50px 0 50px 150px;
            float:left;
        }*/
        /*对话框*/
        /*.dialog-box{

        }*/
        /*消息框*/
        /*.message-box{
            width:350px;
            height:400px;
            margin:50px 0 50px 20px;
            float:left;
        }*/
        .wrapper {
            margin-top:50px;
        }
        .message-body {
            padding:10px 10px 0 10px;
            height:400px;
        }
        /*用户列表*/
        .user-info {
            border-bottom:1px solid #ddd;
            height:80px;
        }
        /*消息框*/
        .message {
            height:300px;
            overflow: auto;
        }

    </style>
</head>
<body>
    <div class="container wrapper">
        <div class="row">
            <!--画板操作区-->
            <div class="col-md-8 draw-box">
                <!--画板-->
                <div class="panel panel-default draw-broad" style="width:748px; height:auto;">
                    <!--用户名、画画提示-->
                    <div class="panel-heading user-name">用户<?php echo $userName; ?></div>
                    <!--画板-->
                    <div class="panel-body" id="draw-container">
                        <!--canvas的宽高要写在HTML里，写在css里画图的坐标会出错-->
                        <!--宽高最好固定，如果设置为跟随浏览器窗口大小改变，canvas会跟着清空；而且当窗口缩到很小时，绘画坐标要怎么显示都是一个问题-->
                        <canvas id="draw-panel" width=748px; height=400px; style="cursor:url('/static/img/pencil.ico'),pointer;">
                        </canvas>
                    </div>
                </div>
                <!--画板工具-->
                <div class="panel panel-default draw-tool">
                    画板工具
                    <button class="btn btn-primary" type="button" id="clear">清空画板</button>
                </div>
            </div>
            <!--对话框-->
            <div class="col-md-4 dialog-box">
                <!--消息框-->
                <div class="panel panel-default message-box">
                    <div class="panel-heading message-title">消息框</div>
                    <div class="panel-body message-body">
                        <div class="user-info">
                            <span>在线人数：</span><span class="user-num"></span>人
                            <div class="user-list"></div>
                        </div>
                        <div class="message"></div>
                    </div>
                </div>
                <!--输入框-->
                <textarea class="form-control input-content" cols="1" rows="2"></textarea>
                <button class="btn btn-primary btn-block send-button" type="button" style="margin-top:5px">发送</button>
            </div>
        </div>
    </div>

<script>
    //用Javascript获取页面元素的位置http://www.ruanyifeng.com/blog/2009/09/find_element_s_position_using_javascript.html
    //http://www.cnblogs.com/fullhouse/archive/2012/01/17/2324706.html

    var $draw_panel = $('#draw-panel');
    var draw_panel = $draw_panel.get(0); //JQuery对象转换成DOM对象
    var ctx = draw_panel.getContext('2d'); //返回一个用于在画布上绘图的环境
    // canvas元素相对于浏览器窗口的偏移量(可能需要定义为全局变量)
    var offset_top = draw_panel.getBoundingClientRect().top;
    var offset_left = draw_panel.getBoundingClientRect().left;
    // JS动态设置canvas的宽高
    var canvasAutoResize = {
        draw : function() {
            var canvas = document.getElementById('draw-panel');
            var drawContainer = document.getElementById('draw-container');
            canvas.width = drawContainer.offsetWidth;
            canvas.height = drawContainer.offsetHeight;
        },
        initialize : function() {
            //self 指窗口本身，它返回的对象跟window对象是一模一样的
            var self = canvasAutoResize;
            self.draw();
            // 当调整浏览器窗口的大小时，动态设置canvas的宽高(暂时不用)
            $(window).on('resize', function() {
                self.draw();
            });
        }
    };
    //当浏览窗口改变时，修改偏移量
    $(window).on('resize',function() {
        offset_top = draw_panel.getBoundingClientRect().top;
        offset_left = draw_panel.getBoundingClientRect().left;
    });
    //当浏览窗口滚动时，修改偏移量
    $(window).on('scroll',function() {
        offset_top = draw_panel.getBoundingClientRect().top;
        offset_left = draw_panel.getBoundingClientRect().left;
    });

    /*页面内容都加载完才执行*/
    $(function() {
        // 初始化canvas的宽高
        // canvasAutoResize.initialize();
        var ws_connect = false;
        var user_name = '<?php echo $userName; ?>';
        if (user_name === '') {
            window.location.href = "{:url('login/index')}";return ;
        }

        //发送对话框消息
        $('.send-button').on('click',function() {
            var content = $('.input-content').val();
            var msg = {'type':'dialog', 'content':{'user_name':user_name,'message':content}};
            sendMsg(msg);
            $('.input-content').val('');
        });

        //清空画布
        $("#clear").click(function() {
            //重新设置宽高来清空画布
            canvasAutoResize.draw();
            var msg = {'type':'clear'};
            sendMsg(msg);
        });

        /*layer.prompt({
            value: '游客',
            title: '请输入用户名',
            area: ['800px', '350px'] //自定义文本域宽高
        }, function (value, index, elem) {
            user_name = value;
            layer.close(index);
        });*/
        /*var $draw_panel = $('#draw-panel');
        var draw_panel = $draw_panel.get(0); //JQuery对象转换成DOM对象
        var ctx = draw_panel.getContext('2d'); //返回一个用于在画布上绘图的环境
        // canvas元素相对于浏览器窗口的偏移量(可能需要定义为全局变量)
        var offset_top = draw_panel.getBoundingClientRect().top;
        var offset_left = draw_panel.getBoundingClientRect().left;*/

        $draw_panel.mousedown(function (event) {
            //var event = event || window.event;
            var x = event.clientX-offset_left;
            var y = event.clientY-offset_top;
            ctx.moveTo(x,y);
            var msg = {'type':'draw', 'content':{'x':x, 'y':y, 'isStart':true}};
            sendMsg(msg);
            document.onmousemove = function(event) {
                //var e = e || window.event;
                var move_x = event.clientX-offset_left;
                var move_y = event.clientY-offset_top;
                //console.log(move_x);
                ctx.lineTo(move_x,move_y);
                var move_msg = {'type':'draw', 'content':{'x':move_x, 'y':move_y, 'isStart':false}};
                sendMsg(move_msg);
                ctx.stroke();

            };
            $draw_panel.mouseup(function() {
                document.onmousemove = null;
            });
            //console.log(event.x);
        });

        //发送数据
        function sendMsg(msg)
        {
            if (ws_connect) {
                var sendMsg = JSON.stringify(msg);
                ws.send(sendMsg);
            }
        }

        //接收数据
        function receiveMsg(event)
        {
            var msg = JSON.parse(event.data);
            return msg;
        }

        //根据坐标画图
        function draw(msg)
        {
            if (msg) {
                if (msg.isStart == true) {
                    ctx.moveTo(msg.x,msg.y);
                } else {
                    ctx.lineTo(msg.x,msg.y);
                    ctx.stroke();
                }
            }
        }

        // 接收数据的处理
        function handleMsg(msg)
        {
            switch (msg.type) {
                case 'handshake' : sendMsg({'type':'login','content':{'user_name':user_name}}); break;
                case 'login' : showMsg(msg); break;
                case 'loginout' : showMsg(msg); break;
                case 'draw' : draw(msg.content); break;
                case 'dialog' : showMsg(msg); break;
                case 'clear' : canvasAutoResize.draw(); break;
                //如果都不会以上的情况，关闭socket连接
                default : //todo
            }
        }

        //显示用户登陆、退出、对话信息、显示所有已登陆的用户
        function showMsg(msg)
        {
            var type = msg.type;
            var content = msg.content;
            var user_list = content.user_list;
            var user_msg = '用户'+content.user_name;
            if (type === 'login') {
                user_msg += '已上线';
            } else if (type === 'loginout') {
                user_msg += '已下线';
            } else if (type === 'dialog') {
                user_msg += ':'+content.message;
            }
            // 显示在线人员
            $('.user-num').text('');
            $('.user-num').append(user_list.length);
            var html = '';
            $.each(user_list, function(k,v) {
                html += '<span style="display: inline-block; margin-right: 10px;">用户'+v+'</span>';
            });
            $('.user-list').text('');
            $('.user-list').append(html);

            // 显示消息框
            user_msg += '<br>';
            $('.message').append(user_msg);
            console.log(msg);
        }

        var ws = new WebSocket("ws://127.0.0.1:8081");

        ws.onopen = function () {
            console.log(ws);
            ws_connect = true;
            //sendMsg({"user_name":user_name});
            //var msg = JSON.stringify("hello socket你好吗\n");
            //ws.send(msg);
        };

        ws.onmessage = function (e) {
            var msg = receiveMsg(e);
            handleMsg(msg);
        };

        ws.onerror = function (e) {
            console.log(e);
            //alert(e);
        };

        ws.onclose = function (e) {
            console.log(e);
            ws_connect = false;
            //alert(e);
        };
    });

//获取元素相对浏览器窗口的高度(暂时用getBoundingClientRect替代)
function getElementViewTop(element)
{
    var actualTop = element.offsetTop;
    var current = element.offsetParent;
    while (current !== null) {
        actualTop += current.offsetTop;
        current = current.offsetParent;
    }
    /*if (document.compatMode == "BackCompat") {
        var elementScrollTop = document.body.scrollTop;
    } else {
        var elementScrollTop = document.documentElement.scrollTop;
    }*/
    if (document.documentElement && document.documentElement.scrollTop) {
        var elementScrollTop = document.documentElement.scrollTop;
    } else if (document.body) {
        var elementScrollTop = document.body.scrollTop;
    }
    return actualTop - elementScrollTop;
}

//获取元素相对浏览器窗口的宽度(暂时用getBoundingClientRect替代)
function getElementViewLeft(element)
{
    var actualLeft = element.offsetLeft;
    var current = element.offsetParent;
    while (current !== null){
        actualLeft += current.offsetLeft;
        current = current.offsetParent;
    }
    /*if (document.compatMode == "BackCompat"){
        var elementScrollLeft=document.body.scrollLeft;
    } else {
        var elementScrollLeft=document.documentElement.scrollLeft;
    }*/
    if (document.documentElement && document.documentElement.scrollLeft) {
        var elementScrollLeft = document.documentElement.scrollLeft;
    } else if (document.body) {
        var elementScrollLeft = document.body.scrollLeft;
    }
    return actualLeft - elementScrollLeft;
}

</script>
</body>
</html>