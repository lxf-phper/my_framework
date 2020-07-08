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
