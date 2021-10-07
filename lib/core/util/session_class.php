<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file session_class.php
 * @brief session机制处理类
 * @author nswe
 * @date 2016/8/24 1:18:34
 * @version 4.6
 * @update session入库保存
 */

//开户session
if( isset($_COOKIE[session_name()]) && $_COOKIE[session_name()] )
{
	session_id($_COOKIE[session_name()]);
}

if(!isset($_SESSION))
{
	//自定义session处理方式
	session_set_save_handler(
		array("ISession","sessOpen"),
		array("ISession","sessClose"),
		array("ISession","sessRead"),
		array("ISession","sessWrite"),
		array("ISession","sessDestroy"),
		array("ISession","sessGc")
	);

	session_start();
}

/**
 * @brief ISession 处理类
 * @class ISession
 * @note
 */
class ISession
{
	//session前缀
	private static $pre='iweb_';

	private static $sessionDB = null;

	//获取配置的前缀
	private static function getPre()
	{
		return self::$pre;
	}
	/**
	 * @brief 开启session的回调函数
	 */
	public static function sessOpen($savePath, $name)
	{
		self::$sessionDB = new IModel('session');
		return true;
	}
	/**
	 * @brief 关闭session的回调函数
	 */
	public static function sessClose(){return true;}
	/**
	 * @brief 读取session的回调函数
	 */
	public static function sessRead($id)
	{
		$sessionData = self::$sessionDB->getObj("id = '{$id}'");
		if($sessionData && isset($sessionData['value']))
		{
			return $sessionData['value'];
		}
		return '';
	}
	/**
	 * @brief 写入session的回调函数
	 * @param $id    string
	 * @param $value string 序列化后的字符串
	 */
	public static function sessWrite($id,$value)
	{
		self::$sessionDB->setData(['id' => $id,'value' => $value,'time' => ITime::getDateTime()]);
		$result = self::$sessionDB->replace();
		self::$sessionDB->commit();
		return $result;
	}
	/**
	 * @brief 销毁session的回调函数
	 */
	public static function sessDestroy($id)
	{
		return self::$sessionDB->del("id = '{$id}'");
	}
	/**
	 * @brief 垃圾回收session的回调函数
	 */
	public static function sessGc($maxTime)
	{
		return self::$sessionDB->del("timestampdiff(SECOND,time,NOW()) > $maxTime");
	}

	/**
	 * @brief 设置session数据
	 * @param string $name 字段名
	 * @param mixed $value 对应字段值
	 */
	public static function set($name,$value='')
	{
		self::$pre = self::getPre();
		$_SESSION[self::$pre.$name]=$value;
	}
    /**
     * @brief 获取session数据
     * @param string $name 字段名
     * @return mixed 对应字段值
     */
	public static function get($name)
	{
		self::$pre = self::getPre();
		return isset($_SESSION[self::$pre.$name]) ? $_SESSION[self::$pre.$name] : null;
	}
    /**
     * @brief 清空某一个Session
     * @param mixed $name 字段名
     */
	public static function clear($name)
	{
		self::$pre = self::getPre();
		unset($_SESSION[self::$pre.$name]);
	}
    /**
     * @brief 清空所有Session
     */
	public static function clearAll()
	{
		return session_destroy();
	}
}