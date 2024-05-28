<?php
/**
 * @class wechatBase
 * @brief 微信支付基类
 * 微信证书分为：商户证书，微信平台证书(通过API接口获取)
	什么是商户证书？什么是平台证书？
	商户证书 是指由商户申请的，包含商户的商户号、公司名称、公钥信息的证书。
	平台证书 是指由微信支付负责申请的，包含微信支付平台标识、公钥信息的证书。
 * @date 2018/2/27 7:38:38
 * @date 2022/09/10 07:45:00 更新微信v3接口
 */
abstract class wechatBase extends paymentPlugin
{
	//全局url提交地址前缀
	const APIURL = 'https://api.mch.weixin.qq.com';

	//获取商户私钥
	protected function getMchidPrikey()
	{
		$SSLKEY_PATH  = dirname(__FILE__).'/key/apiclient_key.pem';
		$mch_private_key = file_get_contents($SSLKEY_PATH);
		return $mch_private_key;
	}

	//获取商户公钥
	protected function getMchidPubkey()
	{
		$SSLCERT_PATH = dirname(__FILE__).'/key/apiclient_cert.pem';
		$mch_public_key = file_get_contents($SSLKEY_PATH);
		return $mch_public_key;
	}

	/**
	 * @brief 获取微信平台公钥
	 * 由cache保存主要是json格式： wechatPlantPub => ['expire_time' => 有效期截至 ,'serial_no' => 证书序列号, 'content' => 证书内容]
	 * @return array
	 */
	protected function getPlatPubkey()
	{
		$cache    = new ICache('file');
		$pubJson  = $cache->get('wechatPlantPub');
		$pubArray = JSON::decode($pubJson);
		if($pubArray)
		{
			return $pubArray;
		}
		else
		{
			return $this->certificates();
		}
	}

	/**
	 * @brief 获取平台证书通过接口
	 */
	public function certificates()
	{
		$url = '/v3/certificates';
		$cipherArray = $this->curlSubmit($url,[],[],'GET');

		if(!$cipherArray || !isset($cipherArray['data']) || !$cipherArray['data'])
		{
			throw new IException("异步支付回调密文数据有问题:".var_export($cipherArray,true));
		}

		$cache  = new ICache('file');

		$config = $this->config();
		$key    = $config['key'];
		foreach($cipherArray['data'] as $item)
		{
			//有效期
			$expire_time = $item['expire_time'];

			//证书编号
			$serial_no   = $item['serial_no'];

			//证书内容
			$resource    = $item['encrypt_certificate'];
			$AesUtilObj  = new AesUtil($key);
			$pem = $AesUtilObj->decryptToString($resource['associated_data'], $resource['nonce'], $resource['ciphertext']);

			$pubKey = ['expire_time' => $expire_time,'serial_no' => $serial_no,'content' => $pem];
			$cache->set('wechatPlantPub', JSON::encode($pubKey));
			break;
		}

		return $pubKey;
	}

	/**
	 * @brief 签名
	 * @param $messageArray 待签名数组，按照次序排列好
	 * 签名串 每一行为一个参数。行尾以 \n（换行符，ASCII编码值为0x0A）结束，包括最后一行。
	 * 如果参数本身以\n结束，也需要附加一个\n。
	 */
	protected function sign($messageArray)
	{
		openssl_sign(join("\n",$messageArray)."\n", $sign, $this->getMchidPrikey(), 'sha256WithRSAEncryption');
		return base64_encode($sign);
	}

	/**
	 * 私密性数据加密
	 * @note 用平台公钥进行加密(到了微信端后微信用私钥进行解密)
	 */
	protected function encode($str)
	{
		$pubArray = $this->getPlatPubkey();
		$plan_public_key = $pubArray['content'];
		$encrypted = '';
		if(openssl_public_encrypt($str, $encrypted, $plan_public_key, OPENSSL_PKCS1_OAEP_PADDING))
		{
			//base64编码
			$sign = base64_encode($encrypted);
			return $sign;
		}
		else
		{
			throw new Exception('encrypt failed');
		}
	}

