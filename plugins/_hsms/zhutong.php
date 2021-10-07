<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file zhutong.php
 * @brief 短信发送接口
 * @author nswe
 * @date 2015/5/30 15:46:38
 * @version 3.3
 *
 * @update 2020/5/29 14:48:02
 * @note 升级短信接口
 */

 /**
 * @class zhutong
 * @brief 短信发送接口 短信后台地址 https://partner.zthysms.com/
 */
class zhutong extends hsmsBase
{
	private $submitUrl  = "https://api.mix2.zthysms.com/v2/sendSms";

	/**
	 * @brief 获取config用户配置
	 * @return array
	 */
	public function getConfig()
	{
		$siteConfigObj = new Config("site_config");

		return array(
			'username' => $siteConfigObj->sms_username,
			'userpwd'  => $siteConfigObj->sms_pwd,
		);
	}

	/**
	 * @brief 发送短信
	 * @param string $mobile
	 * @param string $content
	 * @return
	 */
	public function send($mobile,$content)
	{
		$config = self::getConfig();
		$tKey   = time();
		$codeId = $this->getProductCode($content);

		$postData = array(
			'username' => $config['username'],
			'password' => md5(md5($config['userpwd']).$tKey),
			'tKey'     => $tKey,
			'content'  => $codeId == 3 ? $content.' 退订回复N' : $content,
			'mobile'   => $mobile,
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL,$this->submitUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, JSON::encode($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
		$result = curl_exec($ch);
		if($result === false)
		{
            $error = curl_error($ch);
            curl_close($ch);
			return "CURL错误：".$error;
		}
		return $this->response($result);
	}

	/**
	 * @brief 解析结果
	 * @param $result 发送结果
	 * @return string success or fail
	 */
	public function response($result)
	{
	    $resArray = JSON::decode($result);
		if(!$resArray)
		{
			return '返回json数据异常：'.$result;
		}

		if($resArray['msg'] == 'success')
		{
		    return 'success';
		}
		return $resArray['msg'];
	}

	/**
	 * @brief 根据短信内容返回产品ID
	 * @param $content
	 */
	public function getProductCode($content)
	{
		$codeWord = array(
			1 => array('验证码'),
			2 => array('通知','自提码','消费码','成功','订单','发货'),
			3 => array('营销','活动','购买','广告','打折','降价','促销','机会')
		);

		$resultCode = 2;
		foreach($codeWord as $codeNum => $wordArray)
		{
			if(is_array($wordArray) && $wordArray)
			{
				foreach($wordArray as $word)
				{
					if(strpos($content,$word) !== false)
					{
						$resultCode = $codeNum;
						break 2;
					}
				}
			}
		}
		return $resultCode;
	}
}