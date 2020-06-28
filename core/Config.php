<?php
namespace core;

/**
 * 配置类
 * Class Config
 * @package core
 */
class Config
{
    //配置参数
    private static $config = [];

    /**
     * 设置配置
     * @param array $config 配置(string为键，array则是键值对)
     * @param string $name 配置的键
     */
    public static function set($config = [], string $name = null)
    {
        if (!empty($name)) {
            if (isset(self::$config[$name])) {
                self::$config[$name] = array_merge(self::$config[$name], $config);
            } else {
                self::$config[$name] = $config;
            }
        } else {
            //批量配置
            self::$config = array_merge(self::$config, $config);
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
     * @param string $name 一级配置名
     */
    public static function load($file, $name = '')
    {
        if (is_file($file)) {
            self::set(include $file, $name);
        } else {
            exit('找不到配置文件:' . $file);
        }
    }
}