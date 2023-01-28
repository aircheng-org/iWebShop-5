<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file payment.php
 * @brief 支付方式 操作类
 * @author kane
 * @date 2011-01-20
 * @version 0.6
 * @note
 */

/**
 * @class Payment
 * @brief 支付方式 操作类
 */
//支付状态：支付失败
define ( "PAY_FAILED", - 1);
//支付状态：支付超时
define ( "PAY_TIMEOUT", 0);
//支付状态：支付成功
define ( "PAY_SUCCESS", 1);
//支付状态：支付取消
define ( "PAY_CANCEL", 2);
//支付状态：支付错误
define ( "PAY_ERROR", 3);
//支付状态：支付进行
define ( "PAY_PROGRESS", 4);
//支付状态：支付无效
define ( "PAY_INVALID", 5);

class Payment
{
	/**
	 * @brief 创建支付类实例
	 * @param $payment_id int 支付方式ID
	 * @return 返回支付插件类对象
	 */
	public static function createPaymentInstance($payment_id)
	{
		$paymentRow = self::getPaymentById($payment_id);

		if($paymentRow && isset($paymentRow['class_name']) && $paymentRow['class_name'])
		{
			$class_name = $paymentRow['class_name'];
			$classPath  = IWeb::$app->getBasePath().'plugins/payments/pay_'.$class_name.'/'.$class_name.'.php';
			if(file_exists($classPath))
			{
				require_once($classPath);
				return new $class_name($payment_id);
			}
			else
			{
				IError::show(403,'支付接口类'.$class_name.'没有找到');
			}
		}
		else
		{
			IError::show(403,'支付方式不存在');
		}
	}

	/**
	 * @brief 根据支付方式配置编号  获取该插件的详细配置信息
	 * @param $payment_id int    支付方式ID
	 * @param $key        string 字段
	 * @return 返回支付插件类对象
	 */
	public static function getPaymentById($payment_id,$key = '')
	{
		$paymentDB  = new IModel('payment');
		$paymentRow = $paymentDB->getObj('id = '.$payment_id.' and status = 0 and type = 1');
		if(!$paymentRow)
		{
			return null;
		}

		if($key)
		{
			return isset($paymentRow[$key]) ? $paymentRow[$key] : '';
		}
		return $paymentRow;
	}

	/**
	 * @brief 根据支付方式配置编号  获取该插件的配置信息
	 * @param $payment_id int    支付方式ID
	 * @param $key        string 字段
	 * @return 返回支付插件类对象
	 */
	public static function getConfigParam($payment_id,$key = '')
	{
		$payConfig = self::getPaymentById($payment_id,'config_param');
		if($payConfig)
		{
			$payConfig = JSON::decode($payConfig);
			if($key)
			{
			    return isset($payConfig[$key]) ? $payConfig[$key] : '';
			}
			return $payConfig;
		}
		return '';
	}

