<?php
/**
 * @brief 会员模块
 * @class Member
 * @note  后台
 */
class Member extends IController implements adminAuthorization
{
	public $checkRight  = 'all';
    public $layout='admin';
	private $data = array();

	function init()
	{

	}

	/**
	 * @brief 添加会员
	 */
	function member_edit()
	{
		$uid  = IFilter::act(IReq::get('uid'),'int');
		$userData = array();

		//编辑会员信息读取会员信息
		if($uid)
		{
			$userData = Api::run('getMemberInfo',$uid);
			if(!$userData)
			{
				$this->member_list();
				Util::showMessage("没有找到相关记录！");
				exit;
			}
		}
		$this->setRenderData(array('userData' => $userData));
		$this->redirect('member_edit');
	}

	//保存会员信息
	function member_save()
	{
		$user_id    = IFilter::act(IReq::get('user_id'),'int');
		$user_name  = IFilter::act(IReq::get('username'));
		$email      = IFilter::act(IReq::get('email'));
		$password   = IReq::get('password');
		$repassword = IReq::get('repassword');
		$group_id   = IFilter::act(IReq::get('group_id'),'int');
		$truename   = IFilter::act(IReq::get('true_name'));
		$sex        = IFilter::act(IReq::get('sex'),'int');
		$telephone  = IFilter::act(IReq::get('telephone'));
		$mobile     = IFilter::act(IReq::get('mobile'));
		$birthday   = IFilter::act(IReq::get('birthday'));
		$province   = IFilter::act(IReq::get('province'),'int');
		$city       = IFilter::act(IReq::get('city'),'int');
		$area       = IFilter::act(IReq::get('area'),'int');
		$contact_addr = IFilter::act(IReq::get('contact_addr'));
		$zip        = IFilter::act(IReq::get('zip'));
		$qq         = IFilter::act(IReq::get('qq'));
		$exp        = IFilter::act(IReq::get('exp'),'int');
		$point      = IFilter::act(IReq::get('point'),'int');
		$status     = IFilter::act(IReq::get('status'),'int');

		$_POST['area'] = "";
		if($province && $city && $area)
		{
			$_POST['area'] = ",{$province},{$city},{$area},";
		}

		if(!$user_id && $password == '')
		{
			$this->setError('请输入密码！');
		}

		if($password != $repassword)
		{
			$this->setError('两次输入的密码不一致！');
		}

		//创建会员操作类
		$userDB   = new IModel("user");
		$memberDB = new IModel("member");

		if($userDB->getObj("username='".$user_name."' and id != ".$user_id))
		{
			$this->setError('用户名重复');
		}

		if($email && $memberDB->getObj("email='".$email."' and user_id != ".$user_id))
		{
			$this->setError('邮箱重复');
		}

		if($mobile && $memberDB->getObj("mobile='".$mobile."' and user_id != ".$user_id))
		{
			$this->setError('手机号码重复');
		}

		//操作失败表单回填
		if($errorMsg = $this->getError())
		{
			$this->setRenderData(array('userData' => $_POST));
			$this->redirect('member_edit',false);
			Util::showMessage($errorMsg);
		}

		$member = array(
			'email'        => $email,
			'true_name'    => $truename,
			'telephone'    => $telephone,
			'mobile'       => $mobile,
			'area'         => $_POST['area'],
			'contact_addr' => $contact_addr,
			'qq'           => $qq,
			'sex'          => $sex,
			'zip'          => $zip,
			'exp'          => $exp,
			'point'        => $point,
			'group_id'     => $group_id,
			'status'       => $status,
			'birthday'     => $birthday,
		);

		//添加新会员
		if(!$user_id)
		{
			$user = array(
				'username' => $user_name,
				'password' => md5($password),
			);
			$userDB->setData($user);
			$user_id = $userDB->add();

			$member['user_id'] = $user_id;
			$member['time']    = ITime::getDateTime();

			$memberDB->setData($member);
			$memberDB->add();
		}
		//编辑会员
		else
		{
			$user = array(
				'username' => $user_name,
			);
			//修改密码
			if($password)
			{
				$user['password'] = md5($password);
			}
			$userDB->setData($user);
			$userDB->update('id = '.$user_id);

			$member_info = $memberDB->getObj('user_id='.$user_id);

			//修改积分记录日志
			if($point != $member_info['point'])
			{
				$ctrlType = $point > $member_info['point'] ? '增加' : '减少';
				$diffPoint= $point-$member_info['point'];

				$pointObj = new Point();
				$pointConfig = array(
					'user_id' => $user_id,
					'point'   => $diffPoint,
					'log'     => '管理员'.$this->admin['admin_name'].'将积分'.$ctrlType.$diffPoint.'积分',
				);
				$pointObj->update($pointConfig);
			}

			$memberDB->setData($member);
			$memberDB->update("user_id = ".$user_id);
		}
		$this->redirect('member_list');
	}

