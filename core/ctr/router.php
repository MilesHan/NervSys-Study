<?php
namespace core\ctr;

class router{
	//定义参数
	public static $cmd = '';
	public static $data = [];
	public static $result = [];
	public static $header = [];

	protected static $conf_cgi = [];
	protected static $conf_cli = [];

	//配置文件路径
	const conf_path = ROOT . '/core/conf.ini';

	/**
	 *	跨域请求
	 */
	public static function load_cors(): void{
		//如果 $_SERVER['HTTP_ORIGIN'] 没有被设置，或者 $_SERVER['HTTP_ORIGIN'] 的值没有加入请求白名单中，return
		if (!isset($_SERVER['HTTP_ORIGIN']) || $_SERVER['HTTP_ORIGIN'] === (self::is_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) {
			return;
		}

		//解析地址：scheme, host, port, user, pass, path, query(?), fragment(#)
		$unit = parse_url($_SERVER['HTTP_ORIGIN']);
		//如果没有获取到port，通过https/443, http/80 人为加上
		if (!isset($unit['port'])) {
			$unit['port'] = 'https' === $unit['scheme'] ? 443 : 80;
 		}

 		//拼接出配置文件路径，如 ROOT . /cors/ . http.domain.80.php
 		$cors = realpath(ROOT . '/cors/' . implode('.', $unit) . '.php');
 		//如果文件不存在,exit
 		if (false === $cors) {
 			exit;
 		}

 		//如果文件存在，加载
 		require $cors;
 		unset($unit, $cors);

 		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
 		//如果header中存在其他值，拼接起来
 		if (!empty(self::$header)) {
 			header('Access-Control-Allow-Headers: ' . implode(', ', self::$header));
 		}

 		//如果是"预检"请求，即用的请求方法是OPTIONS，exit
 		if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
 			exit;
 		}

	}

	/**
	 *	加载 CLI / CGI 模式下的配置文件
	 */
	public static function load_conf(): void{
		//获取conf.ini
		$path = realpath(self::conf_path);
		if (false === $path) {
			return;
		}

		//加载conf.ini文件，返回关联数组
		$conf = parse_ini_file($path, true);
		if (false === $conf) {
			return;
		}

		//加载cgi配置
		if (isset($conf['CGI'])) {
			self::$conf_cgi = &$conf['CGI'];
		}

		//加载cli配置
		if (isset($conf['CLI'])) {
			self::$conf_cgi = &$conf['CLI'];
		}

		unset($path, $conf);
	}

	/**
	 * 输出结果
	 */
	public static function output(): void{
		//输出运行时的值
		if (3 === DEBUG) {
			//当前微妙时间 - 请求开始时的时间戳。 四舍五入，保留小数点后四位
			self::$result['duration'] = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's';
			//分配给 PHP 脚本 的内存量(byte), 1048576 bytes = 1M
			self::$result['memory'] = round(memory_get_usage(true) / 1048576, 4) . 'MB';
			//分配给 PHP 内存的峰值
			self::$result['peak'] = round(memory_get_peak_usage(true) / 1048576, 4) . 'MB';

		}

		//创建返回结果
		switch (count(self::$result)) {
			case 0:
				$output = '';
				break;
			case 1:
				$output = json_encode(current(self::$result), JSON_OPT);
				break;
			default:
				$output = json_encode(self::$result, JSON_OPT);
				break;
		}

		//输出结果
		echo 'cli' !== PHP_SAPI ? $output : $output . PHP_EOL;

		unset($output);
	}

	/**
	 * 检查 HTTPS 协议
	 *
	 * @return array
	 */
	public static function is_https(): bool{
		return (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']);
	}

	/**
	 * 从options中取值
	 */
	protected static function opt_val(array &$opt, array $keys): array{
		$result = ['get' => false, 'data' => ''];

		foreach ($keys as $key) {
			if (isset($opt[$key])) {
				$result = ['get' => true, 'data' => $opt[$key]];
				unset($opt[$key]);
			}
		}

		unset($keys, $key);
		return $result;
	}

}

	
























