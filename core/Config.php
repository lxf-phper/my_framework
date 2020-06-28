<?php
//配置类
namespace core;

use think\Exception;

class Config
{
    //配置参数
    private static $config = [];

    //设置配置(支持二级配置，用.分开，如database.type)

    /**
     * 设置配置
     * 支持二级配置，用.分开，如database.type
     * @param array|string|null $name 配置的键(string为键，array则是键值对)
     * @param array|string|null $value 配置的键值
     */
    public static function set($name, $value = null)
    {
        if (is_string($name)) {
            if (false === strpos($name, '.')) {//一级配置
                self::$config[$name] = $value;
            } else {//二级配置
                $name = explode('.', $name);
                self::$config[$name[0]][$name[1]] = $value;
            }
        } elseif (is_array($name)) {
            //批量配置
            self::$config = array_merge(self::$config, $name);
        }
    }

    /**
     * 获取配置
     * 支持二级配置，用.分开，如database.type
     * @param string|null $name 配置的键名(null返回所有配置，string返回对应的配置)
     * @return array|mixed|string
     */
    public static function get($name = null)
    {
        if (is_null($name)) {
            $config = self::$config;
        } else if (false === strpos($name, '.')) {//一级配置
            $config = isset(self::$config[$name]) ? self::$config[$name] : '';
        } else {//二级配置
            $name = explode('.', $name);
            $config = isset(self::$config[$name[0]][$name[1]]) ? self::$config[$name[0]][$name[1]] : '';
        }
        return $config;
    }

    /**
     * 加载配置文件
     * @param string $file 文件绝对路径
     */
    public static function load($file)
    {
        if (is_file($file)) {
            self::set(include $file);
        } else {
            exit('找不到配置文件:' . $file);
        }

    }
}