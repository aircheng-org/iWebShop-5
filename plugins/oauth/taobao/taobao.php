<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file taobao.php
 * @brief taobao的oauth协议登录接口
 * @author chendeshan
 * @date 2011-7-18 9:34:18
 * @version 0.6
 */

/**
 * @class Taobao
 * @brief taobao的oauth协议接口
 */
class Taobao extends OauthBase
{
	private $apiKey    = '';
	private $apiSecret = '';

	public function __construct($config)
	{
		$this->apiKey    = $config['apiKey'];
		$this->apiSecret = $config['apiSecret'];
	}

	public function getFields()
	{
		return array(
			'apiKey' => array(
				'label' => 'apiKey',
				'type'  => 'string',
			),
			'apiSecret'=>array(
				'label' => 'apiSecret',
				'type'  => 'string',
			),
		);
	}

	//获取登录url地址
	public function getLoginUrl()
	{
		$url  = 'https://oauth.taobao.com/authorize?response_type=code';
		$url .= '&client_id='.$this->apiKey;
		$url .= '&redirect_uri='.urlencode(parent::getReturnUrl());
		return $url;
	}

	//获取进入令牌
	public function getAccessToken($parms)
	{
		$url           = 'https://oauth.taobao.com/token';
		$urlParmsArray = array(
			'grant_type'   => 'authorization_code',
			'code'         => $parms['code'],
			'redirect_uri' => urlencode(parent::getReturnUrl()),
			'client_id'    => $this->apiKey,
			'client_secret'=> $this->apiSecret,
		);

		//模拟post提交
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($urlParmsArray));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$tokenInfo = JSON::decode(curl_exec($ch));

		if(!isset($tokenInfo['access_token']))
		{
			die(var_export($tokenInfo));
		}
		ISession::set('taobao_user_nick',urldecode($tokenInfo['taobao_user_nick']));
		ISession::set('taobao_user_id',isset($tokenInfo['taobao_user_id']) ? $tokenInfo['taobao_user_id'] : $tokenInfo['taobao_open_uid']);
	}

	//获取用户数据
	public function getUserInfo()
	{
		$userInfo = array();
		$userInfo['id']   = ISession::get('taobao_user_id');
		$userInfo['name'] = ISession::get('taobao_user_nick');
		$userInfo['sex']  = 1;
		return $userInfo;
	}

	public function checkStatus($parms)
	{
		if(isset($parms['error']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}