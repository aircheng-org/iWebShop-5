<?php
/**
 * @brief 用户中心模块
 * @class Ucenter
 * @note  前台
 */
class Ucenter extends IController implements userAuthorization
{
	public $layout = 'ucenter';

	public function init()
	{

	}
    public function index()
    {
    	//获取用户基本信息
		$user = Api::run('getMemberInfo');

		//获取用户各项统计数据
		$statistics = Api::run('getMemberTongJi');

		//获取用户站内信条数
		$msgObj = new Mess($this->user['user_id']);
		$msgNum = $msgObj->needReadNum();

		//获取用户优惠券
		$propData= Api::run('getPropTongJi');

		$this->setRenderData(array(
			"user"       => $user,
			"statistics" => $statistics,
			"msgNum"     => $msgNum,
			"propData"   => $propData,
		));

        $this->initPayment();
        $this->redirect('index');
    }

	//[用户头像]上传
	function user_ico_upload()
	{
	 	$uploadDir= IWeb::$app->config['upload'].'/user_ico';
		$photoObj = new PhotoUpload($uploadDir);
		$photoObj->setIterance(false);
		$result   = current($photoObj->run());
		if($result && isset($result['flag']) && $result['flag'] == 1)
		{
			$user_id   = $this->user['user_id'];
			$user_obj  = new IModel('user');
			$dataArray = array(
				'head_ico' => $result['img'],
			);
			$user_obj->setData($dataArray);
			$user_obj->update('id = '.$user_id);

			$result['img'] = IUrl::creatUrl().$result['img'];
			ISafe::set('head_ico',$dataArray['head_ico']);
		}
		echo JSON::encode($result);
	}

    /**
     * @brief 我的订单列表
     */
    public function order()
    {
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0",false);
        $this->initPayment();
        $this->redirect('order');

    }
    /**
     * @brief 初始化支付方式
     */
    private function initPayment()
    {
        $payment = new IQuery('payment');
        $payment->fields = 'id,name,type';
        $payments = $payment->find();
        $items = array();
        foreach($payments as $pay)
        {
            $items[$pay['id']]['name'] = $pay['name'];
            $items[$pay['id']]['type'] = $pay['type'];
        }
        $this->payments = $items;
    }
    /**
     * @brief 订单详情
     * @return String
     */
    public function order_detail()
    {
        $id = IFilter::act(IReq::get('id'),'int');

        $orderObj = new order_class();
        $this->order_info = $orderObj->getOrderShow($id,$this->user['user_id']);

        if(!$this->order_info)
        {
        	IError::show(403,'订单信息不存在');
        }
        $this->redirect('order_detail',false);
    }

