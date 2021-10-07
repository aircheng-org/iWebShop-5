<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file _authorization.php
 * @brief 权限校验
 * @author nswe
 * @date 2016/4/14 0:45:00
 * @version 4.4
 */

/**
 * iWebShop4.4正式版本后权限校验以插件的形式运行，有些时候，我们的控制器里面的方法内容不希望被随意访问到，比如管理后台的操作等
 * 此时就必须要用到控制器和动作的权限校验，功能强大，易扩展性强，支持角色管理，比如某个身份的管理员可以访问A，B方法，而不能访问C方法等。
 *
 * iWebShop的权限分为3大类：
 * 1,admin （后台管理员）继承adminAuthorization接口
 * 2,seller（商家管理）  继承sellerAuthorization接口
 * 3,user  （注册用户）  继承userAuthorization接口
 *
 * 使用方法：
 * 1,权限校验基于控制器，在控制器里面通过 implements 继承接口的方式来确定访问控制器所需要的权限,比如 controllers/goods.php 的控制器是后台商品管理的相关操作
 *   我们就让goods控制器继承（implements admin）就可以对整个控制器进行admin的权限校验了，所有不符合admin权限校验的都会拒之门外！
 *   其他权限比如seller和user也是同理，只要是给控制器继承后系统就会自动拦截校验
 *
 * 2,权限除了整体拦截之外，还可以对专门的角色进行拦截或者放行，比如后台的default默认页面，对于任何管理员都是可见的，但是对于订单管理来说必须只有
 *   订单管理权限的这类管理员才能操作，我们可以对管理员进行二次细分，比如商品管理员，订单管理员，系统配置管理员，库存管理员...
 *
 * 3,角色控制也是通过在控制器里面设置【$checkRight】属性来决定哪个动作（action）需要特殊的身份才能运行
 *   具体写法：(1) $checkRight = 'all' 表示所有动作都需要校验
 *             (2) $checkRight = array(
 *                     'check(需要校验)'     => array('动作ID'),
 *                     'uncheck(不需要校验)' => array('动作ID'),
 *                 )
 */
class _authorization extends pluginBase
{
	/**
	 * admin分享给seller的action
	 * 控制器名称(controller) @ 动作名称(action)
	 */
	private static $adminShareSellerAction = array
	(
		'goods@spec_edit',
		'goods@spec_update',
		'goods@member_price',
		'goods@goods_setting',
		'goods@goods_setting_save',
		'goods@goods_img_upload',
		'goods@search',
		'goods@search_result',
		'goods@preorder_setting',
		'goods@preorder_setting_save',

		'order@shop_template',
		'order@pick_template',
		'order@expresswaybill_template',
		'order@expresswaybill_print',
		'order@expresswaybill_ajax',
		'order@search',
		'order@check_code_ajax',
		'order@order_code_check',
		'order@get_code_info_ajax',

	    'market@order_goods_servicefee_list',
		'market@bill_report',
	);

	/**
	 * @brief 商家action校验
	 * 非session会话变量的校验，有些情境下比如flash调用时候，session不起作用，
	 * 需要通过其他方式校验身份权限
	 */
	private static $sellerAction = array();

	//管理员action校验 (同上商家action校验
	private static $adminAction  = array();

	//注册事件
	public function reg()
	{
		plugin::reg("onBeforeCreateAction",$this,"authorizationCheck");
		plugin::reg("clearUser",$this,"clearUser");
		plugin::reg("clearAdmin",$this,"clearAdmin");
		plugin::reg("clearSeller",$this,"clearSeller");
	}

	//清除用户权限
	public function clearUser()
	{
		ISafe::clear('user_id');
		ISafe::clear('username');
		ISafe::clear('head_ico');
		ISafe::clear('user_pwd');
	}

	//清除管理员权限
	public function clearAdmin()
	{
    	ISafe::clear('admin_id');
    	ISafe::clear('admin_right');
    	ISafe::clear('admin_name');
    	ISafe::clear('admin_pwd');
	}

	//清除商家权限
	public function clearSeller()
	{
    	ISafe::clear('seller_id');
    	ISafe::clear('seller_name');
    	ISafe::clear('seller_pwd');
	}

