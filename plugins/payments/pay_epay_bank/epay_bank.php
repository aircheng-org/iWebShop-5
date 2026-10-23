<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file epay_bank.php
 * @brief 彩虹易支付接口
 * @author nswe
 * @date 2026-05-27
 * @version 5.16
 * @note
 */

 /**
 * @class epay_bank
 * @brief 彩虹易支付接口
 */
include_once(dirname(__FILE__)."/../common/epayBase.php");
class epay_bank extends epayBase
{
	//插件名称
    public $name = '彩虹易支付(银联)';

	/**
	 * @see paymentplugin::getSendData()
	 */
	public function getSendData($payment)
	{
		$method = IClient::getDevice() == IClient::MOBILE ? "jump" : "web";

		$data = [
			'notify_url'   => $this->callbackUrl,
			'return_url'   => $this->serverCallbackUrl,
			'name'         => $payment['M_GoodsName'],
			'type'         => 'bank',
			'out_trade_no' => $payment['M_OrderNO'],
			'money'        => $payment['M_Amount'],
			'clientip'     => IClient::getIp(),
			'method'       => $method,
		];

		$initConfig = [
			'apiurl'               => $payment['url'],
			'pid'                  => $payment['pid'],
			'platform_public_key'  => $payment['public_key'],
			'merchant_private_key' => $payment['private_key'],
		];

		$epayObject = new EpayCore($initConfig);
		$result = $epayObject->apiPay($data);

		if($result && is_array($result))
		{
			if($result['code'] === 0)
			{
				$result['amount']   = $payment['M_Amount'];
				$result['order_no'] = $payment['M_OrderNO'];
				return $result;
			}
			else
			{
				throw new IException(__CLASS__." getSendData:".var_export($result,true));
			}
		}
		else
		{
			die(__CLASS__ .':'.$result);
		}
	}

	/**
	 * @see paymentplugin::doPay()
	 */
	public function doPay($sendData)
	{
		//扫码模式
		if($sendData['pay_type'] == 'qrcode')
		{
		    $sendData['code_img'] = IUrl::getHost().IUrl::creatUrl('/pic/qrcode?data='.$sendData['pay_info']);

			if(stripos($sendData['order_no'],'recharge') !== false)
			{
				$sendData['url'] = IUrl::getHost().IUrl::creatUrl('/ucenter/account_log');
			}
			else
			{
				$sendData['url'] = IUrl::getHost().IUrl::creatUrl('/ucenter/order');
			}
			$sendData['orderCheckUrl'] = IUrl::getHost().IUrl::creatUrl('/block/orderCheck/order_no/'.$sendData['order_no']);
			$sendData['successUrl']    = IUrl::getHost().IUrl::creatUrl('/site/success/message/'.urlencode('支付成功！'));
			include(dirname(__FILE__).'/template/pay.php');
		}
		//其他模式
		else
		{
			header('location: '.$sendData['pay_info']);
		}
	}

	/**
	 * @param 获取配置参数
	 */
	public function configParam()
	{
		$result = array(
			'url'         => '接口地址',
			'pid'         => '商户ID',
			'public_key'  => '平台公钥',
			'private_key' => '商户私钥',
		);
		return $result;
	}
}
