<?php
/**
 * @copyright (c) 2016 aircheng.com
 * @file dbfactory.php
 * @brief 数据库工厂类
 * @author chendeshan
 * @date 2017/11/2 10:22:04
 * @version 5.0
 */

/**
* @class IDBFactory
* @brief 数据库工厂
*/
class IDBFactory
{
	//数据库对象
	public static $instance   = NULL;

	//默认的数据库连接方式
	private static $defaultDB = 'mysqli';

	/**
	 * @brief 创建对象
	 * @param $dbConfig array 数据库配置信息参考config.php中的db项
	 * @return object   数据库对象
	 */
	public static function getDB($dbConfig = null)
	{
		//单例模式
		if(self::$instance != NULL && is_object(self::$instance))
		{
			self::$instance->init();
			return self::$instance;
		}

		$dbinfo = $dbConfig ? $dbConfig : IWeb::$app->config['DB'];

		//获取数据库配置信息
		if(!$dbinfo)
		{
			throw new IException('can not find DB info');
		}

		//数据库类型
		$dbType = isset($dbinfo['type']) ? $dbinfo['type'] : self::$defaultDB;

		switch($dbType)
		{
			case "mysqli":
			{
				return self::$instance = new IMysqli();
			}
			break;
		}
	}
    private function __construct(){}
    private function __clone(){}
}