<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file menu.php
 * @brief 后台系统菜单管理
 * @author nswe
 * @date 2016/3/4 23:59:33
 * @version 4.4
 */
class Menu
{
    //菜单的配制数据
	public static $menu = array(
		'商品'=>array(
			'商品管理'=>array(
			    '/goods/goods_list' => '商品列表',
			    '/goods/goods_edit' => '添加普通商品',
			    '/goods/goods_edit/type/code' => '添加服务类商品',
			    '/goods/goods_edit/type/download' => '添加下载类商品',
			    '/goods/goods_edit/type/preorder' => '添加时间类商品'
			),
			'商品分类'=>array(
				'/goods/category_list'	=>	'分类列表',
				'/goods/category_edit'	=>	'添加分类'
			),
			'品牌'=>array(
				'/brand/category_list'  =>	'品牌分类',
				'/brand/brand_list'		=>	'品牌列表'
			),
			'模型'=>array(
				'/goods/model_list'=>'模型列表',
				'/goods/spec_list'=>'规格列表',
				'/goods/spec_photo'=>'规格图库'
			),
			'搜索'=>array(
				'/tools/keyword_list' => '关键词列表',
				'/tools/search_list' => '搜索统计'
			)
		),

		'用户'=>array(
			'会员管理'=>array(
	    		'/member/member_list' 	=> '会员列表',
	     		'/member/group_list' 	=> '会员组列表',
				'/comment/message_list'	=> '会员消息',
				'/member/withdraw_list' => '提现待处理',
				'/member/withdraw_done' => '提现已处理',
			),
			'商户管理' => array(
				'/member/seller_list' => '商户列表',
				'/member/seller_edit' => '添加商户',
			    '/comment/seller_message_list' => '商户消息',
			    '/goods/goods_rate_list'	=>	'单品手续费',
			    '/goods/category_rate_list'	=>	'分类手续费',
				'/market/order_goods_merge' => '待结算货款',
				'/market/bill_list' => '已结算货款',
				'/market/order_goods_list' => '货款明细列表',
			),
			'信息处理' => array(
				'/comment/suggestion_list'  => '建议管理',
				'/comment/refer_list'		=> '咨询管理',
				'/comment/discussion_list'	=> '讨论管理',
				'/comment/comment_list'		=> '评价管理',
				'/message/notify_list'      => '到货通知',
				'/message/registry_list'    => '邮件订阅',
				'/message/marketing_sms_list'=> '营销短信',
			),
		),

	   '订单'=>array(
        	'订单管理'=>array(
                '/order/order_list' => '订单列表',
                '/order/order_edit' => '添加订单',
        		'/order/refundment_list' => '退款申请列表',
        		'/order/exchange_list'   => '换货申请列表',
        		'/order/fix_list'        => '维修申请列表',
        	),
        	'单据管理'=>array(
             	'/order/order_collection_list'  => '收款单',
        		'/order/order_delivery_list'    => '发货单',
        		'/order/order_refundment_list'  => '退款单',
        		'/order/order_exchange_list'    => '换货单',
        		'/order/order_fix_list'         => '维修单',
        	),
        	'发货地址'=>array(
        		'/order/ship_info_list'         => '发货地址管理',
        	),
		),

		'营销'=>array(
        	'促销活动' => array(
        		'/market/pro_rule_list' => '促销活动列表'
        	),
        	'营销活动' => array(
                '/market/cost_point_list' => '积分兑换',
				'/market/pro_speed_list' => '限时抢购',
				'/market/assemble_list' => '拼团',
        		'/market/regiment_list' => '团购',
        		'/market/sale_list' => '特价',
        	),
        	'优惠券管理'=>array(
        		'/market/ticket_list' => '优惠券列表',
        	),
        	'专题管理'=>array(
        		'/market/topic_list' => '专题列表',
        	),
		),

		'统计'=>array(
			'基础数据统计'=>array(
      			'/market/user_reg' 	   => '用户注册统计',
				'/market/spanding_avg' => '人均消费统计',
      			'/market/amount'       => '销售金额统计'
			),
			'日志操作记录'=>array(
				'/market/account_list'   => '用户资金记录',
				'/market/operation_list' => '后台操作记录',
			),
		),


        '系统'=>array(
    		'后台首页'=>array(
    			'/system/default' => '首页',
    		),
        	'网站管理'=>array(
        		'/system/conf_base' => '网站设置',
        		'/system/conf_guide' => '网站导航',
        		'/system/conf_banner' => '首页幻灯图',
        		'/system/conf_ui/type/site'   => '网站前台主题',
        		'/system/conf_ui/type/system'   => '后台管理主题',
        		'/system/conf_ui/type/seller'   => '商家管理主题',
        	),
        	'支付管理'=>array(
            	'/system/payment_list' => '支付方式'
        	),
        	'第三方平台'=>array(
            	'/system/oauth_list' => 'oauth登录列表',
            	'/system/hsms' => '手机短信平台',
        	),
        	'配送管理'=>array(
            	'/system/delivery'  	=> '配送方式',
        		'/system/freight_list'	=> '物流公司',
	    		'/system/takeself_list' => '自提点列表',
        	),
        	'地域管理'=>array(
        		'/system/area_list' => '地区列表',
        	),
        	'权限管理'=>array(
        		'/system/admin_list' => '管理员',
        		'/system/role_list'  => '角色',
        		'/system/right_list' => '权限资源'
        	),
		),

       '工具'=>array(
			'数据库管理'=>array(
				'/tools/db_bak' => '数据库备份',
				'/tools/db_res' => '数据库还原',
			),
			'文章管理'=>array(
				'/tools/article_cat_list'=> '文章分类',
				'/tools/article_list'=> '文章列表'
			),

			'帮助管理'=>array(
   				'/tools/help_cat_list'=> '帮助分类',
   				'/tools/help_list'=> '帮助列表'
   			),

   			'广告管理'=>array(
   				'/tools/ad_position_list'=> '广告位列表',
   				'/tools/ad_list'=> '广告列表'
   			),

   			'公告管理'=>array(
   				'/tools/notice_list'=> '公告列表',
   				'/tools/notice_edit'=> '公告发布'
   			),
     		'网站地图'=>array(
            	'/tools/seo_sitemaps' => '网站搜索地图',
			)
		),
		'插件' => array(
       		'插件管理' => array(
       			'/plugins/plugin_list' => '插件列表',
       		),
		),
	);

