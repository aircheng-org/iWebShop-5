<?php
/**
 * @copyright (c) 2009-2011 aircheng.com
 * @file language_class.php
 * @brief 语言包类文件
 * @author Ben
 * @date 2010-12-10
 * @version 0.6
 */

/**
 * @class ILang
 * @brief ILang 语言包类文件
 */
class ILang
{
	//已经加载的语言内容
	public static $data = [];

	//是否已经加载完毕
	private static $isLoad = false;

	//默认语言:中文
	public static $defaultLang = 'zh_cn';

	/**
	 * @brief 加载语言包
	 * @param string $lang 语言包文件名
	 * @return bool
	 */
	public static function load($lang = '')
	{
		self::$isLoad = true;

		if(!$lang)
		{
			$lang = IWeb::$app->config['lang'] ? IWeb::$app->config['lang'] : self::$defaultLang;
		}

		//遍历目录读取语言文件
		$langDir = IWeb::$app->getBasePath().IWeb::$app->config['langPath'].'/'.$lang;
		if(!file_exists($langDir))
		{
			return false;
		}

		$dirRes  = opendir($langDir);
		while(false !== ($dir = readdir($dirRes)))
		{
			if(stripos($dir,".php") && $dir != 'config.php')
			{
				$temp = include($langDir.'/'.$dir);
				self::$data = array_merge(self::$data,$temp);
			}
		}
	}

	//显示语言内容
	public static function get($name)
	{
		if(self::$isLoad == false)
		{
			self::load();
		}
		return isset(self::$data[$name]) ? self::$data[$name] : $name;
	}
}