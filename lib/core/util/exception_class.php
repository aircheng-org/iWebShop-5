<?php
/**
 * @copyright (c) 2015 aircheng.com
 * @file exception_class.php
 * @brief 异常处理
 * @author nswe
 * @date 2015/8/20 22:01:52
 * @version 4.1
 */
class IException extends Exception
{
	//日志存放路径
	private static $logPath = '';

	//debug模式
	private static $debugMode = false;

	/**
	 * 构造函数
	 * @param string $message
	 * @param mixed $code
	 */
	public function __construct($message = "", $code = 0)
	{
		$wholeData = debug_backtrace(false);
		$lastRow   = reset($wholeData);
		if($lastRow)
		{
			$this->message = $message;
			$this->code    = $code;
			$this->file    = $this->pathFilter($lastRow['file']);
			$this->line    = $lastRow['line'];
		}
	}

	/**
	 * @brief 异常处理接口
	 * @param $excep object exception
	 */
	public static function phpException($excep)
	{
		$traceData      = array();
		$traceDataArray = $excep->getTrace();

		$traceData = self::formatTrace($traceDataArray);
		$logArray = array(
			"excepInfo: ".$excep->getMessage(),
			"excepFile: ".$excep->getFile(),
			"excepLine: ".$excep->getLine(),
			"excepCode: ".$excep->getCode(),
			"excepTime: ".ITime::getDateTime(),
			join("\n",$traceData),
		);
		$logString = join("\n",$logArray);
		self::logError($logString);

		//正式运营情况下减少报错
		if(self::$debugMode == false)
		{
			switch($excep->getCode())
			{
				//数据库报错
				case 1000:
				{
					header("HTTP/1.1 500 SQL ERROR");
					$logString = "SQL语句异常";
				}
				break;

				default:
				{
					header("HTTP/1.1 500 THROW EXCEPTION");
					$logString = $excep->getMessage();
				}
			}
		}
		//增加数据库回滚
		IDBFactory::getDB()->rollback();

		self::show($logString);
	}

	/**
	 * @brief 错误处理接口
	 * @param $errno int 错误代码
	 * @param $errstr string 错误信息
	 * @param $errfile string 错误文件名
	 * @param $errline int 错误行号
	 * @param $errcontext array 错误详情
	 */
	public static function phpError($errno,$errstr,$errfile = false,$errline = false,$errcontext = false)
	{
		$traceData      = array();
		$traceDataArray = debug_backtrace(false);
		array_shift($traceDataArray);

		$traceData = self::formatTrace($traceDataArray);
		$logArray  = array(
			"errorInfo: ".$errstr,
			"errorFile: ".self::pathFilter($errfile),
			"errorLine: ".$errline,
			"errorCode: ".$errno,
			"errorTime: ".ITime::getDateTime(),
			join("\n",$traceData),
		);

		$logString = join("\n",$logArray);
		self::$debugMode == true ? self::show($logString) : self::logError($logString);
	}

	/**
	 * @brief 格式化程序堆栈
	 * @param 默认堆栈数组 $traceDataArray array
	 * @return array
	 */
	public static function formatTrace($traceDataArray)
	{
		foreach($traceDataArray as $key => $val)
		{
			$temp = array(
				"#".$key." ",
				isset($val['file'])     ? self::pathFilter($val['file']) : "",
				isset($val['line'])     ? "(".$val['line'].")"           : "",
				": ",
				isset($val['class'])    ? $val['class']                  : "",
				isset($val['type'])     ? $val['type']                   : "",
				isset($val['function']) ? $val['function']               : "",
			);

			//参数处理（复杂）
			if(isset($val['args']) && is_array($val['args']))
			{
				$argument = array();
				foreach($val['args'] as $key => $val)
				{
					if(is_string($val) || is_int($val) || is_float($val))
					{
						$val        = strval($val);
						$argument[] = self::pathFilter($val);
					}
				}
				$temp[] = "(".join(",",$argument).")";
			}
			$traceData[] = join($temp);
		}
		return $traceData;
	}

	/**
	 * @brief 过滤实际路径
	 * @param $string string 要被过滤的内容
	 */
	public static function pathFilter($string)
	{
		$string    = strtr($string,"\\","/");
		$iweb_path = strtr(IWeb::$app->getBasePath(),"\\","/");

		if($iweb_path && strpos($string,$iweb_path) !== false)
		{
			$string = str_replace($iweb_path,"IWEB_PATH/",$string);
		}
		return $string;
	}

	/**
	 * @brief 设置日志路径
	 */
	public static function setLogPath($path)
	{
		self::$logPath = $path;
	}

	/**
	 * @brief 设置debug开关
	 */
	public static function setDebugMode($mode)
	{
		self::$debugMode = $mode;
	}

	/**
	 * @brief 记录日志文件
	 * @param string $string 要写入的日志内容
	 */
	public static function logError($string)
	{
		//生成错误日志文件
		self::$logPath = self::$logPath ? self::$logPath : "error/".date("y-m-d").".log";

		//记录日志
		$string = "<ERROR_BLOCK>\n".$string."\n</ERROR_BLOCK>";
		$logInstance = new IFileLog(self::$logPath);
		return $logInstance->write($string);
	}

	/**
	 * @brief 在页面中显示错误
	 * @param $infoData 错误信息
	 */
	public static function show($infoData)
	{
		$logString = "<pre style='border:2px solid red'>".$infoData."</pre>";
		die($logString);
	}
}

/**
 * @class IError
 * @brief IError 错误处理类
 */
class IError
{
	/**
	 * @brief 报错 [适合在逻辑(非视图)中使用,此方法支持数据渲染]
	 * @param string $httpNum   HTTP错误代码
	 * @param string $errorData 错误数据
	 */
	public static function show($httpNum = 403,$errorData = "")
	{
		//参数的次序颠倒
		if(is_numeric($errorData))
		{
			list($httpNum,$errorData) = array($errorData,$httpNum);
		}
		else if(is_string($httpNum) && !$errorData)
		{
			list($httpNum,$errorData) = array(403,$httpNum);
		}
		header("HTTP/1.1 ".$httpNum);

		//1,检查用户是否定义了Errors控制器
		$controller = IWeb::$app->createController('errors');
		if(method_exists($controller,'error'.$httpNum))
		{
			call_user_func(array($controller,'error'.$httpNum),$errorData);
		}

		//2,系统内置的错误机制
		else if(file_exists(IWEB_PATH.'web/view/'.'error'.$httpNum.$controller->extend))
		{
			$errorData = is_array($errorData) ? $errorData : array('message' => $errorData);
			$controller->render(IWEB_PATH.'web/view/'.'error'.$httpNum,$errorData);
		}

		//3,输出错误信息
		else
		{
			$controller->renderText($errorData);
		}
		exit;
	}
}