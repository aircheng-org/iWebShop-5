<?php
/**
 * @copyright (c) 2014 aircheng
 * @file themeroute.php
 * @brief 视图布局选择路由类
 * @author nswe
 * @date 2014/7/15 18:50:48
 * @version 2.6
 */
class layoutroute extends IInterceptorBase
{
	/**
	 * @brief layout布局文件进行选择,从主题中的config.php中获取layout配置
	 */
	public static function onCreateView($controller,$actionObj)
	{
		if($controller->layout === false)
		{
			return;
		}
		//从主题中的config.php获取layout配置
		$themeConfig = is_file($controller->getViewPath().'config.php') ? include($controller->getViewPath().'config.php') : null;

		$keyArray = array();
		$keyArray[] = $controller->getId()."@".$actionObj->getId();
		$keyArray[] = $controller->getId();

		foreach($keyArray as $key => $val)
		{
			if(isset($themeConfig['layout'][$val]))
			{
				$controller->layout = $themeConfig['layout'][$val];
				return;
			}
		}
	}
}