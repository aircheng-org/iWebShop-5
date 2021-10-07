<?php
/**
 * @copyright Copyright(c) 2010 aircheng.com
 * @file
 * @brief 系统统验证类文件
 * @author nswe
 * @date 2018/1/19 17:00:13
 * @version 5.1
 */
/**
 * @brief 系统统验证类文件
 * @class IValidate
 */
class IValidate
{
    /**
     * @brief Email格式验证
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function email($str='')
    {
        return (bool)filter_var($str,FILTER_VALIDATE_EMAIL);
    }
    /**
     * @brief QQ号码验证
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function qq($str='')
    {
        return (bool)preg_match('/^[1-9][0-9]{4,}$/i',$str);
    }
    /**
     * @brief 身份证验证包括一二代身份证
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function id($str='')
    {
        return (bool)preg_match('/^\d{15}(\d{2}[0-9x])?$/i',$str);
    }
    /**
     * @brief IP检测
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function ip($str='')
    {
        return (bool)filter_var($str,FILTER_VALIDATE_IP);
    }
    /**
     * @brief 邮政编码验证
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     * @note 此邮编验证只适合中国
     */
    public static function zip($str='')
    {
        return (bool)preg_match('/^\d{6}$/i',$str);
    }
    /**
     * @brief 验证字符串的长度，和数值的大小。$str 为字符串时，判定长度是否在给定的$min到$max之间的长度，为数值时，判定数值是否在给定的区间内。
     * @param mixed $str 要验证的内容
     * @param int $min 最小值或最小长度
     * @param int $max 最大值或最大长度
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function len($str, $min, $max)
    {
        if(is_int($str)) return $str >= $min && $str <= $max;
        if(is_string($str))return IString::getStrLen($str) >= $min && IString::getStrLen($str) <= $max;
        return false;
    }
    /**
     * @brief 电话号码验证
     * @param string $str 需要验证的字符串
     * @return  bool 验证通过返回 true 不通过返回 false
     */
    public static function phone($str='')
    {
        return (bool)preg_match('/^((\d{3,4})|\d{3,4}-)?\d{3,8}(-\d+)*$/i',$str);
    }
    /**
     * @brief 手机号码验证
     * @param string $str
     * @return  bool 验证通过返回 true 不通过返回 false
     */
    public static function mobi($str='')
    {
		return (bool)preg_match("!^1[3|4|5|6|7|8|9][0-9]\d{4,8}$!",$str);
    }
    /**
     * @brief Url地址验证
     * @param string $str 要检测的Url地址字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function url($str='')
    {
        return (bool)filter_var($str,FILTER_VALIDATE_URL);
    }
    /**
     * @brief 正则验证接口
     * @param mixed $reg 正则表达式
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function check($reg, $str='')
    {
        return (bool)preg_match('/^'.$reg.'$/i',$str);
    }
	/**
     * @brief 判断字符串是否为空
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function required($str)
    {
         return (bool)preg_match('/\S+/i',$str);
    }

	/**
     * @brief 百分比数字
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function percent($str)
    {
    	return (bool)preg_match('/^[1-9][0-9]*$/',$str);
    }

	/**
     * @brief 用户名
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function name($str,$minlen=2, $maxlen=20)
    {
		return (bool)preg_match("!^[\w\x{4e00}-\x{9fa5}]{".$minlen.",".$maxlen."}$!u",$str);
    }

	/**
     * @brief 文件名或者文件路径
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function filename($str)
    {
    	//禁止跨越目录
    	if(strpos($str,"..") !== false)
    	{
    		return false;
    	}
		return (bool)preg_match("%^[\w\./:/!]+$%",$str);
    }

	/**
     * @brief 严格的检索过滤
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function strict($str)
    {
    	return (bool)preg_match("|^[\w\.\,\-<>=\!\x{4e00}-\x{9fa5}\s*]+$|u",$str);
    }

	/**
     * @brief 日期过滤检测,比如：2000-01-01
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function date($str)
    {
    	return (bool)preg_match("/^[1-9]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$str);
    }

	/**
     * @brief 时间过滤检测,比如：01:01:01
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function time($str)
    {
    	return (bool)preg_match("/^(20|21|22|23|[0-1]\d):[0-5]\d:[0-5]\d$/",$str);
    }

	/**
     * @brief 日期时间过滤检测,比如：2000-01-01 01:01:01
     * @param string $str 需要验证的字符串
     * @return bool 验证通过返回 true 不通过返回 false
     */
    public static function datetime($str)
    {
    	return (bool)preg_match("/^[1-9]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s+(20|21|22|23|[0-1]\d):[0-5]\d:[0-5]\d$/",$str);
    }
}