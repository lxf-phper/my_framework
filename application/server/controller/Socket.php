<?php
namespace app\server\controller;

use core\Socket as SocketServer;

class Socket
{
    public function index()
    {
        $socket = new SocketServer();
//        $socket->on('message', function($connect){
//            halt($connect);
//        });
//        $socket->onMessage = function ($connect, $data) {
//            halt([$connect, $data]);
//        };
//        $socket->testRun();
        $socket->run();
    }

}