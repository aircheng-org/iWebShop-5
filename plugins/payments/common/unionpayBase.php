<?php
/**
 * @brief 银联支付接口基类
 * @class unionpayBase
 * @date 2018/5/20 9:29:47
 */
class unionpayBase
{
	//签名密码
	public static $SDK_SIGN_CERT_PWD = '000000';

	/**
	 * 数组 排序后转化为字体串
	 *
	 * @param array $params
	 * @param boolen 是否编码
	 * @param boolen 是否包括签名
	 * @return string
	 */
	public static function coverParamsToString($params,$encode = false,$isIncludeSign = false)
	{
		$sign_str = '';

		// 排序
		ksort($params);
		foreach($params as $key => $val)
		{
			if ($isIncludeSign == false && $key == 'signature')
			{
				continue;
			}

			if($encode == true)
			{
				$val = urlencode($val);
			}
			$sign_str .= $key . '=' . $val . '&';
		}
		return trim($sign_str,'&');
	}

	/**
	 * 进行数据签名
	 * @param String $params
	 */
	public static function sign(&$params)
	{
		if(isset($params['transTempUrl']))
		{
			unset($params['transTempUrl']);
		}
		$params_sha1x16 = sha1(self::coverParamsToString($params),FALSE);

		//获取私钥证书内容
		$private_key = self::getPrivateKey();

		//签名
		$sign_falg = openssl_sign($params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1);
		if($sign_falg)
		{
			$params['signature'] = base64_encode($signature);
		}
		else
		{
			throw new IException("签名失败");
		}
	}

	/**
	 * 对服务器端的内容进行验签
	 * @param string $params
	 * @param boolean
	 */
	public static function verify($params)
	{
		$public_key = self::getPublicKey();

		// 签名串
		$signature = base64_decode($params['signature']);
		unset($params['signature']);

		$params_sha1x16 = sha1(self::coverParamsToString($params), FALSE);
		return openssl_verify($params_sha1x16,$signature,$public_key,OPENSSL_ALGO_SHA1);
	}

	/**
	 * 取证书ID(.pfx)
	 * @return string 证书内容
	 */
	public static function getCertId($cert_path)
	{
		$pkcs12certdata = file_get_contents($cert_path);
		openssl_pkcs12_read($pkcs12certdata, $certs, self::$SDK_SIGN_CERT_PWD);
		if(isset($certs['cert']) && $certs['cert'])
		{
			$x509data = $certs['cert'];
			openssl_x509_read($x509data);
			$certdata = openssl_x509_parse($x509data);
			if(isset($certdata['serialNumber']) && $certdata['serialNumber'])
			{
				return $certdata['serialNumber'];
			}
		}

		throw new IException("获取私钥证书ID错误");
	}

	/**
	 * 根据签名证书密码获取签名证书ID
	 * @param string $pwd 签名密码
	 * @return string 证书ID
	 */
	static function getSignCertId($pwd)
	{
		//保存签名密码
		self::$SDK_SIGN_CERT_PWD = $pwd;

		//签名证书路径
		return self::getCertId(SDK_SIGN_CERT_PATH);
	}

	/**
	 * 取证书公钥 -验签
	 * @return string
	 */
	public static function getPublicKey()
	{
		return file_get_contents(SDK_ENCRYPT_CERT_PATH);
	}

	/**
	 * 返回(签名)证书私钥
	 * @return unknown
	 */
	public static function getPrivateKey()
	{
		$pkcs12 = file_get_contents(SDK_SIGN_CERT_PATH);
		openssl_pkcs12_read($pkcs12, $certs, self::$SDK_SIGN_CERT_PWD);
		if(isset($certs['pkey']) && $certs['pkey'])
		{
			return $certs['pkey'];
		}
		throw new IException("获取私钥证书的<pkey>内容错误");
	}
}