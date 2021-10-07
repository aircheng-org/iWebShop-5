<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file interceptor_class.php
 * @brief 事件拦截器，观察者模式，类似框架事件机制
 * @author nswe
 * @date 2016/2/27 21:44:02
 * @version 5.1
 * @update 2018/4/21 11:47:49 重构了这个类库支持自定义事件的支持
 */

/**
 * 事件拦截器
 *
 * 如果需要在应用中使用这个类，需要在config.php里配置interceptor
 * 'interceptor'=>array(
 *		'classname',              //将classname类注册到所有默认事件中
 *		'classname1@onFinishApp', //将classname1类注册到onFinishApp这个事件，当触发事件的时候，会自动调用classname1::onFinishApp();方法。
 * );
 *
 * 被注册的类必须要定义与事件名称相同的静态方法,从而在事件触发的时候系统会自动调用,
 * 如A类注册了abc事件，那么A类中必须定义 public static function abc(){....}方法。
 * 注意：事件中的 "onPhpShutDown" 一旦注册，便肯定会执行，即使程序中调用了die和exit
 *
 * @author nswe
 */
class IInterceptor
{
	/**
	 * @brief 系统中预定的事件
	 */
	private static $defaultEvent = array(
		'onCreateApp',
		'onFinishApp',
		'onBeforeCreateController',
		'onCreateController',
		'onFinishController',
		'onBeforeCreateAction',
		'onCreateAction',
		'onFinishAction',
		'onBeforeCreateView',
		'onCreateView',
		'onFinishView',
		'onPhpShutDown',
	);

	//已经注册监听的事件和类关系
	private static $_listen = array();

	/**
	 * 对事件进行注册监听
	 * @param string|array $event 类名和事件 例如 "iclass_name","class_name@position"，并且支持数组形式表示多个。
	 */
	public static function reg($event)
	{
		if( is_array($event) )
		{
			foreach($event as $e)
			{
				self::reg($e);
			}
		}
		else
		{
			$tmp = explode("@",trim($event));

			//指定拦截器具体位置
			if( count($tmp) == 2 )
			{
				self::regIntoEvent($tmp[1],$tmp[0]);
			}
			//所有拦截器位置都拦截
			else if(count($tmp) == 1)
			{
				foreach(self::$defaultEvent as $evt)
				{
					self::regIntoEvent($evt,$tmp[0]);
				}
			}
		}
	}

	/**
	 * 对事件进行注册监听
	 * @param string $event     事件名称
	 * @param string $className 处理类名称
	 */
	public static function regIntoEvent($event,$className)
	{
		$eventArray = explode(',',$event);
		foreach($eventArray as $event)
		{
			if(!isset(self::$_listen[$event]))
			{
				self::$_listen[$event] = array();
			}
			self::$_listen[$event][] = $className;
		}
	}

	/**
	 * 触发事件
	 * @param string $event 事件名称
	 * @return mixed 返回值
	 */
	public static function trigger($event)
	{
		$params = func_get_args();
		array_shift($params);

		$result = array();
		if(isset(self::$_listen[$event]))
		{
			foreach(self::$_listen[$event] as $key => $className)
			{
				$result[$key] = call_user_func_array(array($className,$event),$params);
			}
		}
		return isset($result[1]) ? $result : current($result);
	}

	/**
	 * 删除事件监听，如果$className != null,则只删除监听这个事件的注册类
	 * @param string      $event     事件名称
	 * @param string|null $className 注册的类名
	 */
	public static function del($event,$className = null)
	{
		if(isset(self::$_listen[$event]))
		{
			if($className)
			{
				foreach(self::$_listen[$event] as $key => $class)
				{
					if($className == $class)
					{
						unset(self::$_listen[$event][$key]);
						break;
					}
				}
			}
			else
			{
				unset(self::$_listen[$event]);
			}
		}
	}

	/**
	 * php结束自动回调，触发onFinishApp事件
	 */
	public static function shutDown()
	{
		self::trigger("onPhpShutDown");
	}
}

/**
 * 拦截器基类，建议大家在创建拦截器对象的时候继承此类。
 */
abstract class IInterceptorBase
{
	//获取当前app对象
	public static function app()
	{
		return IWeb::$app;
	}

	//获取当前controller对象
	public static function controller()
	{
		return IWeb::$app->getController();
	}

	//获取当前action对象
	public static function action()
	{
		return IWeb::$app->getController()->getAction();
	}

	public static function onCreateApp(){}
	public static function onFinishApp(){}
	public static function onBeforeCreateController($ctrlId){}
	public static function onCreateController($ctrlObj){}
	public static function onFinishController($ctrlObj){}
	public static function onBeforeCreateAction($ctrlObj,$actionId){}
	public static function onCreateAction($ctrlObj,$actinObj){}
	public static function onFinishAction($ctrlObj,$actinObj){}
	public static function onCreateView($ctrlObj,$actinObj){}
	public static function onFinishView($ctrlObj,$actinObj){}
	public static function onPhpShutDown(){}
}