	/**
	 * @brief 获取订单中的支付信息 M:必要信息; R表示店铺; P表示用户;
	 * @param $payment_id int    支付方式ID
	 * @param $type       string 信息获取方式 order:订单支付;recharge:在线充值;
	 * @param $argument   mix    参数
	 * @return array 支付提交信息
	 */
	public static function getPaymentInfo($payment_id,$type,$argument = null)
	{
		//最终返回值
		$payment = array();

		//初始化配置参数
		$paymentInstance = Payment::createPaymentInstance($payment_id);
		$configParam = $paymentInstance->configParam();
		foreach($configParam as $key => $val)
		{
			$payment[$key] = '';
		}

		//获取公共信息
		$paymentRow = self::getPaymentById($payment_id,'config_param');
		if($paymentRow)
		{
			$paymentRow = JSON::decode($paymentRow);
			foreach($paymentRow as $key => $item)
			{
				$payment[$key] = $item;
			}
		}

		if($type == 'order')
		{
			$orderIdArray = $argument;
			$M_Amount     = 0;
			$M_OrderNO    = array();
			foreach($orderIdArray as $key => $order_id)
			{
				//获取订单信息
				$orderObj = new IModel('order');
				$orderRow = $orderObj->getObj('id = '.$order_id.' and status = 1');
				if(empty($orderRow))
				{
					IError::show(403,'订单状态不正确，请到用户中心的订单列表查看');
				}

				//判断商品库存
				$orderGoodsDB   = new IModel('order_goods');
				$orderGoodsList = $orderGoodsDB->query('order_id = '.$order_id);
				foreach($orderGoodsList as $key => $val)
				{
					if(!goods_class::checkStore($val['goods_nums'],$val['goods_id'],$val['product_id']))
					{
						IError::show(403,'商品库存不足无法支付，请重新下单');
					}
				}

				//如果是活动订单检查其条件
				if($orderRow['type'] > 0 && $promo = array_search($orderRow['type'],Active::$typeToIdMapping))
				{
					$ac_type    = $val['product_id'] > 0 ? "product"          : "goods";
					$ac_id      = $val['product_id'] > 0 ? $val['product_id'] : $val['goods_id'];
					$ac_buy_num = $val['goods_nums'];

			    	$activeObject = new Active($promo,$orderRow['active_id'],$orderRow['user_id'],$ac_id,$ac_type,$ac_buy_num);
			    	$activeResult = $activeObject->checkValid($orderRow['id']);
			    	if($activeResult && is_string($activeResult))
			    	{
			    		IError::show(403,$activeResult);
			    	}
				}

				$M_Amount   += $orderRow['order_amount'];
				$M_OrderNO[] = $orderRow['order_no'];
			}

			$payment['M_Remark']    = $orderRow['postscript'];
			$payment['M_OrderId']   = $orderRow['id'];
			$payment['M_OrderNO']   = $orderRow['order_no'];
			$payment['M_Amount']    = $M_Amount;
			$payment['M_BatchOrderNO'] = join("_",$M_OrderNO);

			//用户信息
			$payment['P_Mobile']    = $orderRow['mobile'];
			$payment['P_Name']      = $orderRow['accept_name'];
			$payment['P_PostCode']  = $orderRow['postcode'];
			$payment['P_Telephone'] = $orderRow['telphone'];
			$payment['P_Address']   = $orderRow['address'];

			//订单批量结算缓存机制
			Order_Class::setBatch($payment['M_OrderNO'],$M_OrderNO);
		}
		else if($type == 'recharge')
		{
			if(IWeb::$app->getController()->user['user_id'] == null)
			{
				IError::show(403,'请登录系统');
			}

			if(!isset($argument['account']) || $argument['account'] <= 0)
			{
				IError::show(403,'请填入正确的充值金额');
			}

			$rechargeObj = new IModel('online_recharge');
			$reData      = array(
				'user_id'     => IWeb::$app->getController()->user['user_id'],
				'recharge_no' => Order_Class::createOrderNum(),
				'account'     => $argument['account'],
				'time'        => ITime::getDateTime(),
				'payment_name'=> $argument['paymentName'],
			);
			$rechargeObj->setData($reData);
			$r_id = $rechargeObj->add();

			//充值时用户id跟随交易号一起发送
			$payment['M_OrderNO'] = 'recharge'.$reData['recharge_no'];
			$payment['M_OrderId'] = $r_id;
			$payment['M_Amount']  = $reData['account'];
		}

		$siteConfigObj = new Config("site_config");
		$site_config   = $siteConfigObj->getInfo();

		//交易信息
		$payment['M_Time']      = time();
		$payment['M_Paymentid'] = $payment_id;

		//店铺信息
		$payment['R_Address']   = isset($site_config['address']) ? $site_config['address'] : '';
		$payment['R_Name']      = isset($site_config['name'])    ? $site_config['name']    : '';
		$payment['R_Mobile']    = isset($site_config['mobile'])  ? $site_config['mobile']  : '';
		$payment['R_Telephone'] = isset($site_config['phone'])   ? $site_config['phone']   : '';

		return $payment;
	}

