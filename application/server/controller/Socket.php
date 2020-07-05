<?php
namespace app\server\controller;

use core\Websocket;

class Socket
{
    public function index()
    {
        $webSocket = new Websocket();
        $webSocket->run();
    }
}