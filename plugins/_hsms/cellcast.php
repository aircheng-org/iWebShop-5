<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file cellcast.php
 * @brief cellcast短信发送
 * @author nswe
 * @date 2022/09/03 18:19:00
 * @version 5.13
 */

 /**
 * @class cellcast
 * @brief cellcast短信发送
 * @note 需要参数 APPKEY
 */
class cellcast extends hsmsBase
{
	/**
	 * @brief 获取config用户配置
	 * @return array
	 */
	public function getConfig()
	{
		$siteConfigObj = new Config("site_config");

		return [
			'APPKEY' => $siteConfigObj->sms_appkey,
		];
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

		try
		{
			$url = 'https://cellcast.com.au/api/v3/send-sms'; //API URL
			$fields = array(
				'sms_text' => $content, //here goes your SMS text
				'numbers' => $mobile // Your numbers array goes here
			);
			$headers = array(
				'APPKEY: '.$config['APPKEY'],
				'Accept: application/json',
				'Content-Type: application/json',
			);

			$ch = curl_init(); //open connection
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			if (!$result = curl_exec($ch))
			{
				return $this->response(array("status" => 400, "msg" => curl_error($ch) ));
			}
			curl_close($ch);
			return $this->response(array("status" => 200, "msg" => "SMS sent successfully", "result" => json_decode($result)));
		}
		catch (\Exception $e)
		{
			return $this->response(array("status" => 400, "msg" => "Something went to wrong, please try again.", "result" => array()));
		}
	}

	/**
	 * @brief 解析结果
	 * @param $result 发送结果
	 * @return string success or fail
	 */
	public function response($result)
	{
		if($result && $result['status'] == 200)
		{
			return 'success';
		}
		else
		{
			return $result['msg'];
		}
	}
}