	/**
	 * 私密性数据解密
	 * @note 用商户私钥进行加密(到了微信端后微信用商户公钥进行解密)
	 */
	protected function decode($encrypted)
	{
		$mch_pri_key = $this->getMchidPrikey();
		$str = '';
		if(openssl_private_decrypt(base64_decode($encrypted),$str,$mch_pri_key,OPENSSL_PKCS1_OAEP_PADDING))
		{
			return $str;
		}
		else
		{
			throw new Exception('decrypt failed');
		}
		return $str;
	}

	/**
	 * @see paymentplugin::notifyStop()
	 */
	public function notifyStop()
	{
		http_response_code(200);
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
		$ciphertext = file_get_contents("php://input");
		$cipherArray= JSON::decode($ciphertext);

		if(!$cipherArray || !isset($cipherArray['resource']))
		{
			throw new IException("异步支付回调密文数据有问题:".$ciphertext);
		}

		$key = Payment::getConfigParam($paymentId,'key');
		$AesUtilObj = new AesUtil($key);
		$json = $AesUtilObj->decryptToString($cipherArray['resource']['associated_data'], $cipherArray['resource']['nonce'], $cipherArray['resource']['ciphertext']);
		$result = JSON::decode($json);

		//日志记录
	    $logObj = new IFileLog('wechat_server_callback/'.date('Y-m-d').'.log');
	    $logObj->write(['支付接口ID' => $paymentId,'原始数据' => $ciphertext,'解密数据' => $json]);

		if(!$result)
		{
			throw new IException("异步支付回调解密json出现问题:".$json);
		}

		//合单支付情况
		if(isset($result['sub_orders']) && $result['sub_orders'])
		{
			$orderNo = strstr($result['combine_out_trade_no'],"_",true);
			$orderNo = $orderNo ? $orderNo : $result['combine_out_trade_no'];
			$money   = 0;

			foreach($result['sub_orders'] as $item)
			{
				if(isset($item['trade_state']) && $item['trade_state'] == 'SUCCESS')
				{
					$money += $item['amount']['total_amount'];
				}

				//记录回执流水号
				if(isset($item['transaction_id']) && $item['transaction_id'])
				{
					$orderDB = new IModel('order');
					$orderDB->setData(['trade_no' => $item['transaction_id']]);
					$orderDB->update('order_no = "'.$item['out_trade_no'].'"');
				}
			}
			$money = $money/100;
			return true;
		}
		//普通支付情况
		else if(isset($result['trade_state']) && $result['trade_state'] == 'SUCCESS')
		{
			$orderNo = strstr($result['out_trade_no'],"_",true);
			$orderNo = $orderNo ? $orderNo : $result['out_trade_no'];
			$money   = $result['amount']['total']/100;

			//记录回执流水号
			if(isset($result['transaction_id']) && $result['transaction_id'])
			{
				$this->recordTradeNo($orderNo,$result['transaction_id']);
			}
			return true;
		}
		return false;
	}

	/**
	 * @brief 提交数据
	 * @param string $url 短ULR
	 * @param array $data 要发送的数据
	 * @param array $headerAppend 发送header信息
	 * @param string $method 提交方式
	 * @return json 返回数据
	 */
	protected function curlSubmit($url, $data = [], $headerAppend = [], $method = 'POST')
	{
		$json      = $data ? JSON::encode($data) : '';
		$config    = $this->config();

		$mchid     = $config['mch_id'];
		$serial_no = $config['serial_no'];
		$time      = time();
		$nonce_str = rand(100000,999999);

		//发起请求 所有请求都要带 authorization 参数，属于标准
		$signature = $this->sign([$method,$url,$time,$nonce_str,$json]);
		$authorization = 'WECHATPAY2-SHA256-RSA2048 mchid="'.$mchid.'",nonce_str="'.$nonce_str.'",signature="'.$signature.'",timestamp="'.$time.'",serial_no="'.$serial_no.'"';
		$header = ['User-Agent: iWebShop', 'Accept: application/json', 'Content-Type: application/json', 'Authorization: '.$authorization];
		$header = array_merge($header,$headerAppend);

		//发起请求
		$ch = curl_init(self::APIURL.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		if($method == 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		}

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);

		$response = curl_exec($ch);
		if(!$response)
		{
			$errorMsg = curl_error($ch);
			$errorMsg = $errorMsg ? $errorMsg : "CURL异常出错";
			die($errorMsg);
		}
		return JSON::decode($response);
	}

