<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file cache.php
 * @brief 缓存类
 * @author chendeshan
 * @date 2011-7-8 16:02:31
 * @version 0.6
 */

/**
 * @brief 缓存类
 * @class ICache
 */
class ICache
{
	private $cache     = null;    //缓存对象
	private $cacheType = 'file';  //默认缓存类型
	private $expire    = 2592000; //默认缓存过期时间,单位:秒,默认：1个月
	private $timeout   = 0;       //默认缓存删除延迟时间,单位:秒

	/**
	 * @brief  根据缓存类型创建缓存对象
	 * @param  string $cacheType 缓存类型值
	 */
	public function __construct($cacheType = '')
	{
		$this->cacheType = $cacheType ? $cacheType : $this->cacheType;
		$this->expire    = isset(IWeb::$app->config['cache']['expire'])  ? IWeb::$app->config['cache']['expire']  : $this->expire;
		$this->timeout   = isset(IWeb::$app->config['cache']['timeout']) ? IWeb::$app->config['cache']['timeout'] : $this->timeout;

		//当前系统支持的缓存类型
		switch($this->cacheType)
		{
			case "memcache":
			$this->cache = new IMemCache();
			break;

			default:
			$this->cache = new IFileCache();
			break;
		}
	}

	/**
	 * @brief  写入缓存
	 * @param  string $key     缓存的唯一key值
	 * @param  mixed  $data    要写入的缓存数据
	 * @param  int    $expire  缓存数据失效时间,单位：秒
	 * @return bool   true:成功;false:失败;
	 */
	public function set($key,$data,$expire = '')
	{
		if($expire === '')
		{
			$expire = $this->expire;
		}
		$data = serialize($data);
		return $this->cache->set($key,$data,$expire);
	}

	/**
	 * @brief  读取缓存
	 * @param  string $key 缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed  读取出的缓存数据;null:没有取到数据;
	 */
	public function get($key)
	{
		$data = $this->cache->get($key);
		if($data)
		{
			return unserialize($data);
		}
		else
		{
			return null;
		}
	}

	/**
	 * @brief  删除缓存
	 * @param  string $key     缓存的唯一key值
	 * @param  int    $timeout 在间隔单位时间内自动删除,单位：秒
	 * @return bool   true:成功; false:失败;
	 */
	public function del($key,$timeout = '')
	{
		if($timeout === '')
		{
			$timeout = $this->timeout;
		}
		return $this->cache->del($key,$timeout);
	}

	/**
	 * @brief  删除全部缓存
	 * @return bool   true:成功；false:失败;
	 */
	public function flush()
	{
		return $this->cache->flush();
	}

	/**
	 * @brief  获取缓存类型
	 * @return string 缓存类型
	 */
	public function getCacheType()
	{
		return $this->cacheType;
	}
}