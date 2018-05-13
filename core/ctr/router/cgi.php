<?php
namespace core\ctr\router;

use core\ctr\router;

class cgi extends router{
	private static $module = [];
	private static $method = [];
	private static $object = [];
	private static $mapping = [];

	// private static function msg(string $msg): void{
	// 	echo  PHP_EOL. $msg . PHP_EOL;

	// 	echo "【router】:". PHP_EOL;
	// 	var_dump(parent::$cmd);
	// 	var_dump(parent::$data);
	// 	var_dump(parent::$result);
	// 	var_dump(parent::$header);
	// 	var_dump(parent::$conf_cgi);
	// 	var_dump(parent::$conf_cli);

	// 	echo PHP_EOL . "【cgi】:". PHP_EOL;
	// 	var_dump(self::$module);
	// 	var_dump(self::$method);
	// 	var_dump(self::$object);
	// 	var_dump(self::$mapping);
	// }

	/**
	 * 运行 CGI 的路由
	 */
	public static function run(): void{
		// self::msg("1.初始状态");

		// exit;
		//读取数据
		self::read_data();

		//准备cmd
		self::prep_cmd();

		//解析cmd
		self::parse_cmd();

		// //执行cmd
		self::execute_cmd();
	}

	/**
	 * 准备 CGI 模式下的数据
	 * parent--$cmd,$data 赋值
	 */
	private static function read_data(): void{
		if ('' !== parent::$cmd) {
			return;
		}

		// $data = 用户传参
		self::read_http();
		self::read_input();

		//获取到用户以 c 或 cmd 为键名的值
		$val = parent::opt_val(parent::$data, ['c', 'cmd']);

		//如果 parent::opt_val() 中成功获取到值, 而且此值是 string, 不为 ''
		if ($val['get'] && is_string($val['data']) && '' !== $val['data']) {
			//给 parent::$cmd 赋值，用户以 c 或 cmd 为键名的值
			parent::$cmd = &$val['data'];
		}

		unset($val);

		// self::msg("2.read_data:");

		// exit;
	}

	/**
	 * 从 HTTP 请求中获取数据
	 */
	private static function read_http(): void{
		//$_POST为空的话判断后面，否则取 $_POST，$_GET 为空的话取$_REQUEST, 否则取$_GET 
		$data = !empty($_POST) ? $_POST : (!empty($_GET) ? $_GET : $_REQUEST);
		
		//如果 $data 不为空 
		if (!empty($data)) {
			parent::$data += $data;
		}

		if (!empty($_FILES)) {
			parent::$data += $_FILES;
		}

		unset($data);
	}

	/**
	 * 从原生输入流中获取数据
	 */
	private static function read_input(): void{
		$input = file_get_contents("php://input");

		if (false === $input) {
			return;
		}

		//将数据解析成数组
		$data = json_decode($input, true);

		if (is_array($data) && !empty($data)) {
			parent::$data += $data;
		}

		unset($data);
	}

	/**
	 * 准备 “cmd” 数据
	 */
	private static function prep_cmd(): void{
		//如果 cgi 的配置为空，return
		if (empty(parent::$conf_cgi)) {
			return;
		}

		//解析指令
		$data = false !== strpos(parent::$cmd, '-') ? explode('-', parent::$cmd) : [parent::$cmd];

		//解析键名question
		foreach ($data as $key => $value) {
			if (isset(parent::$conf_cgi[$value])) {
				$data[$key] = parent::$conf_cgi[$value];
				self::$mapping[parent::$conf_cgi[$value]] = $value;
			}
		}

		//重建指令
		parent::$cmd = implode('-', $data);

		unset($data,$key,$value);

		// self::msg("3.prepare_cmd:");

		// exit;
	}

	/**
	 * 解析“cmd”数据
	 * 获取 module 和 method
	 */
	private static function parse_cmd(): void{
		//
		$list = false !== strpos(parent::$cmd, '-') ? explode('-', parent::$cmd) : [parent::$cmd];

		foreach ($list as $item) {
			if ('' === $item) {
				continue;
			}

			$module = self::get_module($item);

			if ('' !== $module) {
				if (!isset(self::$module[$module])) {
					self::$module[$module] = [];
				}

				if (!in_array($item, self::$module[$module], true)) {
					self::$module[$module][] = $item;
				}
			}elseif (!in_array($item, self::$method, true)) {
				self::$method[] = $item;
			}
		}

		unset($list, $item, $module);

		// self::msg("4.parse_cmd:");
	}

	/**
	 * 截取模块名,没有为''
	 */
	private static function get_module(string $lib): string{
		$lib = trim($lib, " /\t\n\r\0\x0B");

		$pos = strpos($lib, '/');
		//截取模块名
		$module = false !== $pos ? substr($lib, 0, $pos) : '';

		unset($lib, $pos);
		return $module;
	}