	/**
	 * @brief 执行退款接口
	 * @param array $payment 退款信息接口
	 */
	public function doRefund($payment)
	{
		$this->certificates();
		$config = $this->config();
		$key    = $config['key'];
		$url    = '/v3/refund/domestic/refunds';

		$data = [];

        //基本参数
		$data['transaction_id'] = $payment['M_TransactionId'];
		$data['out_refund_no']  = $payment['M_RefundNo'];
		$data['amount']         = ['refund' => $payment['M_Refundfee']*100, 'total' => $payment['M_Amount']*100, 'currency' => 'CNY'];
		if(isset($payment['M_REASON']) && $payment['M_REASON'])
		{
			$data['reason'] = $payment['M_REASON'];
		}

        $result = $this->curlSubmit($url,$data);
        if(is_array($result) && $result)
        {
			if(isset($result['status']) && in_array($result['status'],['PROCESSING','SUCCESS']))
			{
				if(isset($result['refund_id']) && $result['refund_id'])
				{
					$this->recordRefundTradeNo($payment['M_RefundId'],$result['refund_id']);
				}
				return true;
			}

			if(isset($result) && $result['message'])
			{
				return $result['message'];
			}
        }
		return $result;
	}

	/**
	 * @brief 执行退款接口
	 * @param array $payment 退款信息接口
	 */
	public function doCombineRefund($payment)
	{
		$this->certificates();
		$config = $this->config();
		$key    = $config['key'];
		$url    = '/v3/ecommerce/refunds/apply';

		$data = [];

        //基本参数
		$mchid    = $config['mch_id'];
		$sellerDB = new IModel('seller');
		$orderDB  = new IModel('order');
		$orderRow = $orderDB->getObj('order_no = "'.$payment['M_OrderNO'].'"','seller_id');
		if(!$orderRow)
		{
			return '退款的订单信息不存在';
		}

		if($orderRow['seller_id'] > 0)
		{
			$sellerRow= $sellerDB->getObj($orderRow['seller_id'],'wechat_mchid,true_name');
			if($sellerRow)
			{
				if($sellerRow['wechat_mchid'])
				{
					$mchid = $sellerRow['wechat_mchid'];
				}
				else
				{
					return '退款的商户 '.$sellerRow['true_name'].'没有设置微信商户号';
				}
			}
		}

		$data['sub_mchid']      = $mchid;
		$data['sp_appid']       = $config['appid'];
		$data['transaction_id'] = $payment['M_TransactionId'];
		$data['out_refund_no']  = $payment['M_RefundNo'];
		$data['amount']         = ['refund' => $payment['M_Refundfee']*100, 'total' => $payment['M_Amount']*100, 'currency' => 'CNY'];
		if(isset($payment['M_REASON']) && $payment['M_REASON'])
		{
			$data['reason'] = $payment['M_REASON'];
		}

        $result = $this->curlSubmit($url,$data);
        if(is_array($result) && $result)
        {
			if(isset($result['refund_id']) && $result['refund_id'])
			{
				$this->recordRefundTradeNo($payment['M_RefundId'],$result['refund_id']);
				return true;
			}

			if(isset($result) && $result['message'])
			{
				return $result['message'];
			}
        }
		return $result;
	}

	//获取支付接口的配置参数信息
	public function config()
	{
		if(!$this->paymentId)
		{
			$className = get_called_class();
			$paymentDB = new IModel('payment');
			$paymentRow= $paymentDB->getObj('class_name = "'.$className.'" and status = 0 and type = 1','id');
			if($paymentRow)
			{
				$this->paymentId = $paymentRow['id'];
			}
			else
			{
				return null;
			}
		}
		return payment::getConfigParam($this->paymentId);
	}

