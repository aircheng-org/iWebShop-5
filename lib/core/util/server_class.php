<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file server_class.php
 * @brief 获取服务器环境信息
 * @author chendeshan
 * @date 2010-12-2
 * @version 0.6
 */

/**
 * @class IServer
 * @brief 获取服务器环境的信息
 */
class IServer
{
	/**
	 * @brief 获取服务器端的主机地址
	 * @return string 服务器主机地址
	 * @deprecated 请使用IUrl::getHost()，注意，两者的返回值不一样。
	 */
	public static function getHost()
	{
		return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
	}

	/**
	 * @brief 服务器的php版本比对
	 * @param string 要比对的php版本号 例如:5.1.0
	 * @return bool 值: true:达到版本标准; false:未达到版本标准;
	 */
	public static function isGeVersion($version)
	{
		//获取当前php版本号
		$locVersion = phpversion();

		$result=version_compare($locVersion,$version);
		return ($result >= 0) ? true : false;
	}
}