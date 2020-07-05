<?php

namespace core;

class App
{
    /**
     * 执行方法
     */
    public static function run()
    {
        //加载配置
        self::loadConfig();

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
     * 加载配置文件
     */
    public static function loadConfig()
    {
        $configPath = self::getConfigPath();
        $files = [];
        if (is_dir($configPath)) {
            //查找匹配的文件路径模式
            $files = glob($configPath . '*' . '.php');
        }
        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            Config::load($file, $filename);
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
        //$action->invokeArgs($instance, $params);
    }

    public static function getConfigPath()
    {
        return ROOT_PATH . 'config' . DIRECTORY_SEPARATOR;
    }
}