	/**
	 * @brief 会员列表
	 */
	function member_list()
	{
		$search = IFilter::act(IReq::get('search'),'strict');
		$keywords = IFilter::act(IReq::get('keywords'));
		$where = ' 1 ';
		if($search && $keywords)
		{
			$where .= " and $search like '%{$keywords}%' ";
		}
		$this->data['search'] = $search;
		$this->data['keywords'] = $keywords;
		$tb_user_group = new IModel('user_group');
		$data_group = $tb_user_group->query();
		$group      = array();
		foreach($data_group as $value)
		{
			$group[$value['id']] = $value['group_name'];
		}
		$this->data['group'] = $group;
		$this->setRenderData($this->data);
        $page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query = new IQuery("user as u");
        $query->join   = 'left join member as m on m.user_id = u.id';
        $query->where  = 'm.status != 2 and '.$where;
        $query->fields = 'm.*,u.username';
        $query->order  = 'm.user_id desc';
        $query->page   = $page;
        $this->query   = $query;
		$this->redirect('member_list');
	}

	/**
	 * 用户预存款管理页面
	 */
	function member_balance()
	{
		$this->layout = '';
		$this->redirect('member_balance');
	}
	/**
	 * @brief 删除至回收站
	 */
	function member_reclaim()
	{
		$user_ids = IReq::get('check');
		$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
		$user_ids = IFilter::act($user_ids,'int');
		if($user_ids)
		{
			$ids = implode(',',$user_ids);
			if($ids)
			{
				$tb_member = new IModel('member');
				$tb_member->setData(array('status'=>'2'));
				$where = "user_id in (".$ids.")";
				$tb_member->update($where);
			}
		}
		$this->member_list();
	}
	//批量用户预存款操作
    function member_recharge()
    {
    	$id       = IFilter::act(IReq::get('check'),'int');
    	$balance  = IFilter::act(IReq::get('balance'),'float');
    	$type     = IFIlter::act(IReq::get('type')); //操作类型 recharge充值,withdraw提现金
    	$even     = '';

    	if(!$id)
    	{
			die(JSON::encode(array('flag' => 'fail','message' => '请选择要操作的用户')));
			return;
    	}

    	//执行写入操作
    	$id = is_array($id) ? join(',',$id) : $id;
    	$memberDB = new IModel('member');
    	$memberData = $memberDB->query('user_id in ('.$id.')');

		foreach($memberData as $value)
		{
			//用户预存款进行的操作记入account_log表
			$log = new AccountLog();
			$config=array
			(
				'user_id'  => $value['user_id'],
				'admin_id' => $this->admin['admin_id'],
				'event'    => $type,
				'num'      => $balance,
				'way'      => '后台手动',
			);
			$re = $log->write($config);
			if($re == false)
			{
				die(JSON::encode(array('flag' => 'fail','message' => $log->error)));
			}
		}
		die(JSON::encode(array('flag' => 'success')));
    }
	/**
	 * @brief 用户组添加
	 */
	function group_edit()
	{
		$gid = (int)IReq::get('gid');
		//编辑会员等级信息 读取会员等级信息
		if($gid)
		{
			$db = new IModel('user_group');
			$group_info = $db->getObj($gid);

			if($group_info)
			{
				$this->data['group'] = $group_info;
			}
			else
			{
				$this->redirect('group_list',false);
				Util::showMessage("没有找到相关记录！");
				return;
			}
		}
		$this->setRenderData($this->data);
		$this->redirect('group_edit');
	}

