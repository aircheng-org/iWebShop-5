<?php
/**
 * @copyright Copyright(c) 2014 aircheng.com
 * @file area.php
 * @brief 省市地区调用函数
 * @author nswe
 * @date 2014/8/6 20:46:52
 * @version 2.6
 * @note
 */

 /**
 * @class area
 * @brief 省市地区调用函数
 */
class area
{
	/**
	 * @brief 根据传入的地域ID获取地域名称，获取的名称是根据ID依次获取的
	 * @param int 地域ID 匿名参数可以多个id
	 * @return array
	 */
	public static function name()
	{
		$result     = array();
		$paramArray = func_get_args();

		//根据参数ID初始化数组值
		foreach($paramArray as $key => $val)
		{
			$result[$val] = "";
		}

		$areaDB     = new IModel('areas');
		$areaData   = $areaDB->query("area_id in (".trim(join(',',$paramArray),",").")");

		foreach($areaData as $key => $value)
		{
			$result[$value['area_id']] = $value['area_name'];
		}
		return $result;
	}
}