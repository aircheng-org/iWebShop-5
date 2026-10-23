<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file memcache_class.php
 * @brief memcached内存级缓存类
 * @author chendeshan
 * @date 2011-7-8 16:02:31
 * @version 0.6
 */

/**
 * @brief memcached内存级缓存类
 * @class IMemCache
 */
class IMemCache implements ICacheInte
{
	private $cache       = null;        //缓存对象
	private $defaultHost = '127.0.0.1'; //默认服务器地址
	private $defaultPort = 11211;       //默认端口号

	//构造函数
	public function __construct()
	{
		if(!extension_loaded('memcache'))
		{
			throw new IException('can not find the memcache extension');
		}

		$this->cache = new Memcache;
		$server = isset(IWeb::$app->config['cache']['server']) ? IWeb::$app->config['cache']['server'] : $this->defaultHost;
		if(is_array($server))
		{
			foreach($server as $key => $val)
			{
				$this->addServe($val);
			}
		}
		else
		{
			$this->addServe($server);
		}
	}

	/**
	 * @brief  添加服务器到连接池
	 * @param  string $address 服务器地址
	 * @return bool   true:成功;false:失败;
	 */
	private function addServe($address)
	{
		$addressArray = explode(':',$address);
		$host         = $addressArray[0];
		$port         = isset($addressArray[1]) ? $addressArray[1] : $this->defaultPort;
		return $this->cache->addServer($host,$port);
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
		return $this->cache->set($key,$data,MEMCACHE_COMPRESSED,$expire);
	}

	/**
	 * @brief  读取缓存
	 * @param  string $key 缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed  读取出的缓存数据;null:没有取到数据;
	 */
	public function get($key)
	{
		return $this->cache->get($key);
	}

	/**
	 * @brief  删除缓存
	 * @param  string $key     缓存的唯一key值
	 * @param  int    $timeout 在间隔单位时间内自动删除,单位：秒
	 * @return bool   true:成功; false:失败;
	 */
	public function del($key,$timeout = '')
	{
		return $this->cache->delete($key,$timeout);
	}

	/**
	 * @brief  删除全部缓存
	 * @return bool   true:成功；false:失败;
	 */
	public function flush()
	{
		return $this->cache->flush();
	}

}
