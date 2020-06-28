<?php
//定义一些便捷的函数
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