	/**
	 * @brief 权限校验
	 * @param string $actionId 动作ID
	 */
	public function authorizationCheck($actionId)
	{
		$controller = self::controller();

		//管理员权限判断
		if($controller instanceof adminAuthorization)
		{
			self::checkAdminRights($actionId);
		}
		//商家权限判断
		else if($controller instanceof sellerAuthorization)
		{
			self::checkSellerRights($actionId);
		}
		//用户权限判断
		else if($controller instanceof userAuthorization)
		{
			self::checkUserRights($actionId);
		}
	}

	/**
	 * @brief 获取通用的管理员数组
	 * @return array or null管理员数据
	 */
	public static function getAdmin()
	{
		$admin = array(
			'admin_name'      => ISafe::get('admin_name'),
			'admin_pwd'       => ISafe::get('admin_pwd'),
			'admin_role_name' => ISafe::get('admin_role_name'),
		);

		if($adminRow = self::isValidAdmin($admin['admin_name'],$admin['admin_pwd']))
		{
			$admin['admin_id'] = $adminRow['id'];
			$admin['role_id']  = $adminRow['role_id'];
			return $admin;
		}
		return null;
	}

	/**
	 * @brief 获取通用的商户数组
	 * @return array or null商家数据
	 */
	public static function getSeller()
	{
		$seller = array(
			'seller_name' => ISafe::get('seller_name'),
			'seller_pwd'  => ISafe::get('seller_pwd'),
		);

		if($sellerRow = self::isValidSeller($seller['seller_name'],$seller['seller_pwd']))
		{
			$seller['seller_id'] = $sellerRow['id'];
			return $seller;
		}
		return null;
	}

	/**
	 * @brief 获取通用的注册用户数组
	 * @return array or null用户数据
	 */
	public static function getUser()
	{
		return plugin::trigger('getUser');
	}

	/**
	 * user权限拦截
	 * @param $actionId string 动作ID
	 */
	public static function checkUserRights($actionId)
	{
		$object = IWeb::$app->getController();
		$userRow= self::getUser();
		if(!$userRow)
		{
			$object->redirect('/simple/login?callback='.urlencode(IUrl::getUrl()));
			exit;
		}

		//角色权限校验
		$rights = "";
		if(self::checkRight($rights,$actionId) == false)
		{
			IError::show('503','no permission to access');
			exit;
		}
		$object->user = $userRow;
		plugin::trigger('checkUserRights');
	}

	/**
	 * @brief seller权限拦截
	 * @param $actionId string 动作ID
	 */
	public static function checkSellerRights($actionId)
	{
		$object       = IWeb::$app->getController();
		$controllerId = $object->getId();

		//1,针对独立配置的action检测
		if(isset(self::$sellerAction[$controllerId."@".$actionId]) && method_exists(__CLASS__,self::$sellerAction[$controllerId."@".$actionId]))
		{
			call_user_func(array(__CLASS__,self::$sellerAction[$controllerId."@".$actionId]));
			return;
		}
		//2,其余action检测
		else
		{
			$sellerRow = self::getSeller();
			if(!$sellerRow)
			{
				$object->redirect('/systemseller/index');
				exit;
			}

			//角色权限校验
			$rights = "";
			if(self::checkRight($rights,$actionId) == false)
			{
				IError::show('503','no permission to access');
				exit;
			}
			$object->seller = $sellerRow;
			plugin::trigger('checkSellerRights');
		}
	}

	/**
	 * @brief admin权限拦截
	 * @param $actionId string 动作ID
	 */
	public static function checkAdminRights($actionId)
	{
		$object       = IWeb::$app->getController();
		$controllerId = $object->getId();

		//1,针对独立配置的action检测
		if(isset(self::$adminAction[$controllerId."@".$actionId]) && method_exists(__CLASS__,self::$adminAction[$controllerId."@".$actionId]))
		{
			call_user_func(array(__CLASS__,self::$adminAction[$controllerId."@".$actionId]));
			return;
		}
		//2,admin共享给seller
		else if( (in_array($controllerId."@".$actionId,self::$adminShareSellerAction) || in_array($controllerId."@*",self::$adminShareSellerAction) ) && ($object->seller = self::getSeller()))
		{
			//URL中的seller_id作为商家身份标示
			$seller_id = IReq::get('seller_id');
			if($seller_id)
			{
				if($seller_id != $object->seller['seller_id'])
				{
					die('当前商家身份与要操作的商家身份不符');
				}
			}
			//[属于测试环境]当admin和seller权限都有的时候，启用admin权限
			else if($object->admin = self::getAdmin())
			{
				$object->seller = null;
			}
			else
			{
			    die('缺少seller_id身份参数');
			}
			return;
		}
		//3,其余action检测
		else
		{
			$adminRow = self::getAdmin();
			if(!$adminRow)
			{
				$object->redirect('/admin/index');
				exit;
			}

			//非超管角色
			if($adminRow['role_id'] != 0)
			{
				$roleObj = new IModel('admin_role');
				$where   = 'id = '.$adminRow["role_id"].' and is_del = 0';
				$roleRow = $roleObj->getObj($where);
				if(!$roleRow)
				{
					IError::show('503','admin role is delete');
					exit;
				}

				//角色权限校验
				$rights = $roleRow['rights'];
				if(self::checkRight($rights,$actionId) == false)
				{
					IError::show('503','no permission to access');
					exit;
				}
			}
			$object->admin = $adminRow;
			plugin::trigger('checkAdminRights');
		}
	}

