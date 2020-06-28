<?php
//定义系统常量
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__).DS);
define('APP_PATH', ROOT_PATH.'application'.DS);
define('CORE_PATH', ROOT_PATH.'core'.DS);

require CORE_PATH.'function.php';
require CORE_PATH.'Loader.php';
//注册自动加载
spl_autoload_register('Loader::autoload');

require CORE_PATH.'App.php';
require CORE_PATH.'Config.php';