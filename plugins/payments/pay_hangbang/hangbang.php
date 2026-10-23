<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file hangbang.php
 * @brief 航邦支付接口
 * @author nswe
 * @date 2026-05-27
 * @version 5.16
 * @note
 */

 /**
 * @class hangbang
 * @brief 航邦支付接口
 */
class hangbang extends paymentPlugin
{
	//插件名称
    public $name = '航邦支付';

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl()
	{
	    //https://mamipay.com
		return 'https://pay.erpyt.com';
	}

	/**
	 * @see paymentplugin::doRefund()
	 */
	public function doRefund($payment)
	{
		$url = $this->getSubmitUrl() . '/api-order-receipt/order/api/refund';

        //交易号
        $trade_no = $payment['M_TransactionId'];

        //需要退款的金额，该金额不能大于订单金额，必填
        $refund_amount = $payment['M_Refundfee'];

        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        $out_request_no = $payment['M_RefundNo'];

		$data = [
			'idNo' => $payment['idNo'],
			'merchantCode' => $payment['merchantCode'],
			'orderNo' => $trade_no,//聚合平台订单号
			'payEntrance' => 2,
			'price' => $refund_amount,
			'outNotifyUrl' => $this->serverCallbackUrl,
		];

		$sign = $this->openssl_sign_create(json_encode($data,64|256),$payment['private_key']);
		$jsonParams = [
			"data" => json_encode($data,64|256),
			"sign" => $sign
		];
		$hbPblicKey  = $this->getHBPublicKey();
		$encryptBody = $this->public_encrypt(json_encode($jsonParams,64|256),$hbPblicKey);
		$s = "param=".rawurlencode($encryptBody);

		$result = $this->request_curl($url,$s,$error);
		if($error)
		{
			file_put_contents('hangbang_doRefund.log',var_export($error,true) . PHP_EOL , FILE_APPEND);
			return $error;
		}

		//解密data数据
		$paramJsonStr = $this->private_decrypt($result['data'],$payment['private_key']);
        file_put_contents('hangbang_doRefund.log', "时间：" . date('Y-m-d H:i:s') . PHP_EOL . "返回值(密文)：" . var_export($result,true) . PHP_EOL . "返回值(data明文):" . var_export($paramJsonStr,true) . PHP_EOL , FILE_APPEND);

		return ($result && $result['code'] == '0000') ? true : false;
	}

