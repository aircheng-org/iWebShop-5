<?php
/**
 * @brief 公共方法集合
 * @class Common
 * @note  公开方法集合适用于整个系统
 */
class Common
{
	/**
	 * @brief 获取评价分数
	 * @param $grade float 分数
	 * @param $comments int 评论次数
	 * @return float
	 */
	public static function gradeWidth($grade,$comments = 1)
	{
		return $comments == 0 ? 0 : round($grade/$comments);
	}

	/**
	 * @brief 获取用户状态
	 * @param $status int 状态代码
	 * @return string
	 */
	public static function userStatusText($status)
	{
		$mapping = array('1' => '正常','2' => '删除','3' => '锁定');
		return isset($mapping[$status]) ? $mapping[$status] : '';
	}

	/**
	 * 获取本地版本信息
	 * @return String
	 */
	public static function getLocalVersion()
	{
		return include(IWeb::$app->getBasePath().'docs/version.php');
	}

	/**
	 * @brief 重量换算
	 * @param string $weight 带有重量单位的重量值,如5kg，8t等
	 * @param string $export 转换的重量单位，默认：g(克)
	 * @return int           转换后的重量数字
	 */
	public static function weight($weight,$export = 'g')
	{
		$weight = preg_replace("|\s|","",$weight);
		preg_match("|^(\d+)([a-z]+)$|",$weight,$result);

		//有单位的重量数据,如:5kg,8t等
		if(isset($result[2]))
		{
			list($wholeMatch,$weightVal,$weightUnit) = $result;
		}
		//无单位的重量数据,如:500,默认单位g(克)
		else
		{
			$weightVal  = floatval($weight);
			$weightUnit = 'g';
		}

		//单位相同不需要转换，直接返回
		if($weightUnit == $export)
		{
			return $weightVal;
		}

		//统一转换成g(克)
		switch($weightUnit)
		{
			case "kg":
			{
				$weightVal *= 1000;
			}
			break;

			case "t":
			{
				$weightVal *= 1000000;
			}
			break;
		}

		//从标准g(克)进行转化
		switch($export)
		{
			case "kg":
			{
				$weightVal /= 1000;
			}
			break;

			case "t":
			{
				$weightVal /= 1000000;
			}
			break;
		}
		return $weightVal;
	}

	/**
	 * @brief  格式化重量数据(克)
	 * @param  int $weightVal 纯重量数字(克)
	 * @return int            转换后的重量数据包含单位,如：5kg，8t等
	 */
	public static function formatWeight($weightVal)
	{
		$weightUnit = "g";
		if($weightVal > 1000000)
		{
			$weightUnit = "t";
			$weightVal /= 1000000;
		}
		else if($weightVal > 1000)
		{
			$weightUnit = "kg";
			$weightVal /= 1000;
		}
		return $weightVal.$weightUnit;
	}
}