<?php
/**
 * @copyright Copyright(c) 2014 aircheng.com
 * @file offline.php
 * @brief 线下支付
 * @author nswe
 * @date 2014/9/1 9:09:28
 * @version 2.7
 */

 /**
 * @class offline
 * @brief 线下支付
 */
class offline extends paymentPlugin
{
	//支付插件名称
    public $name = '线下支付';

	public function doPay($sendData)
	{
		die("请通过线下支付的方式尽快付款");
	}

	/**
	 * @see paymentplugin::notifyStop()
	 */
	public function notifyStop(){}

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl(){}

	/**
	 * @see paymentplugin::getSendData()
	 */
	public function getSendData($paymentInfo){}

	/**
	 * @see paymentplugin::callback()
	 */
	public function callback($ExternalData,&$paymentId,&$money,&$message,&$orderNo){}

	/**
	 * @see paymentplugin::serverCallback()
	 */
	public function serverCallback($ExternalData,&$paymentId,&$money,&$message,&$orderNo){}
}