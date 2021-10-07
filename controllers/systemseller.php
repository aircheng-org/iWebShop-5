<?php
/**
 * @brief 商家登录控制器
 * @class Seller
 * @author chendeshan
 * @datetime 2014/7/19 15:18:56
 */
class SystemSeller extends IController
{
	public $layout = '';

	/**
	 * @brief 商家登录动作
	 */
	public function login()
	{
		$seller_name = IFilter::act(IReq::get('username'));
		$password    = IReq::get('password');
		$message     = '';

		if($seller_name == '')
		{
			$message = '登录名不能为空';
		}
		else if($password == '')
		{
			$message = '密码不能为空';
		}
        else
		{
			$sellerObj = new IModel('seller');
			$sellerRow = $sellerObj->getObj('seller_name = "'.$seller_name.'" or mobile = "'.$seller_name.'" and is_del = 0 and is_lock = 0');
			if($sellerRow && ($sellerRow['password'] == md5($password)))
			{
				$dataArray = array(
					'login_time' => ITime::getDateTime(),
				);
				$sellerObj->setData($dataArray);
				$where = 'id = '.$sellerRow["id"];
				$sellerObj->update($where);

				//存入私密数据
				ISafe::set('seller_id',$sellerRow['id']);
				ISafe::set('seller_name',$sellerRow['seller_name']);
				ISafe::set('seller_pwd',$sellerRow['password']);

				//通知事件
				plugin::trigger("sellerLoginCallback",$sellerRow);

				$this->redirect('/seller/index');
			}
			else
			{
				$message = '用户名与密码不匹配';
			}
		}

		if($message != '')
		{
			$this->redirect('index',false);
			Util::showMessage($message);
		}
	}

	//后台登出
	function logout()
	{
		plugin::trigger('clearSeller');
    	$this->redirect('index');
	}
}