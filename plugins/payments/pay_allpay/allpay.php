<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file allpay.php
 * @brief 偶可贝支付插件
 * @author nswe
 * @date 2016/2/28 22:37:14
 * @version 4.4
 */

 /**
 * @class allpay
 * @brief 偶可贝支付插件类
 */
class allpay extends paymentPlugin
{
	//支付插件名称
    public $name = '偶可贝';

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl()
	{
		//生产环境
		return "https://pay.veritrans-link.com/epayment/payment";

		//测试环境
		//return 'http://115.28.142.180:8000/epayment/payment';
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
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($callbackData);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildMysign($para_sort,Payment::getConfigParam($paymentId,'merKey'));

		if($callbackData['signature'] == $mysign)
		{
			//回传数据
			$orderNo = $callbackData['orderNum'];
			$money   = $callbackData['orderAmount'];

			//记录回执流水号
			if(isset($callbackData['transID']) && $callbackData['transID'])
			{
				$this->recordTradeNo($orderNo,$callbackData['transID']);
			}

			if($callbackData['RespCode'] == '00')
			{
				return true;
			}
		}
		else
		{
			$message = '签名不正确';
		}
		return false;
	}

	/**
	 * @see paymentplugin::serverCallback()
	 */
	public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
		return $this->callback($callbackData,$paymentId,$money,$message,$orderNo);
	}

	/**
	 * @see paymentplugin::getSendData()
	 */
	public function getSendData($payment)
	{
		/* Fixed parameters */
		$version = 'VER000000002';
		$charset = 'UTF-8';
		$trans_type = 'PURC';
		$mer_reserve = '';
		$acq_id = $payment['acqID'];
		$payment_schema = 'AP';
		$code = $payment_schema == 'WX' ? 'Quick' : '';//微信特有参数
		$sign_type = 'MD5';
		$trans_time = date('YmdHis');

		/* Order related parameters */
		$order_num    = $payment['M_OrderNO'];
		$order_amount = number_format($payment['M_Amount'], 2, '.', '');
		$order_currency = "CNY";

		/* Configuration parameters */
		$merchant_id = $payment['merID'];
		$secret_key = $payment['merKey'];

		$return = array(
			'version' => $version,
			'charSet' => $charset,
			'transType' => $trans_type,
			'orderNum' => $order_num,
			'orderAmount' => $order_amount,
			'orderCurrency' => $order_currency,
			'merReserve' => $mer_reserve,
			'frontURL' => $this->callbackUrl,
			'backURL' => $this->serverCallbackUrl,
			'merID' => $merchant_id,
			'acqID' => $acq_id,
			'paymentSchema' => $payment_schema,
			'transTime' => $trans_time,
			'signType' => $sign_type,
			'code'     => $code,
		);

		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($return);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildMysign($para_sort, $payment['merKey']);

		//签名结果与签名方式加入请求提交参数组中
		$return['signature'] = $mysign;
		return $return;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function paraFilter($para)
	{
		$para_filter = array();
		foreach($para as $key => $val)
		{
			if($key != "signature")
			{
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * 对数组排序
	 * @param $para 排序前的数组
	 * return 排序后的数组
	 */
	private function argSort($para)
	{
		ksort($para);
		reset($para);
		return $para;
	}

	/**
	 * 生成签名结果
	 * @param $sort_para 要签名的数组
	 * @param $key 支付宝交易安全校验码
	 * @param $sign_type 签名类型 默认值：MD5
	 * return 签名结果字符串
	 */
	private function buildMysign($sort_para,$key,$sign_type = "MD5")
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($sort_para);
		//把拼接后的字符串再与安全校验码直接连接起来
		$prestr = $prestr.$key;
		//把最终的字符串签名，获得签名结果
		$mysgin = md5($prestr);
		return $mysgin;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	private function createLinkstring($para)
	{
		$arg  = "";
		foreach($para as $key => $val)
		{
			$arg.=$key."=".$val."&";
		}

		//去掉最后一个&字符
		$arg = trim($arg,'&');

		//如果存在转义字符，那么去掉转义
		if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
		{
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	/**
	 * @param 获取配置参数
	 */
	public function configParam()
	{
		$result = array(
			'merID'  => '商户MerchantID',
			'merKey' => '商户KEY',
			'acqID'  => '商户AcquireID',
		);
		return $result;
	}
}