<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file qq.php
 * @brief qq的oauth协议登录接口
 * @author chendeshan
 * @date 2011-7-18 9:34:18
 * @version 0.6
 */

/**
 * @class QQ
 * @brief QQ的oauth协议接口
 */
class qq extends OauthBase
{
	private $apiId  = '';
	private $apiKey = '';

	public function __construct($config)
	{
		$this->apiId  = $config['apiId'];
		$this->apiKey = $config['apiKey'];
	}

	public function getFields()
	{
		return array(
			'apiId' => array(
				'label' => 'apiId',
				'type'  => 'string',
			),
			'apiKey'=> array(
				'label' => 'apiKey',
				'type'  => 'string',
			),
		);
	}

	//获取登录url地址
	public function getLoginUrl()
	{
		require_once(dirname(__FILE__)."/API/qqConnectAPI.php");
		$qc = new QC();
		return $qc->qq_login($this->apiId,urlencode(parent::getReturnUrl()));
	}

	//获取进入令牌
	public function getAccessToken($parms)
	{
		require_once(dirname(__FILE__)."/API/qqConnectAPI.php");
		$qc = new QC();
		$access_token = $qc->qq_callback($this->apiId,parent::getReturnUrl(),$this->apiKey);
		$openid = $qc->get_openid();
		ISession::set('openid',$openid);
		ISession::set('access_token',$access_token);
	}

	//获取用户数据
	public function getUserInfo()
	{
		require_once(dirname(__FILE__)."/API/qqConnectAPI.php");
		$qc = new QC(ISession::get('access_token'),ISession::get('openid'),$this->apiId);
		$arr = $qc->get_user_info();
		$userInfo         = array();
	    $userInfo['id']   = ISession::get('openid');
	    $userInfo['name'] = isset($arr['nickname']) ? $arr['nickname'] : '';
	    return $userInfo;
	}

	public function checkStatus($parms)
	{
		return true;
	}
}