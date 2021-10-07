<?php
/**
 * @copyright (c) 2014 aircheng
 * @file themeroute.php
 * @brief 主题皮肤选择路由类
 * @author nswe
 * @date 2014/7/15 18:50:48
 * @version 2.6
 *
 */
class themeroute extends IInterceptorBase
{
	/**
	 * @brief theme和skin进行选择
	 */
	public static function onCreateController($controller)
	{
		/**
		 * 对于theme和skin的判断流程
		 * 1,直接从URL中获取是否已经设定了方案__theme,__skin
		 * 2,从cookie获取数据
		 */
		$urlTheme = IReq::get('__theme');
		$urlSkin  = IReq::get('__skin');

		if($urlTheme && $urlSkin && preg_match('|^\w+$|',$urlTheme) && preg_match('|^\w+$|',$urlSkin))
		{
			ISafe::set('__theme',$theme = $urlTheme);
			ISafe::set('__skin',$skin  = $urlSkin);
		}
		elseif(ISafe::get('__theme') && ISafe::get('__skin'))
		{
			$theme = ISafe::get('__theme');
			$skin  = ISafe::get('__skin');
		}

		if(isset($theme) && isset($skin))
		{
			$themePath = IWeb::$app->getViewPath().$theme."/".$controller->getId();
			if(is_dir($themePath))
			{
				$controller->theme = $theme;
				$controller->skin  = $skin;
			}
		}
	}

	/**
	 * @brief 检查主题方案是否被应用
	 * @param string $plan 方案名称
	 * @return boolean
	 */
	public static function isThemeUsed($plan)
	{
		if(isset(IWeb::$app->config['theme']))
		{
			if(is_array(IWeb::$app->config['theme']))
			{
				foreach(IWeb::$app->config['theme'] as $client => $themeList)
				{
					if(array_key_exists($plan,$themeList))
					{
						return $themeList;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @brief 检查皮肤方案是否被应用
	 * @param string $theme 主题名称
	 * @param string $plan 方案名称
	 * @return boolean
	 */
	public static function isSkinUsed($theme,$plan)
	{
		$themeList = self::isThemeUsed($theme);
		if($themeList)
		{
			if(in_array($plan,$themeList))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief 获取主题方案的类型
	 * @param string $theme 主题方案
	 * @return 主题类型编号; 网站前台:site;后台管理:system;商家管理:seller;
	 */
    public static function themeType($theme)
    {
    	$list = array("site","system","seller");
    	foreach($list as $key => $checkController)
    	{
    		$dirname = IWeb::$app->getViewPath().$theme."/".$checkController;
    		if(is_dir($dirname))
    		{
				return $checkController;
    		}
    	}
    	return "";
    }

	/**
	 * @brief 根据主题类型编号返回名字字符串
	 * @param string $type 主题类型名字
	 * @return string 主题名称名字字符串
	 */
    public static function themeTypeTxt($type)
    {
    	$data = array("site" => "网站前台","system" => "后台管理","seller" => "商家管理");
    	return isset($data[$type]) ? $data[$type] : "";
    }

	/**
	 * @brief 获取不同类型主题方案的列表
	 * @param string $type 主题类型
	 * @return 主题列表; 网站前台:site;后台管理:system;商家管理:seller;
	 */
    public static function themeTypeList($type)
    {
    	$result   = array();
    	$allTheme = Config::getSitePlan('theme');
    	foreach($allTheme as $theme => $item)
    	{
    		if(self::themeType($theme) == $type)
    		{
    			$item['skin']   = Config::getSitePlan('skin',$theme);
    			$result[$theme] = $item;
    		}
    	}
    	return $result;
    }
}