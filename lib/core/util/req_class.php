<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file request_class.php
 * @brief 处理 $_GET,$_POST 数据
 * @author chendeshan
 * @date 2010-12-2
 * @version 0.6
 * @see 4.6 更新了get参数的urldecode
 */

/**
 * @class IReq
 * @brief 获取$_GET,$_POST的数据
 */
class IReq
{
	/**
	 * @brief 获取键为$key的 $_GET 和 $_POST 传送方式的数据
	 * @param string $key $_GET 或 $_POST 的键
	 * @param string $type 传送方式 值: false:默认(先get后post); get:get方式; post:post方式;
	 * @return string $_GET 或者 $_POST 的值
	 * @note 优先获取 $_GET 方式的数据,如果不存在则获取 $_POST 方式的数据
	 */
	public static function get($key, $type=false)
	{
		//默认方式
		if($type==false)
		{
			if(isset($_GET[$key]))
			{
				if(is_array($_GET[$key]))
				{
					array_walk_recursive($_GET[$key],function(&$v){$v = urldecode($v);});
					return $_GET[$key];
				}
				else
				{
					return urldecode($_GET[$key]);
				}
			}
			else if(isset($_POST[$key]))
			{
				return $_POST[$key];
			}
			else
			{
				return null;
			}
		}

		//get方式
		else if($type=='get' && isset($_GET[$key]))
		{
			if(is_array($_GET[$key]))
			{
				array_walk_recursive($_GET[$key],function(&$v){$v = urldecode($v);});
				return $_GET[$key];
			}
			else
			{
				return urldecode($_GET[$key]);
			}
		}

		//post方式
		else if($type=='post' && isset($_POST[$key]))
		{
			return $_POST[$key];
		}

		//无匹配
		else
		{
			return null;
		}
	}

	/**
	 * @brief 设置键为$key 的$_GET 或者 $_POST 的变量值
	 * @param string $key $_GET 或者 $_POST 键
	 * @param string $value 设置的值
	 * @param string $type 设置的类型 值: get:默认,get方式; post:post方式
	 */
	public static function set($key, $value, $type='get')
	{
		//get方式
		if($type=='get')
			$_GET[$key] = $value;

		//post方式
		else if($type=='post')
			$_POST[$key] = $value;
	}
}