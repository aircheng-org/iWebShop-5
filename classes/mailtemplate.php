<?php
/**
 * @copyright (c) 2014 aircheng.com
 * @file sendmail.php
 * @brief 邮件数据模板
 * @author chendeshan
 * @date 2014/11/28 23:20:51
 * @version 2.9
 */
class mailTemplate
{
	/**
	 * @brief 找回密码模板
	 * @param array $param 模版参数
	 * @return string
	 */
	public static function findPassword($param)
	{
		$siteConfig = new Config("site_config");
		$templateString = "您好，您在{$siteConfig->name}申请找回密码的操作，点击下面这个链接进行密码重置：<a href='{url}'>{url}</a>。<br />如果不能点击，请您把它复制到地址栏中打开。";
		return strtr($templateString,$param);
	}

	/**
	 * @brief 验证邮件模板
	 * @param array $param 模版参数
	 * @return string
	 */
	public static function checkMail($param)
	{
		$siteConfig = new Config("site_config");
		$templateString = "感谢您注册{$siteConfig->name}服务，点击下面这个链接进行邮箱验证并激活您的帐号：<a href='{url}'>{url}</a>。<br />如果不能点击，请您把它复制到地址栏中打开。";
		return strtr($templateString,$param);
	}

	/**
	 * @brief 到货通知邮件模板
	 * @param array $param 模版参数
	 * @return string
	 */
	public static function notify($param)
	{
		$templateString = "尊敬的用户，您需要购买的 <{goodsName}> 现已全面到货，机不可失，从速购买！ <a href='{url}' target='_blank'>立即购买</a>";
		return strtr($templateString,$param);
	}
}
