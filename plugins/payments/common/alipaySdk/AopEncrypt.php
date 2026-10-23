<?php
/**
 *   加密工具类
 *
 * User: jiehua
 * Date: 16/3/30
 * Time: 下午3:25
 *
 * PHP 8 改造：mcrypt 扩展在 7.2 已移除，改用 openssl。
 * 支付宝接口内容加密的约定是 AES-128-CBC + 16 字节全 0 的 IV + PKCS7 填充，
 * 原来 mcrypt 版本虽然生成了随机 IV 但没传给 mcrypt_encrypt，实际生效的就是全 0 IV，
 * 所以这里直接用零 IV，跟历史上发出去的密文完全兼容。
 */

/**
 * 加密方法
 * @param string $str
 * @return string
 */
 function encrypt($str,$screct_key){
	$screct_key = base64_decode($screct_key);
	$str = trim($str);
	$str = addPKCS7Padding($str);

	$iv = str_repeat("\0", 16);
	$encrypt_str = openssl_encrypt($str, 'aes-128-cbc', $screct_key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

	return base64_encode((string) $encrypt_str);
}

/**
 * 解密方法
 * @param string $str
 * @return string
 */
 function decrypt($str,$screct_key){
	$str = base64_decode($str);
	$screct_key = base64_decode($screct_key);

	$blockSize = 16;
	// 密文长度必须是块大小的整数倍，否则说明传进来的不是这段逻辑加密的数据
	if(strlen($str) % $blockSize !== 0)
	{
		return '';
	}

	$iv = str_repeat("\0", 16);
	$encrypt_str = openssl_decrypt($str, 'aes-128-cbc', $screct_key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

	if($encrypt_str === false)
	{
		return '';
	}

	$encrypt_str = trim($encrypt_str);
	$encrypt_str = stripPKSC7Padding($encrypt_str);

	return $encrypt_str;
}

/**
 * 填充算法
 * @param string $source
 * @return string
 */
function addPKCS7Padding($source){
	$source = trim($source);
	$block  = 16;

	$pad = $block - (strlen($source) % $block);
	if ($pad <= $block) {
		$char = chr($pad);
		$source .= str_repeat($char, $pad);
	}
	return $source;
}
/**
 * 移去填充算法
 * @param string $source
 * @return string
 */
function stripPKSC7Padding($source){
	$source = trim($source);
	$char = substr($source, -1);
	$num = ord($char);
	if($num==62)return $source;
	$source = substr($source,0,-$num);
	return $source;
}
