#!/usr/bin/env php
<?php
//定义入口的模块
define('BIND_MODULE', 'server/Socket');

require dirname(__DIR__).'/core/base.php';

\core\App::run();