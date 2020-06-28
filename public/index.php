<?php

//定义入口的模块
define('BIND_MODULE', 'index');

require dirname(__DIR__).'/core/base.php';

\core\App::run();