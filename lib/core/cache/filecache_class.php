<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file IFile_cache.php
 * @brief 文件级缓存类
 * @author chendeshan
 * @date 2011-7-8 16:02:31
 * @version 0.6
 */

/**
 * @brief 文件级缓存类
 * @class IFileCache
 */
class IFileCache implements ICacheInte
{
	private $cachePath      = 'runtime/cache'; //默认文件缓存存放路径
	private $cacheExt       = '.data';         //默认文件缓存扩展名
	private $directoryLevel = 1;               //目录层级,基于$cachePath之下的

	/**
	 * @brief  构造函数
	 */
	public function __construct()
	{
		$this->cachePath = isset(IWeb::$app->config['cache']['path']) ? IWeb::$app->config['cache']['path'] : $this->cachePath;
		$this->cacheExt  = isset(IWeb::$app->config['cache']['ext'])  ? IWeb::$app->config['cache']['ext']  : $this->cacheExt;
	}

	/**
	 * @brief  根据key值计算缓存文件名
	 * @param  string $key 缓存的唯一key值
	 * @return string 缓存文件路径
	 */
	private function getFileName($key)
	{
		$key      = str_replace(' ','',$key);
		$cacheDir = rtrim($this->cachePath,'\\/').'/';
		if($this->directoryLevel > 0)
		{
			$hash      = abs(crc32($key));
			$cacheDir .= $hash % 1024;
			for($i = 1;$i < $this->directoryLevel;++$i)
			{
				if(($prefix = substr($hash,$i,2)) !== false)
				{
					$cacheDir .= '/'.$prefix;
				}
			}
		}
		return $cacheDir.'/'.md5($key).$this->cacheExt;
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
		$fileName = $this->getFileName($key);
		if(!file_exists($dirname=dirname($fileName)))
		{
			IFile::mkdir($dirname);
		}

		$writeLen = file_put_contents($fileName,$data);

		if($writeLen == 0)
		{
			return false;
		}
		else
		{
			$expire = time() + $expire;
			touch($fileName,$expire);
			return true;
		}
	}

	/**
	 * @brief  读取缓存
	 * @param  string $key 缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed  读取出的缓存数据;null:没有取到数据或者缓存已经过期了;
	 */
	public function get($key)
	{
		$fileName = $this->getFileName($key);
		if(file_exists($fileName))
		{
			if(time() > filemtime($fileName))
			{
				$this->del($key,0);
				return null;
			}
			else
			{
				return file_get_contents($fileName);
			}
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
		$fileName = $this->getFileName($key);
		if(file_exists($fileName))
		{
			if($timeout > 0)
			{
				$timeout = time() + $timeout;
				return touch($fileName,$timeout);
			}
			else
			{
				return unlink($fileName);
			}
		}
		else
		{
			return true;
		}
	}

	/**
	 * @brief  删除全部缓存
	 * @return bool   true:成功；false:失败;
	 */
	public function flush()
	{
		return IFile::clearDir($this->cachePath);
	}
}