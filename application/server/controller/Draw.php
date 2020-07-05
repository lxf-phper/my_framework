<?php
namespace app\server\controller;

use core\Websocket;

class Draw
{
    public function index()
    {
        $webSocket = new Websocket();
        $webSocket->run();
    }
}