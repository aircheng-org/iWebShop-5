<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file menuUcenter.php
 * @brief 用户中心菜单管理
 * @author nswe
 * @date 2016/3/8 9:33:25
 * @version 4.4
 */
class menuUcenter
{
	//菜单与图标对应关系
	public static $ico = array(
		"我的订单"   => "fa fa-shopping-bag",
		"我的积分"   => "fa fa-trophy",
		"我的优惠券" => "fa fa-tags",
		"售后服务"   => "fa fa-reply",
		"站点建议"   => "fa fa-file",
		"商品咨询"   => "fa fa-comment",
		"商品评价"   => "fa fa-comments",
		"短信息"     => "fa fa-bell",
		"收藏夹"     => "fa fa-heart",
		"预存款"   => "fa fa-money",
		"在线充值"   => "fa fa-sign-in",
		"地址管理"   => "fa fa-map-marker",
		"个人资料"   => "fa fa-table",
		"修改密码"   => "fa fa-key",
		"发票管理"   => "fa fa-ticket",
	);

    //菜单的配制数据
	public static $menu = array(
		"交易记录" => array(
			"/ucenter/order" => "我的订单",
			"/ucenter/integral" => "我的积分",
			"/ucenter/redpacket" => "我的优惠券",
		),

		"服务中心" => array(
			"/ucenter/refunds" => "售后服务",
			"/ucenter/complain" => "站点建议",
			"/ucenter/consult" => "商品咨询",
			"/ucenter/evaluation" => "商品评价",
		),

		"应用" => array(
			"/ucenter/message" => "短信息",
			"/ucenter/favorite" => "收藏夹",
		),

		"账户资金" => array(
			"/ucenter/account_log" => "预存款",
			"/ucenter/online_recharge" => "在线充值",
		),

		"个人设置" => array(
			"/ucenter/address" => "地址管理",
			"/ucenter/info" => "个人资料",
			"/ucenter/password" => "修改密码",
			"/ucenter/invoice" => "发票管理",
		),
	);

    /**
     * @brief 根据权限初始化菜单
     * @param int $roleId 角色ID
     * @return array 菜单数组
     */
    public static function init($roleId = "")
    {
		//菜单创建事件触发
		plugin::trigger("onUcenterMenuCreate");
		return self::$menu;
    }
}