	//上传文件
	public function uploadFile($filename)
	{
		$url = '/v3/merchant/media/upload';
		$binContent = file_get_contents($filename);//二进制图片内容
		$meta = [
			'filename' => basename($filename),
			'sha256'   => hash_file('sha256', $filename),
		];

		$config    = $this->config();
		$mchid     = $config['mch_id'];
		$serial_no = $config['serial_no'];
		$time      = time();
		$nonce_str = rand(100000,999999);
		$boundary  = uniqid();
		$mime_type = "image/".pathinfo($filename,PATHINFO_EXTENSION);

		//发起请求 所有请求都要带 authorization 参数，属于标准
		$signature = $this->sign(["POST",$url,$time,$nonce_str,json_encode($meta)]);
		$authorization = 'WECHATPAY2-SHA256-RSA2048 mchid="'.$mchid.'",nonce_str="'.$nonce_str.'",signature="'.$signature.'",timestamp="'.$time.'",serial_no="'.$serial_no.'"';
		$header = ['User-Agent: iWebShop', 'Accept: application/json', 'Content-Type: multipart/form-data;boundary='.$boundary, 'Authorization: '.$authorization];

		$body = "--{$boundary}\r\n";
		$body.= 'Content-Disposition: form-data; name="meta"'."\r\n";
		$body.= 'Content-Type: application/json'."\r\n";
		$body.= "\r\n";
		$body.= json_encode($meta)."\r\n";
		$body.= "--{$boundary}\r\n";
		$body.= 'Content-Disposition: form-data; name="file"; filename="'.$meta['filename'].'"'."\r\n";
		$body.= 'Content-Type: '.$mime_type.';'."\r\n";
		$body.= "\r\n";
		$body.= $binContent."\r\n";
		$body.= "--{$boundary}--\r\n";

		//发起请求
		$ch = curl_init(self::APIURL.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);

		$response = curl_exec($ch);
		$result = JSON::decode($response);
		if($result && isset($result['media_id']))
		{
			return ['media_id' => $result['media_id']];
		}

		throw new IException($response);
	}
}

/**
 * @brief AEAD_AES_256_GCM 解密算法
 */
class AesUtil
{
	/**
	 * AES key
	 *
	 * @var string
	 */
	private $aesKey;

	const KEY_LENGTH_BYTE = 32;
	const AUTH_TAG_LENGTH_BYTE = 16;

	/**
	 * Constructor
	 */
	public function __construct($aesKey)
	{
		if(strlen($aesKey) != self::KEY_LENGTH_BYTE)
		{
			throw new IException('无效的ApiV3Key，长度应为32个字节');
		}
		$this->aesKey = $aesKey;
	}

	/**
	 * Decrypt AEAD_AES_256_GCM ciphertext
	 *
	 * @param string    $associatedData     AES GCM additional authentication data
	 * @param string    $nonceStr           AES GCM nonce
	 * @param string    $ciphertext         AES GCM cipher text
	 *
	 * @return string|bool      Decrypted string on success or FALSE on failure
	 */
	public function decryptToString($associatedData, $nonceStr, $ciphertext)
	{
		$ciphertext = base64_decode($ciphertext);
		if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE)
		{
			return false;
		}

		// ext-sodium (default installed on >= PHP 7.2)
		if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && \sodium_crypto_aead_aes256gcm_is_available())
		{
			return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->aesKey);
		}

		// ext-libsodium (need install libsodium-php 1.x via pecl)
		if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') && \Sodium\crypto_aead_aes256gcm_is_available())
		{
			return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->aesKey);
		}

		// openssl (PHP >= 7.1 support AEAD)
		if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods()))
		{
			$ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
			$authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);

			return openssl_decrypt($ctext, 'aes-256-gcm', $this->aesKey, OPENSSL_RAW_DATA, $nonceStr, $authTag, $associatedData);
		}

		throw new Exception('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
	}
}