	//非菜单连接映射关系,array(视图名称 => menu数组中已存在的菜单连接)
	public static $innerPathUrl = array(
		"/system/navigation" => "/system/default",
		"/system/navigation_edit" => "/system/default",
	);

    /**
     * @brief 根据权限初始化菜单
     * @param int $roleId 角色ID
     * @return array 菜单数组
     */
    public static function init($roleId)
    {
		//菜单创建事件触发
		plugin::trigger("onSystemMenuCreate");

		//根据角色分配权限
		if($roleId == 0)
		{
			$adminRights = 'administrator';
		}
		else
		{
			$roleObj = new IModel('admin_role');
			$where   = 'id = '.$roleId.' and is_del = 0';
			$roleRow = $roleObj->getObj($where);
			$adminRights = isset($roleRow['rights']) ? $roleRow['rights'] : '';
		}

		//1,超管返回全部菜单
		if($adminRights == "administrator")
		{
			return self::$menu;
		}

		//2,根据权限码显示菜单
		$result      = array();
		$defaultShow = array('/system/default');
		foreach(self::$menu as $key1 => $val1)
		{
			foreach($val1 as $key2 => $val2)
			{
				foreach($val2 as $key3 => $val3)
				{
					//把菜单数据里面的路径转化成@符号做权限码比对
					preg_match("@/(\w+)/(\w+)@",$key3,$match);
					if(count($match) < 3)
					{
						continue;
					}
					$tempKey3 = $match[1].'@'.$match[2];
					if(in_array($key3,$defaultShow) || strpos($adminRights,$tempKey3) !== false)
					{
						$result[$key1][$key2][$key3] = $val3;
					}
				}
			}
		}
		return $result;
    }

    /**
     * @brief 根据当前URL动态生成菜单分组
     * @param array  $menu 菜单数据
     * @param string $info 连接信息
     * @return array 菜单数组
     */
    public static function get($menu,$info)
    {
    	$result = self::menuInfo($menu,$info);
    	if($result)
    	{
    		return $result;
    	}

		//历史URL信息
		$lastInfo = IUrl::getRefRoute();
		if($lastInfo && strpos($lastInfo,$info) === false && $result = self::menuInfo($menu,$lastInfo))
		{
			ICookie::set('lastInfo',$lastInfo);
			return $result;
		}

		//从COOKIE读取URL信息
		$lastInfo = ICookie::get('lastInfo');
		if($lastInfo)
		{
			return self::menuInfo($menu,$lastInfo);
		}
		return array('插件' => self::$menu['插件']);
    }

	/**
	 * @brief 判断url路径获取定义的菜单项
	 * @param array  $menu 当前管理员权限合法的菜单
	 * @param string $info 访问的URL
	 * @return array(地址=>名称) or null
	 */
    public static function menuInfo($menu,$info)
    {
    	//已有菜单查找
		foreach($menu as $key1 => $val1)
		{
			foreach($val1 as $key2 => $val2)
			{
				foreach($val2 as $key3 => $val3)
				{
					if(strpos($key3,$info) !== false || strpos($info,$key3) !== false)
					{
						return array($key1 => $menu[$key1]);
					}
				}
			}
		}

		//配置菜单映射
		if(self::$innerPathUrl)
		{
			foreach(self::$innerPathUrl as $key => $val)
			{
				if(strpos($key,$info) !== false)
				{
					return self::menuInfo($menu,$val);
				}
			}
		}
		return null;
    }

    /**
     * @brief 获取顶级分类关系数据
     * @param array $menu 菜单数据
     * @return array 顶级菜单数组
     */
    public static function getTopMenu($menu)
    {
    	$result = array();
		foreach($menu as $key1 => $val1)
		{
			foreach($val1 as $key2 => $val2)
			{
				foreach($val2 as $key3 => $val3)
				{
					$result[$key1] = $key3;
					break 2;
				}
			}
		}
		return $result;
    }
}