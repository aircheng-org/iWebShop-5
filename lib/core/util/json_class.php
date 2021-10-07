<?php
/**
 * @copyright (c) 2013 aircheng.com
 * @file json_class.php
 * @brief 文件处理
 * @author chendeshan
 * @date 2013-4-15
 * @version 1.0
 */

/**
 * @class JSON
 * @brief JSON json数据格式的处理
 */
class JSON
{
	//第三方JSON类库实例
	private static $_jsonInstance = null;

	/**
	 * @brief json数据格式编码,支持中文原文转换
	 * @param $param max 要编码转换的数据
	 * @return String json数据格式
	 */
	public static function encode($param)
	{
		if(version_compare(phpversion(),'5.4.0') >= 0)
		{
			return json_encode($param,JSON_UNESCAPED_UNICODE);
		}

		$result = '';
		if(function_exists('json_encode'))
		{
			$result = json_encode($param);
		}
		else
		{
			$jsonObject = self::getJsonInstance();
			$result = $jsonObject->encodeUnsafe($param);
		}
		//对于中文的转换
		return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $result);
	}

	/**
	 * @brief 解析json数据格式
	 * @param $string String 要解析的json串
	 * @return max php数据格式
	 */
	public static function decode($string)
	{
		if(strpos($string,"\t") !== false)
		{
			$string = str_replace("\t",'',$string);
		}

		if(function_exists('json_decode'))
		{
			return json_decode($string,true);
		}

		$jsonObject = self::getJsonInstance();
		return $jsonObject->decode($string);
	}

	/**
	 * @brief 初始化第三方JSON类库
	 * @return Object 第三方json处理类
	 */
	private static function getJsonInstance()
	{
		if(self::$_jsonInstance == null)
		{
			include(dirname(__FILE__).'/Services_JSON-1.0.3/JSON.php');
			self::$_jsonInstance = new Services_JSON();
		}
		return self::$_jsonInstance;
	}
}