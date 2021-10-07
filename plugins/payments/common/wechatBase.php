<?php
/**
 * @class wechatBase
 * @brief 微信支付基类
 * @date 2018/2/27 7:38:38
 */
abstract class wechatBase extends paymentPlugin
{
	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl()
	{
		return 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	}

	/**
	 * @see paymentplugin::notifyStop()
	 */
	public function notifyStop()
	{
		die("<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>");
	}

	/**
	 * @see paymentplugin::callback()
	 */
	public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo){}

	/**
	 * @see paymentplugin::serverCallback()
	 */
	public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
		$postXML      = file_get_contents("php://input");
		$callbackData = $this->converArray($postXML);

		if(isset($callbackData['return_code']) && $callbackData['return_code'] == 'SUCCESS')
		{
			//除去待签名参数数组中的空值和签名参数
			$para_filter = $this->paraFilter($callbackData);

			//对待签名参数数组排序
			$para_sort = $this->argSort($para_filter);

			//生成签名结果
			$mysign = $this->buildMysign($para_sort,Payment::getConfigParam($paymentId,'key'));

			//验证签名
			if($mysign == $callbackData['sign'])
			{
				if($callbackData['result_code'] == 'SUCCESS')
				{
					$orderNo = strstr($callbackData['out_trade_no'],"_",true);
					$orderNo = $orderNo ? $orderNo : $callbackData['out_trade_no'];
					$money   = $callbackData['total_fee']/100;

					//记录回执流水号
					if(isset($callbackData['transaction_id']) && $callbackData['transaction_id'])
					{
						$this->recordTradeNo($orderNo,$callbackData['transaction_id']);
					}
					return true;
				}
				else
				{
					$message = $callbackData['err_code_des'];
				}
			}
			else
			{
				$message = '签名不匹配';
			}
		}

		$message = $message ? $message : $callbackData['message'];
		return false;
	}

	/**
	 * @brief 提交数据
	 * @param xml $xmlData 要发送的xml数据
	 * @return xml 返回数据
	 */
	protected function curlSubmit($xmlData)
	{
		//接收xml数据的文件
		$url = $this->getSubmitUrl();

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$response = curl_exec($ch);
		if(!$response)
		{
			$errorMsg = curl_error($ch);
			$errorMsg = $errorMsg ? $errorMsg : "CURL异常出错";
			die($errorMsg);
		}
		curl_close($ch);
		return $response;
	}

	/**
	 * @brief 从array到xml转换数据格式
	 * @param array $arrayData
	 * @return xml
	 */
	protected function converXML($arrayData)
	{
		$xml = '<xml>';
		foreach($arrayData as $key => $val)
		{
			$xml .= '<'.$key.'><![CDATA['.$val.']]></'.$key.'>';
		}
		$xml .= '</xml>';
		return $xml;
	}

	/**
	 * @brief 从xml到array转换数据格式
	 * @param xml $xmlData
	 * @return array
	 */
	protected function converArray($xmlData)
	{
		$result = array();
		$xmlHandle = xml_parser_create();
		xml_parse_into_struct($xmlHandle, $xmlData, $resultArray);

		foreach($resultArray as $key => $val)
		{
			if($val['tag'] != 'XML' && isset($val['value']))
			{
				$result[$val['tag']] = $val['value'];
			}
		}
		return array_change_key_case($result);
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	protected function paraFilter($para)
	{
		$para_filter = array();
		foreach($para as $key => $val)
		{
			if($key == "sign" || $key == "sign_type" || $val == "")
			{
				continue;
			}
			else
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
	protected function argSort($para)
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
	protected function buildMysign($sort_para,$key,$sign_type = "MD5")
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($sort_para);
		//把拼接后的字符串再与安全校验码直接连接起来
		$prestr = $prestr.'&key='.$key;
		//把最终的字符串签名，获得签名结果
		$mysgin = md5($prestr);
		return strtoupper($mysgin);
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	protected function createLinkstring($para)
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
	 * @brief 执行退款接口
	 * @param array $payment 退款信息接口
	 */
	public function doRefund($payment)
	{
		$return = array();

        //基本参数
        $return['appid']          = $payment['appid'];
        $return['mch_id']         = $payment['mch_id'];
        $return['nonce_str']      = rand(100000,999999);
        $return['transaction_id'] = $payment['M_TransactionId'];
        $return['out_refund_no']  = $payment['M_RefundNo'];
        $return['total_fee']      = $payment['M_Amount']*100;
        $return['refund_fee']     = $payment['M_Refundfee']*100;

        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($return);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        //生成签名结果
        $mysign = $this->buildMysign($para_sort, $payment['key']);


        //签名结果与签名方式加入请求提交参数组中
        $return['sign'] = $mysign;

        $xmlData     = $this->converXML($return);
        $url         = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $response    = self::postXmlCurl($xmlData, $url);
        $resultArray = $this->converArray($response);
        if($resultArray['return_code'] == "SUCCESS")
        {
        	if(isset($resultArray['err_code_des']) && $resultArray['err_code_des'])
        	{
        		return $resultArray['err_code_des'];
        	}
        	else
        	{
	            if(isset($resultArray['refund_id']) && $resultArray['refund_id'])
	            {
	                $this->recordRefundTradeNo($payment['M_RefundId'],$resultArray['refund_id']);
	            }
	            return true;
        	}
        }
        else
        {
            return $resultArray['return_msg'];
        }
	}

	//发送可加密的xml的post请求
	protected function postXMLCurl($xml, $url)
    {
		$SSLCERT_PATH = dirname(__FILE__).'/key/apiclient_cert.pem';
		$SSLKEY_PATH  = dirname(__FILE__).'/key/apiclient_key.pem';

        $ch = curl_init();

        //设置基础设置
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, $SSLCERT_PATH);
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, $SSLKEY_PATH);

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
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
            if(stripos($error,'not found'))
            {
                $error .= "请拷贝微信退款证书在此目录下";
            }
            die("curl出错，错误信息:".$error);
        }
    }
}