	/**
	 * @brief 保存用户组修改
	 */
	function group_save()
	{
		$group_id = IFilter::act(IReq::get('id'),'int');
		$maxexp   = IFilter::act(IReq::get('maxexp'),'int');
		$minexp   = IFilter::act(IReq::get('minexp'),'int');
		$discount = IFilter::act(IReq::get('discount'),'float');
		$group_name = IFilter::act(IReq::get('group_name'));

		$group = array(
			'maxexp' => $maxexp,
			'minexp' => $minexp,
			'discount' => $discount,
			'group_name' => $group_name
		);

		if($discount > 100)
		{
			$errorMsg = '折扣率不能大于100';
		}

		if($maxexp <= $minexp)
		{
			$errorMsg = '最大经验值必须大于最小经验值';
		}

		if(isset($errorMsg) && $errorMsg)
		{
			$group['id'] = $group_id;
			$data = array($group);

			$this->setRenderData($data);
			$this->redirect('group_edit',false);
			Util::showMessage($errorMsg);
			exit;
		}
		$tb_user_group = new IModel("user_group");
		$tb_user_group->setData($group);

		if($group_id)
		{
			$affected_rows = $tb_user_group->update("id=".$group_id);
			$this->redirect('group_list');
		}
		else
		{
			$tb_user_group->add();
			$this->redirect('group_list');
		}
	}

	/**
	 * @brief 删除会员组
	 */
	function group_del()
	{
		$group_ids = IReq::get('check');
		$group_ids = is_array($group_ids) ? $group_ids : array($group_ids);
		$group_ids = IFilter::act($group_ids,'int');
		if($group_ids)
		{
			$ids = implode(',',$group_ids);
			if($ids)
			{
				$tb_user_group = new IModel('user_group');
				$where = "id in (".$ids.")";
				$tb_user_group->del($where);
			}
		}
		$this->redirect('group_list');
	}

	/**
	 * @brief 回收站
	 */
	function recycling()
	{
		$tb_user_group = new IModel('user_group');
		$data_group    = $tb_user_group->query();
		$group         = array();
		foreach($data_group as $value)
		{
			$group[$value['id']] = $value['group_name'];
		}
		$this->data['group'] = $group;
		$this->setRenderData($this->data);
        $page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query = new IQuery("member as m");
        $query->join   = 'left join user as u on m.user_id = u.id left join user_group as gp on m.group_id = gp.id';
        $query->where  = 'm.status = 2';
        $query->fields = 'm.*,u.username,gp.group_name';
        $query->order  = 'm.user_id desc';
        $query->page   = $page;
        $this->query   = $query;
		$this->redirect('recycling');
	}

	/**
	 * @brief 彻底删除会员
	 */
	function member_del()
	{
		$user_ids = IReq::get('check');
		$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
		$user_ids = IFilter::act($user_ids,'int');
		if($user_ids)
		{
			$ids = implode(',',$user_ids);

			if($ids)
			{
				$tb_member = new IModel('member');
				$where = "user_id in (".$ids.")";
				$tb_member->del($where);

				$tb_user = new IModel('user');
				$where = "id in (".$ids.")";
				$tb_user->del($where);

				$logObj = new log('db');
				$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除了用户","被删除的用户ID为：".$ids));
			}
		}
		$this->redirect('member_list');
	}

	/**
	 * @brief 从回收站还原会员
	 */
	function member_restore()
	{
		$user_ids = IReq::get('check');
		$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
		if($user_ids)
		{
			$user_ids = IFilter::act($user_ids,'int');
			$ids = implode(',',$user_ids);
			if($ids)
			{
				$tb_member = new IModel('member');
				$tb_member->setData(array('status'=>'1'));
				$where = "user_id in (".$ids.")";
				$tb_member->update($where);
			}
		}
		$this->redirect('recycling');
	}

