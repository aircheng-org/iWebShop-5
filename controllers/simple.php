<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file Simple.php
 * @brief
 * @author webning
 * @date 2011-03-22
 * @version 0.6
 *
 * @udate 2019/4/8 19:20:11
 * @author nswe
 * @note 优化了购物流程，自提点，支付方式与配送分离
 */

/**
 * @brief Simple
 * @class Simple
 * @note
 */
class Simple extends IController
{
    public $layout='site_mini';

	function init()
	{

	}

	function login()
	{
		//如果已经登录，就跳到ucenter页面
		if($this->user)
		{
			$this->redirect("/ucenter/index");
		}
		else
		{
			$this->redirect('login');
		}
	}

	//退出登录
    function logout()
    {
    	plugin::trigger('clearUser');
    	$this->redirect('login');
    }

    //用户注册
    function reg_act()
    {
    	//调用_userInfo注册插件
    	$result = plugin::trigger("userRegAct");
    	if(is_array($result))
    	{
			//自定义跳转页面
			$msg = isset($result['msg']) ? $result['msg'] : ILang::get('恭喜您注册成功');
			$this->redirect('/site/success?message='.urlencode($msg));
    	}
    	else
    	{
    		$this->setError($result);
    		$this->redirect('reg',false);
    		Util::showMessage($result);
    	}
    }

    //用户登录
    function login_act()
    {
    	//调用_userInfo登录插件
		$result = plugin::trigger('userLoginAct',$_POST);
		if(is_array($result))
		{
			//自定义跳转页面
			$callback = plugin::trigger('getCallback');
			if($callback)
			{
				$this->redirect($callback);
			}
			else
			{
				$this->redirect('/ucenter/index');
			}
		}
		else
		{
			$this->setError($result);
			$this->redirect('login',false);
			Util::showMessage($result);
		}
    }

    //商品加入购物车[ajax]
    function joinCart()
    {
    	$link      = IReq::get('link');
    	$goods_id  = IFilter::act(IReq::get('goods_id'),'int');
    	$goods_num = IFilter::act(IReq::get('goods_num'),'int');
    	$goods_num = $goods_num == 0 ? 1 : $goods_num;
		$type      = IFilter::act(IReq::get('type'));

		//加入购物车
    	$cartObj   = new Cart();
    	$addResult = $cartObj->add($goods_id,$goods_num,$type);

    	if($link != '')
    	{
    		if($addResult === false)
    		{
    			$this->cart(false);
    			Util::showMessage($cartObj->getError());
    		}
    		else
    		{
    			$this->redirect($link);
    		}
    	}
    	else
    	{
	    	if($addResult === false)
	    	{
		    	$result = array(
		    		'isError' => true,
		    		'message' => $cartObj->getError(),
		    	);
	    	}
	    	else
	    	{
		    	$result = array(
		    		'isError' => false,
		    		'message' => ILang::get('添加成功'),
		    	);
	    	}
	    	echo JSON::encode($result);
    	}
    }

    //根据goods_id获取货品
    function getProducts()
    {
    	$id           = IFilter::act(IReq::get('id'),'int');
    	$productObj   = new IModel('products');
    	$productsList = $productObj->query('goods_id = '.$id,'sell_price,id,spec_array,goods_id','store_nums desc',7);
		if($productsList)
		{
			foreach($productsList as $key => $val)
			{
				$productsList[$key]['specData'] = goods_class::show_spec($val['spec_array']);
			}
		}
		echo JSON::encode($productsList);
    }

    //删除购物车
    function removeCart()
    {
    	$link      = IReq::get('link');
    	$goods_id  = IFilter::act(IReq::get('goods_id'),'int');
    	$type      = IFilter::act(IReq::get('type'));

    	$cartObj   = new Cart();
    	$cartInfo  = $cartObj->getMyCart();
    	$delResult = $cartObj->del($goods_id,$type);

    	if($link)
    	{
    		if($delResult === false)
    		{
    			$this->cart(false);
    			Util::showMessage($cartObj->getError());
    		}
    		else
    		{
    			$this->redirect($link);
    		}
    	}
    	else
    	{
	    	if($delResult === false)
	    	{
	    		$result = array(
		    		'isError' => true,
		    		'message' => $cartObj->getError(),
	    		);
	    	}
	    	else
	    	{
		    	$goodsRow = $cartInfo[$type]['data'][$goods_id];
		    	if($goodsRow && isset($goodsRow['sell_price']) && isset($goodsRow['count']))
		    	{
    		    	$cartInfo['sum']   -= $goodsRow['sell_price'] * $goodsRow['count'];
    		    	$cartInfo['count'] -= $goodsRow['count'];

    		    	$result = array(
    		    		'isError' => false,
    		    		'data'    => $cartInfo,
    		    	);
		    	}
		    	else
		    	{
    	    		$result = array(
    		    		'isError' => true,
    		    		'message' => ILang::get('商品信息不存在'),
    	    		);
		    	}
	    	}
	    	echo JSON::encode($result);
    	}
    }

    //清空购物车
    function clearCart()
    {
    	$cartObj = new Cart();
    	$cartObj->clear();
    	$this->redirect('cart');
    }

