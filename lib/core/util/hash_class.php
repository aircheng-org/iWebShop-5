<?php
/**
 * @copyright Copyright(c) 2010 aircheng.com
 * @file hash_class.php
 * @brief 实现系统中常用的散列
 * @author webning
 * @date 2010-12-02
 * @version 0.6
 * @note
 */
 /**
  * @brief IHash 整合系统常用到的Hash算法及一些常用的hash算法
  * @class IHash
  * @note
  */
class IHash
{
    /**
     * @brief 调用系统的CRC32系统函数
     * @param String $str
     * @return int 加密后的数字
     * @note 此方法不利于存放散列
     */
    public static function crc($str)
    {
        return crc32($str);
    }
     /**
      * @brief 自定时间格式散列，书写与date()函数相同 当$rand 为false时生成format的散列，为true时生成format+5位随机数的散列,这也是默认方式
      * @param string $format 默认是 Y/m/d/His
      * @param bool $rand true加5位随机数，false 不加随机数 默认为true
      * @return String
      */
    public static function time($format='Y/m/d/His', $rand=true)
    {
        if($rand) return ITime::getNow($format).rand(10000,99999);
        else return ITime::getNow($format);
    }
    /**
     * @brief 随机生成字符串函数
     * @param int $len 要生成的长度
     * @param string $type 生成字符串的类型
     * @return String 随机生成字符
     */
	public static function random($len=6,$type='mix')
	{
        $len = intval($len);
		if($len >32) $len = 32;
		$str  = '';
		switch($type)
		{
			case 'int':
				$int_attr = array('0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','5','9');
				shuffle($int_attr);
				$str= implode($int_attr);
				break;
			case 'char':
				$attr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','g','m','p','t','z','c');
				shuffle($attr);
				$str = implode($attr);
				break;
			case 'ukey':
				//去掉了容易混淆的字符oOLl和数字01
				$str = str_shuffle('ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789');
				break;
			default:
				$str = md5(uniqid(mt_rand(), true));
				break;
		}
		return substr($str,0,$len);
	}
    /**
     * @brief 调用系统的MD5散列方式
     * @param String $str 要md5加密的数据
     * @return String md5加密后的数据
     */
    public static function md5($str)
    {
        return md5($str);
    }
    /**
     * @brief 文件的MD5计算
     * @param string $fileName 要md5的文件名称
     * @return String 文件的md5值
     */
    public static function md5_file($fileName)
    {
        return md5_file($fileName);
    }
    /**
     * @brief 根据$str进行散列到给定的$array数组资源上;
     * @param array $array 散列的数组
     * @param string $str  依据散列的字符串
     * @return mixed
     */
    public static function hash($array, $str)
    {
        $len = count($array);
        $key = abs(crc32($str)) % $len;
        return $array[$key];
    }
}
?>