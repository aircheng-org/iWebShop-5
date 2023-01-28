<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file _userInfo.php
 * @brief 用户注册登录插件
 * @author nswe
 * @date 2016/4/15 8:51:13
 * @version 4.4
 */
class _userInfo extends pluginBase
{
	//注册事件
	public function reg()
	{
		//用户登录后的操作回调
		plugin::reg("userLoginCallback",$this,"userLoginCallback");

		//注册页面拦截配置
		plugin::reg("onCreateView@simple@reg",$this,"initUserReg");
		plugin::reg("onCreateView@simple@bind_user",$this,"initUserReg");

		//用户注册方法
		plugin::reg("userRegAct",$this,"userRegAct");

		//手机注册验证码
		plugin::reg("onBeforeCreateAction@simple@_sendMobileCode",function(){
			self::controller()->_sendMobileCode = function(){$this->sendRegMobileCode();};
		});

		//邮箱验证
		plugin::reg("onBeforeCreateAction@simple@check_mail",function(){
			self::controller()->check_mail = function(){$this->check_mail();};
		});

		//用户登录校验
		plugin::reg("userLoginAct",$this,"userLoginAct");

		//登陆页面拦截
		plugin::reg('onCreateView@simple@login',function(){
			//记录callback地址
			$this->saveCallback();
		});

		//获取保存的路径地址
		plugin::reg("getCallback",function(){
		    $callback = IReq::get('callback') ? IReq::get('callback') : ICookie::get('callback');
			return htmlspecialchars_decode(IFilter::act($callback,'text'));
		});

		//保存的路径地址
		plugin::reg("setCallback",function($url){
			return ICookie::set('callback',$url);
		});

		//经验值更新
		plugin::reg('expUpdate',$this,"expUpdate");

		//注册成功后处理
		plugin::reg('userRegFinish',$this,'userRegFinishCallback');
	}

	//注册用户初始化
	public function initUserReg()
	{
		//记录callback地址
		$this->saveCallback();

		$siteObj = new Config('site_config');
		if($siteObj->reg_option == 2)
		{
			IError::show("网站当前已经关闭注册");
		}

		if($siteObj->reg_option == 3)
		{
			plugin::reg("onFinishView",function(){
				$this->view("mobileCheck");
			});
		}
	}

	//处理callback回调地址
	public static function saveCallback($url = '')
	{
	    if($url)
	    {
	        $callback = $url;
	    }
	    else
	    {
	        $callback = IReq::get('callback') ? htmlspecialchars(IReq::get('callback')) : IUrl::getRefRoute();
	    }

		$notCallback = array('/simple/reg','/simple/login','bind_user','oauth_callback','errors','/simple/index');
		if($callback)
		{
			foreach($notCallback as $key => $url)
			{
				if(stripos($callback,$url) !== false)
				{
					$callback = '';
				}
			}
			$callback ? ICookie::set('callback',rtrim($callback,"/")) : '';
		}
	}

	//用户登录
	public function userLoginAct()
	{
    	$login_info = IFilter::act(IReq::get('login_info','post'));
    	$password   = IReq::get('password','post');
    	$remember   = IFilter::act(IReq::get('remember','post'));

    	if($login_info == '')
    	{
    		return '请填写用户名，邮箱，手机号';
    	}

		if(!preg_match('|\S{6,32}|',$password))
    	{
    		return '密码格式不正确,请输入6-32个字符';
    	}

    	$password = md5($password);

		if($userRow = _authorization::isValidUser($login_info,$password))
		{
			$this->userLoginCallback($userRow);

			//记住帐号
			if($remember == 1)
			{
				ICookie::set('loginName',$login_info);
			}
			return $userRow;
		}
		return "账号或密码错误";
	}

