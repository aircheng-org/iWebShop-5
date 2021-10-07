<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file crypt_class.php
 * @brief 加密解密
 * @author RogueWolf
 * @date 2010-12-2
 * @version 0.6
 * @note
 */

/**
 * @class ICrypt
 * @brief ICrypt 加密解密类
 * @note
 */
class ICrypt
{
	/**
	 * @brief  MD5加密方法
	 * @param  string $string  字符串
	 * @return string $string  加密后的字符串
	 */
	public static function md5($string)
	{
		return md5($string);
	}

	/**
	 * @brief base64加密方法
	 * @param  String $str 字符串
	 * @return String $str 加密后字符串
	 */
	public static function base64encode($str)
	{
		return base64_encode($str);
	}

	/**
	 * @brief  base64解密方法
	 * @param  String $str  字符串
	 * @return String $str  解密后的字符串
	 */
	public static function base64decode($str)
	{
		return base64_decode($str);
	}

	/**
	 * @brief 动态解密函数
	 * @param  String $string 字符串
	 * @param  String $key    解密私钥
	 * @param  Int    $expiry 保留时间，默认为0，即为不限制
	 * @return String $str    解密后的字符串
	 */
	public static function decode($string, $key='', $expiry=0)
	{
		return self::code($string,'decode', $key, $expiry);
	}

	/**
	 * @brief 动态解密函数
	 * @param  String  $string 字符串
	 * @param  String  $key    加密私钥
	 * @param  Int     $expiry 保留时间，默认为0，即为不限制
	 * @return $string String  加密后的字符串
	 */
	public static function encode($string, $key='', $expiry=0)
	{
		return self::code($string,'encode', $key, $expiry);
	}
	/**
	 * @brief 加密解密算法
	 * @param  String  $string  要处理的字符串
	 * @param  String  $op      处理方式，加密或者解密，默认为decode即为解密
	 * @param  Int     $expiry  保留时间，默认为0即为不限制
	 * @return String  $string  处理后的字符串
	 */
	private static function code($string, $op="decode", $key='', $expiry=0)
	{
		$op=strtolower($op);
		$key_length=18;
		$key=md5($key?$key:"aircheng");
		//生成256长度的密码
		$key_1=md5(substr($key,0,4));
		$key_2=md5(substr($key,4,4));
		$key_3=md5(substr($key,8,4));
		$key_4=md5(substr($key,12,4));
		$key_5=md5(substr($key,16,4));
		$key_6=md5(substr($key,20,4));
		$key_7=md5(substr($key,24,4));
		$key_8=md5(substr($key,28,4));
		$key_e= $key_length ? ($op == 'decode' ? substr($string, 0, $key_length): substr(md5(microtime()), -$key_length)) : '';
		$cryptkey = md5($key_1|$key_e).md5($key_3|$key_e).md5($key_5|$key_e).md5($key_7|$key_e).md5($key_8|$key_e).md5($key_6|$key_e).md5($key_4|$key_e).md5($key_2|$key_e);
		$cryptkey_length = strlen($cryptkey);
		$string = $op == 'decode' ? self::base64decode(substr($string, $key_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$key_5), 0, 22).$string;
		$string_length = strlen($string);
		$result="";
		//通过循环的方式异或的方式加密，异或方式是加密中常用的一种处理方式
		for($i = 0; $i < $string_length; $i++)
		{
			$ordVal = ord($string[$i]) ^ ord($cryptkey[$i % 256]);
			$result.= chr($ordVal);
		}
		//解码部分
		if($op == 'decode')
		{
			$tempString = substr($result, 0, 10);
			if(($tempString == 0 || (is_numeric($tempString) && $tempString - time() > 0)) && substr($result, 10, 22) == substr(md5(substr($result, 32).$key_5), 0, 22))
			{
				return substr($result, 32);
			}
			else
			{
				return '';
			}
		}
		else
		{
			return $key_e.str_replace('=', '', self::base64encode($result));
		}
	}

	/**
	 * @brief  简单加密算法
	 * @param  String  $string  要处理的字符串
	 * @param  String  $skey    密钥
	 * @return String  $string  处理后的字符串
	 */
	public static function simpleEncode($string, $skey = 'iWebShop')
	{
	    $strArr   = str_split(base64_encode($string));
	    $strCount = count($strArr);
	    foreach(str_split($skey) as $key => $value)
	    {
	        $key < $strCount && $strArr[$key].=$value;
	    }
	    return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
	}
	/**
	 * @brief  简单解密算法
	 * @param  String  $string  要处理的字符串
	 * @param  String  $skey    密钥
	 * @return String  $string  处理后的字符串
	 */
	public static function simpleDecode($string, $skey = 'iWebShop')
	{
	    $strArr   = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
	    $strCount = count($strArr);
	    foreach(str_split($skey) as $key => $value)
	    {
	        $key <= $strCount && isset($strArr[$key]) && isset($strArr[$key][1]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
	    }
	    return base64_decode(join('', $strArr));
	}

	/**
	 * @brief rsa私钥加密
	 * @param String $data   待加密字符串
	 * @param String $priKey 私秘钥
	 * @param Bool $isBase64 是否进行base64编码
	 * @param String         返回加密字符串
	 */
	public static function rsaPriEncode($data,$priKey,$isBase64 = true)
	{
		$outval = '';
		$res = openssl_pkey_get_private($priKey);
		openssl_private_encrypt($data, $outval, $res);
		if($isBase64)
		{
			$outval = base64_encode($outval);
		}
		return $outval;
	}

	/**
	 * @brief rsa私钥解密
	 * @param String $data   已加密字符串
	 * @param String $priKey 私秘钥
	 * @param Bool $isBase64 是否进行base64编码
	 * @param String         返回原文字符串
	 */
	public static function rsaPriDecode($data,$priKey,$isBase64 = true)
	{
        $outval = '';
        if($isBase64)
		{
            $data = base64_decode($data);
        }
        $res = openssl_pkey_get_private($priKey);
        openssl_private_decrypt($data, $outval, $res);
        return $outval;
	}

	/**
	 * @brief rsa公钥加密
	 * @param String $data   待加密字符串
	 * @param String $pubKey 共秘钥
	 * @param Bool $isBase64 是否进行base64编码
	 * @param String         返回加密字符串
	 */
	public static function rsaPubEncode($data,$pubKey,$isBase64 = true)
	{
        $outval = '';
        $res = openssl_pkey_get_public($pubKey);
        openssl_public_encrypt($data, $outval, $res);
        if($isBase64)
		{
            $outval = base64_encode($outval);
        }
        return $outval;
	}

	/**
	 * @brief rsa公钥解密
	 * @param String $data   已加密字符串
	 * @param String $pubKey 共秘钥
	 * @param Bool $isBase64 是否进行base64编码
	 * @param String         返回原文字符串
	 */
	public static function rsaPubDecode($data,$pubKey,$isBase64 = true)
	{
        $outval = '';
        if($isBase64)
		{
            $data = base64_decode($data);
        }
        $res = openssl_pkey_get_public($pubKey);
        openssl_public_decrypt($data, $outval, $res);
        return $outval;
	}
}