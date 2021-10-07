<?php
/**
 * @copyright (c) 2015 aircheng.com
 * @file wechatOauth.php
 * @brief wechat 的oauth协议登录接口
 * @author nswe
 * @date 2016/4/27 7:41:04
 * @version 4.3

 * @update
 * @date 2018/6/21 0:48:47
 * @info 增加APP登录功能
 * @version 5.2

 * @update
 * @date 2019/3/28 13:19:06
 * @info 优化APP登录功能
 * @version 5.5
 */

/**
 * @class wechatOauth
 * @brief wechat的oauth协议接口
 */
class wechatOauth extends OauthBase
{
	private $AppID     = '';
	private $AppSecret = '';

	public function __construct($config)
	{
		if(IClient::isApp() == true)
		{
			$this->AppID     = isset($config['AppID_APP'])     ? $config['AppID_APP']     : "";
			$this->AppSecret = isset($config['AppSecret_APP']) ? $config['AppSecret_APP'] : "";
		}
		else
		{
			$this->AppID     = isset($config['AppID'])     ? $config['AppID']     : "";
			$this->AppSecret = isset($config['AppSecret']) ? $config['AppSecret'] : "";
		}
	}

	//后台可配置的参数
	public function getFields()
	{
		return array(
			'AppID' => array(
				'label' => 'AppID网站应用',
				'type'  => 'string',
			),
			'AppSecret'=>array(
				'label' => 'AppSecret网站应用',
				'type'  => 'string',
			),
			'AppID_APP' => array(
				'label' => 'AppID移动应用',
				'type'  => 'string',
			),
			'AppSecret_APP'=>array(
				'label' => 'AppSecret移动应用',
				'type'  => 'string',
			),
		);
	}

	//获取登录url地址
	public function getLoginUrl($url = '')
	{
	    //公众号环境
	    if(IClient::isWechat() == true)
	    {
	        $url = $url ? $url : "/ucenter/index";
	        $wechatObj = new wechat();
	        return $wechatObj->oauthUrl($url);
	    }
	    //APP环境
	    else if(IClient::isApp() == true)
	    {
	        $callbackUrl = parent::getReturnUrl();
	        $callbackUrl.= stripos($callbackUrl,"?") === false ? "?code=#code#" : "&code=#code#";
	        $failUrl     = IUrl::getHost().IUrl::creatUrl('/errors/error/message/#message#');
echo <<< OEF
<script>
//授权登录
function apiready()
{
    var wx = api.require('wx');
    wx.auth({}, function(ret, err)
    {
        if(ret.status)
        {
            var callbackUrl = "{$callbackUrl}";
            window.location.href = callbackUrl.replace("#code#",ret.code);
        }
        else
        {
            var failUrl = "{$failUrl}";
            var message = {"-1":"未知错误","1":"取消登录","2":"拒绝授权","3":"没有安装微信客户端"};
            window.location.href = failUrl.replace("#message#",message[err.code]);
        }
    });
}
</script>
OEF;
exit;
	    }
	    //其他环境
	    else
	    {
	        $url = $url ? IUrl::getHost().IUrl::creatUrl($url) : parent::getReturnUrl();
    		$urlparam = array(
    			"appid=".$this->AppID,
    			"redirect_uri=".urlencode($url),
    			"response_type=code",
    			"scope=snsapi_login",
    			"state=".rand(100,999),
    		);
    		$url = "https://open.weixin.qq.com/connect/qrconnect?".join("&",$urlparam)."#wechat_redirect";
    		return $url;
	    }
	}

	//获取进入令牌
	public function getAccessToken($parms)
	{
		$urlparam = array(
			"appid=".$this->AppID,
			"secret=".$this->AppSecret,
			"code=".$parms['code'],
			"grant_type=authorization_code",
		);
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?".join("&",$urlparam);

		//模拟post提交
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch);
		if($result)
		{
			$tokenInfo = JSON::decode($result);
			if(isset($tokenInfo['access_token']) && isset($tokenInfo['openid']))
			{
				//保存令牌
				ISession::set('access_token',$tokenInfo['access_token']);
				ISession::set('oauth_openid',$tokenInfo['openid']);
				return $tokenInfo;
			}
			else
			{
			    return false;
			}
		}
		else
		{
			die(curl_error($ch));
		}
	}

	//获取用户数据
	public function getUserInfo()
	{
		$accessToken = ISession::get('access_token');
		$openid      = ISession::get('oauth_openid');
		$urlparam    = array(
			'access_token='.$accessToken,
			'openid='.$openid,
		);
		//获取用户信息
		$apiUrl = "https://api.weixin.qq.com/sns/userinfo?";
		$apiUrl .= join("&",$urlparam);
		$json    = file_get_contents($apiUrl);
		if(stripos($json,"errcode") !== false)
		{
			return $json;
		}
		$userInfo = JSON::decode($json);

		//处理用户信息
		$unid = $userInfo['openid'];

		//当公众号和开发平台有多个应用会存在此 unionid,此时需要开放这里,可以让微信公众账号和微信OAuth平台同步用户信息
		$oauthUserDB = new IModel('oauth_user');
		$oldOauthUser= $oauthUserDB->getObj('oauth_user_id = "'.$unid.'" and oauth_id = 5');
		if($oldOauthUser && isset($userInfo['unionid']))
		{
			$oauthUserDB->setData(array('oauth_user_id' => $userInfo['unionid']));
			$oauthUserDB->update('id = '.$oldOauthUser['id']);
		}
		$unid = isset($userInfo['unionid']) ? $userInfo['unionid'] : $userInfo['openid'];
		$name = substr($unid,-8);

		//获取微信用户信息
		if(isset($userInfo['nickname']))
		{
			$wechatName = trim(preg_replace('/[\x{10000}-\x{10FFFF}]/u',"",$userInfo['nickname']));
			$name = $wechatName ? $wechatName : $name;
		}
		$sex = $userInfo['sex'];

		return array(
			'id'   => $unid,
			'name' => $name,
			'sex'  => $sex,
		);
	}

	public function checkStatus($parms)
	{
		if(isset($parms['code']))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}