	/**
	 * 执行 cmd
	 */
	private static function execute_cmd(): void{
		//如果没有找到 module, 返回
		if (empty(self::$module)) {
			debug('CGI','Command ERROR!');
			return;
		}

		//加载每个模块的conf文件
		foreach (self::$module as $module => $method) {
			$conf = realpath(ROOT . '/' . $module . '/conf.php');
			if (false !== $conf) {
				require $conf;
			}

			self::call_api($method);
		}

		unset($module, $method, $conf);
	}

	/**
	 * 调用模块下的类
	 */
	private static function call_api(array $lib): void{
		foreach ($lib as $class) {
			$space = '\\' . strtr($class, '/', '\\');

			//判断类是否被定义，调用该类
			class_exists($space) ? self::call_class($class, $space) : debug(self::map_key($class), 'Class ['. $space .'] NOT found!');
		}

		unset($lib, $class, $space);
	}

	/**
	 * 调用模块下的类的方法
	 */
	private static function call_class(string $class, string $space): void{
		// var_dump($class);
		// var_dump($space);

		if (!isset($space::$tz) || !is_array($space::$tz)) {
			debug(self::map_key($class), 'TrustZone NOT Open!');
			return;
		}

		if (method_exists($space, 'init')) {
			try{
				//调用类的 init 方法
				self::call_method($class, $space, 'init');
			} catch (\Throwable $exception){
				debug(self::map_key($class, 'init'), 'Execute Failed! ' . $exception->getMessage());
				unset($exception);
			}
		}

		if (empty($space::$tz)) {
			return;
		}

		$tz_list = array_keys($space::$tz);
		$func_list = get_class_methods($space);

		$method_list = !empty(self::$method) ? array_intersect(self::$method, $tz_list, $func_list) : array_intersect($tz_list, $func_list);

		if (in_array('init', $method_list, true)) {
			unset($method_list[array_search('init', $method_list, true)]);
		}

		foreach ($method_list as $method) {
			try {
				$inter = array_intersect(array_keys(parent::$data), $space::$tz[$method]);
				$diff = array_diff($space::$tz[$method], $inter);

				if (!empty($diff)) {
					throw new \Exception('TrustZone missing [' . (implode(' , ', $diff)) . ']!');
				}

				self::call_method($class, $space, $method);
			} catch (\Throwable $exception){
				debug(self::map_key($class, $method), 'Execute Failed! ' . $exception->getMessage());
				unset($exception);
			}
		}

		unset($class, $space, $tz_list, $func_list, $method_list, $method, $inter, $diff);

	}

	/**
	 * 执行模块下的类的方法
	 */
	private static function call_method(string $class, string $space, string $method): void{
		$reflect = new \ReflectionMethod($space, $method);

		if (!$reflect->isPublic()) {
			return;
		}

		$data = self::map_data($reflect);

		if (!$reflect->isStatic()) {
			$space = self::$object[$class] ?? self::$object[$class] = new $space;
		}

		//执行方法
		$result = empty($data) ? forward_static_call([$space, $method]) : forward_static_call_array([$space, $method], $data);

		if (isset($result)) {
			parent::$result[self::map_key($class, $method)] = &$result;
		}

		unset($class, $space, $method, $reflect, $data, $result);
	} 

	/**
	 * 创建键名映射
	 */
	private static function map_key(string $class, string $method = ''): string{
		$key = '' !== $method ? (self::$mapping[$class . '-' . $method] ?? (self::$mapping[$class] ?? $class) . '/' .$method) : (self::$mapping[$class] ?? $class);

		unset($class, $method);

		return $key;
	}

	/**
	 * 创建数据映射
	 */
	private static function map_data($reflect): array{
		$params = $reflect->getParameters();
		if (empty($params)) {
			return [];
		}

		$data = $diff = [];

		foreach ($params as $param) {
			$name = $param->getName();

			if (isset(parent::$data[$name])) {
				switch ($param->getType()) {
					case 'int':
                        $data[$name] = (int)parent::$data[$name];
                        break;
                    case 'bool':
                        $data[$name] = (bool)parent::$data[$name];
                        break;
                    case 'float':
                        $data[$name] = (float)parent::$data[$name];
                        break;
                    case 'array':
                        $data[$name] = (array)parent::$data[$name];
                        break;
                    case 'string':
                        $data[$name] = (string)parent::$data[$name];
                        break;
                    default:
                        $data[$name] = parent::$data[$name];
                        break;
				}
			} else {
				$param->isOptional() ? $data[$name] = $param->getDefaultValue() : $diff[] = $name;
			}
		}

		if (!empty($diff)) throw new \Exception('Argument missing [' . (implode(', ', $diff)) . ']!');

        unset($reflect, $params, $diff, $param, $name);
        return $data;

	}

}


