	//更新在线充值
	public static function updateRecharge($recharge_no)
	{
		$rechargeObj = new IModel('online_recharge');
		$rechargeRow = $rechargeObj->getObj('recharge_no = "'.$recharge_no.'"');
		if(empty($rechargeRow))
		{
			return false;
		}

		if($rechargeRow['status'] == 1)
		{
			return true;
		}

		$dataArray = array(
			'status' => 1
		);

		$rechargeObj->setData($dataArray);
		$result = $rechargeObj->update('recharge_no = "'.$recharge_no.'"');

		if($result == '')
		{
			return false;
		}

		$money    = $rechargeRow['account'];
		$user_id  = $rechargeRow['user_id'];
		$pay_name = $rechargeRow['payment_name'];
		$userObj  = new IModel('user');
		$userRow  = $userObj->getObj('id = '.$user_id);
		$username = $userRow['username'];

		$log = new AccountLog();
		$config=array(
			'user_id'  => $user_id,
			'event'    => 'recharge',
			'note'     => '用户['.$username.']通过 '.$pay_name.' 在线充值',
			'num'      => $money,
		);
		$log_result = $log->write($config);
		if ($log_result)
		{
		    // 在线充值奖励操作
			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('user_id = '.$user_id,'group_id');

		    $proObj = new ProRule();
			$proObj->setUserGroup($memberRow['group_id']);
		    $proObj->setRechargeAward($money, $user_id);

		    //事件发送
		    plugin::trigger('onlineRechargeFinish',$recharge_no);
		}
		return $log_result;
	}

	/**
	 * @brief 搜集退款信息
	 * @param int   $payment_id 支付方式ID
	 * @param array $orderRow   订单数据
	 * @param array $refundRow  退款单数据
	 * @return array
	 */
	public static function getRefundInfo($payment_id,$orderRow,$refundsRow)
	{
        //最终返回值
        $payment = array();

        //初始化配置参数
        $paymentInstance = Payment::createPaymentInstance($payment_id);
        $configParam = $paymentInstance->configParam();
        foreach($configParam as $key => $val)
        {
            $payment[$key] = '';
        }

        //获取公共信息
        $paymentRow = self::getPaymentById($payment_id,'config_param');
        if($paymentRow)
        {
            $paymentRow = JSON::decode($paymentRow);
            foreach($paymentRow as $key => $item)
            {
                $payment[$key] = $item;
            }
        }

        $payment['M_RefundId']      = $refundsRow['id'];
        $payment['M_Refundfee']     = $refundsRow['amount'];
        $payment['M_RefundNo']      = $orderRow['order_no']."M".$refundsRow['id'];
        $payment['M_OrderNO']       = $orderRow['order_no'];
        $payment['M_Amount']        = $orderRow['order_amount'];
        $payment['M_TransactionId'] = $orderRow['trade_no'];
		$payment['M_REASON']        = $refundsRow['content'];

        //获取同一个流水号的所有订单
        if($orderRow['trade_no'])
        {
            $orderDB = new IModel('order');
            $sumRow  = $orderDB->getObj("trade_no = '".$orderRow['trade_no']."'","sum(`order_amount`) as sumAmount");
            $payment['M_Amount'] = $sumRow['sumAmount'];
        }

        //交易信息
        $payment['M_Time']      = time();
        $payment['M_Paymentid'] = $payment_id;

        return $payment;
	}

	//转账方式
	public static function transferWay($type)
	{
		switch($type)
		{
			case "wechatBalance":
			{
				return "微信余额";
			}
			break;

			case "offline":
			{
				return "人工线下";
			}
			break;
		}
		return '未知';
	}
}