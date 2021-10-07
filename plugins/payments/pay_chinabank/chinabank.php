<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file pay_wangjin.php
 * @brief 网银在线支付接口
 * @author chendeshan
 * @date 2011-01-27
 * @version 0.6
 * @note
 */

 /**
 * @class pay_wangjin
 * @brief 网银在线支付接口
 */
class chinabank extends paymentPlugin
{
	//插件名称
    public $name = '网银在线支付';

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
	public function getSubmitUrl()
	{
		return 'https://Pay3.chinabank.com.cn/PayGate';
	}

	/**
	 * @see paymentplugin::notifyStop()
	 */
	public function notifyStop()
	{
		echo "ok";
	}

	/**
	 * @see paymentplugin::callback()
	 */
	public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
        $key         = Payment::getConfigParam($paymentId,'M_PartnerKey');

		$v_oid       = trim($_POST['v_oid']);      // 商户发送的v_oid定单编号
		$v_pmode     = trim($_POST['v_pmode']);    // 支付方式（字符串）
		$v_pstatus   = trim($_POST['v_pstatus']);  // 支付状态 ：20（支付成功）；30（支付失败）
		$v_pstring   = trim($_POST['v_pstring']);  // 支付结果信息 ： 支付完成（当v_pstatus=20时）；失败原因（当v_pstatus=30时,字符串）；
		$v_amount    = trim($_POST['v_amount']);   // 订单实际支付金额
		$v_moneytype = trim($_POST['v_moneytype']);// 订单实际支付币种
		$v_md5str    = trim($_POST['v_md5str' ]);  // 拼凑后的MD5校验值

		$result      = $v_oid.$v_pstatus.$v_amount.$v_moneytype.$key;
		$md5string   = strtoupper(md5($result));

        if($v_md5str == $md5string)
        {
			$money   = $v_amount;
			$orderNo = $v_oid;
			$message = $v_pstring;

            //支付单号
            switch($v_pstatus)
            {
                case '20':
                {
                	return true;
                    break;
                }

                case '30':
                {
                	return false;
                	break;
                }
            }
        }
        else
        {
        	$message = '校验码不正确';
        	return false;
        }
	}

	/**
	 * @see paymentplugin::serverCallback()
	 */
	public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
	{
		return $this->callback($callbackData,$paymentId,$money,$message,$orderNo);
	}

	/**
	 * @see paymentplugin::getSendData()
	 */
	public function getSendData($payment)
	{
    	$return                = array();
    	$uid                   = $payment['M_PartnerId'];
    	$key                   = $payment['M_PartnerKey'];

    	$return['v_mid']       = $uid;
    	$return['v_oid']       = $payment['M_OrderNO'];
    	$return['v_amount']    = $payment['M_Amount'];
    	$return['v_moneytype'] = "CNY";
    	$return['v_url']       = $this->callbackUrl;
    	$return['remark1']     = "";
    	$return['remark2']     = "[url:=".$this->serverCallbackUrl."]";

    	$text                  = $return['v_amount'].$return['v_moneytype'].$return['v_oid'].$return['v_mid'].$return['v_url'].$key;
		$return['v_md5info']   = strtoupper(md5($text));

        return $return;
	}
}
