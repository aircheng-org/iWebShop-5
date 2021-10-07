<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file time_class.php
 * @brief 时间处理
 * @author RogueWolf
 * @date 2010-12-02
 * @version 0.6
 */

/**
 * @class ITime
 * @brief ITime 时间处理类
 * @note
 */
class ITime
{
	/**
	 * @brief 获取当前时间
	 * @param String  $format  返回的时间格式，默认返回当前时间的时间戳
	 * @return String $time    时间
	 */
	public static function getNow($format='')
	{
		if($format)
		{
			return self::getDateTime($format);
		}
		return self::getTime();
	}

	/**
	 * @brief  根据指定的格式输出时间
	 * @param  String  $format 格式为年-月-日 时:分：秒,如‘Y-m-d H:i:s’
	 * @param  String  $time   输入的时间
	 * @return String  $time   时间
	 */
	public static function getDateTime($format='',$time='')
	{
		$time   = $time   ? $time   : time();
		$format = $format ? $format : 'Y-m-d H:i:s';
		return date($format,$time);
	}

	/**
	 * @brief  根据输入的时间返回时间戳
	 * @param  $time String 输入的时间，格式为年-月-日 时:分：秒,如2010-01-01 00:00:00
	 * @return $time Int 指定时间的时间戳
	 */
	public static function getTime($time='')
	{
		if($time)
		{
			return strtotime($time);
		}
		return time();
	}

	/**
	 * @brief 获取第一个时间与第二个时间之间相差的秒数
	 * @param $first_time  String 第一个时间 格式为英文时间格式，如2010-01-01 00:00:00
	 * @param $second_time String 第二个时间 格式为英文时间格式，如2010-01-01 00:00:00
	 * @return $difference Int 时间差，单位是秒
	 * @note  如果第一个时间早于第二个时间，则会返回负数
	 */
	public static function getDiffSec($first_time,$second_time='')
	{
		$second_time = $second_time ? $second_time : self::getDateTime();
		$difference  = strtotime($first_time) - strtotime($second_time);
		return $difference;
	}

	/**
	 * @brief 获取过去或未来的时间
	 * @param $second  string 差秒数
	 * @param $format  string 日期时间格式
	 * @param $time    int    时间戳
	 * @param string   返回日期时间
	 */
	public static function pass($interval_spec,$format = 'Y-m-d H:i:s',$time = '')
	{
		$datetime = self::getDateTime($format,$time);
		$secData  = strtotime($datetime) + $interval_spec;
		return date($format,$secData);
	}

	/**
	 * @brief 验证日期是否正确
	 * @param string $date YYYY-MM-DD格式的日期
	 * @return boolean 是否正确
	 */
	public static function checkDateTime($date)
	{
		$len = strlen($date);
		if (10 != $len)
		{
			return false;
		}
		$date_array = explode("-", $date);
		if (!isset($date_array[1]) || !isset($date_array[2]))
		{
			return false;
		}
		$month = intval($date_array[1]);
		$day = intval($date_array[2]);
		$year = intval($date_array[0]);
		return checkdate($month, $day, $year);
	}

	/**
	 * @brief 列出2个日期的区间天数据
	 * @param date $startDate YYYY-MM-DD格式的日期
	 * @param date $endDate YYYY-MM-DD格式的日期
	 * @param string 0:包括开始和截至日期;1:不包括开始日期;2:不包括截止日期;3:不包括开始和截至日期
	 * @return array 区间日期
	 */
	public static function listDay($startDate,$endDate,$type = 0)
	{
	    if($startDate >= $endDate)
	    {
	        return false;
	    }

        $result = [$startDate];
        while(true)
        {
            $dateObj = new DateTime($startDate);
            $dateObj->add(new DateInterval('P1D'));
            $nextDay = $dateObj->format('Y-m-d');

            if($nextDay == $endDate)
            {
                $result[] = $endDate;
                break;
            }
            else
            {
                $result[] = $nextDay;
                $startDate = $nextDay;
            }
        }

        switch($type)
        {
            case 1:
            {
                array_shift($result);
            }
            break;

            case 2:
            {
                array_pop($result);
            }
            break;

            case 3:
            {
                array_shift($result);
                array_pop($result);
            }
            break;
        }

        return $result;
	}
}