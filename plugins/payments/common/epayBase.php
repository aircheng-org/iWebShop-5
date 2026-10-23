<?php
/**
 * @brief 彩虹易支付支付接口基类
 * @class epayBase
 * @date 2026/5/20 9:29:47
 */
include_once(dirname(__FILE__)."/epaySdk/EpayCore.class.php");
abstract class epayBase extends paymentPlugin
{
	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl(){}

	/**
	 * @see paymentplugin::doRefund()
	 */
	public function doRefund($payment)
	{
        //交易号
        $trade_no = $payment['M_TransactionId'];

        //需要退款的金额，该金额不能大于订单金额，必填
        $refund_amount = $payment['M_Refundfee'];

        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        $out_request_no = $payment['M_RefundNo'];

		$epay = new EpayCore($epay_config);
		$result = $epay->refund($out_request_no, $trade_no, $refund_amount);

		if($result && $result['code'] === 0)
		{
			$this->recordRefundTradeNo($payment['M_RefundId'],$result['trade_no']);
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @see paymentplugin::notifyStop()
	 */
	public function notifyStop()
	{
		echo "success";
	}

	/**
	 * @see paymentplugin::callback()
	 */
	public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
	    $url = IUrl::getHost().IUrl::creatUrl('/ucenter/order');
	    header('location: '.$url);
	    exit;
	}

	/**
	 * @see paymentplugin::serverCallback()
	 */
	public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
		$config = Payment::getConfigParam($paymentId);

		//计算得出通知验证结果
		$initConfig = [
			'apiurl'               => $config['url'],
			'pid'                  => $config['pid'],
			'platform_public_key'  => $config['public_key'],
			'merchant_private_key' => $config['private_key'],
		];

		$epay = new EpayCore($initConfig);
		$verify_result = $epay->verify($callbackData);

		//验证成功
		if($verify_result)
		{
			//商户订单号
			$orderNo = $_GET['out_trade_no'];

			//彩虹易支付交易号
			$trade_no = $_GET['trade_no'];

			//交易状态
			$trade_status = $_GET['trade_status'];

			//支付方式
			$type = $_GET['type'];

			//支付金额
			$money = $_GET['money'];

			if($_GET['trade_status'] == 'TRADE_SUCCESS')
			{
				//记录回执流水号
				if($trade_no)
				{
					$this->recordTradeNo($orderNo,$trade_no);
				}
				return true;
			}
		}
		else
		{
			return false;
		}
	}
}