	//[提现管理] 删除ajax
	function withdraw_del()
	{
		$id = IFilter::act(IReq::get('id'));

		if($id)
		{
			$id = IFilter::act($id,'int');
			$withdrawObj = new IModel('withdraw');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
			}
			else
			{
				$where = 'id = '.$id;
			}
			$withdrawObj->del($where);
		}
	}

	//[提现管理] 详情展示
	function withdraw_detail()
	{
		$id = IFilter::act( IReq::get('id'),'int' );

		if($id)
		{
			$withdrawObj = new IModel('withdraw');
			$this->withdrawRow = $withdrawObj->getObj($id);

			$userDB = new IModel('user as u,member as m');
			$this->userRow = $userDB->getObj('u.id = m.user_id and u.id = '.$this->withdrawRow['user_id']);
			$this->redirect('withdraw_detail');
		}
		else
		{
			$this->redirect('withdraw_list');
		}
	}

	//用户提现操作
	function pay_countfee()
	{
		set_time_limit(0);
		ini_set("max_execution_time",0);
		$ids  = IFilter::act(IReq::get('ids'),'int');
		$type = IFilter::act(IReq::get('type'));

		if(!$ids)
		{
			die('没有选择要转款的用户');
		}

		$withdrawObj = new IModel('withdraw');
		$memberDB    = new IModel('member');
		$error       = '';
		$successNum  = 0;
		$failNum     = 0;
		foreach($ids as $id)
		{
			$withdrawRow = $withdrawObj->getObj($id);
			$memberRow   = $memberDB->getObj('user_id = '.$withdrawRow['user_id'],'balance');
			if($memberRow['balance'] < $withdrawRow['amount'])
			{
				$failNum++;
				$error .= $withdrawRow['name'].'预存款余额不足';
				continue;
			}

			$payCheck = false;
			$billNo   = 'T'.Order_Class::createOrderNum();
			switch($type)
			{
				//微信余额
				case "wechatBalance":
				{
					include_once(dirname(__FILE__)."/../plugins/transfer/wechatBalance.php");
					$oauthUserDB = new IModel('oauth_user');

					//用户有绑定的openid参数
					$relationRow = $oauthUserDB->getObj('user_id = '.$withdrawRow['user_id']);
					if($relationRow && ($relationRow['openid'] || $relationRow['openid_mini']))
					{
						if($relationRow['openid'])
						{
							$openid = $relationRow['openid'];
							$payment_id = wechatBalance::WAP_WECHAT_PAYMENT_ID;
						}
						else
						{
							$openid = $relationRow['openid_mini'];
							$payment_id = wechatBalance::MINI_WECHAT_PAYMENT_ID;
						}

						//引入微信支付转账接口
						$data = [
							'openid'           => $openid,
							're_user_name'     => $withdrawRow['name'],
							'amount'           => $withdrawRow['amount'],
							'desc'             => IWeb::$app->getController()->_siteConfig->name.'预存款余额提现',
							'partner_trade_no' => $billNo,
							'payment_id'       => $payment_id,
						];

						$transferObj = new wechatBalance();
						$tranResult = $transferObj->run($data);
						if(is_array($tranResult) && $tranResult['result_code'] == 'SUCCESS')
						{
							$PayNo    = $tranResult['payment_no'];
							$payCheck = true;
						}
						else
						{
							$error .= $tranResult;
						}
					}
					else
					{
						$error .= "用户ID：[".$withdrawRow['name']."] 没有绑定微信";
					}
				}
				break;

				//人工线下
				case "offline":
				{
					$PayNo    = '88888888';
					$payCheck = true;
				}
				break;
			}

			if($payCheck == true)
			{
				//用户预存款进行的操作记入account_log表
				$log    = new AccountLog();
				$config = [
					'user_id'  => $withdrawRow['user_id'],
					'admin_id' => $this->admin['admin_id'],
					'event'    => "withdraw",
					'num'      => $withdrawRow['amount'],
					'way'      => AccountLog::way($type),
				];
				$result = $log->write($config);
				if($result == false)
				{
					$failNum++;
					$error .= "危险：微信转账成功，但是用户".$withdrawRow['name']."没有扣款。".$result->error;
				}
				else
				{
					$successNum++;

					$withdrawObj->setData(['way' => $config['way'],'status' => 2,'pay_no' => $PayNo,'finish_time' => ITime::getDateTime()]);
					$withdrawObj->update($id);

					//发送事件
					plugin::trigger('withdrawStatusUpdate',$id);
				}
			}
			else
			{
				$failNum++;
			}
		}

		die('成功：'.$successNum.'个; 失败：'.$failNum.'个; '.$error);
	}

	//[提现管理] 修改提现申请的状态
	function withdraw_status()
	{
		$id      = IFilter::act( IReq::get('id'),'int');
		$re_note = IFilter::act( IReq::get('re_note'),'string');
		$status  = IFilter::act(IReq::get('status'),'int');

		if($id)
		{
			$withdrawObj = new IModel('withdraw');
			$withdrawObj->setData([
				'status'  => $status,
				're_note' => $re_note,
			]);
			$withdrawObj->update($id);
		}

		$this->redirect('withdraw_list');
	}

    //获取商户列表
    public function seller_list()
    {
        $where = Util::search(IReq::get('search'));
        $page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query = new IQuery("seller");
        $query->where = 'is_del = 0 and '.$where;
        $query->order = 'id desc';
        $query->page  = $page;
        $this->query  = $query;
        $this->redirect('seller_list');
    }

	/**
	 * @brief 商家修改页面
	 */
	public function seller_edit()
	{
		$seller_id = IFilter::act(IReq::get('id'),'int');

		//修改页面
		if($seller_id)
		{
			$sellerDB        = new IModel('seller');
			$this->sellerRow = $sellerDB->getObj('id = '.$seller_id);
		}
		$this->redirect('seller_edit');
	}

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_add()
	{
		$seller_id   = IFilter::act(IReq::get('id'),'int');
		$seller_name = IFilter::act(IReq::get('seller_name'));
		$email       = IFilter::act(IReq::get('email'));
		$password    = IFilter::act(IReq::get('password'));
		$repassword  = IFilter::act(IReq::get('repassword'));
		$truename    = IFilter::act(IReq::get('true_name'));
		$phone       = IFilter::act(IReq::get('phone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$cash        = IFilter::act(IReq::get('cash'),'float');
		$is_vip      = IFilter::act(IReq::get('is_vip'),'int');
		$is_lock     = IFilter::act(IReq::get('is_lock'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$account     = IFilter::act(IReq::get('account'));
		$server_num  = IFilter::act(IReq::get('server_num'));
		$home_url    = IFilter::act(IReq::get('home_url'));
		$sort        = IFilter::act(IReq::get('sort'),'int');
		$discount     = IFilter::act(IReq::get('discount'),'float');

		if(!$seller_id && $password == '')
		{
			$errorMsg = '请输入密码！';
		}

		if($password != $repassword)
		{
			$errorMsg = '两次输入的密码不一致！';
		}

		//创建商家操作类
		$sellerDB = new IModel("seller");

		if($sellerDB->getObj("seller_name = '{$seller_name}' and id != {$seller_id}"))
		{
			$errorMsg = "登录用户名重复";
		}
		else if($sellerDB->getObj("true_name = '{$truename}' and id != {$seller_id}"))
		{
			$errorMsg = "商户真实全称重复";
		}

		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->sellerRow = $_POST;
			$this->redirect('seller_edit',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
			'true_name' => $truename,
			'account'   => $account,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'is_vip'    => $is_vip,
			'is_lock'   => $is_lock,
			'cash'      => $cash,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'server_num'=> $server_num,
			'home_url'  => $home_url,
			'sort'      => $sort,
		    'discount'   => $discount,
		);

		//附件上传$_FILE
		if($_FILES)
		{
		    $uploadDir = IWeb::$app->config['upload'].'/seller';
			$uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();

			//商户资质上传
			if(isset($photoInfo['paper_img']['img']) && $photoInfo['paper_img']['img'])
			{
				$sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
			}

			//logo图片处理
			if(isset($photoInfo['logo']['img']) && $photoInfo['logo']['img'])
			{
				$sellerRow['logo'] = $photoInfo['logo']['img'];
			}
		}

		//添加新会员
		if(!$seller_id)
		{
			$sellerRow['seller_name'] = $seller_name;
			$sellerRow['password']    = md5($password);
			$sellerRow['create_time'] = ITime::getDateTime();

			$sellerDB->setData($sellerRow);
			$sellerDB->add();
		}
		//编辑会员
		else
		{
			//修改密码
			if($password)
			{
				$sellerRow['password'] = md5($password);
			}

			$sellerDB->setData($sellerRow);
			$sellerDB->update("id = ".$seller_id);
		}
		$this->redirect('seller_list');
	}
	/**
	 * @brief 商户的删除动作
	 */
	public function seller_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$sellerDB = new IModel('seller');
		$data = array('is_del' => 1);
		$sellerDB->setData($data);

		if(is_array($id))
		{
			$sellerDB->update('id in ('.join(",",$id).')');
		}
		else
		{
			$sellerDB->update('id = '.$id);
		}
		$this->redirect('seller_list');
	}
	/**
	 * @brief 商户的回收站删除动作
	 */
	public function seller_recycle_del()
	{
		$id       = IFilter::act(IReq::get('id'),'int');
		$sellerDB = new IModel('seller');

		if(is_array($id))
		{
			$id = join(",",$id);
		}

		//删除商家扩展表数据
		$sellerExtTable = array("merch_ship_info","spec","delivery_extend","category_seller","delivery_doc","promotion","regiment","ticket","bill","takeself","prop","goods_extend_preorder_discount","goods_extend_preorder_disnums");
		foreach($sellerExtTable as $tableName)
		{
			$selletExtDB = new IModel($tableName);
			$selletExtDB->del('seller_id in ('.$id.')');
		}
		$sellerDB->del('id in ('.$id.')');
		$this->redirect('seller_recycle_list');
	}
	/**
	 * @brief 商户的回收站恢复动作
	 */
	public function seller_recycle_restore()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$sellerDB = new IModel('seller');
		$data = array('is_del' => 0);
		$sellerDB->setData($data);
		if(is_array($id))
		{
			$sellerDB->update('id in ('.join(",",$id).')');
		}
		else
		{
			$sellerDB->update('id = '.$id);
		}

		$this->redirect('seller_recycle_list');
	}
	//商户状态ajax
	public function ajax_seller_lock()
	{
		$id   = IFilter::act(IReq::get('id'));
		$lock = IFilter::act(IReq::get('lock'));
		$sellerObj = new IModel('seller');
		$sellerObj->setData(array('is_lock' => $lock));
		$sellerObj->update("id = ".$id);

		//短信通知状态修改
		plugin::trigger("updateSellerStatus",$id);
	}

	//导出预存款excel
	public function balance_report()
	{
		$memberQuery = new IQuery('member as m');
		$memberQuery->join   = "left join user as u on m.user_id=u.id";
		$memberQuery->fields = "u.username,m.time,m.email,m.mobile,m.balance";
		$memberQuery->where  = "m.balance > 0";
		$memberList          = $memberQuery->find();

		$reportObj = new report('balance');
		$reportObj->setTitle(["用户名","手机号","注册日期","邮箱","账号余额"]);
		foreach($memberList as $k => $val)
		{
			$insertData = array($val['username'],$val['mobile'],$val['time'],$val['email'],$val['balance']);
			$reportObj->setData($insertData);
		}
		$reportObj->toDownload();
	}
}