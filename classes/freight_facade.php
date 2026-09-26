<?php
/**
 * @copyright Copyright(c) 2014 aircheng.com
 * @file freight_facade.php
 * @author nswe
 * @date 2017/10/25 21:42:48
 * @version 5.0
 */

/**
 * @class freight_facade
 * @brief 快递跟踪接口类
 */
class freight_facade
{
	//物流接口实例
	private static $instance = null;

	//使用的物流接口,kdniao:快递鸟; kuaidi100:快递100;
	private static $freightInterface = 'kuaidi100';

	/**
	 * @brief 显示快递跟踪
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public static function line($ShipperCode,$LogisticCode)
	{
		return self::createObject()->line($ShipperCode,$LogisticCode);
	}

	/**
	 * @brief 订阅快递跟踪信息
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 物流单号
	 * @return mixed
	 */
	public static function subscribe($ShipperCode,$LogisticCode)
	{
		return self::createObject()->subscribe($ShipperCode,$LogisticCode);
	}

	/**
	 * @brief 订阅物流快递回调接口
	 * @param $callbackData mixed 物流回传信息
	 * @return mixed
	 */
	public static function subCallback($callbackData)
	{
		return self::createObject()->subCallback($callbackData);
	}

	/**
	 * @brief 创建物流接口实力
	 * @return object 快递跟踪类实例
	 */
	private static function createObject()
	{
		if(self::$instance)
		{
			return self::$instance;
		}

		//类库路径
		$basePath = IWeb::$app->getBasePath().'plugins/freight/';
		switch(self::$freightInterface)
		{
			case "kdniao":
			{
				include($basePath.'kdniao.php');
				self::$instance = new kdniao();
			}
			break;

			default:
			{
				include($basePath.'kuaidi100.php');
				self::$instance = new kuaidi100();
			}
		}

		if(self::$instance)
		{
			return self::$instance;
		}

		throw new IException("未获取到物流接口");
	}
}

/**
 * @brief 物流开发接口
 */
interface freight_inter
{
	/**
	 * @brief 显示快递跟踪
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function line($ShipperCode,$LogisticCode);

	/**
	 * @brief 订阅物流快递轨迹
	 * @param $ShipperCode  string  物流公司代号
	 * @param $LogisticCode string  快递单号
	 * @return mixed
	 */
	public function subscribe($ShipperCode,$LogisticCode);

	/**
	 * @brief 订阅物流快递回调接口
	 * @param $callbackData mixed 物流回传信息
	 * @return mixed
	 */
	public function subCallback($callbackData);
}