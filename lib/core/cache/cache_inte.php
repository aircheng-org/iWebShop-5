<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file cache_inte.php
 * @brief 缓存接口
 * @author chendeshan
 * @date 2011-7-8 16:02:31
 * @version 0.6
 */

/**
 * @brief 缓存接口
 * @class ICacheInte
 */
interface ICacheInte
{
	/**
	 * @brief  写入缓存
	 * @param  string $key     缓存的唯一key值
	 * @param  mixed  $data    要写入的缓存数据
	 * @param  int    $expire  缓存数据失效时间,单位：秒
	 * @return bool   true:成功;false:失败;
	 */
	public function set($key,$data,$expire = '');

	/**
	 * @brief  读取缓存
	 * @param  string $key 缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed  读取出的缓存数据;null:没有取到数据;
	 */
	public function get($key);

	/**
	 * @brief  删除缓存
	 * @param  string $key     缓存的唯一key值
	 * @param  int    $timeout 在间隔单位时间内自动删除,单位：秒
	 * @return bool   true:成功; false:失败;
	 */
	public function del($key,$timeout = '');

	/**
	 * @brief  删除全部缓存
	 * @return bool   true:成功；false:失败;
	 */
	public function flush();
}