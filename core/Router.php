<?php

namespace core;

/**
 * 路由解析
 * Class Router
 * @package core
 */
class Router
{
    protected static $module;
    protected static $controller;
    protected static $action;
    protected static $param = [];

    /**
     * 绑定默认模块
     * @param $module
     */
    public static function bind($module)
    {
        self::$module = $module;
    }

    /**
     * 获取路由地址
     * @return array
     */
    public static function getPathInfo()
    {
        if (is_cli()) {
            //$argv = $_SERVER['argv'];
            $pathInfo = explode('/', self::$module);
            self::$module = isset($pathInfo[0]) ? lcfirst($pathInfo[0]) : 'index';
            self::$controller = isset($pathInfo[1]) ? ucfirst($pathInfo[1]) : 'Index';
            self::$action = isset($pathInfo[2]) ? lcfirst($pathInfo[2]) : 'index';
        } else {
            //获取path_info
            if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
                //如果有$_SERVER['PATH_INFO']，则url为http://www.framework.com/index.php/index/test
                $pathInfo = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            } else {
                try {
                    if ($_SERVER['REQUEST_URI'] != '/') {
                        $pathInfo = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
                        $module = array_shift($pathInfo);//模块
                        if (self::$module != str_replace('.php', '', $module)) {//如果模块名称不相等
                            if (is_dir(APP_PATH . $module)) { //如果模块存在，绑定模块
                                self::bind($module);
                            } else { //如果模块不存在，抛送异常
                                throw new \Exception('模块' . $module . '不存在');
                            }
                        }
                    } else { //如果只输入http://www.framework.com，默认绑定index模块
                        self::bind('index');
                    }
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    die;
                }
            }
            //获取控制器和方法
            self::$controller = isset($pathInfo[0]) ? ucfirst($pathInfo[0]) : 'Index';//控制器
            self::$action = isset($pathInfo[1]) ? $pathInfo[1] : 'index';//方法
            //$controller = $pathInfo[0] ?? 'index';
            //$action = $pathInfo[1] ?? 'index';

            //获取URL参数
            if (isset($pathInfo)) {
                array_splice($pathInfo, 0, 2);
                for ($i = 0; $i <= count($pathInfo); $i = $i + 2) {
                    if (isset($pathInfo[$i + 1])) {
                        self::$param[$pathInfo[$i]] = $pathInfo[$i + 1];
                    }
                }
            }
        }

        return [self::$module, self::$controller, self::$action];
    }

    public function getModule()
    {
        return self::$module;
    }

    public function getController()
    {
        return self::$controller;
    }

    public function getAction()
    {
        return self::$action;
    }

}