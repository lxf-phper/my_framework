<?php

/**
 * 文件加载类
 * Class Loader
 */
class Loader
{
    /* 路径映射 */
    public static $vendorMap = [
        'app' => APP_PATH,
        'core' => CORE_PATH,
    ];

    /**
     * 自动加载器
     * @param $class
     */
    public static function autoload($class)
    {
        $file = self::findFile($class);
        if (file_exists($file)) {
            self::includeFile($file);
        }
    }

    /**
     * 解析文件路径
     * @param $class
     * @return string
     */
    private static function findFile($class)
    {
        $vendor = substr($class, 0, strpos($class, '\\')); // 顶级命名空间
        if (isset(self::$vendorMap[$vendor])) {
            $vendorDir = self::$vendorMap[$vendor]; // 文件基目录
        } else {
            //todo
        }
        $path = $vendorDir . substr($class, strlen($vendor) + 1) . '.php'; // 文件相对路径
        $filePath = strtr($path, '\\', DS); // 文件标准路径
        return $filePath;
    }

    /**
     * 引入文件
     * @param $file
     */
    private static function includeFile($file)
    {
        if (is_file($file)) {
            include $file;
        }
    }

    /**
     * 增加路径映射
     * @param $key
     * @param $value
     */
    public static function addVendorMap($key, $value)
    {
        !empty($key) && !empty($value) && self::$vendorMap[$key] = $value;
    }
}