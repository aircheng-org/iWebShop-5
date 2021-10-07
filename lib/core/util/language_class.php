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
 * @class ILanguage
 * @brief ILanguage 语言包类文件
 */
class ILanguage
{
	private $is_loaded = array();
	private $language  = array();

	/**
	 * @brief 加载语言包
	 * @param string $language 语言包文件名
	 * @return bool
	 */
	function load()
	{
		$args = func_get_args();
		foreach($args as $langfile)
		{
			$langfile =str_replace('_lang.', '', $langfile).'_lang.php';
			if(in_array($langfile, $this->is_loaded, TRUE))
			{
				continue;
			}
			$path = IWeb::$app->config['langPath'];		//LANG  应用中语言包路径

			if(file_exists(IWeb::$app->getBasePath().$path.DIRECTORY_SEPARATOR.$langfile))
			{
				$lang = include(IWeb::$app->getBasePath().$path.DIRECTORY_SEPARATOR.$langfile);
			}
			else if(file_exists(IWEB_PATH.'lang'.DIRECTORY_SEPARATOR.'zh_sc'.DIRECTORY_SEPARATOR.$langfile))
			{
				$lang = include(IWEB_PATH.'lang'.DIRECTORY_SEPARATOR.'zh_sc'.DIRECTORY_SEPARATOR.$langfile);
			}
			if(!is_array($lang))
			{
				continue;
			}
			$this->language = array_merge($this->language, $lang);
			$this->is_loaded[] = $langfile;
			unset($lang);
		}
		return TRUE;
	}
    public function __get($name)
	{
		echo ($name=='' OR !isset($this->language[$name])) ? FALSE : $this->language[$name];
	}

	function g($name)
	{
		return ($name=='' OR !isset($this->language[$name])) ? FALSE : $this->language[$name];
	}

	/**
	 * @brief 获取语言包数组
	 * @return array
	 */
	function getLanguage()
	{
		return $this->language;
	}
}