<?php

namespace core;

class App
{
    /**
     * 执行方法
     */
    public static function run()
    {
        //绑定默认模块
        Router::bind(BIND_MODULE);

        list($module, $controller, $action) = Router::getPathInfo();

        //增加路径映射
        $vendor = 'app\\' . $module . '\\controller\\' . $controller;
        if (!isset(\Loader::$vendorMap[$vendor])) {
            $vendorDir = APP_PATH . $module . DS . 'controller' . DS . $controller;
            \Loader::addVendorMap($vendor, $vendorDir);
        }
        //路由分发
        try {
            self::dispatch($vendor, $action);
        } catch (\Exception $e) {
            echo $e->getMessage();die;
        }
    }

    /**
     * 路由分发
     * @param $vendor
     * @param $action
     * @throws \ReflectionException
     */
    public static function dispatch($vendor, $action)
    {
        //执行类的方法
        $obj = new \ReflectionClass($vendor); //建立类的反射类
        $instance = $obj->newInstanceArgs(); //实例化类
        $action = $obj->getMethod($action); //获取类中的方法
        $action->invoke($instance); //执行类的方法
    }
}