	//用户注册
	public function userRegAct()
	{
		$email      = IFilter::act(IReq::get('email','post'));
		$mobile     = IFilter::act(IReq::get('mobile','post'));
		$mobile_code= IFilter::act(IReq::get('mobile_code','post'));
    	$username   = IFilter::act(IReq::get('username','post'));
    	$password   = IReq::get('password','post');
    	$repassword = IReq::get('repassword','post');
    	$captcha    = IFilter::act(IReq::get('captcha','post'));
    	$_captcha   = ISafe::get('captcha');

    	//获取注册配置参数
		$siteConfig = new Config('site_config');
		$reg_option = $siteConfig->reg_option;

		/*注册信息校验*/
		if($reg_option == 2)
		{
			return "当前网站禁止新用户注册";
		}

    	if(!preg_match('|\S{6,32}|',$password))
    	{
    		return "密码是字母，数字，下划线组成的6-32个字符";
    	}

    	if($password != $repassword)
    	{
    		return "2次密码输入不一致";
    	}

    	if($reg_option != 3 && (!$_captcha || !$captcha || $captcha != $_captcha))
    	{
    		return "图形验证码输入不正确";
    	}

		//邮箱验证
		if($reg_option == 1)
		{
			if(IValidate::email($email) == false)
			{
				return "邮箱格式不正确";
			}
			$memberObj = new IModel('member as m,user as u');
			$memberRow = $memberObj->getObj('m.user_id = u.id and m.email = "'.$email.'"',"u.*,m.status");
			if($memberRow)
			{
				//再次发送激活邮件
				if($memberRow['status'] == 3)
				{
					$memberRow['msg'] = "您的邮箱验证邮件已发送到{$email}！请到您的邮箱中去激活";
					$emailResult = $this->send_check_mail($email);
					return $emailResult === true ? $memberRow : $emailResult;
				}
				else
				{
					return "邮箱已经被注册";
				}
			}
		}
		//手机验证
		else if($reg_option == 3)
		{
			if(IValidate::mobi($mobile) == false)
			{
				return "手机号格式不正确";
			}

			$_mobileCode = ISafe::get('code'.$mobile);
			if(!$mobile_code || !$_mobileCode || $mobile_code != $_mobileCode)
			{
				return "手机号验证码不正确";
			}

			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('mobile = "'.$mobile.'"');
			if($memberRow)
			{
				return "手机号已经被注册";
			}
		}

		//用户名检查
    	if(IValidate::name($username) == false)
    	{
    		return "用户名必须是由2-20个字符，可以为字母、数字、下划线和中文";
    	}
    	else
    	{
			$userObj = new IModel('user');
			$userRow = $userObj->getObj('username = "'.$username.'"');
			if($userRow)
			{
				return "用户名已经被注册";
			}
    	}

		//插入user表
		$userArray = array(
			'username' => $username,
			'password' => md5($password),
		);
		$userObj->setData($userArray);
		$user_id = $userObj->add();
		if(!$user_id)
		{
			$userObj->rollback();
			return "用户创建失败";
		}
		$userArray['id'] = $user_id;
		$userArray['head_ico'] = "";

		//插入member表
		$memberArray = array(
			'user_id' => $user_id,
			'time'    => ITime::getDateTime(),
			'status'  => $reg_option == 1 ? 3 : 1,
			'mobile'  => $mobile,
			'email'   => $email,
		);
		$memberObj = new IModel('member');
		$memberObj->setData($memberArray);
		$memberObj->add();

		//通知事件用户注册完毕
		plugin::trigger("userRegFinish",$userArray);

		//邮箱激活帐号
		if($reg_option == 1)
		{
			$userArray['msg'] = "您的邮箱验证邮件已发送到{$email}！请到您的邮箱中去激活";
			$emailResult = $this->send_check_mail($email);
			return $emailResult === true ? $userArray : $emailResult;
		}
		else if($reg_option == 3)
		{
			ISafe::clear('code'.$mobile);
		}

		$this->userLoginCallback($userArray);
		return $userArray;
	}

	//发送注册验证码
	public function sendRegMobileCode()
	{
		$mobile   = IReq::get('mobile');
		$captcha  = IReq::get('captcha');
		$_captcha = ISafe::get('captcha');

		if(IValidate::mobi($mobile) == false)
		{
			die("请填写正确的手机号码");
		}
		if(!$captcha || !$_captcha || $captcha != $_captcha)
		{
			die("请填写正确的图形验证码");
		}

		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('mobile = "'.$mobile.'"');
		if($memberRow)
		{
			die("手机号已经被注册");
		}

		$mobile_code = rand(1000,9999);
		$result = _hsms::checkCode($mobile,array('{mobile_code}' => $mobile_code));
		if($result == 'success')
		{
			//删除图形验证码防止重复提交
			ISafe::set('captcha','');
			ISafe::set("code".$mobile,$mobile_code);
		}
		else
		{
			die($result);
		}
	}