    //购物车div展示
    function showCart()
    {
    	$cartObj  = new Cart();
    	$cartList = $cartObj->getMyCart();
    	$data['data'] = array_merge($cartList['goods']['data'],$cartList['product']['data']);
    	$data['count']= $cartList['count'];
    	$data['sum']  = round($cartList['sum'],2);
    	echo JSON::encode($data);
    }

    //购物车页面及商品价格计算[复杂]
    function cart()
    {
    	//防止页面刷新
    	header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);

		//开始计算购物车中的商品价格
		$cartObj  = new Cart();
    	$countObj = new CountSum();
    	$result   = $countObj->goodsCount($cartObj->getMyCart(true));

    	if(is_string($result))
    	{
    		IError::show($result,403);
    	}

    	//返回值
    	$this->final_sum = $result['final_sum'];
    	$this->promotion = $result['promotion'];
    	$this->proReduce = $result['proReduce'];
    	$this->sum       = $result['sum'];
    	$this->goodsList = $result['goodsList'];
    	$this->count     = $result['count'];
    	$this->reduce    = $result['reduce'];
    	$this->weight    = $result['weight'];

		//渲染视图
    	$this->redirect('cart');
    }

    //计算促销规则[ajax]
    function promotionRuleAjax()
    {
		$countSumObj    = new CountSum();
		$countSumResult = $countSumObj->cart_count();
    	echo JSON::encode($countSumResult);
    }

    //填写订单信息cart2
    function cart2()
    {
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0",false);
		$id        = IFilter::act(IReq::get('id'),'int');
		$type      = IFilter::act(IReq::get('type'));//goods,product
		$buy_num   = IReq::get('num') ? IFilter::act(IReq::get('num'),'int') : 1;
		$tourist   = IReq::get('tourist');//游客方式购物

    	//必须为登录用户
    	if($tourist === null && $this->user['user_id'] == null)
    	{
    		if($id == 0 || $type == '')
    		{
    			$this->redirect('/simple/login?tourist&callback=/simple/cart2');
    		}
    		else
    		{
    			$url = '/simple/login?tourist&callback=/simple/cart2/id/'.$id.'/type/'.$type.'/num/'.$buy_num;
    			$this->redirect($url);
    		}
    	}

		//游客的user_id默认为0
    	$user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];

		//计算商品
		$countSumObj = new CountSum($user_id);
		$result = $countSumObj->cart_count($id,$type,$buy_num);
		if($countSumObj->error)
		{
			IError::show(403,$countSumObj->error);
		}

    	//获取收货地址
    	$addressObj  = new IModel('address');
    	$addressList = $addressObj->query('user_id = '.$user_id,"*","is_default desc");

		//更新$addressList数据
    	foreach($addressList as $key => $val)
    	{
    		$temp = area::name($val['province'],$val['city'],$val['area']);
    		if(isset($temp[$val['province']]) && isset($temp[$val['city']]) && isset($temp[$val['area']]))
    		{
	    		$addressList[$key]['province_str'] = $temp[$val['province']];
	    		$addressList[$key]['city_str']     = $temp[$val['city']];
	    		$addressList[$key]['area_str']     = $temp[$val['area']];
    		}
    	}

		//获取可用优惠券
		$ticketRow = [];
		if($user_id)
		{
			$memberObj  = new IModel('member');
			$memberRow  = $memberObj->getObj('user_id = '.$user_id,'prop');

			if(isset($memberRow['prop']) && ($propId = trim($memberRow['prop'],',')))
			{
				$porpObj = new IModel('prop');
				$propData = $porpObj->query('id in ('.$propId.') and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1','*','value desc');
				foreach($propData as $key => $item)
				{
				    $tempData = ticket::verify($item['id'],$result);
					if($tempData['result'] == true)
					{
						$ticketRow = [
							'price' => $tempData['price'],
							'name'  => $item['name'],
							'id'    => $item['id'],
						];
						break;
					}
				}
			}
		}

    	//返回值
		$this->ticketRow = $ticketRow;
    	$this->final_sum = $result['final_sum'];
    	$this->promotion = $result['promotion'];
    	$this->proReduce = $result['proReduce'];
    	$this->sum       = $result['sum'];
    	$this->goodsList = $result['goodsList'];
    	$this->count       = $result['count'];
    	$this->reduce      = $result['reduce'];
    	$this->weight      = $result['weight'];
    	$this->freeFreight = $result['freeFreight'];
    	$this->sellerData  = $result['seller'];
    	$this->spend_point = $result['spend_point'];
    	$this->goodsType   = $result['goodsType'];

		//自提点列表
		$this->takeselfList = $result['takeself'];

		//收货地址列表
		$this->addressList = $addressList;

		//获取商品税金
		$this->goodsTax    = $result['tax'];

    	//渲染页面
    	$this->redirect('cart2');
    }

	/**
	 * 生成订单
	 */
    function cart3()
    {
		//防止表单重复提交
    	if(IReq::get('timeKey'))
    	{
    		if(ISafe::get('timeKey') == IReq::get('timeKey'))
    		{
	    		IError::show(403,ILang::get('订单数据不能被重复提交'));
    		}
    		ISafe::set('timeKey',IReq::get('timeKey'));
    	}

    	$address_id    = IFilter::act(IReq::get('radio_address'),'int');
    	$delivery_id   = IFilter::act(IReq::get('delivery_id'),'int');
    	$accept_time   = IFilter::act(IReq::get('accept_time'));
    	$payment       = IFilter::act(IReq::get('payment'),'int');
    	$accept_name   = IFilter::act(IReq::get('accept_name'));
    	$accept_mobile = IFilter::act(IReq::get('accept_mobile'));
    	$order_message = IFilter::act(IReq::get('message'));
    	$ticket_id     = IFilter::act(IReq::get('ticket_id'),'int');
    	$taxes         = IFilter::act(IReq::get('taxes'),'float');
    	$invoice_id    = IFilter::act(IReq::get('invoice_id'),'int');
    	$gid           = IFilter::act(IReq::get('id'),'int');
    	$num           = IFilter::act(IReq::get('num'),'int');
    	$type          = IFilter::act(IReq::get('type'));//商品或者货品
    	$takeself      = IFilter::act(IReq::get('takeself'),'int');
    	$startDate     = IFilter::act(IReq::get('start_date'),'date');
    	$endDate       = IFilter::act(IReq::get('end_date'),'date');
    	$dataArray     = [];
    	$user_id       = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];

		//获取商品数据信息
		$preorderDate= $startDate && $endDate ? [$startDate,$endDate] : [];
    	$countSumObj = new CountSum($user_id);
		$goodsResult = $countSumObj->cart_count($gid,$type,$num,$preorderDate);
		if($countSumObj->error)
		{
			IError::show(403,$countSumObj->error);
		}

		//如果未选择开票，那么把发票数据都清空
		if(IReq::get('taxes') === null)
		{
			$invoiceData = null;
		}
		else
		{
            //发票信息处理
            $invoiceData = $invoice_id ? Api::run('getInvoiceRowById',array('#id#',$invoice_id)) : ISafe::get('invoice');
            if(!$invoiceData)
            {
                IError::show(403,ILang::get('请填写发票信息'));
            }
		}

        //根据商品类型检查条件
        if(goods_class::isDelivery($goodsResult['goodsType']))
        {
    		/**
    		 * 处理用户的收件地址
    		 */
    		//1,自提点地址
    		if($takeself)
    		{
    		    if(!$accept_name || !$accept_mobile)
    		    {
    		        IError::show(403,ILang::get('收货人和手机号没有填写'));
    		    }

    		    $takeselfDB = new IModel('takeself');
    		    $addressRow = $takeselfDB->getObj($takeself);
    		    if(!$addressRow)
    		    {
    		        IError::show(403,ILang::get('自提点信息不存在'));
    		    }
    		    $addressRow['accept_name'] = $accept_name;
    		    $addressRow['mobile']      = $accept_mobile;

    		    //配送方式ID
    		    $delivery_id = 0;
    		}
    		//2,自定义收货地址
    		else
    		{
        		//1,访客; 2,注册用户
        		if($user_id == 0)
        		{
        			$addressRow = ISafe::get('address');
        		}
        		else
        		{
        			$addressDB   = new IModel('address');
        			$addressRow  = $addressDB->getObj('id = '.$address_id.' and user_id = '.$user_id);
        		}

                //配送方式
                $deliveryObj = new IModel('delivery');
                $deliveryRow = $deliveryObj->getObj($delivery_id);
                if (!$deliveryRow)
                {
                    IError::show(403, ILang::get('配送方式不存在'));
                }

                //1,在线支付
                if($deliveryRow['type'] == 0 && $payment == 0)
                {
                    IError::show(403,ILang::get('请选择正确的支付方式'));
                }
                //2,货到付款
                else if($deliveryRow['type'] == 1)
                {
                    $payment = 0;
                }
    		}

            if(!$addressRow)
            {
                IError::show(403,ILang::get('收货地址信息不存在'));
            }

            $accept_name   = IFilter::act($addressRow['accept_name'],'name');
            $province      = $addressRow['province'];
            $city          = $addressRow['city'];
            $area          = $addressRow['area'];
            $address       = IFilter::act($addressRow['address']);
            $mobile        = IFilter::act($addressRow['mobile'],'mobile');
            $telphone      = isset($addressRow['telphone']) ? IFilter::act($addressRow['telphone'],'phone') : "";
            $zip           = isset($addressRow['zip']) ? IFilter::act($addressRow['zip'],'zip') : "";
        }
        else
        {
			$delivery_id= 0;
            $address_id = 0;
	        $mobile = $accept_mobile;
            if($payment == 0)
            {
                IError::show(403,ILang::get('请选择正确的支付方式'));
            }
        }

		//检查订单重复
    	$checkData = array(
    		"mobile" => $mobile,
    	);
    	$result = order_class::checkRepeat($checkData,$goodsResult['goodsList']);
    	if(is_string($result))
    	{
			IError::show(403,$result);
    	}

    	//判断商品是否存在
    	if(is_string($goodsResult) || !$goodsResult['goodsList'])
    	{
    		IError::show(403,ILang::get('商品数据不存在'));
    	}

		$paymentObj = new IModel('payment');
		$paymentRow = $paymentObj->getObj('id = '.$payment,'type,name');
		if(!$paymentRow)
		{
			IError::show(403,ILang::get('支付方式不存在'));
		}
		$paymentName= $paymentRow['name'];
		$paymentType= $paymentRow['type'];

		//最终订单金额计算
        $orderData = $countSumObj->countOrderFee($goodsResult,isset($area) ? $area : "",isset($delivery_id) ? $delivery_id : "",$taxes,0,$preorderDate);
        if(is_string($orderData))
		{
			IError::show(403,$orderData);
		}

		//使用了优惠券
		if($ticket_id)
		{
			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('user_id = '.$user_id,'prop');
			foreach($ticket_id as $tk => $tid)
			{
				//游客手动添加或注册用户道具中已有的优惠券
				if(ISafe::get('ticket_'.$tid) == $tid || stripos(','.trim($memberRow['prop'],',').',',','.$tid.',') !== false)
				{
					$propObj   = new IModel('prop');
					$ticketRow = $propObj->getObj('id = '.$tid.' and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1');
					if(!$ticketRow)
					{
						IError::show(403,ILang::get('优惠券不可用'));
					}

					//优惠券有效性验证
                    $verifyResult = ticket::verify($tid,$goodsResult);
					if($verifyResult['result'] == false)
                    {
                        IError::show(403,$verifyResult['error']);
                    }
					unset($ticket_id[$tk]);
					break;
				}
			}
		}

		if(!$gid)
		{
			//清空购物车
			$cartObj = new Cart();
			$cartObj->clear();
		}

		//根据商品所属商家不同批量生成订单
		$orderIdArray  = array();
		$orderNumArray = array();
		$final_sum     = 0;
		foreach($orderData as $seller_id => $goodsResult)
		{
			//生成的订单数据
			$dataArray = array(
				'order_no'            => Order_Class::createOrderNum(),
				'user_id'             => $user_id,
				'accept_name'         => isset($accept_name) ? $accept_name : "",
				'pay_type'            => $payment,
				'distribution'        => isset($delivery_id) ? $delivery_id : "",
				'postcode'            => isset($zip) ? $zip : "",
				'telphone'            => isset($telphone) ? $telphone : "",
				'province'            => isset($province) ? $province : "",
				'city'                => isset($city) ? $city : "",
				'area'                => isset($area) ? $area : "",
				'address'             => isset($address) ? $address : "",
				'mobile'              => $mobile,
				'create_time'         => ITime::getDateTime(),
				'postscript'          => $order_message,
				'accept_time'         => isset($accept_time) ? $accept_time : "",
				'exp'                 => $goodsResult['exp'],
				'point'               => $goodsResult['point'],
				'type'                => $goodsResult['promo'],

				//商品价格
				'payable_amount'      => $goodsResult['sum'],
				'real_amount'         => $goodsResult['final_sum'],

				//运费价格
				'payable_freight'     => $goodsResult['deliveryOrigPrice'],
				'real_freight'        => $goodsResult['deliveryPrice'],

				//税金
				'invoice'             => $invoiceData ? 1                          : 0,
				'invoice_info'        => $invoiceData ? JSON::encode($invoiceData) : "",
				'taxes'               => $goodsResult['taxPrice'],

				//优惠价格
				'promotions'          => $goodsResult['proReduce'] + $goodsResult['reduce'],

				//订单应付总额
				'order_amount'        => $goodsResult['orderAmountPrice'],

				//订单保价
				'insured'             => $goodsResult['insuredPrice'],

				//自提点ID
				'takeself'            => $takeself,

				//促销活动ID
				'active_id'           => $goodsResult['active_id'],

				//商家ID
				'seller_id'           => $seller_id,

				//备注信息
				'note'                => '',

				//奖励性促销规则IDS
				'prorule_ids'         => $goodsResult['giftIds'],

                //所需要支付的积分
                'spend_point'         => $goodsResult['spend_point'],

                //商品类型
                'goods_type'          => $goodsResult['goodsType'],
			);

            //优惠券金额抵扣
            if(isset($verifyResult) && $verifyResult && $seller_id == $verifyResult['seller_id'])
            {
    			$ticketRow['value']         = $verifyResult['price'] >= $goodsResult['final_sum'] ? $goodsResult['final_sum'] : $verifyResult['price'];
    			$dataArray['prop']          = $tid;
    			$dataArray['promotions']   += $ticketRow['value'];
    			$dataArray['order_amount'] -= $ticketRow['value'];
    			$goodsResult['promotion'][] = ["plan" => ILang::get('优惠券'),"info" => ILang::get('使用了优惠券').'￥'.$ticketRow['value']];

    			//锁定红包状态
    			$propObj->setData(array('is_close' => 2));
    			$propObj->update('id = '.$tid);
            }

			//促销规则
			if(isset($goodsResult['promotion']) && $goodsResult['promotion'])
			{
				foreach($goodsResult['promotion'] as $key => $val)
				{
					$dataArray['note'] .= join("，",$val)."。";
				}
			}

			$dataArray['order_amount'] = $dataArray['order_amount'] <= 0 ? 0 : $dataArray['order_amount'];

			//生成订单插入order表中
			$orderObj  = new IModel('order');
			$orderObj->setData($dataArray);
			$order_id = $orderObj->add();

			if($order_id == false)
			{
				IError::show(403,ILang::get('订单生成错误'));
			}

			/*将订单中的商品插入到order_goods表*/
	    	$orderInstance = new Order_Class();
	    	$orderGoodsResult = $orderInstance->insertOrderGoods($order_id,$goodsResult['goodsResult']);
	    	if($orderGoodsResult !== true)
	    	{
	    		IError::show(403,$orderGoodsResult);
	    	}

			//属于活动订单
			if($dataArray['type'])
			{
				Active::orderCallback($dataArray['order_no'],$dataArray);
			}

			//订单金额小于等于0直接免单
			if($dataArray['order_amount'] <= 0)
			{
				Order_Class::updateOrderStatus($dataArray['order_no']);
			}
			else
			{
				$orderIdArray[]  = $order_id;
				$orderNumArray[] = $dataArray['order_no'];
				$final_sum      += $dataArray['order_amount'];
			}

			plugin::trigger('orderCreateFinish',$dataArray);
		}

		//收货地址的处理
		if($user_id && $address_id)
		{
			$addressDefRow = $addressDB->getObj('user_id = '.$user_id.' and is_default = 1');
			if(!$addressDefRow)
			{
				$addressDB->setData(array('is_default' => 1));
				$addressDB->update('user_id = '.$user_id.' and id = '.$address_id);
			}
		}

		//获取备货时间
		$this->stockup_time = $this->_siteConfig->stockup_time ? $this->_siteConfig->stockup_time : 2;

		//数据渲染
		$this->order_id    = join("_",$orderIdArray);
		$this->final_sum   = $final_sum;
		$this->order_num   = join(" ",$orderNumArray);
		$this->payment     = $paymentName;
		$this->paymentType = $paymentType;
		$this->delivery    = isset($deliveryRow['name']) ? $deliveryRow['name'] : "";
		$this->tax_title   = isset($invoiceData['company_name']) ? $invoiceData['company_name'] : "";
		$this->deliveryType= isset($deliveryRow['type']) ? $deliveryRow['type'] : "";
		plugin::trigger('setCallback','/ucenter/order');

		//订单金额为0时，订单自动完成
		if($this->final_sum <= 0)
		{
			$this->redirect('/site/success/message/'.urlencode(ILang::get('订单确认成功等待发货')));
		}
		else
		{
			$this->setRenderData($dataArray);
			$this->redirect('cart3');
		}
    }

    //到货通知处理动作
	function arrival_notice()
	{
		$user_id  = $this->user['user_id'];
		if(!$user_id)
		{
		    $this->redirect("/simple/login/_msg/".ILang::get('请先登录账号'));
		}

		$email    = IFilter::act(IReq::get('email'),'email');
		$mobile   = IFilter::act(IReq::get('mobile'),'mobile');
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$register_time = ITime::getDateTime();

		if(!$goods_id)
		{
			IError::show(403,ILang::get('商品ID不存在'));
		}

		$model = new IModel('notify_registry');
		$obj = $model->getObj("email = '{$email}' and user_id = '{$user_id}' and goods_id = '$goods_id'");
		if(!$obj)
		{
			$model->setData(array('email'=>$email,'user_id'=>$user_id,'mobile'=>$mobile,'goods_id'=>$goods_id,'register_time'=>$register_time));
			$model->add();
		}
		else
		{
			$model->setData(array('email'=>$email,'user_id'=>$user_id,'mobile'=>$mobile,'goods_id'=>$goods_id,'register_time'=>$register_time,'notify_status'=>0));
			$model->update('id = '.$obj['id']);
		}
		$this->redirect('/site/success');
	}

	/**
	 * @brief 邮箱找回密码进行
	 */
    function find_password_email()
	{
		$username = IReq::get('username');
		if($username === null || !IValidate::name($username)  )
		{
			IError::show(403,ILang::get('请输入正确的用户名'));
		}

		$email = IReq::get("email");
		if($email === null || !IValidate::email($email ))
		{
			IError::show(403,ILang::get('请输入正确的邮箱地址'));
		}

		$tb_user  = new IModel("user as u,member as m");
		$username = IFilter::act($username);
		$email    = IFilter::act($email);
		$user     = $tb_user->getObj(" u.id = m.user_id and u.username='{$username}' AND m.email='{$email}' ");
		if(!$user)
		{
			IError::show(403,ILang::get('用户不存在'));
		}
		$hash = IHash::md5( microtime(true) .mt_rand());

		//重新找回密码的数据
		$tb_find_password = new IModel("find_password");
		$tb_find_password->setData( array( 'hash' => $hash ,'user_id' => $user['id'] , 'addtime' => time() ) );

		if($tb_find_password->query("`hash` = '{$hash}'") || $tb_find_password->add())
		{
			$url     = IUrl::getHost().IUrl::creatUrl("/simple/restore_password/hash/{$hash}/user_id/".$user['id']);
			$content = mailTemplate::findPassword(array("{url}" => $url));

			$smtp   = new SendMail();
			$result = $smtp->send($user['email'],ILang::get('您的密码找回'),$content);

			if($result===false)
			{
				IError::show(403,ILang::get('发信失败请重试'));
			}
		}
		else
		{
			IError::show(403,ILang::get('生成HASH重复请重试'));
		}
		$message = ILang::get('密码重置邮件已经发送请到您的邮箱中去激活');
		$this->redirect("/site/success/message/".urlencode($message));
	}

	//手机短信找回密码
	function find_password_mobile()
	{
		$username = IReq::get('username');
		if($username === null || !IValidate::name($username))
		{
			IError::show(403,ILang::get('请输入正确的用户名'));
		}

		$mobile = IReq::get("mobile");
		if($mobile === null || !IValidate::mobi($mobile))
		{
			IError::show(403,ILang::get('请输入正确的电话号码'));
		}

		$mobile_code = IFilter::act(IReq::get('mobile_code'));
		if($mobile_code === null)
		{
			IError::show(403,ILang::get('请输入短信校验码'));
		}

		$userDB = new IModel('user as u , member as m');
		$userRow = $userDB->getObj('u.username = "'.$username.'" and m.mobile = "'.$mobile.'" and u.id = m.user_id');
		if($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow = $findPasswordDB->getObj('user_id = '.$userRow['user_id'].' and hash = "'.$mobile_code.'"');
			if($dataRow)
			{
				//短信验证码已经过期
				if(time() - $dataRow['addtime'] > 3600)
				{
					$findPasswordDB->del("user_id = ".$userRow['user_id']);
					IError::show(403,ILang::get('您的校验码已经过期了请重新找回密码'));
				}
				else
				{
					$this->redirect('/simple/restore_password/hash/'.$mobile_code.'/user_id/'.$userRow['user_id']);
				}
			}
			else
			{
				IError::show(403,ILang::get('您输入的短信校验码错误'));
			}
		}
		else
		{
			IError::show(403,ILang::get('用户名与手机号码不匹配'));
		}
	}

	//找回密码发送手机验证码短信
	function send_message_mobile()
	{
		$username = IFilter::act(IReq::get('username'));
		$mobile = IFilter::act(IReq::get('mobile'));

		if($username === null || !IValidate::name($username))
		{
			die(ILang::get('请输入正确的用户名'));
		}

		if($mobile === null || !IValidate::mobi($mobile))
		{
			die(ILang::get('请输入正确的手机号码'));
		}

		$userDB = new IModel('user as u , member as m');
		$userRow = $userDB->getObj('u.username = "'.$username.'" and m.mobile = "'.$mobile.'" and u.id = m.user_id');

		if($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow = $findPasswordDB->query('user_id = '.$userRow['user_id'],'*','addtime desc');
			$dataRow = current($dataRow);

			//120秒是短信发送的间隔
			if( isset($dataRow['addtime']) && (time() - $dataRow['addtime'] <= 120) )
			{
				die(ILang::get('申请验证码的时间间隔过短请稍候再试'));
			}
			$mobile_code = rand(10000,99999);
			$findPasswordDB->setData(array(
				'user_id' => $userRow['user_id'],
				'hash'    => $mobile_code,
				'addtime' => time(),
			));
			if($findPasswordDB->add())
			{
				$result = _hsms::findPassword($mobile,array('{mobile_code}' => $mobile_code));
				if($result == 'success')
				{
					die('success');
				}
				die($result);
			}
		}
		else
		{
			die(ILang::get('手机号码与用户名不符合'));
		}
	}

	/**
	 * @brief 重置密码验证
	 */
	function restore_password()
	{
		$hash = IFilter::act(IReq::get("hash"));
		$user_id = IFilter::act(IReq::get("user_id"),'int');

		if(!$hash)
		{
			IError::show(403,ILang::get('找不到校验码'));
		}
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$where  = " `hash`='$hash' AND addtime > $addtime ";
		$where .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if(!$row)
		{
			IError::show(403,ILang::get('校验码已经超时'));
		}

		if($row['user_id'] != $user_id)
		{
			IError::show(403,ILang::get('验证码不属于此用户'));
		}

		$this->formAction = IUrl::creatUrl("/simple/do_restore_password/hash/$hash/user_id/".$user_id);
		$this->redirect("restore_password");
	}

	/**
	 * @brief 执行密码修改重置操作
	 */
	function do_restore_password()
	{
		$hash = IFilter::act(IReq::get("hash"));
		$user_id = IFilter::act(IReq::get("user_id"),'int');

		if(!$hash)
		{
			IError::show(403,ILang::get('找不到校验码'));
		}
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$where  = " `hash`='$hash' AND addtime > $addtime ";
		$where .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if(!$row)
		{
			IError::show(403,ILang::get('校验码已经超时'));
		}

		if($row['user_id'] != $user_id)
		{
			IError::show(403,ILang::get('验证码不属于此用户'));
		}

		//开始修改密码
		$pwd   = IReq::get("password");
		$repwd = IReq::get("repassword");
		if($pwd == null || strlen($pwd) < 6 || $repwd!=$pwd)
		{
			IError::show(403,ILang::get('新密码至少六位且两次输入的密码应该一致'));
		}
		$pwd = md5($pwd);
		$tb_user = new IModel("user");
		$tb_user->setData(array("password" => $pwd));
		$re = $tb_user->update("id='{$row['user_id']}'");
		if($re !== false)
		{
			$message = ILang::get('修改密码成功');
			$tb->del("`hash`='{$hash}'");
			$this->redirect("/site/success/message/".urlencode($message));
			return;
		}
		IError::show(403,ILang::get('密码修改失败请重试'));
	}

    //添加收藏夹
    function favorite_add()
    {
    	$goods_id = IFilter::act(IReq::get('goods_id'),'int');
    	$message  = '';

    	if($goods_id == 0)
    	{
    		$message = ILang::get('商品id值不能为空');
    	}
    	else if(!isset($this->user['user_id']) || !$this->user['user_id'])
    	{
    		$message = ILang::get('请先登录');
    	}
    	else
    	{
    		$favoriteObj = new IModel('favorite');
    		$goodsRow    = $favoriteObj->getObj('user_id = '.$this->user['user_id'].' and goods_id = '.$goods_id);
    		if($goodsRow)
    		{
    			$message = ILang::get('您已经收藏过此件商品');
    		}
    		else
    		{
    			$catObj = new IModel('category_extend');
    			$catRow = $catObj->getObj('goods_id = '.$goods_id);
    			$cat_id = $catRow ? $catRow['category_id'] : 0;

	    		$dataArray   = array(
	    			'user_id' => $this->user['user_id'],
	    			'goods_id'=> $goods_id,
	    			'time'    => ITime::getDateTime(),
	    			'cat_id'  => $cat_id,
	    		);
	    		$favoriteObj->setData($dataArray);
	    		$favoriteObj->add();
	    		$message = ILang::get('收藏成功');

	    		//商品收藏信息更新
	    		$goodsDB = new IModel('goods');
	    		$goodsDB->setData(array("favorite" => "favorite + 1"));
	    		$goodsDB->update("id = ".$goods_id,'favorite');
    		}
    	}
		$result = array(
			'isError' => true,
			'message' => $message,
		);

    	echo JSON::encode($result);
    }

    //获取oauth登录地址
    public function oauth_login()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	if($id)
    	{
    		$oauthObj = new OauthCore($id);
    		$url      = $oauthObj->getLoginUrl();
    		header('location: '.$url);
    	}
    	else
    	{
    	    IError::show(403,ILang::get('请选择要登录的平台'));
    	}
    }

    //第三方登录回调
    public function oauth_callback()
    {
    	$oauth_name = IFilter::act(IReq::get('oauth_name'));
    	$oauthObj   = new IModel('oauth');
    	$oauthRow   = $oauthObj->getObj('file = "'.$oauth_name.'"');

    	if(!$oauth_name && !$oauthRow)
    	{
    		IError::show(403,ILang::get('第三方平台信息不存在'));
    	}
		$id       = $oauthRow['id'];
    	$oauthObj = new OauthCore($id);
    	$result   = $oauthObj->checkStatus($_GET);

    	if($result === true)
    	{
    		$oauthObj->getAccessToken($_GET);
	    	$userInfo = $oauthObj->getUserInfo();

	    	if(isset($userInfo['id']) && isset($userInfo['name']) && $userInfo['id'] && $userInfo['name'])
	    	{
	    		$this->bindUser($userInfo,$id);
	    		return;
	    	}
	    	else
	    	{
	    	    IError::show(403,ILang::get('登录失败未获取到oauth信息'));
	    	}
    	}
    	else
    	{
    		$this->redirect("/simple/login/_msg/".ILang::get('登录失败或者取消'));
    	}
    }

    //同步绑定用户数据
    public function bindUser($userInfo,$oauthId)
    {
    	$userObj      = new IModel('user');
    	$oauthUserObj = new IModel('oauth_user');
    	$oauthUserRow = $oauthUserObj->getObj("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ",'user_id');
		if($oauthUserRow)
		{
			//清理oauth_user和user表不同步匹配的问题
			$tempRow = $userObj->getObj("id = '{$oauthUserRow['user_id']}'");
			if(!$tempRow)
			{
				$oauthUserObj->del("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ");
			}
		}

    	//存在绑定账号oauth_user与user表同步正常！
    	if(isset($tempRow) && $tempRow)
    	{
    		$userRow = _authorization::isValidUser($tempRow['username'],$tempRow['password']);
    		plugin::trigger("userLoginCallback",$userRow);
    		$callback = plugin::trigger('getCallback');
    		$callback = $callback ? $callback : "/ucenter/index";
			$this->redirect($callback);
    	}
    	//没有绑定账号
    	else
    	{
	    	$userCount = $userObj->getObj("username = '{$userInfo['name']}'",'count(*) as num');

	    	//没有重复的用户名
	    	if($userCount['num'] == 0)
	    	{
	    		$username = $userInfo['name'];
	    	}
	    	else
	    	{
	    		//随即分配一个用户名
	    		$username = $userInfo['name'].$userCount['num'];
	    	}
			$userInfo['name'] = $username;
	    	ISession::set('oauth_id',$oauthId);
	    	ISession::set('oauth_userInfo',$userInfo);
	    	$this->setRenderData($userInfo);
	    	$this->redirect('bind_user',false);
    	}
    }

	//执行绑定已存在用户
    public function bind_exists_user()
    {
    	$login_info     = IReq::get('login_info');
    	$password       = IReq::get('password');
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

    	if(!$oauth_id || !$oauth_userInfo || !isset($oauth_userInfo['id']))
    	{
    		IError::show(ILang::get('缺少oauth信息'));
    	}

    	if($userRow = _authorization::isValidUser($login_info,md5($password)))
    	{
    		$oauthUserObj = new IModel('oauth_user');

    		//插入关系表
    		$oauthUserData = array(
    			'oauth_user_id' => $oauth_userInfo['id'],
    			'oauth_id'      => $oauth_id,
    			'user_id'       => $userRow['user_id'],
    			'datetime'      => ITime::getDateTime(),
    		);
    		$oauthUserObj->setData($oauthUserData);
    		$oauthUserObj->add();

    		plugin::trigger("userLoginCallback",$userRow);

			//自定义跳转页面
			$this->redirect('/site/success?message='.urlencode(ILang::get('登录成功')));
    	}
    	else
    	{
    		$this->setError(ILang::get('用户名和密码不匹配'));
    		$_GET['bind_type'] = 'exists';
    		$this->redirect('bind_user',false);
    		Util::showMessage(ILang::get('用户名和密码不匹配'));
    	}
    }

	//执行绑定注册新用户用户
    public function bind_not_exists_user()
    {
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

    	if(!$oauth_id || !$oauth_userInfo || !isset($oauth_userInfo['id']))
    	{
    		IError::show(ILang::get('缺少oauth信息'));
    	}

    	//调用_userInfo注册插件
		$result = plugin::trigger('userRegAct');
		if(is_array($result))
		{
			//插入关系表
			$oauthUserObj = new IModel('oauth_user');
			$oauthUserData = array(
				'oauth_user_id' => $oauth_userInfo['id'],
				'oauth_id'      => $oauth_id,
				'user_id'       => $result['id'],
				'datetime'      => ITime::getDateTime(),
			);
			$oauthUserObj->setData($oauthUserData);
			$oauthUserObj->add();
			$msg = isset($result['msg']) ? $result['msg'] : ILang::get('恭喜您注册成功');
			$this->redirect('/site/success?message='.urlencode($msg));
		}
		else
		{
    		$this->setError($result);
    		$this->redirect('bind_user',false);
    		Util::showMessage($result);
		}
    }

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_reg()
	{
		$seller_name = IValidate::name(IReq::get('seller_name')) ? IReq::get('seller_name') : "";
		$email       = IValidate::email(IReq::get('email'))      ? IReq::get('email')       : "";
		$truename    = IValidate::name(IReq::get('true_name'))   ? IReq::get('true_name')   : "";
		$phone       = IValidate::phone(IReq::get('phone'))      ? IReq::get('phone')       : "";
		$mobile      = IValidate::mobi(IReq::get('mobile'))      ? IReq::get('mobile')      : "";
		$home_url    = IValidate::url(IReq::get('home_url'))     ? IReq::get('home_url')    : "";

		$password    = IFilter::act(IReq::get('password'));
		$repassword  = IFilter::act(IReq::get('repassword'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));

		if($password == '')
		{
			$errorMsg = ILang::get('请输入密码');
		}

		if($password != $repassword)
		{
			$errorMsg = ILang::get('两次输入的密码不一致');
		}

		if(!$seller_name)
		{
			$errorMsg = ILang::get('填写正确的登陆用户名');
		}

		if(!$truename)
		{
			$errorMsg = ILang::get('填写正确的商户真实全称');
		}

		//创建商家操作类
		$sellerDB = new IModel("seller");
		if($seller_name && $sellerDB->getObj("seller_name = '{$seller_name}'"))
		{
			$errorMsg = ILang::get('登录用户名重复');
		}
		else if($truename && $sellerDB->getObj("true_name = '{$truename}'"))
		{
			$errorMsg = ILang::get('商户真实全称重复');
		}

		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->sellerRow = IFilter::act($_POST,'text');
			$this->redirect('seller',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
			'true_name' => $truename,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'home_url'  => $home_url,
			'is_lock'   => 1,
		);

		//商户资质上传
		if(isset($_FILES['paper_img']['name']) && $_FILES['paper_img']['name'])
		{
		    $uploadDir = IWeb::$app->config['upload'].'/seller';
		    $uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['paper_img']['img']) && $photoInfo['paper_img']['img'])
			{
				$sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
			}
		}

		$sellerRow['seller_name'] = $seller_name;
		$sellerRow['password']    = md5($password);
		$sellerRow['create_time'] = ITime::getDateTime();

		$sellerDB->setData($sellerRow);
		$seller_id = $sellerDB->add();

		//商家注册完成事件
		plugin::trigger('sellerRegFinish',$seller_id);
		$this->redirect('/site/success?message='.urlencode(ILang::get('申请成功！请耐心等待管理员的审核')));
	}
	//计算购物车中选择的商品
	public function exceptCartGoodsAjax()
	{
		$data    = IFilter::act(IReq::get('data'));
		$data    = $data ? join(",",$data) : "";
		$cartObj = new Cart();
		$result  = $cartObj->setUnselected($data);
		echo JSON::encode(array("result" => $result));
	}
}