    //操作订单状态
	public function order_status()
	{
		$op    = IFilter::act(IReq::get('op'));
		$id    = IFilter::act( IReq::get('order_id'),'int' );
		$model = new IModel('order');

		switch($op)
		{
			case "cancel":
			{
				$model->setData(array('status' => 3));
				if($model->update("id = ".$id." and distribution_status = 0 and status = 1 and user_id = ".$this->user['user_id']))
				{
					//生成订单日志
					$tb_order_log = new IModel('order_log');
					$tb_order_log->setData([
						'order_id' => $id,
						'user'     => "用户操作",
						'action'   => '取消订单',
						'result'   => '成功',
						'note'     => '订单被用户操作取消',
						'addtime'  => ITime::getDateTime(),
					]);
					$tb_order_log->add();

					order_class::resetOrderProp($id);
					$this->redirect("order_detail/id/$id");
				}
				//订单状态是付款或者发货则需要走退款退货申请流程
				else
				{
				    $order_goods_id = [];
					$refunds_nums   = [];
				    $goodsList = Api::run('getOrderGoodsListByGoodsid',array('#order_id#',$id));
				    foreach($goodsList as $item)
				    {
				        $order_goods_id[] = $item['id'];
						$refunds_nums[] = $item['goods_nums'];
				    }

                    IReq::set('order_goods_id',$order_goods_id);
					IReq::set('refunds_nums',$refunds_nums);
                    IReq::set('order_id',$id);
                    IReq::set('content','申请取消订单');
                    IReq::set('type','cancel');

					//生成订单日志
					$tb_order_log = new IModel('order_log');
					$tb_order_log->setData([
						'order_id' => $id,
						'user'     => "用户操作",
						'action'   => '申请退款订单',
						'result'   => '成功',
						'note'     => '订单被用户操作申请退款',
						'addtime'  => ITime::getDateTime(),
					]);
					$tb_order_log->add();

				    $this->refunds_update();
				}
			}
			break;

			case "confirm":
			{
				$model->setData(array('status' => 5,'completion_time' => ITime::getDateTime()));
				if($model->update("id = ".$id." and status in (1,2) and distribution_status = 1 and user_id = ".$this->user['user_id']))
				{
					$orderRow = $model->getObj('id = '.$id);

					//确认收货后进行支付
					Order_Class::updateOrderStatus($orderRow['order_no']);

		    		//增加用户评论商品机会
		    		Order_Class::addGoodsCommentChange($id);

					//生成订单日志
					$tb_order_log = new IModel('order_log');
					$tb_order_log->setData([
						'order_id' => $id,
						'user'     => "用户操作",
						'action'   => '完成',
						'result'   => '成功',
						'note'     => '订单被用户操作完成',
						'addtime'  => ITime::getDateTime(),
					]);
					$tb_order_log->add();

					//发送完成事件
					plugin::trigger('orderConfirmFinish',$orderRow);

		    		//确认收货以后直接跳转到评论页面
		    		$this->redirect('evaluation');
				}
				else
				{
				    $this->redirect('order');
				}
			}
			break;
		}
	}
    /**
     * @brief 我的地址
     */
    public function address()
    {
		//取得自己的地址
		$query = new IQuery('address');
        $query->where = 'user_id = '.$this->user['user_id'];
		$address = $query->find();
		$areas   = array();

		if($address)
		{
			foreach($address as $ad)
			{
				$temp = area::name($ad['province'],$ad['city'],$ad['area']);
				if(isset($temp[$ad['province']]) && isset($temp[$ad['city']]) && isset($temp[$ad['area']]))
				{
					$areas[$ad['province']] = $temp[$ad['province']];
					$areas[$ad['city']]     = $temp[$ad['city']];
					$areas[$ad['area']]     = $temp[$ad['area']];
				}
			}
		}

		$this->areas = $areas;
		$this->address = $address;
        $this->redirect('address');
    }
    /**
     * @brief 收货地址删除处理
     */
	public function address_del()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
		$model = new IModel('address');
		$model->del('id = '.$id.' and user_id = '.$this->user['user_id']);
		$this->redirect('address');
	}
    /**
     * @brief 设置默认的收货地址
     */
    public function address_default()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::act(IReq::get('is_default'));
        $model = new IModel('address');
        if($default == 1)
        {
            $model->setData(array('is_default' => 0));
            $model->update("user_id = ".$this->user['user_id']);
        }
        $model->setData(array('is_default' => $default));
        $model->update("id = ".$id." and user_id = ".$this->user['user_id']);
        $this->redirect('address');
    }
    /**
     * @brief 售后申请页面
     */
    public function refunds_update()
    {
        $order_goods_id = IFilter::act( IReq::get('order_goods_id'),'int' );
        $order_id       = IFilter::act( IReq::get('order_id'),'int' );
        $user_id        = $this->user['user_id'];
        $content        = IFilter::act(IReq::get('content'),'text');
        $img_list       = IFilter::act(IReq::get("_imgList"));
        $type           = IFilter::act(IReq::get("type"));
		$refunds_nums   = IFilter::act( IReq::get('refunds_nums'),'int' );

        if(!$content || !$order_goods_id)
        {
            IError::show(403,"请填写售后原因和商品");
        }

		//过滤多余的商品数量
		foreach($refunds_nums as $key => $item)
		{
			if(!isset($order_goods_id[$key]) || $item <= 0)
			{
				unset($refunds_nums[$key]);
			}
		}

		if(count($order_goods_id) != count($refunds_nums))
		{
			IError::show(403,"退款数量不匹配");
		}

        $orderDB      = new IModel('order');
        $orderRow     = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);
        $refundResult = Order_Class::isRefundmentApply($orderRow,$order_goods_id,$type);

        //判断是否允许提交售后
        if($refundResult === true)
        {
            //售后申请数据
    		$updateData = array(
				'order_no'       => $orderRow['order_no'],
				'order_id'       => $order_id,
				'user_id'        => $user_id,
				'time'           => ITime::getDateTime(),
				'content'        => $content,
                'img_list'       => '',
				'seller_id'      => $orderRow['seller_id'],
				'order_goods_id' => join(",",$order_goods_id),
				'order_goods_nums' => join(",",$refunds_nums),
			);

            if(isset($img_list) && $img_list)
            {
                $img_list = explode(",",trim($img_list,","));
                $img_list = array_filter($img_list);
                if(count($img_list) > 5)
                {
                    IError::show(403,"最多上传5张图片");
                }

                $img_list = JSON::encode($img_list);
                $updateData['img_list'] = $img_list;
            }

            switch($type)
            {
                //换货
                case "exchange":
                {
            		$exchangeDB = new IModel('exchange_doc');
            		$exchangeDB->setData($updateData);
            		$id = $exchangeDB->add();

                    plugin::trigger('exchangeApplyFinish',$id);
            		$this->redirect('exchange');
                }
                break;

                //维修
                case "fix":
                {
            		$fixDB = new IModel('fix_doc');
            		$fixDB->setData($updateData);
            		$id = $fixDB->add();

                    plugin::trigger('fixApplyFinish',$id);
            		$this->redirect('fix');
                }
                break;

                //退款
                default:
                {
            		$refundsDB = new IModel('refundment_doc');
            		$refundsDB->setData($updateData);
            		$id = $refundsDB->add();

                    plugin::trigger('refundsApplyFinish',$id);
            		$this->redirect('refunds');
                }
            }
        }
        else
        {
            IError::show(403,$refundResult);
        }
    }
    /**
     * @brief 退款申请删除
     */
    public function refunds_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $model = new IModel("refundment_doc");
        $result= $model->del("id = ".$id." and pay_status = 0 and user_id = ".$this->user['user_id']);
        $this->redirect('refunds');
    }
    /**
     * @brief 查看退款申请详情
     */
    public function refunds_detail()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $refundDB = new IModel("refundment_doc");
        $refundRow = $refundDB->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
        if($refundRow)
        {
        	//获取商品信息
        	$orderGoodsDB   = new IModel('order_goods');
        	$orderGoodsList = $orderGoodsDB->query("id in (".$refundRow['order_goods_id'].")");
        	if($orderGoodsList)
        	{
				$refundRow['goods'] = $orderGoodsList;
        		$this->data = $refundRow;
        	}
        	else
        	{
	        	$this->redirect('refunds',false);
	        	Util::showMessage("没有找到要退款的商品");
        	}
        	$this->redirect('refunds_detail');
        }
        else
        {
        	$this->redirect('refunds',false);
        	Util::showMessage("退款信息不存在");
        }
    }
    /**
     * @brief 查看退款申请详情
     */
	public function refunds_edit()
	{
		$order_id = IFilter::act(IReq::get('order_id'),'int');
		if($order_id)
		{
			$orderDB  = new IModel('order');
			$orderRow = $orderDB->getObj('id = '.$order_id.' and user_id = '.$this->user['user_id']);
			if($orderRow)
			{
				$this->orderRow = $orderRow;
				$this->redirect('refunds_edit');
				return;
			}
		}
		$this->redirect('refunds');
	}

    /**
     * @brief 建议中心
     */
    public function complain_edit()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $title = IFilter::act(IReq::get('title'),'string');
        $content = IFilter::act(IReq::get('content'),'string' );
        $user_id = $this->user['user_id'];
        $model = new IModel('suggestion');
        $model->setData(array('user_id'=>$user_id,'title'=>$title,'content'=>$content,'time'=>ITime::getDateTime()));
        if($id =='')
        {
            $model->add();
        }
        else
        {
            $model->update('id = '.$id.' and user_id = '.$this->user['user_id']);
        }
        $this->redirect('complain');
    }
    /**
     * @brief 删除消息
     * @param int $id 消息ID
     */
    public function message_del()
    {
        $id = IFilter::act( IReq::get('id') ,'int' );
        $msg = new Mess($this->user['user_id']);
        $msg->delMessage($id);
        $this->redirect('message');
    }
    public function message_read()
    {
        $id     = IFilter::act( IReq::get('id'),'int' );
        $msgObj = new Mess($this->user['user_id']);
        $content= $msgObj->readMessage($id);
        $result = array('status' => 'fail','error' => '读取内容错误');
        if($content)
        {
            $msgObj->writeMessage($id,1);
            $result = array('status' => 'success','data' => $content);
        }
        die(JSON::encode($result));
    }

    //[修改密码]修改动作
    function password_edit()
    {
    	$user_id    = $this->user['user_id'];

    	$fpassword  = IReq::get('fpassword');
    	$password   = IReq::get('password');
    	$repassword = IReq::get('repassword');

    	$userObj    = new IModel('user');
    	$where      = 'id = '.$user_id;
    	$userRow    = $userObj->getObj($where);

		if(!preg_match('|\w{6,32}|',$password))
		{
			$message = '密码格式不正确，请重新输入';
		}
    	else if($password != $repassword)
    	{
    		$message  = '二次密码输入的不一致，请重新输入';
    	}
    	else if(md5($fpassword) != $userRow['password'])
    	{
    		$message  = '原始密码输入错误';
    	}
    	else
    	{
    		$passwordMd5 = md5($password);
	    	$dataArray = array(
	    		'password' => $passwordMd5,
	    	);

	    	$userObj->setData($dataArray);
	    	$result  = $userObj->update($where);
	    	if($result)
	    	{
	    		ISafe::set('user_pwd',$passwordMd5);
	    		$message = '密码修改成功';
	    	}
	    	else
	    	{
	    		$message = '密码修改失败';
	    	}
		}

    	$this->redirect('password',false);
    	Util::showMessage($message);
    }

    //[个人资料]展示 单页
    function info()
    {
    	$userData = Api::run('getMemberInfo');
    	$this->setRenderData(array('userData' => $userData));
    	$this->redirect('info');
    }

    //[个人资料] 修改 [动作]
    function info_edit_act()
    {
		$email     = IFilter::act( IReq::get('email'),'string');
		$mobile    = IFilter::act( IReq::get('mobile'),'string');

    	$user_id   = $this->user['user_id'];
    	$memberObj = new IModel('member');
    	$where     = 'user_id = '.$user_id;

		if($email)
		{
			$memberRow = $memberObj->getObj('user_id != '.$user_id.' and email = "'.$email.'"');
			if($memberRow)
			{
				IError::show('邮箱已经被注册');
			}
		}

		if($mobile)
		{
			$memberRow = $memberObj->getObj('user_id != '.$user_id.' and mobile = "'.$mobile.'"');
			if($memberRow)
			{
				IError::show('手机已经被注册');
			}
		}

    	//地区
    	$province = IFilter::act( IReq::get('province','post') ,'string');
    	$city     = IFilter::act( IReq::get('city','post') ,'string' );
    	$area     = IFilter::act( IReq::get('area','post') ,'string' );
    	$areaArr  = array_filter(array($province,$city,$area));

    	$dataArray       = array(
    		'email'        => $email,
    		'true_name'    => IFilter::act( IReq::get('true_name') ,'string'),
    		'sex'          => IFilter::act( IReq::get('sex'),'int' ),
    		'birthday'     => IFilter::act( IReq::get('birthday') ),
    		'zip'          => IFilter::act( IReq::get('zip') ,'string' ),
    		'qq'           => IFilter::act( IReq::get('qq') , 'string' ),
    		'contact_addr' => IFilter::act( IReq::get('contact_addr'), 'string'),
    		'mobile'       => $mobile,
    		'telephone'    => IFilter::act( IReq::get('telephone'),'string'),
    		'area'         => $areaArr ? ",".join(",",$areaArr)."," : "",
    	);

    	$memberObj->setData($dataArray);
    	$memberObj->update($where);
    	$this->info();
    }

    //[账户预存款] 展示[单页]
    function withdraw()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member','balance');
    	$where     = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);
    	$this->redirect('withdraw');
    }

	//[账户预存款] 提现动作
    function withdraw_act()
    {
    	$user_id = $this->user['user_id'];
    	$amount  = IFilter::act( IReq::get('amount','post') ,'float' );
    	$message = '';

    	$dataArray = array(
    		'name'   => IFilter::act( IReq::get('name','post') ,'string'),
    		'note'   => IFilter::act( IReq::get('note','post'), 'string'),
			'amount' => $amount,
			'user_id'=> $user_id,
			'time'   => ITime::getDateTime(),
    	);

		$mixAmount = $this->_siteConfig->low_withdraw ? $this->_siteConfig->low_withdraw : 1;
		$memberObj = new IModel('member');
		$where     = 'user_id = '.$user_id;
		$memberRow = $memberObj->getObj($where,'balance');

		$withdrawDB = new IModel('withdraw');

		//提现金额范围
		if($amount <= $mixAmount)
		{
			$message = '提现的金额必须大于'.$mixAmount.'元';
		}
		else if($amount > $memberRow['balance'])
		{
			$message = '提现的金额不能大于您的帐户预存款';
		}
		else if($withdrawDB->getObj('user_id = '.$this->user['user_id'].' and status in (0,1)'))
		{
		    $message = '您已经提交过申请，请耐心等待';
		}
		else
		{
	    	$obj = new IModel('withdraw');
	    	$obj->setData($dataArray);
	    	$id = $obj->add();
	    	if($id)
	    	{
	    	    plugin::trigger('withdrawApplyFinish',$id);
	    	}
	    	$this->redirect('/ucenter/withdraw/_msg/申请成功等待审核通过');
		}

		if($message)
		{
			$this->memberRow = array('balance' => $memberRow['balance']);
			$this->withdrawRow = $dataArray;
			$this->redirect('withdraw',false);
			Util::showMessage($message);
		}
    }

    //[账户预存款] 提现详情
    function withdraw_detail()
    {
    	$user_id = $this->user['user_id'];

    	$id  = IFilter::act( IReq::get('id'),'int' );
    	$obj = new IModel('withdraw');
    	$where = 'id = '.$id.' and user_id = '.$user_id;
    	$this->withdrawRow = $obj->getObj($where);
    	$this->redirect('withdraw_detail');
    }

    //[提现申请] 取消
    function withdraw_del()
    {
    	$id = IFilter::act( IReq::get('id'),'int');
    	if($id)
    	{
    		$withdrawObj = new IModel('withdraw');
    		$withdrawObj->del('id = '.$id.' and user_id = '.$this->user['user_id']);
    	}
    	$this->redirect('withdraw');
    }

    //[预存款交易记录]
    function account_log()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member');
    	$where     = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);
    	$this->redirect('account_log');
    }

    //[收藏夹]备注信息
    function edit_summary()
    {
    	$user_id = $this->user['user_id'];

    	$id      = IFilter::act( IReq::get('id'),'int' );
    	$summary = IFilter::act( IReq::get('summary'),'string' );

    	//ajax返回结果
    	$result  = array(
    		'isError' => true,
    	);

    	if(!$id)
    	{
    		$result['message'] = '收藏夹ID值丢失';
    	}
    	else if(!$summary)
    	{
    		$result['message'] = '请填写正确的备注信息';
    	}
    	else
    	{
	    	$favoriteObj = new IModel('favorite');
	    	$where       = 'id = '.$id.' and user_id = '.$user_id;

	    	$dataArray   = array(
	    		'summary' => $summary,
	    	);

	    	$favoriteObj->setData($dataArray);
	    	$is_success = $favoriteObj->update($where);

	    	if($is_success === false)
	    	{
	    		$result['message'] = '更新信息错误';
	    	}
	    	else
	    	{
	    		$result['isError'] = false;
	    	}
    	}
    	echo JSON::encode($result);
    }

    //[收藏夹]删除
    function favorite_del()
    {
    	$user_id = $this->user['user_id'];
    	$id      = IReq::get('id');

		if($id)
		{
			$id = IFilter::act($id,'int');

			$favoriteObj = new IModel('favorite');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = 'user_id = '.$user_id.' and id in ('.$idStr.')';
			}
			else
			{
				$where = 'user_id = '.$user_id.' and id = '.$id;
			}

			$favoriteObj->del($where);
			$this->redirect('favorite');
		}
		else
		{
			$this->redirect('favorite',false);
			Util::showMessage('请选择要删除的数据');
		}
    }

    //[我的积分] 单页展示
    function integral()
    {
    	$memberObj       = new IModel('member');
    	$this->memberRow = $memberObj->getObj("user_id = ".$this->user['user_id'],'point');
    	$this->redirect('integral',false);
    }

    //[我的积分]积分兑换优惠券 动作
    function trade_ticket()
    {
    	$ticketId  = IFilter::act( IReq::get('ticket_id'),'int' );
		$ticketObj = new IModel('ticket');
		$ticketRow = $ticketObj->getObj('id = '.$ticketId.' and NOW() between start_time and end_time');
		if($ticketRow)
		{
			$passMsg = ticket::isPassByUser($ticketRow,$this->user['user_id']);
			if($passMsg === true)
			{
				$insert_id = ticket::create($ticketRow);
				$result    = ticket::bindByUser($insert_id,$this->user["user_id"]);

				//优惠券成功
				if($result && $ticketRow['point'] > 0)
				{
					$pointConfig = array(
						'user_id' => $this->user['user_id'],
						'point'   => '-'.$ticketRow['point'],
						'log'     => '积分兑换优惠券，扣除了 -'.$ticketRow['point'].'积分',
					);
					$pointObj = new Point;
					$pointObj->update($pointConfig);
				}
			}
			else
			{
				$this->setError($passMsg);
			}
		}
		else
		{
			$this->setError('优惠券不存在');
		}

    	$error = $this->getError();
    	if($error)
    	{
			if(IReq::get('isAjax'))
			{
				die(JSON::encode(['status' => 'fail','msg' => $error]));
			}
			else
			{
				$this->redirect('redpacket',false);
				Util::showMessage($error);
			}
    	}
    	else
    	{
			if(IReq::get('isAjax'))
			{
				die(JSON::encode(['status' => 'success','msg' => '优惠券获取成功']));
			}
			else
			{
				$this->redirect('/ucenter/redpacket/_msg/优惠券获取成功');
			}
    	}
    }

    /**
     * 预存款付款
     * T:支付失败;
     * F:支付成功;
     */
    function payment_balance()
    {
    	$urlStr  = '';
    	$user_id = intval($this->user['user_id']);
		$return  = array(
	    	'attach'    => IReq::get('attach'),
	    	'total_fee' => IReq::get('total_fee'),
	    	'order_no'  => IReq::get('order_no'),
	    	'sign'      => IReq::get('sign'),
		);

		$paymentDB  = new IModel('payment');
		$paymentRow = $paymentDB->getObj('class_name = "balance" ');
		if(!$paymentRow)
		{
			IError::show(403,'预存款支付方式不存在');
		}

		$paymentInstance = Payment::createPaymentInstance($paymentRow['id']);
		$payResult       = $paymentInstance->callback($return,$paymentRow['id'],$money,$message,$orderNo);
		if($payResult == false)
		{
			IError::show(403,$message);
		}

    	$memberObj = new IModel('member');
    	$memberRow = $memberObj->getObj('user_id = '.$user_id);

    	if(empty($memberRow))
    	{
    		IError::show(403,'用户信息不存在');
    	}

    	if($memberRow['balance'] < $return['total_fee'])
    	{
    	    $recharge = $return['total_fee'] - $memberRow['balance'];
    	    $this->redirect('/ucenter/online_recharge/_msg/预存款不足请充值 ￥'.$recharge);
    	    return;
    	}

		//检查订单状态
		$orderObj = new IModel('order');
		$orderRow = $orderObj->getObj('order_no  = "'.$return['order_no'].'" and pay_status = 0 and status = 1 and user_id = '.$user_id);
		if(!$orderRow)
		{
			IError::show(403,'订单号【'.$return['order_no'].'】已经被处理过，请查看订单状态');
		}

		//扣除预存款并且记录日志
		$logObj = new AccountLog();
		$config = array(
			'user_id'  => $user_id,
			'event'    => 'pay',
			'num'      => $return['total_fee'],
			'order_no' => str_replace("_",",",$return['attach']),
		);
		$is_success = $logObj->write($config);
		if(!$is_success)
		{
			$orderObj->rollback();
			IError::show(403,$logObj->error ? $logObj->error : '用户预存款更新失败');
		}

		//订单批量结算缓存机制
		$moreOrder = Order_Class::getBatch($orderNo);
		if($money >= array_sum($moreOrder))
		{
			foreach($moreOrder as $key => $item)
			{
				$order_id = Order_Class::updateOrderStatus($key);
				if(!$order_id)
				{
					$orderObj->rollback();
					IError::show(403,'订单修改失败');
				}
			}
		}
		else
		{
			$orderObj->rollback();
			IError::show(403,'付款金额与订单金额不符合');
		}

		//支付成功结果
		plugin::trigger('setCallback','/ucenter/order');
		$this->redirect('/site/success/message/'.urlencode("支付成功"));
    }

    //发票删除
    function invoice_del()
    {
		$id = IFilter::act( IReq::get('id'),'int' );
		$model = new IModel('invoice');
		$model->del('id = '.$id.' and user_id = '.$this->user['user_id']);
		$this->redirect('invoice');
    }

    //退款申请图片上传
    function refunds_img_upload()
    {
		$photoObj = new PhotoUpload(IWeb::$app->config['upload']."/refunds/".$this->user['user_id']);
		$photoObj->setIterance(false);
		$result   = current($photoObj->run());
		echo JSON::encode($result);
    }

    //商品评价申请图片上传
    function comment_img_upload()
    {
		$photoObj = new PhotoUpload(IWeb::$app->config['upload']."/comment/".$this->user['user_id']);
		$photoObj->setIterance(false);
		$result   = current($photoObj->run());
		echo JSON::encode($result);
    }

    //商品资源下载 隐藏真实地址
    function download()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
		$user_id = $this->user ? $this->user['user_id'] : 0;
        $goodsDownloadRelationObj = new IModel('order_download_relation');
        $goodsDownloadRelationRow = $goodsDownloadRelationObj->getObj($id);
        if(!$goodsDownloadRelationRow || $goodsDownloadRelationRow['user_id'] != $user_id)
        {
            IError::show(403,'未找到记录');
        }

        $goodsExtendDownloadObj = new IModel('goods_extend_download');
        $goodsExtendDownloadRow = $goodsExtendDownloadObj->getObj('goods_id = '.$goodsDownloadRelationRow['goods_id'],'url,end_time,limit_num');
        if(!$goodsExtendDownloadRow)
        {
            IError::show(403,'未找到资源');
        }

        if(ITime::getDateTime() > $goodsExtendDownloadRow['end_time'])
        {
            IError::show(403,'资源到期,停止下载,到期时间:'.$goodsExtendDownloadRow['end_time']);
        }

        if($goodsDownloadRelationRow['num'] >= $goodsExtendDownloadRow['limit_num'])
        {
            IError::show(403,'资源限制下载'.$goodsExtendDownloadRow['limit_num'].'次');
        }

        $file = $goodsExtendDownloadRow['url'];
        if(stripos($file,"http") !== 0 && !file_exists($file))
        {
            IError::show(403,'资源已失效');
        }

		//检查订单完成状态
		$orderDB = new IModel('order');
		if(!$orderDB->getObj('id = '.$goodsDownloadRelationRow['order_id'].' and status = 5 and if_del = 0'))
		{
			IError::show(403,'当前订单未完成付款');
		}

        //更新下载次数
        $goodsDownloadRelationObj->setData(array('num' => 'num + 1'));
        $goodsDownloadRelationObj->update('id = '.$goodsDownloadRelationRow['id'],'num');

        header('Content-Type: application/x-zip-compressed');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    //换货申请删除
    public function exchange_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $model = new IModel("exchange_doc");
        $result= $model->del("id = ".$id." and status = 0 and user_id = ".$this->user['user_id']);
        $this->redirect('exchange');
    }

    /**
     * @brief 查看退款申请详情
     */
    public function exchange_detail()
    {
        $id  = IFilter::act( IReq::get('id'),'int' );
        $db  = new IModel("exchange_doc");
        $row = $db->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
        if($row)
        {
        	//获取商品信息
        	$orderGoodsDB   = new IModel('order_goods');
        	$orderGoodsList = $orderGoodsDB->query("id in (".$row['order_goods_id'].")");
        	if($orderGoodsList)
        	{
        		$row['goods'] = $orderGoodsList;
        		$this->data = $row;
        	}
        	else
        	{
	        	$this->redirect('exchange',false);
	        	Util::showMessage("没有找到申请售后的商品");
        	}
        	$this->redirect('exchange_detail');
        }
        else
        {
        	$this->redirect('exchange',false);
        	Util::showMessage("申请信息不存在");
        }
    }

    //维修申请删除
    public function fix_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $model = new IModel("fix_doc");
        $result= $model->del("id = ".$id." and status = 0 and user_id = ".$this->user['user_id']);
        $this->redirect('fix');
    }

    /**
     * @brief 查看退款申请详情
     */
    public function fix_detail()
    {
        $id  = IFilter::act( IReq::get('id'),'int' );
        $db  = new IModel("fix_doc");
        $row = $db->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
        if($row)
        {
        	//获取商品信息
        	$orderGoodsDB   = new IModel('order_goods');
        	$orderGoodsList = $orderGoodsDB->query("id in (".$row['order_goods_id'].")");
        	if($orderGoodsList)
        	{
        		$row['goods'] = $orderGoodsList;
        		$this->data = $row;
        	}
        	else
        	{
	        	$this->redirect('fix',false);
	        	Util::showMessage("没有找到申请售后的商品");
        	}
        	$this->redirect('fix_detail');
        }
        else
        {
        	$this->redirect('fix',false);
        	Util::showMessage("申请信息不存在");
        }
    }

    //退货物流信息更新
    function refunds_freight()
    {
        $user_freight_id = IFilter::act(IReq::get('user_freight_id'),'int');
        $user_delivery_code = IFilter::act(IReq::get('user_delivery_code'));
        $id = IFilter::act(IReq::get('id'),'int');

        $db = new IModel('refundment_doc');
        $db->setData([
            "pay_status" => 4,
            "user_freight_id" => $user_freight_id,
            "user_delivery_code" => $user_delivery_code,
            "user_send_time" => ITime::getDateTime(),
        ]);

        if($db->update("id = ".$id." and user_id = ".$this->user['user_id']))
        {
            plugin::trigger('refundDocUpdate',$id);
        }
        $this->redirect('refunds');
    }

    //换货物流信息更新
    function exchange_freight()
    {
        $user_freight_id = IFilter::act(IReq::get('user_freight_id'),'int');
        $user_delivery_code = IFilter::act(IReq::get('user_delivery_code'));
        $id = IFilter::act(IReq::get('id'),'int');

        $db = new IModel('exchange_doc');
        $db->setData([
            "status" => 4,
            "user_freight_id" => $user_freight_id,
            "user_delivery_code" => $user_delivery_code,
            "user_send_time" => ITime::getDateTime(),
        ]);

        if($db->update("id = ".$id." and user_id = ".$this->user['user_id']))
        {
            plugin::trigger('exchangeDocUpdate',$id);
        }
        $this->redirect('exchange');
    }

    //维修物流信息更新
    function fix_freight()
    {
        $user_freight_id = IFilter::act(IReq::get('user_freight_id'),'int');
        $user_delivery_code = IFilter::act(IReq::get('user_delivery_code'));
        $id = IFilter::act(IReq::get('id'),'int');

        $db = new IModel('fix_doc');
        $db->setData([
            "status" => 4,
            "user_freight_id" => $user_freight_id,
            "user_delivery_code" => $user_delivery_code,
            "user_send_time" => ITime::getDateTime(),
        ]);

        if($db->update("id = ".$id." and user_id = ".$this->user['user_id']))
        {
            plugin::trigger('fixDocUpdate',$id);
        }
        $this->redirect('fix');
    }
}