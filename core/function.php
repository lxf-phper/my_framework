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
