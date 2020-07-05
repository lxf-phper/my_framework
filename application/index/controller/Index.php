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

}