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

    public function test()
    {
        $template = View::Instance();
        $template->assign('test',123);
        $template->assign('aaa','测试');
        $template->assign('list',['123','234','456']);
        $template->assign('lists',[
            ['id'=>12,'name'=>'Tom'],
            ['id'=>34,'name'=>'Jack'],
        ]);
        $template->fetch();die;
        //var_dump(Router::$param);
        $res = Db::name('sys_order')
            ->alias('o')
            ->join('sys_order_sample os', 'o.order_id = os.order_id')
            ->join([['sys_order_user ou', 'o.order_id = ou.order_id'],['sys_user u', 'ou.user_id = u.user_id']])
            //->where('o.order_id', 1)
            //->where(['u.user_id'=>['in',[1,2,3]]])
            ->field(['o.order_id','os.sample_id','u.user_name'])
            ->limit(3)
            ->select();
        var_dump(Db::name('sys_order')->getLastSql());

        $res = Db::name('sys_user')->where(['user_name'=>'aaa'])->delete();
        halt($res);
        halt(Config::get());
    }
}