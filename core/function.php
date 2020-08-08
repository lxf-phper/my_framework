<?php
//定义一些便捷的函数
if (!function_exists('dump')) {
    /**
     * 调试输出
     * @param $data
     */
    function dump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

if (!function_exists('halt')) {
    /**
     * 调试输出
     * @param $data
     */
    function halt($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die;
    }
}

if (!function_exists('is_cli')) {
    /**
     * 是否命令行模式
     * @return bool
     */
    function is_cli()
    {
        return preg_match('/cli/i', php_sapi_name()) ? true : false;
    }
}

if (!function_exists('pt_progress')) {
    /**
     * 打印进度
     * @param string $contents
     */
    function pt_progress($contents)
    {
        echo '[' . date('Y-m-d H:i:s') . ']';
        echo $contents;
        echo PHP_EOL;
    }
}

/**
 * 生成唯一标识ID
 * @return string
 */
function guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen .
            substr($charid, 8, 4) . $hyphen .
            substr($charid, 12, 4) . $hyphen .
            substr($charid, 16, 4) . $hyphen .
            substr($charid, 20, 12);
        return $uuid;
    }
}
