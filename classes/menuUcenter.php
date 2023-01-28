<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file menuUcenter.php
 * @brief 用户中心菜单管理
 * @author nswe
 * @date 2016/3/8 9:33:25
 * @version 4.4

 * @update 2022-10-20
 * @author nswe
 * @note 把静态数组改成静态方法
 */
class menuUcenter
{
	//菜单与图标对应关系
	public static $ico = [];

    //菜单的配制数据
	public static $menu = [];

    /**
     * @brief 根据权限初始化菜单
     * @param int $roleId 角色ID
     * @return array 菜单数组
     */
    public static function init($roleId = "")
    {
		//图标配置
		self::$ico = [
			ILang::get("我的订单")   => "fa fa-shopping-bag",
			ILang::get("我的积分")   => "fa fa-trophy",
			ILang::get("我的优惠券") => "fa fa-tags",
			ILang::get("售后服务")   => "fa fa-reply",
			ILang::get("站点建议")   => "fa fa-file",
			ILang::get("商品咨询")   => "fa fa-comment",
			ILang::get("商品评价")   => "fa fa-comments",
			ILang::get("短信息")     => "fa fa-bell",
			ILang::get("收藏夹")     => "fa fa-heart",
			ILang::get("预存款")     => "fa fa-money",
			ILang::get("在线充值")   => "fa fa-sign-in",
			ILang::get("地址管理")   => "fa fa-map-marker",
			ILang::get("个人资料")   => "fa fa-table",
			ILang::get("修改密码")   => "fa fa-key",
			ILang::get("发票管理")   => "fa fa-ticket",
		];

		//数据配置
		self::$menu = [
			ILang::get("交易记录") => [
				"/ucenter/order" => ILang::get("我的订单"),
				"/ucenter/integral" => ILang::get("我的积分"),
				"/ucenter/redpacket" => ILang::get("我的优惠券"),
			],

			ILang::get("服务中心") => [
				"/ucenter/refunds" => ILang::get("售后服务"),
				"/ucenter/complain" => ILang::get("站点建议"),
				"/ucenter/consult" => ILang::get("商品咨询"),
				"/ucenter/evaluation" => ILang::get("商品评价"),
			],

			ILang::get("应用") => [
				"/ucenter/message" => ILang::get("短信息"),
				"/ucenter/favorite" => ILang::get("收藏夹"),
			],

			ILang::get("账户资金") => [
				"/ucenter/account_log" => ILang::get("预存款"),
				"/ucenter/online_recharge" => ILang::get("在线充值"),
			],

			ILang::get("个人设置") => [
				"/ucenter/address" => ILang::get("地址管理"),
				"/ucenter/info" => ILang::get("个人资料"),
				"/ucenter/password" => ILang::get("修改密码"),
				"/ucenter/invoice" => ILang::get("发票管理"),
			],
		];

		//菜单创建事件触发
		plugin::trigger("onUcenterMenuCreate");
		return self::$menu;
    }
}