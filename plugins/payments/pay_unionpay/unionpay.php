<?php
/**
 * @copyright Copyright(c) 2015 www.aircheng.com
 * @file  unionpay.php
 * @brief 中国银联支付接口
 * @author dabao
 * @date 2018/5/20 13:41:57
 * @version 5.1
 */

 /**
 * @class unionpay
 * @brief 中国银联支付接口
 */
include_once(dirname(__FILE__)."/../common/unionpayBase.php");
class unionpay extends paymentPlugin
{
    public $name = '中国银联';//插件名称

	//构造函数
    public function __construct($payment_id)
    {
    	parent::__construct($payment_id);

    	//签名证书(私钥)路径
    	defined('SDK_SIGN_CERT_PATH') or define("SDK_SIGN_CERT_PATH",dirname(__FILE__)."/key/700000000000001_acp.pfx");

    	//验签证书(公钥)路径
    	defined('SDK_ENCRYPT_CERT_PATH') or define("SDK_ENCRYPT_CERT_PATH",dirname(__FILE__)."/key/verify_sign_acp.cer");
    }

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl()
	{
		//return 'https://101.231.204.80:5000/gateway/api/frontTransReq.do'; //测试环境请求地址
		return 'https://gateway.95516.com/gateway/api/frontTransReq.do'; //生产环境
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
		if (isset($callbackData['signature']))
		{
			if (unionpayBase::verify($callbackData))
			{
				if($callbackData['respCode'] == "00")
				{
					$orderNo = $callbackData['orderId'];//订单号
					$money   = $callbackData['txnAmt']/100;

					//记录回执流水号
					if(isset($callbackData['queryId']) && $callbackData['queryId'])
					{
						$this->recordTradeNo($orderNo,$callbackData['queryId']);
					}
					return true;
				}
				$message = '状态码不正确:'.$callbackData['respCode'];
			}
			else
			{
				$message = '签名不正确';
			}
		}
		else
		{
			$message = '签名为空';
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
		$return = array(
			'version' => '5.0.0',//版本号
			'encoding' => 'utf-8',//编码方式
			'certId' => unionpayBase::getSignCertId($payment['M_certPwd']),//证书ID
			'txnType' => '01',//交易类型
			'txnSubType' => '01',//交易子类 01消费
			'bizType' => '000201',//业务类型
			'frontUrl' =>  $this->callbackUrl,//前台通知地址
			'backUrl' => $this->serverCallbackUrl,//后台通知地址
			'signMethod' => '01',//签名方法
			'channelType' => '07',//渠道类型，07-PC，08-手机
			'accessType' => '0',//接入类型
			'merId' => $payment['M_merId'],//商户代码，请改自己的测试商户号
			'currencyCode' => '156',//交易币种
			'defaultPayType' => '0001',//默认支付方式
			'txnTime' => date('YmdHis'),//订单发送时间
		);

		$return['orderId']     = $payment['M_OrderNO'];	//商户订单号
		$return['txnAmt']      = $payment['M_Amount']*100;//交易金额，单位分
		$return['reqReserved'] = $payment['M_OrderId'];	//订单发送时间'透传信息'; //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
		// 签名
		unionpayBase::sign($return);
        return $return;
	}

	/**
	 * @param 获取配置参数
	 */
	public function configParam()
	{
		$result = array(
			'M_merId'  => '商户号（merId）',
			'M_certPwd' => '签名密码(certPwd)',
		);
		return $result;
	}

	/**
	 * @brief 执行退款接口
	 * @param array $payment 退款信息接口
	 */
	public function doRefund($payment)
	{
        $return = array(
            'version'     => '5.0.0',//版本号
            'encoding'    => 'utf-8',//编码方式
            'signMethod'  => '01',//签名方法
            'txnType'     => '04',//交易类型
            'txnSubType'  => '00',//交易子类 01消费
            'bizType'     => '000201',//业务类型
            'accessType'  => '0',//接入类型
            'channelType' => '07',//渠道类型，07-PC，08-手机
            'backUrl'     => $this->refundCallbackUrl,//后台通知地址
            'orderId'     => $payment['M_RefundNo'],//商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生，不同于原消费，此处默认取demo演示页面传递的参数
            'merId'       => $payment['M_merId'],//商户代码，请改自己的测试商户号
            'origQryId'   => $payment['M_TransactionId'],//原消费的queryId，可以从查询接口或者通知接口中获取，
            'txnTime'     => date('YmdHis'),//订单发送时间
            'txnAmt'      => $payment['M_Refundfee']*100,//退款金额
            'certId'      => unionpayBase::getSignCertId($payment['M_certPwd']),//证书ID
        );
		//签名
		unionpayBase::sign($return);
		$resultString = $this->curlPost($return);
		parse_str($resultString,$resultArray);

        if(isset($resultArray['respCode']) && $resultArray['respCode'] == "00")
        {
            if(isset($resultArray['queryId']) && $resultArray['queryId'])
            {
                $this->recordRefundTradeNo($payment['M_RefundId'],$resultArray['queryId']);
            }
            return true;
        }
        else
        {
            return isset($resultArray['respMsg']) ? $resultArray['respMsg'] : "退款接口发生错误";
        }
	}

	/**
	 * @brief 发送post请求
	 * @param array $return 发送的数据
	 */
	public function curlPost($return)
	{
		//测试环境
		//$post_url = "https://gateway.test.95516.com/gateway/api/backTransReq.do";
		//生产环境
		$post_url = "https://gateway.95516.com/gateway/api/backTransReq.do";
        $ch       = curl_init();
        $sendData = unionpayBase::coverParamsToString($return,true,true);

        //设置基础设置
        curl_setopt($ch,CURLOPT_URL, $post_url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch,CURLOPT_SSLVERSION, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array (
            'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
        ) );
        //设置header
        curl_setopt($ch,CURLOPT_HEADER, FALSE);

        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

        //post提交方式
        curl_setopt($ch,CURLOPT_POST, TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $sendData);
        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_error($ch);
            curl_close($ch);
            die("curl出错，错误信息:".$error);
        }
	}
}