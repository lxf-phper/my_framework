<?php
namespace app\index\controller;

use app\lib\Websocket;

class Draw
{
    public function test()
    {
        $webSocket = new Websocket();
        $webSocket->run();
    }
}