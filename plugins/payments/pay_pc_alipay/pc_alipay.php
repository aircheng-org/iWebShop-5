<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file pc_alipay.php
 * @brief 支付宝电脑网站支付
 * @author nswe
 * @date 2019/6/25 9:27:43
 * @version 5.6
 */
require_once dirname(__FILE__).'/service/AlipayTradeService.php';
class pc_alipay extends paymentPlugin
{
	//支付插件名称
    public $name = '电脑网站支付';

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl()
	{
		return 'https://openapi.alipay.com/gateway.do';
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
	    $config['gatewayUrl'] = $this->getSubmitUrl();

        $alipaySevice = new AlipayTradeService($config);
        $result = $alipaySevice->check($callbackData);

		if($result)
		{
			//回传数据
			$orderNo = $callbackData['out_trade_no'];
			$money   = $callbackData['total_amount'];

			//记录回执流水号
			if(isset($callbackData['trade_no']) && $callbackData['trade_no'])
			{
				$this->recordTradeNo($orderNo,$callbackData['trade_no']);
			}

			if($callbackData['trade_status'] == 'TRADE_FINISHED' || $callbackData['trade_status'] == 'TRADE_SUCCESS')
			{
				return true;
			}
		}
		else
		{
			$message = "签名不正确";
			throw new IException("签名不正确,参数接口回调数据：".var_export($callbackData,true));
		}
		return false;
	}

	/**
	 * @see paymentplugin::getSendData()
	 */
	public function getSendData($payment)
	{
		$return = [
		    'out_trade_no' => $payment['M_OrderNO'],
		    'subject'      => $payment['R_Name'],
		    'total_amount' => number_format($payment['M_Amount'], 2, '.', ''),
		    'app_id'       => $payment['app_id'],
		    'merchant_private_key' => $payment['merchant_private_key'],
		    'notify_url'   => $this->serverCallbackUrl,
		    'return_url'   => $this->callbackUrl,
		    'gatewayUrl'   => $this->getSubmitUrl(),
		    'alipay_public_key' => $payment['alipay_public_key'],
		];

		return $return;
	}

	/**
	 * @see paymentplugin::doPay()
	 */
	public function doPay($sendData)
	{
	    require_once dirname(__FILE__).'/buildermodel/AlipayTradePagePayContentBuilder.php';

    	//构造参数
    	$payRequestBuilder = new AlipayTradePagePayContentBuilder();
    	$payRequestBuilder->setSubject($sendData['subject']);
    	$payRequestBuilder->setTotalAmount($sendData['total_amount']);
    	$payRequestBuilder->setOutTradeNo($sendData['out_trade_no']);

    	$aop = new AlipayTradeService($sendData);

    	/**
    	 * pagePay 电脑网站支付请求
    	 * @param $builder 业务参数，使用buildmodel中的对象生成。
    	 * @param $return_url 同步跳转地址，公网可以访问
    	 * @param $notify_url 异步通知地址，公网可以访问
    	 * @return $response 支付宝返回的信息
     	 */
    	$response = $aop->pagePay($payRequestBuilder,$sendData['return_url'],$sendData['notify_url']);

    	//输出表单
    	echo($response);
	}

	/**
	 * @brief 执行退款接口
	 * @param array $payment 退款信息接口
	 */
	public function doRefund($payment)
	{
	    require_once dirname(__FILE__).'/buildermodel/AlipayTradeRefundContentBuilder.php';

	    $config = [
	        'app_id'               => $payment['app_id'],
	        'merchant_private_key' => $payment['merchant_private_key'],
	        'alipay_public_key'    => $payment['alipay_public_key'],
	        'gatewayUrl'           => $this->getSubmitUrl(),
	    ];

        //支付宝交易号
        $trade_no = $payment['M_TransactionId'];

        //需要退款的金额，该金额不能大于订单金额，必填
        $refund_amount = $payment['M_Refundfee'];

        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        $out_request_no = date("YmdHis")."MMM".$payment['M_RefundId'];

        //构造参数
        $RequestBuilder=new AlipayTradeRefundContentBuilder();
        $RequestBuilder->setTradeNo($trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setOutRequestNo($out_request_no);

        $aop = new AlipayTradeService($config);

        /**
        * alipay.trade.refund (统一收单交易退款接口)
        * @param $builder 业务参数，使用buildmodel中的对象生成。
        * @return $response 支付宝返回的信息
        */
        $response = $aop->Refund($RequestBuilder);
		if(is_object($response))
		{
			//处理正确
			if(isset($response->code) && $response->code == '10000')
			{
				$this->recordRefundTradeNo($payment['M_RefundId'],$out_request_no);
				return true;
			}
			else
			{
				die($response->msg.$response->sub_msg);
			}
		}
		else
		{
			die($response);
		}
		return null;
	}

	/**
	 * @param 获取配置参数
	 */
	public function configParam()
	{
		$result = array(
			'app_id'               => 'APPID',
			'merchant_private_key' => '商户应用私钥',
			'alipay_public_key'    => '支付宝公钥',
		);
		return $result;
	}
}