<?php
// 允许任意域名发起的跨域请求
$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin:".$origin);

define('SRC_PATH', 'http://test.ddyy.com');

// define('BIND_MODULE', 'Admin');

define('APP_DEBUG', true);

define('APP_PATH', './API/');

require './thinkphp/ThinkPHP.php';