	/**
	 * @see paymentplugin::notifyStop()
	 */
	public function notifyStop()
	{
		echo '{"code":"200","res":"SUCCESS"}';
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
    {"reason":null,"splitState":"0","payTime":"2026-07-01 12:25:27","openId":"oM3oK0U_9RkPTMQ69IziEfIeMwwY","payWay":"1","enStr":"XUbLur1wdJIM/FogjKiEQg+/1e41t2RE8OkuuXALbpHyu9fLeqA3pCr98E9R7B6BrJwv4Z2kLkYYACn6yEWhby8cTDspLIZzMH8wmdCNGCxC8WHM+1Ue/V/YPvMj1u7yOxS+hrDHkN3Ji+3IA33TbZnxW3eGXMRCqEgpSccXBaQ=","signStr":"iWqjrKBGR+Uud7Fixfp01/97HHlgECmnoodSYOXfvDILVclgFQBMgJS2UWRdIrCwB1XAImI9vre5tiv1xzwlLwqWRqGeuSAYxdw7U9mMZ/B/ZYR4AidwUnvDfomCELBqHGfxGah/1wRp1nd91o1r36kMN1gAh0pdBF2M6hFLp7A=","merchantName":"小刀电动车","price":"0.02","outTradeNo":"202607011222482357","id":"2072174072128036866","attach":null,"tradeType":"0","attempts":"1","merchantCode":"HB_M202408060006","orderNo":"HB2026070112225200000007","cardType":"2","message":"OK.","tradeState":"2","transactionId":"4200003177202607013937097043","totalAmount":"0.02","merchantRate":"0.0028","payChannel":"9","instantRate":"0.0055","remarks":null}
	 */
	public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
	    $ciphertext = file_get_contents("php://input");
        file_put_contents('hangbang_serverCallback.log', "时间：" . date('Y-m-d H:i:s') . PHP_EOL ."返回数据：". $ciphertext . PHP_EOL , FILE_APPEND);

        $callbackData = JSON::decode($ciphertext);
		$config = Payment::getConfigParam($paymentId);

		switch($callbackData['tradeType'])
		{
		    //退款单
            case "1":
            {

            }
            break;

            //支付单
            default:
            {
                //验证成功
        		$verify_result = true;
        		if($verify_result)
        		{
        			//商户订单号
        			$orderNo = $callbackData['outTradeNo'];

        			//三方支付交易号
        			$trade_no = $callbackData['orderNo'];

        			//交易状态
        			$trade_status = $callbackData['tradeState'];

        			//支付金额
        			$money = $callbackData['price'];

        			if($trade_status == '2')
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
	}

	/**
	 * @see paymentplugin::getSendData()
	 */
	public function getSendData($payment)
	{
		$dotmore = [
			'title'   => $payment['M_GoodsName'],
			'content' => $payment['M_GoodsName'],
		];

		$data = [
			'idNo'         => $payment['idNo'],
			'merchantCode' => $payment['merchantCode'],
			'outTradeNo'   => $payment['M_OrderNO'],
			'price'        => $payment['M_Amount'],
			'payWay'       => $payment['payWay'], //1微信 2支付宝 3云闪付
			'outNotifyUrl' => $this->serverCallbackUrl,
			'dotmoreContent' => json_encode($dotmore,64|256),
		];

		//微信支付
		if($payment['payWay'] == 1)
		{
		    //1微信公众号 2微信小程序 3支付宝生活号 4银联
		    if(IClient::isWechat())
		    {
		        $data['payType'] = 1;
            }
            else
            {
                $data['payType'] = 2;
            }
		}

		$sign = $this->openssl_sign_create(json_encode($data,64|256),$payment['private_key']);
		$jsonParams = [
			"data" => json_encode($data,64|256),
			"sign" => $sign,
		];
		$hbPblicKey  = $this->getHBPublicKey();
		$encryptBody = $this->public_encrypt(json_encode($jsonParams,64|256),$hbPblicKey);
		$s = "param=".rawurlencode($encryptBody);

		$url = $this->getSubmitUrl() . '/api-order-receipt/order/api/blankPayUrl';
		$result = $this->request_curl($url,$s,$error);

		if($error)
		{
            file_put_contents('hangbang_getSendData.log', "时间：" . date('Y-m-d H:i:s') . PHP_EOL . "提交地址：" . var_export($url,true) . PHP_EOL . "提交数据(明文)：" . var_export($data,true) . PHP_EOL . "提交数据(密文):" . var_export($s,true) . PHP_EOL . "返回值：" . var_export($error,true) . PHP_EOL , FILE_APPEND);

			throw new IException(__CLASS__." getSendData:".var_export($error,true));
		}
		else
		{
			$paramJsonStr = $this->private_decrypt($result['data'],$payment['private_key']);

            file_put_contents('hangbang_getSendData.log', "时间：" . date('Y-m-d H:i:s') . PHP_EOL . "提交地址：" . var_export($url,true) . PHP_EOL . "提交数据(明文)：" . var_export($data,true) . PHP_EOL . "提交数据(密文):" . var_export($s,true) . PHP_EOL . "返回值(密文)：" . var_export($result,true) . PHP_EOL . "返回值(data明文):" . var_export($paramJsonStr,true) . PHP_EOL , FILE_APPEND);

			return JSON::decode($paramJsonStr);
		}
	}

	/**
	 * @see paymentplugin::doPay()
	 */
	public function doPay($sendData)
	{
		if($sendData && isset($sendData['redirectUrl']))
		{
			header('location: '.$sendData['redirectUrl']);
		}
	}

	//获取公钥内容
	function get_public_key($public_key,$_input_charset = 'UTF-8')
	{
		if(empty($public_key)){
			return false;
		}

		if(!is_file($public_key)){
			$public_key = "-----BEGIN PUBLIC KEY-----\n".chunk_split($public_key, 64, "\n")."-----END PUBLIC KEY-----\n";
			$public_key = mb_convert_encoding($public_key,$_input_charset);
		}else{
			$public_key = file_get_contents($public_key);
		}
		$pu_key = openssl_pkey_get_public($public_key);

		return $pu_key;
	}

	//公钥对称加密
	function public_encrypt($str,$public_key = '')
	{
		if(empty($str))
			return null;

		if($pu_key = $this->get_public_key($public_key))
		{
			$crypto = '';
			foreach (str_split($str, 117) as $chunk) {
				if(openssl_public_encrypt($chunk, $encrypted, $pu_key)){
					$crypto .= $encrypted;
				}else{
					return null;
				}
			}
			return base64_encode($crypto);
		}
		return null;
	}

	//获取私钥内容
	function get_private_key($private_key,$_input_charset = 'UTF-8')
	{
		if(empty($private_key))
			return null;
		if(!is_file($private_key)){
			$private_key = chunk_split($private_key, 64, "\n");;
			$priv_key = "-----BEGIN RSA PRIVATE KEY-----\n$private_key-----END RSA PRIVATE KEY-----\n";
		}else{
			$priv_key = file_get_contents($private_key);
		}
		$priv_key = openssl_pkey_get_private($priv_key);
		return $priv_key;
	}

	//私钥对称解密
	function private_decrypt($crypt_data,$private_key = '')
	{
		if(empty($crypt_data))
			return false;
		$crypt_data = str_replace(' ','+',$crypt_data);
		if($pkeyid = $this->get_private_key($private_key))
		{
			$crypto = '';
			foreach (str_split(base64_decode($crypt_data), 128) as $chunk)
			{
				if(openssl_private_decrypt($chunk, $decrypted, $pkeyid))
				{
					$crypto .= $decrypted;
				}
				else
				{
					echo openssl_error_string();
					return null;
				}
			}

			return $crypto;
		}
		return null;
	}

	//签名
	function openssl_sign_create($params_str,$private_key = '',$signature_alg = OPENSSL_ALGO_SHA1)
	{
		if($pkeyid = $this->get_private_key($private_key))
		{
			$status = openssl_sign ( $params_str, $signMsg, $pkeyid, $signature_alg );
			openssl_free_key ( $pkeyid );
			if($status)
			{
				$signMsg = base64_encode ( $signMsg );
				return $signMsg;
			}
			return null;
		}
		return null;
	}

	//验签
	function openssl_sign_verify($params_str,$sign,$public_key = '',$signature_alg = OPENSSL_ALGO_SHA1)
	{
		if($pu_key = $this->get_public_key($public_key))
		{
			$status = openssl_verify ( $params_str, base64_decode ($sign), $pu_key, $signature_alg );
			!empty($pubkeyid) ? openssl_free_key ( $pubkeyid ) : false;
			$status = $status == 1 ? true : false;
			return $status;
		}
		return null;
	}

	//http请求工具
	function request_curl($url, $params = null, &$error_message = '')
	{
		$curl = curl_init($url);

		// 连接建立最长耗时
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

		//超时时间
		curl_setopt($curl, CURLOPT_TIMEOUT,60);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_POST, true); // post传输数据
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params); // post传输数据

		$response = curl_exec($curl);

        file_put_contents('hangbang_request.log', "时间：" . date('Y-m-d H:i:s') . PHP_EOL . "提交地址：" . var_export($url,true) . PHP_EOL . "提交数据：" . var_export($params,true) . PHP_EOL . "返回值：" . var_export($response,true) . PHP_EOL , FILE_APPEND);

		if($error = curl_errno($curl))
		{
			$error_message = curl_strerror($error);
			curl_close($curl);
			return false;
		}

		$resultData = json_decode($response,true);
		if($resultData['code'] == "0000")
		{
			curl_close($curl);
			return $resultData;
		}
		else
		{
			$error_message = $resultData['msg'];
			curl_close($curl);
			return false;
		}
	}

	//系统公钥
	function getHBPublicKey()
	{
		$cache  = new ICache('file');
		$data = $cache->get('hangbangPlantPub');
		if($data)
		{
			return $data;
		}

		$url = $this->getSubmitUrl() . '/api-order-receipt/order/api/get_public_key';

		$s   = "";
		$res = $this->request_curl($url,$s,$error_message);

		if($res && $res['data'])
		{
			$cache->set('hangbangPlantPub', $res['data']);
		}
		return $res['data'];
	}

	/**
	 * @param 获取配置参数
	 */
	public function configParam()
	{
		$result = [
			'idNo'        => '厂商识别号',
			'agentCode'   => '代理商标识',
			'merchantCode'=> '商户标识',
			'private_key' => '商户私钥',
			'payWay'      => '1微信 2支付宝 3云闪付',
		];
		return $result;
	}
}
