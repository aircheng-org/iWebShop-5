<?php
/**
 * @copyright (c) 2017 aircheng.com
 * @file kuaidi100.php
 * @brief 快递100查询接口
 * @date 2017/11/24 23:02:08
 * @version 5.0
 */
class kuaidi100 implements freight_inter
{
	/**
	 * @brief 显示快递跟踪
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function line($ShipperCode,$LogisticCode)
	{
		$freightDB = new IModel('freight_company');
		$freightRow= $freightDB->getObj('freight_type = "'.$ShipperCode.'"');
		if(!$freightRow)
		{
			throw new IException("根据:【".$ShipperCode."】未找到相应物流公司");
		}
		$ShipperName = IString::pinyin($freightRow['freight_name']);
		$ShipperName = str_replace(" ","",$ShipperName);

		if(IClient::getDevice() == IClient::MOBILE)
		{
			$url = "https://m.kuaidi100.com/app/query/?coname=iwebshop&nu=".$LogisticCode;
		}
		else
		{
			$url = "https://www.kuaidi100.com/chaxun?nu=".$LogisticCode;
		}
		header("Location: ".$url);
		exit;
	}

	/**
	 * @brief 订阅物流快递轨迹
	 * @param $ShipperCode  string 物流公司快递号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function subscribe($ShipperCode,$LogisticCode)
	{

	}

	/**
	 * @brief 订阅物流快递回调接口
	 * @param $callbackData mixed 物流回传信息
	 * @return mixed
	 */
	public function subCallback($callbackData)
	{

	}
}