	/**
	 * @brief 用户登录
	 * @param array $userRow 用户信息登录
	 */
	public function userLoginCallback($userRow)
	{
		//用户私密数据
		ISafe::set('user_id',$userRow['id']);
		ISafe::set('username',$userRow['username']);
		ISafe::set('user_pwd',$userRow['password']);
		ISafe::set('head_ico',isset($userRow['head_ico']) ? $userRow['head_ico'] : '');
		ISafe::set('last_login',isset($userRow['last_login']) ? $userRow['last_login'] : '');

		//延迟写入cache数据
		$cacheObj = new ICache('file');
		$cacheObj->set("commission".$userRow['id'],ICookie::get('invite_goods'));
		ICookie::clear('invite_goods');

		//更新最后一次登录时间
		$memberObj = new IModel('member');
		$dataArray = array(
			'last_login' => ITime::getDateTime(),
		);
		$memberObj->setData($dataArray);
		$where     = 'user_id = '.$userRow["id"];

		//会员组更新
		$this->expUpdate($userRow['id']);
		$memberObj->update($where);
	}


	/**
	 * @brief 发送验证邮箱邮件
	 * @param $email string 邮箱地址
	 */
	public function send_check_mail($email)
	{
		if(IValidate::email($email) == false)
		{
			return '邮件格式错误';
		}

		$memberDB  = new IModel('member');
		$memberRow = $memberDB->getObj('email = "'.$email.'"');
		if(!$memberRow)
		{
			return '用户信息不存在';
		}
		$code    = base64_encode($memberRow['email']."|".$memberRow['user_id']);
		$url     = IUrl::getHost().IUrl::creatUrl("/simple/check_mail/code/{$code}");
		$content = mailTemplate::checkMail(array("{url}" => $url));

		//发送邮件
		$smtp   = new SendMail();
		$result = $smtp->send($email,"用户注册邮箱验证",$content);
		if($result===false)
		{
			return "发信失败,请重试！或者联系管理员查看邮件服务是否开启";
		}
		return true;
	}

	/**
	 * @brief 验证邮箱
	 */
	public function check_mail()
	{
		$code = IReq::get("code");
		list($email,$user_id) = explode('|',base64_decode($code));
		if(IValidate::email($email) == false)
		{
			$message = "邮箱格式不正确";
		}
		else
		{
			$email   = IFilter::act($email);
			$user_id = IFilter::act($user_id,'int');

			$memberObj = new IModel("member");
			$memberRow = $memberObj->getObj(" email = '{$email}' and user_id = ".$user_id );
			if($memberRow)
			{
				//更新用户状态
				$memberObj->setData(array("status" => 1));
				$memberObj->update("user_id = ".$user_id);

				//获取用户信息
				$userObj = new IModel('user');
				$userRow = $userObj->getObj('id = '.$user_id);
				$message = "恭喜，您的邮箱激活成功！";
				$this->userLoginCallback($userRow);
			}
			else
			{
				$message = "验证信息有误，请核实！";
			}
		}
		self::controller()->redirect('/site/success?message='.urlencode($message));
	}

	/**
	 * @brief 经验值更新
	 * @param $user_id int 用户ID
	 * @param $exp     int 经验值
	 */
	public function expUpdate($user_id,$exp = 0)
	{
	    $user_id   = IFilter::act($user_id,'int');
		$memberObj = new IModel('member');
		$memberObj->setData(array('exp' => 'exp + '.$exp));
		$memberObj->update('user_id = '.$user_id,'exp');

		//根据经验值查询晋升的会员组
		$memberRow = $memberObj->getObj('user_id = '.$user_id,'exp');
		if(!$memberRow)
		{
		    return '用户信息不存在';
		}

		if($memberRow['exp'] < 0)
		{
			$memberObj->setData(array('exp' => 0));
			$memberObj->update('user_id = '.$user_id,'exp');
			$memberRow['exp'] = 0;
		}

		$groupObj  = new IModel('user_group');
		$groupRow  = $groupObj->query($memberRow['exp'].' between minexp and maxexp and maxexp > 0','id','discount asc',1);
		$updateData = array('group_id' => 0);
		if($groupRow && $groupRow = current($groupRow))
		{
			$updateData['group_id'] = $groupRow['id'];
		}

		//如果是经验不减少则保留会员组
		if($exp >= 0 && $updateData['group_id'] == 0)
		{
			return;
		}
		$memberObj->setData($updateData);
		$memberObj->update('user_id = '.$user_id);
	}

	/**
	 * @brief 注册成功后的回调处理
	 * @param array $userArray user表的模型数据
	 */
	public function userRegFinishCallback($userArray)
	{
		//促销活动注册领取
		$proObj  = new ProRule();
		$proList = $proObj->regPromotion();
		if($proList)
		{
			foreach($proList as $val)
			{
				$proObj->setAwardByIds($val['id'],$userArray['id']);
			}
		}
	}
}