<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file client_class.php
 * @brief 获取客户端数据信息
 * @author chendeshan
 * @date 2010-12-2
 * @version 0.6
 */

/**
 * @class IClient
 * @brief IClient 获取客户端信息
 */
class IClient
{
	const PC     = 'pc';
	const MOBILE = 'mobile';

	/**
	 * @brief 获取客户端ip地址
	 * @return string 客户端的ip地址
	 */
	public static function getIp()
	{
	    $realip = '';
	    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	    {
	    	$ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	    	foreach($ipArray as $rs)
	    	{
	    		$rs = trim($rs);
	    		if($rs != 'unknown')
	    		{
	    			$realip = $rs;
	    			break;
	    		}
	    	}
	    }
	    else if(isset($_SERVER['HTTP_CLIENT_IP']))
	    {
	    	$realip = $_SERVER['HTTP_CLIENT_IP'];
	    }
	    else if(isset($_SERVER['REMOTE_ADDR']))
	    {
	    	$realip = $_SERVER['REMOTE_ADDR'];
	    }
	    return IFilter::act($realip,'ip');
	}

	/**
	 * @brief 获取客户端浏览的上一个页面的url地址
	 * @return string 客户端上一个访问的url地址
	 */
	public static function getPreUrl()
	{
		return $_SERVER['HTTP_REFERER'];
	}

	/**
	 * @brief 获取客户端当前访问的时间戳
	 * @return int 时间戳
	 */
	public static function getTime()
	{
		if(IServer::isGeVersion('5.1.0'))
			return $_SERVER['REQUEST_TIME'];
		else
			return time();
	}

	/**
	 * @brief 获取客户设备类型
	 * @return string pc,mobile
	 */
	public static function getDevice()
	{
		//如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
		{
			return self::MOBILE;
		}

		//判断手机发送的客户端标志
		if (isset($_SERVER['HTTP_USER_AGENT']))
		{
			$clientKEY = array ('nokia','sony','ericsson','mot','samsung','htc','huawei','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iPhone','phone','ipod','ipad','blackberry','meizu','Android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','WindowsWechat','MiniProgramEnv');

			//从HTTP_USER_AGENT中查找手机浏览器的关键字
			if (preg_match("/(" . implode('|', $clientKEY) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
			{
				return self::MOBILE;
			}
		}

		//判断协议
		if (isset($_SERVER['HTTP_ACCEPT']))
		{
			//如果只支持wml并且不支持html那一定是移动设备，如果支持wml和html但是wml在html之前则是移动设备
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
			{
				return self::MOBILE;
			}
		}
		return self::PC;
	}
	/**
	 * @brief 支持返回的客户端
	 * @return 客户端平台
	 */
	public static function supportClient()
	{
		return array(self::PC,self::MOBILE);
	}

	/**
	 * @brief 判断客户端请求是否为ajax方式
	 * @return boolean
	 */
	public static function isAjax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false;
	}

	/**
	 * @brief 判断客户端是否为微信浏览器
	 * @return boolean
	 */
	public static function isWechat()
	{
		if(isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') && stripos($_SERVER['HTTP_USER_AGENT'], 'miniProgram') === false && ISession::get('_from') != 'miniProgram')
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief 判断客户端是否为微信小程序
	 * @return boolean
	 */
	public static function isMini()
	{
		if((isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'miniProgram')) || ISession::get('_from') == 'miniProgram')
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief 判断客户端是否为APP
	 * @return boolean
	 */
	public static function isApp()
	{
		if(self::getDevice() == self::MOBILE && (stripos($_SERVER['HTTP_USER_AGENT'],'iwebshop_app') === true || self::isAjax() == true))
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief 获取当前地理位置
	 * @return array(country=>国家, province=>省份, city=>城市,area=>市区)
	 */
	public static function getLocal()
	{
	    $ip  = self::getIp();
	    $url = "http://ipservice.suning.com/ipQuery.do";
	    if($ip)
	    {
	        $url .= "?ip=".$ip;
	    }
		$result = file_get_contents($url);
		if($result)
		{
			$data = JSON::decode($result);
			return array("province" => $data['provinceName'],"city" => $data['cityName'],"area" => $data['districtName']);
		}
	}
}