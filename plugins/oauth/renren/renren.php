<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file renren.php
 * @brief renren的oauth协议登录接口
 * @author chendeshan
 * @date 2011-7-18 9:34:18
 * @version 0.6
 */

/**
 * @class Renren
 * @brief Renren的oauth协议接口
 */
class Renren extends OauthBase
{
	private $apiKey = '';
	private $Secret = '';

	public function __construct($config)
	{
		$this->apiKey = isset($config['APIKey']) ? $config['APIKey'] : '';
		$this->Secret = isset($config['Secret']) ? $config['Secret'] : '';
	}

	public function getFields()
	{
		return array(
			'APIKey' => array(
				'label' => 'APIKey',
				'type'  => 'string',
			),
			'Secret' => array(
				'label' => 'Secret',
				'type'  => 'string',
			),
		);
	}

	//获取登录url地址
	public function getLoginUrl()
	{
		$loginUrl  = 'https://graph.renren.com/oauth/authorize?response_type=code';
		$loginUrl .= '&client_id='.$this->apiKey;
		$loginUrl .= '&display=page';
		$loginUrl .= '&redirect_uri='.urlencode(parent::getReturnUrl());

		return $loginUrl;
	}

	//检查返回值
	public function checkStatus($parm)
	{
		if(isset($parm['error']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	//获取令牌数据
	public function getAccessToken($parm)
	{
		$accessTokenUrl = $this->getAccessTokenUrl($parm);
		if($accessTokenUrl)
		{
	    	$accessToken    = file_get_contents($accessTokenUrl);
	    	$tokenArray     = JSON::decode($accessToken);
	    	if(isset($tokenArray['access_token']))
	    	{
	    		ISession::set('access_token',$tokenArray['access_token']);
	    	}
	    	else
	    	{
	    		die($accessToken);
	    	}
		}
		else
		{
			return false;
		}
	}

	//获取用户信息
	public function getUserInfo()
	{
		$apiUrl   = 'http://api.renren.com/restserver.do';
		$parms    = array(
			'access_token' => ISession::get('access_token'),
			'call_id'      => time(),
			'method'       => 'users.getInfo',
			'v'            => '1.0',
			'format'       => 'json',
		);
		$sign = $this->createSign($parms);
		$parms['sig'] = $sign;

		$userInfo = array();

		//模拟post提交
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$apiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parms));
		$renrenUser = JSON::decode(curl_exec($ch));

		$userInfo['id']   = isset($renrenUser[0]['uid'])  ? $renrenUser[0]['uid']  : '';
		$userInfo['name'] = isset($renrenUser[0]['name']) ? $renrenUser[0]['name'] : '';
		$userInfo['sex']  = isset($renrenUser[0]['sex'])  ? ($renrenUser[0]['sex'] == 1 ? 1 : 2) : '';

		return $userInfo;
	}

	//获取令牌url地址
	private function getAccessTokenUrl($parm)
	{
		$accessTokenUrl  = 'http://graph.renren.com/oauth/token?';
		$accessTokenUrl .= 'client_id='.$this->apiKey;
		$accessTokenUrl .= '&client_secret='.$this->Secret;
		$accessTokenUrl .= '&redirect_uri='.urlencode(parent::getReturnUrl());
		$accessTokenUrl .= '&grant_type=authorization_code';
		$accessTokenUrl .= '&code='.$parm['code'];

		return $accessTokenUrl;
	}

	//计算sign md5值
	private function createSign($parms)
	{
		$sendStr = '';
		ksort($parms);
		foreach($parms as $key => $val)
		{
			$sendStr .= $key.'='.$val;
		}
		$sendStr .= $this->Secret;
		return md5($sendStr);
	}
}