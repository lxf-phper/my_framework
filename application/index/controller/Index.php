<?php

namespace app\index\controller;

use core\Router;
use core\Config;
use core\Db;
use core\View;
use app\common\controller\Base;

class Index extends Base
{
    public function index()
    {
        echo '欢迎使用framework';
    }

    public function draw()
    {
        $template = View::Instance();
        $template->fetch('', [
            'userName' => '测试',
        ]);
    }

    //测试用php连接socket
    public function test()
    {
        halt([guid(),guid(),guid(),guid()]);
        $st="socket send message";
        $length = strlen($st);
        //创建tcp套接字
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        //连接tcp
        socket_connect($socket, '127.0.0.1',8081);
        //向打开的套集字写入数据（发送数据）
        $s = socket_write($socket, $st, $length);
        //从套接字中获取服务器发送来的数据
        $msg = socket_read($socket,8190);

        echo $msg;
        //关闭连接
        //socket_close($socket);
    }

}