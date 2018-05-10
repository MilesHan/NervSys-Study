<?php

//声明严格校验模式
declare(strict_types = 1);

//检查php版本
if (version_compare(PHP_VERSION,'7.1.0','<')) {
	exit("NervSys needs PHP 7.1.0 or higher!");
}

//加载配置文件
require __DIR__.'/core/conf.php';

//加载CORS
\core\ctr\router::load_cors();

//加载路由配置
\core\ctr\router::load_conf();

//执行进程
// 'cli' !== PHP_SAPI ? \core\ctr\router\cgi::run() : \core\ctr\router\cli::run();

//输出结果
\core\ctr\router::output();