	/**
	 * @brief 角色校验拦截
	 * @see 在控制器里面设置【$checkRight】属性来决定哪个动作（action）需要特殊的身份才能运行
	 *      $checkRight = 'all' 或者 array('check' => 'all' 或者 array('动作ID'),uncheck => array('动作ID') )
	 * @param string $ownRight 权限码
	 * @param string $actionId 动作ID
	 * @return bool true:校验通过; false:校验未通过
	 */
	private static function checkRight($ownRight,$actionId)
	{
		$controllerInstance = IWeb::$app->getController();
		if($controllerInstance->checkRight == null)
		{
			return true;
		}

		//是否需要权限校验 true:需要; false:不需要
		$isCheckRight = false;
		if($controllerInstance->checkRight == 'all')
		{
			$isCheckRight = true;
		}
		else if(is_array($controllerInstance->checkRight))
		{
			if(isset($controllerInstance->checkRight['check']) && ( ($controllerInstance->checkRight['check'] == 'all') || ( is_array($controllerInstance->checkRight['check']) && in_array($actionId,$controllerInstance->checkRight['check']) ) ) )
			{
				$isCheckRight = true;
			}

			if(isset($controllerInstance->checkRight['uncheck']) && is_array($controllerInstance->checkRight['uncheck']) && in_array($actionId,$controllerInstance->checkRight['uncheck']))
			{
				$isCheckRight = false;
			}
		}

		//需要校验权限
		if($isCheckRight == true)
		{
			$rightCode = $controllerInstance->getId().'@'.$actionId; //拼接的权限校验码
			$ownRight  = ','.trim($ownRight,',').',';

			if(stripos($ownRight,','.$rightCode.',') === false)
				return false;
			else
				return true;
		}
		else
			return true;
	}

	/**
	 * @brief  校验注册用户身份信息
	 * @param  string $login_info 登录信息
	 * @param  string $password   用户名的md5密码
	 * @return array or false 如果合法则返回用户数据;不合法返回false
	 */
	public static function isValidUser($login_info,$password)
	{
		return plugin::trigger('isValidUser',array($login_info,$password));
	}

	/**
	 * @brief 验证卖家身份信息
	 * @param string $login_info 登录信息
	 * @param string $password 登录密码
	 * @param array or false
	 */
	private static function isValidSeller($login_info,$password)
	{
		$login_info = IFilter::act($login_info);
		$password   = IFilter::act($password);

		$sellerObj = new IModel('seller');
		$where     = "seller_name = '{$login_info}' and is_del = 0 and is_lock = 0";
		$sellerRow = $sellerObj->getObj($where);

		if($sellerRow && ($sellerRow['password'] == $password))
		{
			return $sellerRow;
		}
		return false;
	}

	/**
	 * @brief 验证管理员身份信息
	 * @param string $login_info 登录信息
	 * @param string $password 登录密码
	 * @param array or false
	 */
	private static function isValidAdmin($login_info,$password)
	{
		$login_info = IFilter::act($login_info);
		$password   = IFilter::act($password);

		$adminObj = new IModel('admin');
		$where    = "admin_name='{$login_info}' and is_del = 0";
		$adminRow = $adminObj->getObj($where);

		if($adminRow && ($adminRow['password'] == $password))
		{
			return $adminRow;
		}
		return false;
	}
}

/**
 * @brief 管理员权限
 */
interface adminAuthorization
{

}

/**
 * @brief 管理员权限
 */
interface sellerAuthorization
{

}

/**
 * @brief 注册用户权限
 */
interface userAuthorization
{

}