<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file OauthCore.php
 * @brief oauth协议登录接口
 * @author chendeshan
 * @date 2017/1/18 18:29:40
 * @version 4.7
 *
 * @update
 * @info 增加OauthBase基类，所有oauth类都需要经过此类库
 * @date 2018/6/21 12:08:15
 * @version 5.2
 */

/**
 * @class OauthCore
 * @brief oauth协议接口
 */
class OauthCore
{
	//oauth对象实例化
	private $oauthObj = null;

	/**
	 * @brief 构造函数
	 * @param int $id oauthID参数
	 */
	public function __construct($id)
	{
		$oauthRow = $this->getOauthRow($id);
		if(!$oauthRow)
		{
			throw new IException("【ID:{$id}】的第三方登录方式不存在");
		}

		if($this->requireFile($oauthRow['file']))
		{
			$config   = unserialize($oauthRow['config']);
			$fileName = $oauthRow['file'];
			$this->oauthObj = new $fileName($config);
		}
		else
		{
			return false;
		}
	}

	//获取字段数据
	public function getFields()
	{
		return $this->oauthObj->getFields();
	}

	//回调函数
	public function checkStatus($parms)
	{
		return $this->oauthObj->checkStatus($parms);
	}

	//获取平台的用户信息
	public function getUserInfo()
	{
		return $this->oauthObj->getUserInfo();
	}

	//获取登录url地址
	public function getLoginUrl($url = '')
	{
		return $this->oauthObj->getLoginUrl($url);
	}

	//获取令牌数据
	public function getAccessToken($parms)
	{
		return $this->oauthObj->getAccessToken($parms);
	}

	//根据id值获取数据库中的数据
	private function getOauthRow($id)
	{
		$oauthObj = new IModel('oauth');
		return $oauthObj->getObj('id = '.$id);
	}

	//引入平台接口文件
	private function requireFile($fileName)
	{
		$classFile = IWeb::$app->getBasePath().'plugins/oauth/'.$fileName.'/'.$fileName.'.php';
		if(file_exists($classFile))
		{
			include_once($classFile);
			return true;
		}
		else
		{
			return false;
		}
	}
}

/**
 * @class Oauth
 * @brief oauth协议登录基础类
 */
abstract class OauthBase
{
	//获取回调URL地址
	protected function getReturnUrl()
	{
		$className = strtolower( get_class($this) );
		return IUrl::getHost().IUrl::creatUrl('/simple/oauth_callback/oauth_name/'.$className);
	}

	abstract public function getLoginUrl();
	abstract public function checkStatus($parms);
	abstract public function getAccessToken($parms);
	abstract public function getUserInfo();
	abstract public function getFields();
}