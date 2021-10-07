<?php
/**
 * 新浪oauth2.0登录
 * @author nswe
 */
class Sina extends OauthBase
{
	function __construct($config)
	{
		ISession::set('apiKey',$config['apiKey']);
		ISession::set('apiSecret',$config['apiSecret']);
	}

	function getLoginUrl()
	{
		require_once(dirname(__FILE__)."/API/saetv2.ex.class.php");

		$o = new SaeTOAuthV2(ISession::get('apiKey') , ISession::get('apiSecret'));
		$code_url = $o->getAuthorizeURL(parent::getReturnUrl());

		return $code_url;
	}

	function checkStatus($parms)
	{
		return true;
	}

	function getAccessToken($parms)
	{
		require_once(dirname(__FILE__)."/API/saetv2.ex.class.php");

		$o = new SaeTOAuthV2(ISession::get('apiKey') , ISession::get('apiSecret'));
		$token = '';
		if(isset($_REQUEST['code']))
		{
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] = parent::getReturnUrl();
			try
			{
				$token = $o->getAccessToken('code',$keys) ;
			}
			catch (OAuthException $e)
			{}
		}

		if($token)
		{
			ISession::set('token',$token);
			setcookie( 'weibojs_'.$o->client_id, http_build_query($token) );
		}
	}

	function getUserInfo()
	{
		require_once(dirname(__FILE__)."/API/saetv2.ex.class.php");

		$token = ISession::get('token');
		$c = new SaeTClientV2(ISession::get('apiKey'),ISession::get('apiSecret'),$token['access_token'] );
		$ms  = $c->home_timeline();
		$uid_get = $c->get_uid();
		$uid = isset($uid_get['uid']) ? $uid_get['uid'] : "0";
		$user_message = $c->show_user_by_id($uid);
		if(isset($user_message['error']))
		{
			print_r($user_message);
			exit;
		}

		$userInfo = array();
		$userInfo['id']   = $uid;
		$userInfo['name'] = isset($user_message['screen_name']) ? $user_message['screen_name'] : '';

		return $userInfo;
	}

	public function getFields()
	{
		return array(
			'apiKey' => array(
				'label' => 'apiKey',
				'type'  => 'string',
			),
			'apiSecret' => array(
				'label' => 'apiSecret',
				'type'  => 'string',
			),
		);
	}
}
