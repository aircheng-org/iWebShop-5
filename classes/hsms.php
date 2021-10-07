<?php
/**
 * @copyright (c) 2015 aircheng.com
 * @file hsms.php
 * @brief 短信发送接口
 * @author nswe
 * @date 2015/5/30 16:23:21
 * @version 3.3
 * @update 2018/11/30 7:56:49
 * @note 修正了插件路径改为plugins/_hsms
 */

 /**
 * @class Hsms
 * @brief 短信发送接口
 */
class Hsms
{
	private static $smsInstance = null;

	//每次用户主动（非系统）发送的短信间隔
	private static $sendStep = 50;

	/**
	 * @brief 获取config用户配置
	 * @return array
	 */
	private static function getPlatForm()
	{
		$siteConfigObj = new Config("site_config");
		return $siteConfigObj->sms_platform;
	}

	/**
	 * @brief 发送短信
	 * @param string $mobiles 多个手机号为用半角,分开，如13899999999,13688888888（最多200个）
	 * @param string $content 短信内容
	 * @param int $delay 延迟设置
	 * @return success or fail
	 */
	public static function send($mobiles, $content, $delay = 1)
	{
		if(!$content)
		{
			return "短信内容不能为空";
		}

		if( $delay == 1 && !isset($_SERVER['HTTP_USER_AGENT']) )
		{
			return "非客户端访问";
		}

		if(IClient::getIp() == '')
		{
			return "ip信息不合法";
		}

		$mobile_array = explode(",", $mobiles);
		foreach ($mobile_array as $key => $val)
		{
			if(false === IValidate::mobi($val))
			{
				unset($mobile_array[$key]);
			}
		}

		if(!$mobile_array)
		{
			return "非法手机号码";
		}

		if(count($mobile_array) > 200)
		{
			return "手机号超过200个";
		}

		//延迟机制
		if($delay == 1)
		{
			$cacheObj = new ICache();
			$smsTime = $cacheObj->get('smsDelay'.md5($mobiles));
			if($smsTime && time() - $smsTime < self::$sendStep)
			{
				return "短信发送频率太快，请稍候再试...";
			}
			//更新发送时间
			$cacheObj->set('smsDelay'.md5($mobiles),time());
		}

		if(self::$smsInstance == null)
		{
			$platform  = self::getPlatForm();
			$classFile = IWeb::$app->getBasePath().'plugins/_hsms/'.$platform.'.php';
			if(!$platform || !is_file($classFile))
			{
			    return '短信平台未配置';
			}

			require($classFile);
			self::$smsInstance = new $platform();
		}

		$log = ["开始记录" => "短信发送","手机号" => $mobiles,"短信内容" => $content];
		self::log($log);

		$result = self::$smsInstance->send($mobiles, $content);

		$log = ["返回记录" => "短信发送","返回值" => $result];
		self::log($log);

		return $result;
	}

    //记录日志信息
	private static function log($content)
	{
	    $logObj = new IFileLog('hsms/'.date('Y-m-d').'.log');
	    $logObj->write($content);
	}
}

/**
 * @brief 短信抽象类
 */
abstract class hsmsBase
{
	//短信发送接口
	abstract public function send($mobile,$content);

	//短信发送结果接口
	abstract public function response($result);
}