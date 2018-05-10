<?php
/**
* 调试模式：
* 	0：生产环境（不报错）
* 	1：开发环境（显示所有的错误，警告，提示）
*	2：优化开发（显示所有的错误，警告，提示和运行时的值）
*/
define('DEBUG', 3);

//基本设置

//脚本最大执行时间，如果是0，则没有限制
set_time_limit(0);

//设置客户端断开连接时是否中断脚本的执行；
//声明即使客户端断开并不终止脚本的执行
ignore_user_abort(true);

//设置应该报告何种php错误
error_reporting(0 == DEBUG ? 0 : E_ALL);

//设置时区
date_default_timezone_set('PRC');

//设置HTTP头
header('Content-Type: application/json; charset=utf-8');

//定义NervSys版本
define('NS_VER', '5.1.8');

/*
JSON 编码设置

JSON_PRETTY_PRINT                        用空白字符格式化返回的数据
JSON_NUMERIC_CHECK                       将所有数字字符串编码成数字
JSON_BIGINT_AS_STRING                    将大数字编码成原始字符原来的值
JSON_UNESCAPED_SLASHES                   不要编码
JSON_UNESCAPED_UNICODE                   以字面编码多字节 Unicode 字符（默认是编码成 \uXXXX）
JSON_PRESERVE_ZERO_FRACTION              Ensures that float values are always encoded as a float value.
JSON_PARTIAL_OUTPUT_ON_ERROR             Substitute some unencodable values instead of failing
JSON_UNESCAPED_LINE_TERMINATORS
*/
define(
    'JSON_OPT',
    JSON_PRETTY_PRINT |
    JSON_NUMERIC_CHECK |
    JSON_BIGINT_AS_STRING |
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_PRESERVE_ZERO_FRACTION |
    JSON_PARTIAL_OUTPUT_ON_ERROR |
    JSON_UNESCAPED_LINE_TERMINATORS
);

//定义ROOT常量：根目录
define('ROOT', realpath(substr(__DIR__, 0, -4)));

//注册给定的函数作为 __autoload 的实现
spl_autoload_register('load');

/**
 * 自动加载
 *
 * @param string $lib
 */
function load(string $lib): void{
	//如果没有\，直接返回
	if (false === strpos($lib, '\\')) {
		return;
	}

	//拼接文件路径及名称
	$file = realpath(ROOT . '/' . strtr($lib, '\\', '/') . '.php');

	//如果文件存在，则加载之
	if (false !== $file) {
		require $file;
	}

	//销毁函数内部的$lib,$file变量
	unset($lib,$file);
}

/**
 * 调试
 *
 * @param string $module
 * @param string $message
 */
function debug(string $module, string $message): void{
	//如果是生产环境，return
	if (0 === DEBUG) {
		return;
	}

	//赋值 \core\ctr\router中的静态变量 $result
	//以 $module 为键名，以 $message 为键值
	\core\ctr\router::$result[$module] = &$message;

	unset($module, $message);
}















