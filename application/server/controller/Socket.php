<?php
namespace app\server\controller;

use core\Socket as SocketServer;

class Socket
{
    public function index()
    {
        $webSocket = new SocketServer();
        $webSocket->run();
    }
}