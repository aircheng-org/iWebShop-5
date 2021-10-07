<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file safe_class.php
 * @brief 安全机制session或者cookie数据操作
 * @author chendeshan
 * @date 2011-02-24
 * @version 0.6
 */

/**
 * @class ISafe
 * @brief ISafe 安全机制session或者cookie数据操作
 */
class ISafe
{
	/**
	 * @brief session_id()
	 * @return string
	 */
	public static function id()
	{
		$session_id   = session_id();
		if(!$session_id)
		{
			$session_name = self::name();
			$session_id   = isset($_COOKIE[$session_name]) ? $_COOKIE[$session_name] : '';
		}
		return $session_id;
	}
	/**
	 * @brief session_name()
	 * @return string
	 */
	public static function name()
	{
		return session_name();
	}
	/**
	 * @brief 设置数据
	 * @param string $key  键名;
	 * @param mixed  $val  值;
	 * @param string $type 安全方式:cookie or session;
	 */
	public static function set($key,$val,$type = '')
	{
		$className = self::getSafeClass($type);
		return call_user_func(array($className, 'set'),$key,$val);
	}

	/**
	 * @brief 获取数据
	 * @param string $key  要获取数据的键名
	 * @param string $type 安全方式:cookie or session;
	 * @return mixed 键名为$key的值;
	 */
	public static function get($key,$type = '')
	{
		$className = self::getSafeClass($type);
		return call_user_func(array($className, 'get'),$key);
	}

	/**
	 * @brief 清除safe数据
	 * @param string $name 要删除的键值
	 * @param string $type 安全方式:cookie or session;
	 */
	public static function clear($name = null,$type = '')
	{
		$className = self::getSafeClass($type);
		call_user_func(array($className, 'clear'),$name);
	}

	/**
	 * @brief 清除所有的cookie或者session数据
	 * @param string $type 安全方式:cookie or session;
	 */
	public static function clearAll($type = '')
	{
		$className = self::getSafeClass($type);
		call_user_func(array($className, 'clearAll'));
	}

	/**
	 * @brief 获取cookie或者session对象
	 * @param  string $type 安全方式:cookie or session;
	 * @return object cookie或者session操作对象
	 */
	public static function getSafeClass($type = '')
	{
		$mappingConf = array('cookie'=>'ICookie','session'=>'ISession');
		if($type != '' && isset($mappingConf[$type]))
		{
			return $mappingConf[$type];
		}
		else if(isset(IWeb::$app->config['safe']) && IWeb::$app->config['safe'] == 'session')
		{
			return $mappingConf['session'];
		}
		else
		{
			return $mappingConf['cookie'];
		}
	}
}