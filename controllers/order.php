<?php
/**
 * @brief 订单模块
 * @class Order
 * @note  后台
 */
class Order extends IController implements adminAuthorization
{
	public $checkRight  = 'all';
	public $layout='admin';
	function init()
	{

	}
	/**
	 * @brief查看订单
	 */
	public function order_show()
	{
		//获得post传来的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id);
			if($data)
			{
		 		//获取地区
		 		$data['area_addr'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']));
		 		$this->orderRow    = $data;
			 	$this->setRenderData($data);
				$this->redirect('order_show',false);
			}
		}
		if(!$data)
		{
			$this->redirect('order_list');
		}
	}
	/**
	 * @brief查看收款单
	 */
	public function collection_show()
	{
		//获得post传来的收款单id值
		$collection_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($collection_id)
		{
			$tb_collection = new IQuery('collection_doc as c ');
			$tb_collection->join=' left join order as o on c.order_id=o.id left join payment as p on c.payment_id = p.id left join user as u on u.id = c.user_id';
			$tb_collection->fields = 'o.order_no,p.name as pname,o.create_time,p.type,u.username,c.amount,o.pay_time,c.admin_id,c.note';
			$tb_collection->where = 'c.id='.$collection_id;
			$collection_info = $tb_collection->find();
			if($collection_info)
			{
				$data = $collection_info[0];

				$this->setRenderData($data);
				$this->redirect('collection_show',false);
			}
		}
		if(count($data)==0)
		{
			$this->redirect('order_collection_list');
		}
	}
	/**
	 * @brief查看退款单
	 */
	public function refundment_show()
	{
	 	//获得post传来的退款单id值
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IQuery('refundment_doc as c');
	 		$db->join   ='left join user as u on u.id = c.user_id';
	 		$db->fields = 'u.username,c.*';
	 		$db->where  = 'c.id='.$id;
	 		$list = $db->find();
	 		if($list)
	 		{
	 			$data = current($list);
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('order_refundment_list');
	}
	/**
	 * @brief查看申请退款单
	 */
	public function refundment_doc_show()
	{
	 	//获得post传来的申请退款单id值
	 	$refundment_id = IFilter::act(IReq::get('id'),'int');
	 	if($refundment_id)
	 	{
	 		$refundsDB = new IModel('refundment_doc');
	 		$data = $refundsDB->getObj($refundment_id);
	 		if($data)
	 		{
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_doc_show');
	 			return;
	 		}
	 	}

	 	$this->redirect('refundment_list');
	}
	//删除申请退款单
	public function refundment_doc_del()
	{
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$refundment_id = is_array($refundment_id) ? join(",",$refundment_id) : $refundment_id;
		if($refundment_id)
		{
			$tb_refundment_doc = new IModel('refundment_doc');
			$tb_refundment_doc->del("id IN ($refundment_id)");

    		$logObj = new log('db');
    		$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"退款申请单被删除",'移除的ID：'.$refundment_id));
		}
		$this->redirect('refundment_list');
	}

	/**
	 * @brief更新申请退款单
	 */
	public function refundment_doc_show_save()
	{
		$refundment_id = IFilter::act(IReq::get('id'),'int');
		$dispose_idea  = IFilter::act(IReq::get('dispose_idea'),'text');
		$pay_status    = IFilter::act(IReq::get('pay_status'),'int');
		if($refundment_id)
		{
			$tb_refundment_doc = new IModel('refundment_doc');
			$tb_refundment_doc->setData(array(
				'pay_status'   => $pay_status,
				'dispose_idea' => $dispose_idea,
				'dispose_time' => ITime::getDateTime(),
				'admin_id'     => $this->admin['admin_id'],
			));
			$tb_refundment_doc->update($refundment_id);

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"修改了退款申请",'修改的ID：'.$refundment_id));

			//处理退款单
			plugin::trigger('refundDocUpdate',$refundment_id);
		}
		$pay_status == 3 ? $this->redirect('refundment_list') : $this->redirect('order_refundment_list');
	}
	/**
	 * @brief查看发货单
	 */
	public function delivery_show()
	{
	 	//获得post传来的发货单id值
	 	$delivery_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($delivery_id)
	 	{
	 		$tb_delivery = new IQuery('delivery_doc as c');
	 		$tb_delivery->join=' left join order as o on c.order_id=o.id left join delivery as p on c.delivery_type = p.id left join user as u on u.id = c.user_id';
	 		$tb_delivery->fields = 'c.*,o.order_no,c.order_id,p.name as pname,o.create_time,u.username';
	 		$tb_delivery->where = 'c.id='.$delivery_id;
	 		$delivery_info = $tb_delivery->find();
	 		if($delivery_info)
	 		{
	 			$data = current($delivery_info);
	 			$data['country'] = join("-",area::name($data['province'],$data['city'],$data['area']));

	 			$this->setRenderData($data);
	 			$this->redirect('delivery_show');
	 		}
	 	}

	 	if(!$data)
		{
			$this->redirect('order_delivery_list');
		}
	}
	/**
	 * @brief 支付订单页面collection_doc
	 */
	public function order_collection()
	{
	 	//去掉左侧菜单和上部导航
	 	$this->layout='';
	 	$order_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($order_id)
	 	{
	 		$order_show = new Order_Class();
	 		$data = $order_show->getOrderShow($order_id);
	 	}
	 	$this->setRenderData($data);
	 	$this->redirect('order_collection');
	}
	/**
	 * @brief 保存支付订单页面collection_doc
	 */
	public function order_collection_doc()
	{
	 	//获得订单号
	 	$order_no = IFilter::act(IReq::get('order_no'));
	 	$note     = IFilter::act(IReq::get('note'));

	 	if(Order_Class::updateOrderStatus($order_no,$this->admin['admin_id'],$note))
	 	{
		 	//生成订单日志
	    	$tb_order_log = new IModel('order_log');
	    	$tb_order_log->setData(array(
	    		'order_id' =>IFilter::act(IReq::get('id'),'int'),
	    		'user' =>$this->admin['admin_name'],
	    		'action' =>'付款',
	    		'result' =>'成功',
	    		'note' =>'订单【'.$order_no.'】被操作，付款'.IFilter::act(IReq::get('amount'),'float').'元',
	    		'addtime' => ITime::getDateTime(),
	    	));
	    	$tb_order_log->add();

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"订单更新为已付款","订单号：".$order_no.'，已经确定付款'));
	 		echo '<script type="text/javascript">parent.actionCallback();</script>';
	 	}
	 	else
	 	{
	 		echo '<script type="text/javascript">parent.actionFailCallback();</script>';
	 	}
	}
	/**
	 * @brief 退款单页面
	 */
	public function order_refundment()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$orderId   = IFilter::act(IReq::get('id'),'int');
		$refundsId = IFilter::act(IReq::get('refunds_id'),'int');

		if($orderId)
		{
			$orderDB = new Order_Class();
			$data    = $orderDB->getOrderShow($orderId);

			//已经存退款申请
			if($refundsId)
			{
				$refundsDB  = new IModel('refundment_doc');
				$refundsRow = $refundsDB->getObj($refundsId);
				$data['refunds'] = $refundsRow;
			}
			$this->setRenderData($data);
			$this->data = $data;
			$this->redirect('order_refundment');
			return;
		}
		die('订单数据不存在');
	}
	/**
	 * @brief 保存退款单页面
	 */
	public function order_refundment_doc()
	{
		$refunds_id = IFilter::act(IReq::get('refunds_id'),'int');
		$amount   = IFilter::act(IReq::get('amount'),'float');
		$order_id = IFilter::act(IReq::get('id'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));
		$user_id  = IFilter::act(IReq::get('user_id'),'int');
		$order_goods_id = IFilter::act(IReq::get('order_goods_id'),'int'); //要退款的商品,如果是用户已经提交的退款申请此数据为NULL,需要获取出来
		$way = IFilter::act(IReq::get('way'));
		$refunds_nums = IFilter::act( IReq::get('refunds_nums'),'int' );

		//访客订单不能退款到预存款中
		if(!$user_id && $way == "balance")
		{
			die('<script text="text/javascript">parent.actionCallback("游客无法退款");</script>');
		}

		//过滤多余的商品数量
		if($refunds_nums)
		{
			foreach($refunds_nums as $key => $item)
			{
				if(!isset($order_goods_id[$key]) || $item <= 0)
				{
					unset($refunds_nums[$key]);
				}
			}

			if(!$order_goods_id || count($order_goods_id) != count($refunds_nums))
			{
				IError::show(403,"退款数量不匹配");
			}
		}

		//1,退款单存在更新退款价格
		$tb_refundment_doc = new IModel('refundment_doc');
		if($refunds_id)
		{
			if($amount > 0)
			{
				$tb_refundment_doc->setData(['amount' => $amount]);
				$tb_refundment_doc->update("id = ".$refunds_id." and pay_status = 0");
			}
		}
		//2,无退款申请单，必须生成退款单
		else
		{
			if(!$order_goods_id)
			{
				die('<script text="text/javascript">parent.actionCallback("请选择要退款的商品");</script>');
			}

			$orderDB = new IModel('order');
			$orderRow= $orderDB->getObj("id = ".$order_id);

			//插入refundment_doc表
			$updateData = array(
				'amount'        => $amount,
				'order_no'      => $order_no,
				'order_id'      => $order_id,
				'admin_id'      => $this->admin['admin_id'],
				'pay_status'    => 1,
				'dispose_time'  => ITime::getDateTime(),
				'dispose_idea'  => '',
				'user_id'       => $user_id,
				'time'          => ITime::getDateTime(),
				'seller_id'     => $orderRow['seller_id'],
				'order_goods_id'=> join(",",$order_goods_id),
				'order_goods_nums' => join(",",$refunds_nums),
			);
			$tb_refundment_doc->setData($updateData);
			$refunds_id = $tb_refundment_doc->add();
		}

		try
		{
			$result = Order_Class::refund($refunds_id,$this->admin['admin_id'],'admin',$way);
		}
		catch(exception $e)
		{
			$result = $e->getMessage();
		}

		if(is_string($result))
		{
			$tb_refundment_doc->rollback();
			die('<script text="text/javascript">parent.actionCallback("'.$result.'");</script>');
		}
		else
		{
			//记录操作日志
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"订单更新为退款",'订单号：'.$order_no));
			die('<script text="text/javascript">parent.actionCallback();</script>');
		}
	}
	/**
	 * @brief 保存订单备注
	 */
	public function order_note()
	{
	 	//获得post数据
	 	$order_id = IFilter::act(IReq::get('order_id'),'int');
	 	$note = IFilter::act(IReq::get('note'),'text');

	 	//获得order的表对象
	 	$tb_order =  new IModel('order');
	 	$tb_order->setData(array(
	 		'note'=>$note
	 	));
	 	$tb_order->update('id='.$order_id);
	 	IReq::set('id',$order_id);
	 	$this->order_show();
	}
	/**
	 * @brief 完成或作废订单页面
	 **/
	public function order_complete()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$type     = IFilter::act(IReq::get('type'),'int');
		$order_no = IFilter::act(IReq::get('order_no'));

		//oerder表的对象
		$tb_order = new IModel('order');
		$tb_order->setData(array(
			'status'          => $type,
			'completion_time' => ITime::getDateTime(),
		));
		if(!$tb_order->update($order_id))
		{
		    die('fail');
		}

		//生成订单日志
		$tb_order_log = new IModel('order_log');
		$action = '作废';
		$note   = '订单【'.$order_no.'】作废成功';

		if($type=='5')
		{
			$action = '完成';
			$note   = '订单【'.$order_no.'】完成成功';

			//完成订单并且进行支付
			Order_Class::updateOrderStatus($order_no);

			//增加用户评论商品机会
			Order_Class::addGoodsCommentChange($order_id);

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"订单更新为完成",'订单号：'.$order_no));

			//发送完成事件
			$orderRow = $tb_order->getObj('id = '.$order_id);
			plugin::trigger('orderConfirmFinish',$orderRow);
		}
		else
		{
			Order_class::resetOrderProp($order_id);

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"订单更新为作废",'订单号：'.$order_no));

			//发送作废事件
			$orderRow = $tb_order->getObj('id = '.$order_id);
			plugin::trigger('orderCancelFinish',$orderRow);
		}

		$tb_order_log->setData(array(
			'order_id' => $order_id,
			'user'     => $this->admin['admin_name'],
			'action'   => $action,
			'result'   => '成功',
			'note'     => $note,
			'addtime'  => ITime::getDateTime(),
		));
		$tb_order_log->add();
		die('success');
	}
	/**
	 * @brief 发货订单页面
	 */
	public function order_deliver()
	{
		//去掉左侧菜单和上部导航
		$this->layout='';
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id);
		}
		$this->setRenderData($data);
		$this->redirect('order_deliver');
	}
	/**
	 * @brief 发货操作
	 */
	public function order_delivery_doc()
	{
	 	//获得post变量参数
	 	$order_id = IFilter::act(IReq::get('id'),'int');
		$type = IFilter::act(IReq::get('type'));

		//发送的商品关联
		if($type == 'all')
		{
			$sendgoods = [];
			$orderDB = new IModel('order_goods');
			$orderList = $orderDB->query('order_id = '.$order_id);
			foreach($orderList as $key => $val)
			{
				$sendgoods[] = $val['id'];
			}
		}
		else
		{
			$sendgoods = IFilter::act(IReq::get('sendgoods'),'int');
		}

	 	if(!$sendgoods)
	 	{
	 		die('<script type="text/javascript">parent.actionCallback("请选择要发货的商品");</script>');
	 	}

	 	$result = Order_Class::sendDeliveryGoods($order_id,$sendgoods);

		if($result === true)
		{
			die('<script type="text/javascript">parent.actionCallback();</script>');
		}
		die('<script type="text/javascript">parent.actionCallback("'.$result.'");</script>');
	}
	/**
	 * @brief 保存修改订单
	 */
    public function order_update()
    {
    	//获取必要数据
    	$order_id = IFilter::act(IReq::get('id'),'int');

    	//生成order数据
    	$dataArray                  = array();
    	$dataArray['invoice']       = IFilter::act(IReq::get('invoice'),'int');
    	$dataArray['pay_type']      = IFilter::act(IReq::get('pay_type'),'int');
    	$dataArray['accept_name']   = IFilter::act(IReq::get('accept_name'));
    	$dataArray['postcode']      = IFilter::act(IReq::get('postcode'));
    	$dataArray['telphone']      = IFilter::act(IReq::get('telphone'));
    	$dataArray['province']      = IFilter::act(IReq::get('province'),'int');
    	$dataArray['city']          = IFilter::act(IReq::get('city'),'int');
    	$dataArray['area']          = IFilter::act(IReq::get('area'),'int');
    	$dataArray['address']       = IFilter::act(IReq::get('address'));
    	$dataArray['mobile']        = IFilter::act(IReq::get('mobile'));
    	$dataArray['discount']      = $order_id ? IFilter::act(IReq::get('discount'),'float') : 0;
    	$dataArray['postscript']    = IFilter::act(IReq::get('postscript'));
    	$dataArray['distribution']  = IFilter::act(IReq::get('distribution'),'int');
    	$dataArray['accept_time']   = IFilter::act(IReq::get('accept_time'));
    	$dataArray['takeself']      = IFilter::act(IReq::get('takeself'));
    	$dataArray['real_freight']  = IFilter::act(IReq::get('real_freight'));
    	$dataArray['note']          = IFilter::act(IReq::get('note'));
    	if($dataArray['invoice'] == 1)
    	{
    		$dataArray['invoice_info'] = JSON::encode(array(
    			"company_name" => IFilter::act(IReq::get('invoice_company_name')),
    			"taxcode"      => IFilter::act(IReq::get('invoice_taxcode')),
    			"address"      => IFilter::act(IReq::get('invoice_address')),
    			"telphone"     => IFilter::act(IReq::get('invoice_telphone')),
    			"bankname"     => IFilter::act(IReq::get('invoice_bankname')),
    			"bankno"       => IFilter::act(IReq::get('invoice_bankno')),
    			"type"         => IFilter::act(IReq::get('invoice_type')),
    		));
    	}

		//设置订单持有者
		$username = IFilter::act(IReq::get('username'));
		$userDB   = new IModel('user');
		$userRow  = $userDB->getObj('username = "'.$username.'"');
		$dataArray['user_id'] = isset($userRow['id']) ? $userRow['id'] : 0;

		//拼接要购买的商品或货品数据,组装成固有的数据结构便于计算价格
		$goodsId   = IFilter::act(IReq::get('goods_id'));
		$productId = IFilter::act(IReq::get('product_id'));
		$num       = IFilter::act(IReq::get('goods_nums'));

		$goodsArray  = array();
		$productArray= array();
		if($goodsId)
		{
	    	foreach($goodsId as $key => $goods_id)
	    	{
	    		if(!$goods_id)
	    		{
	    			continue;
	    		}

	    		$pid = $productId[$key];
	    		$nVal= $num[$key];

	    		if($pid > 0)
	    		{
	    			$productArray[$pid] = $nVal;
	    		}
	    		else
	    		{
	    			$goodsArray[$goods_id] = $nVal;
	    		}
	    	}
		}

		if(!$goodsArray && !$productArray)
		{
			IError::show("商品信息不存在");
		}

		//开始算账
		$countSumObj  = new CountSum($dataArray['user_id']);
		$cartObj      = new Cart();
		$countSumObj->method = 'offline';
		$goodsResult  = $countSumObj->goodsCount($cartObj->cartFormat(array("goods" => $goodsArray,"product" => $productArray)));
		$orderData    = $countSumObj->countOrderFee($goodsResult,$dataArray['province'],$dataArray['distribution'],$dataArray['invoice'],$dataArray['discount']);
		if(is_string($orderData))
		{
			IError::show(403,$orderData);
		}

		//根据商品所属商家不同批量生成订单
		foreach($orderData as $seller_id => $goodsResult)
		{
			//运费自定义
			if(is_numeric($dataArray['real_freight']) && $goodsResult['deliveryPrice'] != $dataArray['real_freight'])
			{
				$goodsResult['orderAmountPrice'] += $dataArray['real_freight'] - $goodsResult['deliveryPrice'];
				$goodsResult['deliveryPrice']     = $dataArray['real_freight'];
			}
			$dataArray['payable_freight']= $goodsResult['deliveryOrigPrice'];
			$dataArray['payable_amount'] = $goodsResult['sum'];
			$dataArray['real_amount']    = $goodsResult['final_sum'];
			$dataArray['real_freight']   = $goodsResult['deliveryPrice'];
			$dataArray['insured']        = $goodsResult['insuredPrice'];
			$dataArray['taxes']          = $goodsResult['taxPrice'];
			$dataArray['promotions']     = $goodsResult['proReduce'] + $goodsResult['reduce'];
			$dataArray['order_amount']   = $goodsResult['orderAmountPrice'] <= 0 ? 0 : $goodsResult['orderAmountPrice'];
			$dataArray['exp']            = $goodsResult['exp'];
			$dataArray['point']          = $goodsResult['point'];
			$dataArray['goods_type']     = $goodsResult['goodsType'];

			//商家ID
			$dataArray['seller_id'] = $seller_id;

	    	//生成订单
	    	$orderDB = new IModel('order');

	    	//修改操作
	    	if($order_id)
	    	{
	    		//获取订单信息
	    		$orderRow = $orderDB->getObj('id = '.$order_id);

	    		//修改订单不能加入其他商家产品
	    		if(count($orderData) != 1 || $orderRow['seller_id'] != $seller_id)
	    		{
					IError::show(403,"此订单中不能混入其他商家的商品");
	    		}

	    		//订单中已经使用了优惠券
	    		if(isset($orderRow['prop']) && $orderRow['prop'])
	    		{
					$propObj   = new IModel('prop');
					$ticketRow = $propObj->getObj('id = '.$orderRow['prop']);
					if($ticketRow)
					{
						$ticketRow['value']         = $ticketRow['value'] >= $goodsResult['final_sum'] ? $goodsResult['final_sum'] : $ticketRow['value'];
						$dataArray['promotions']   += $ticketRow['value'];
						$dataArray['order_amount'] -= $ticketRow['value'];
					}
	    		}

	    		$orderDB->setData($dataArray);
	    		$orderDB->update('id = '.$order_id);

				//记录日志信息
				$logObj = new log('db');
				$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"修改了订单信息",'订单号：'.$orderRow['order_no']));
	    	}
	    	//添加操作
	    	else
	    	{
	    		$dataArray['create_time'] = ITime::getDateTime();
	    		$dataArray['order_no']    = Order_Class::createOrderNum();

	    		$orderDB->setData($dataArray);
	    		$order_id = $orderDB->add();

				//记录日志信息
				$logObj = new log('db');
				$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"添加了订单信息",'订单号：'.$dataArray['order_no']));

				plugin::trigger('orderCreateFinish',$dataArray);
			}

	    	//同步order_goods表
	    	$orderInstance = new Order_Class();
	    	$result = $orderInstance->insertOrderGoods($order_id,$goodsResult['goodsResult']);
	    	if($result !== true)
	    	{
	    		IError::show($result);
	    	}
		}

    	$this->redirect('order_list');
    }
	/**
	 * @brief 修改订单
	 */
	public function order_edit()
    {
    	$data = array();

    	//获得order_id的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		if($order_id)
		{
			$orderDB = new IModel('order');
			$data    = $orderDB->getObj('id = '.$order_id);
			if(Order_class::getOrderStatus($data) >= 3)
			{
				IError::show(403,"当前订单状态不允许修改");
			}

			$this->orderRow = $data;

			//存在自提点
			if($data['takeself'])
			{
				$takeselfObj = new IModel('takeself');
				$takeselfRow = $takeselfObj->getObj('id = '.$data['takeself']);
				$dataArea    = area::name($takeselfRow['province'],$takeselfRow['city'],$takeselfRow['area']);
				$takeselfRow['province_str'] = $dataArea[$takeselfRow['province']];
				$takeselfRow['city_str']     = $dataArea[$takeselfRow['city']];
				$takeselfRow['area_str']     = $dataArea[$takeselfRow['area']];
				$this->takeself = $takeselfRow;
			}

			//获取订单中的商品信息
			$orderGoodsDB         = new IQuery('order_goods as og');
			$orderGoodsDB->join   = "left join goods as go on og.goods_id = go.id left join products as p on p.id = og.product_id";
			$orderGoodsDB->fields = "go.id,go.name,p.spec_array,p.id as product_id,og.real_price,og.goods_nums,go.goods_no,p.products_no";
			$orderGoodsDB->where  = "og.order_id = ".$order_id;
			$this->orderGoods     = $orderGoodsDB->find();

			//获取用户名
			if($data['user_id'])
			{
				$userDB  = new IModel('user');
				$userRow = $userDB->getObj("id = ".$data['user_id']);
				$this->username = isset($userRow['username']) ? $userRow['username'] : '';
			}
		}
		$this->redirect('order_edit');
    }
    /**
     * @brief 订单列表
     */
    public function order_list()
    {
		//搜索条件
		$search       = IFilter::act(IReq::get('search'));
		$page         = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$searchString = http_build_query(array('search' => $search));

		//条件筛选处理
		list($join,$where) = order_class::getSearchCondition($search);

		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,d.name as distribute_name,p.name as payment_name";
		$orderHandle->page   = $page;
		$orderHandle->where  = $where;
		$orderHandle->join   = $join;

		$this->orderHandle = $orderHandle;
		$this->setRenderData(array('search' => $searchString));
		$this->redirect("order_list");
    }
    /**
     * @brief 订单删除功能_删除到回收站
     */
    public function order_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');

    	//生成order对象
    	$tb_order = new IModel('order');
    	$tb_order->setData(array('if_del'=>1));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			//获取订单编号
			$orderRs   = $tb_order->query(Util::joinStr($id),'order_no');
			$orderData = array();
			foreach($orderRs as $val)
			{
				$orderData[] = $val['order_no'];
			}

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"订单移除到回收站内",'订单号：'.join(',',$orderData)));
		}
		$this->redirect('order_list');
    }
	/**
     * @brief 收款单删除功能_删除到回收站
     */
    public function collection_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('collection_doc');
    	$tb_order->setData(array('if_del'=>1));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"收款单移除到回收站内",'收款单ID：'.join(',',$id)));

			$this->redirect('order_collection_list');
		}
		else
		{
			$this->redirect('order_collection_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
     * @brief 收款单删除功能_删除回收站中的数据，彻底删除
     */
    public function collection_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('collection_doc');
    	if($id)
		{
			$tb_order->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除回收站内的收款单",'收款单ID：'.join(',',$id)));

			$this->redirect('collection_recycle_list');
		}
		else
		{
			$this->redirect('collection_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
	 * @brief 还原还款单列表
	 */
    public function collection_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('collection_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"恢复了回收站内的收款单",'收款单ID：'.join(',',$id)));

			$this->redirect('collection_recycle_list');
		}
		else
		{
			$this->redirect('collection_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
	/**
	 * @brief 退款单删除功能_删除到回收站
	 */
    public function refundment_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$tb_order = new IModel('refundment_doc');
    	$tb_order->setData(array('if_del'=>1));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"退款单移除到回收站内",'退款单ID：'.join(',',$id)));
		}
		$this->redirect('order_refundment_list');
    }
	/**
	 * @brief 退款单删除功能_删除回收站中的数据，彻底删除
	 */
    public function refundment_recycle_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$tb_order = new IModel('refundment_doc');
    	if($id)
		{
			$tb_order->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除了回收站内的退款单",'退款单ID：'.join(',',$id)));
		}
		$this->redirect('refundment_recycle_list');
    }
	/**
	 * @brief 还原还款单列表
	 */
    public function refundment_recycle_restore()
    {
    	$id = IFilter::act(IReq::get('id'),'int');

    	$tb_order = new IModel('refundment_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"还原了回收站内的退款单",'退款单ID：'.join(',',$id)));

			$this->redirect('refundment_recycle_list');
		}
		else
		{
			$this->redirect('refundment_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
    /**
     * @brief 发货单删除功能_删除到回收站
     */
    public function delivery_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('delivery_doc');
    	$tb_order->setData(array('if_del'=>1));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"发货单移除到回收站内",'发货单ID：'.join(',',$id)));

			$this->redirect('order_delivery_list');
		}
		else
		{
			$this->redirect('order_delivery_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
     * @brief 发货单删除功能_删除回收站中的数据，彻底删除
     */
    public function delivery_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('delivery_doc');
    	if($id)
		{
			$tb_order->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除了回收站中的发货单",'发货单ID：'.join(',',$id)));

			$this->redirect('delivery_recycle_list');
		}
		else
		{
			$this->redirect('delivery_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
	/**
	 * @brief 还原发货单列表
	 */
    public function delivery_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('delivery_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"还原了回收站中的发货单",'发货单ID：'.join(',',$id)));

			$this->redirect('delivery_recycle_list');
		}
		else
		{
			$this->redirect('delivery_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
    /**
     * @brief 订单删除功能_删除回收站中的数据，彻底删除
     */
    public function order_recycle_del()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');

    	//生成order对象
    	$tb_order = new IModel('order');

    	if($id)
		{
			$id = is_array($id) ? join(',',$id) : $id;

			Order_class::resetOrderProp($id);

			//删除订单
			$tb_order->del('id in ('.$id.')');

			//记录日志
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除回收站中退货单",'退货单ID：'.$id));

			//删除订单相关的表
			$orderExtTables = array("order_log","order_goods","delivery_doc","collection_doc","refundment_doc","order_goods_servicefee","order_download_relation","order_code_relation","order_extend_preorder");
			foreach($orderExtTables as $tableName)
			{
				$orderExtDB = new IModel($tableName);
				$orderExtDB->del("order_id in (".$id.")");
			}

			$this->redirect('order_recycle_list');
		}
		else
		{
			$this->redirect('order_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
    }
    /**
	 * @brief 还原订单列表
	 */
    public function order_recycle_restore()
    {
    	//post数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	//生成order对象
    	$tb_order = new IModel('order');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));
			$this->redirect('order_recycle_list');
		}
		else
		{
			$this->redirect('order_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }
	/**
	 * @brief 订单打印模板修改
	 */
    public function print_template()
    {
		//获取根目录路径
		$path = $this->getViewPath().$this->getId();

    	//获取 购物清单模板
		$ifile_shop = new IFile($path.'/shop_template.html');
		$arr['ifile_shop']=$ifile_shop->read();
		//获取 配货单模板
		$ifile_pick = new IFile($path."/pick_template.html");
		$arr['ifile_pick']=$ifile_pick->read();

		$this->setRenderData($arr);
		$this->redirect('print_template');
    }
	/**
	 * @brief 订单打印模板修改保存
	 */
    public function print_template_update()
    {
		// 获取POST数据
    	$con_shop = IReq::get("con_shop");
		$con_pick = IReq::get("con_pick");

    	//获取根目录路径
		$path = $this->getViewPath().$this->getId();
    	//保存 购物清单模板
		$ifile_shop = new IFile($path.'/shop_template.html','w');
		if(!($ifile_shop->write($con_shop)))
		{
			$this->redirect('print_template',false);
			Util::showMessage('保存购物清单模板失败！');
		}
		//保存 配货单模板
		$ifile_pick = new IFile($path."/pick_template.html",'w');
		if(!($ifile_pick->write($con_pick)))
		{
			$this->redirect('print_template',false);
			Util::showMessage('保存配货单模板失败！');
		}
		$this->setRenderData(array('where'=>''));
		$this->redirect('order_list');
	}

	//购物单
	public function shop_template()
	{
		$this->layout='print';
		$order_id = IFilter::act( IReq::get('id'),'int' );
		$seller_id= IFilter::act( IReq::get('seller_id'),'int' );

		$tb_order = new IModel('order');
		$where    = $seller_id ? 'id='.$order_id.' and seller_id = '.$seller_id : 'id='.$order_id;
		$data     = $tb_order->getObj($where);
		if(!$data)
		{
			IError::show(403,"您没有权限查阅该订单");
		}

		if($data['seller_id'])
		{
			$sellerObj   = new IModel('seller');
			$config_info = $sellerObj->getObj('id = '.$data['seller_id']);

	     	$data['set']['name']   = isset($config_info['true_name'])? $config_info['true_name'] : '';
	     	$data['set']['phone']  = isset($config_info['phone'])    ? $config_info['phone']     : '';
	     	$data['set']['email']  = isset($config_info['email'])    ? $config_info['email']     : '';
	     	$data['set']['url']    = isset($config_info['home_url']) ? $config_info['home_url']  : '';
		}
		else
		{
			$config = new Config("site_config");
			$config_info = $config->getInfo();

	     	$data['set']['name']   = isset($config_info['name'])  ? $config_info['name']  : '';
	     	$data['set']['phone']  = isset($config_info['phone']) ? $config_info['phone'] : '';
	     	$data['set']['email']  = isset($config_info['email']) ? $config_info['email'] : '';
	     	$data['set']['url']    = isset($config_info['url'])   ? $config_info['url']   : '';
		}

		$data['address']   = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']))."&nbsp;".$data['address'];
		$this->setRenderData($data);
		$this->redirect("shop_template");
	}
	//配货单
	public function pick_template()
	{
		$this->layout='print';
		$order_id = IFilter::act( IReq::get('id'),'int' );
		$seller_id= IFilter::act( IReq::get('seller_id'),'int' );

		$tb_order = new IModel('order');
		$where    = $seller_id ? 'id='.$order_id.' and seller_id = '.$seller_id : 'id='.$order_id;
		$data     = $tb_order->getObj($where);
		if(!$data)
		{
			IError::show(403,"您没有权限查阅该订单");
		}
 		//获取地区
 		$data['address'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']))."&nbsp;".$data['address'];

		$this->setRenderData($data);
		$this->redirect('pick_template');
	}

	/**
	 * @brief 添加/修改发货信息
	 */
	public function ship_info_edit()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get("id"),'int');
    	$ship_info = array();
    	if($id)
    	{
    		$tb_ship   = new IModel("merch_ship_info");
    		$ship_info = $tb_ship->getObj("id=".$id." and seller_id = 0");
    		if(!$ship_info)
    		{
    			IError::show(403,'数据信息不存在');
    		}
    	}
    	$this->setRenderData(array('ship' => $ship_info));
		$this->redirect('ship_info_edit');
	}
	/**
	 * @brief 设置发货信息的默认值
	 */
	public function ship_info_default()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::string(IReq::get('default'));
        $tb_merch_ship_info = new IModel('merch_ship_info');
        if($default == 1)
        {
            $tb_merch_ship_info->setData(array('is_default'=>0));
            $tb_merch_ship_info->update("seller_id = 0");
        }
        $tb_merch_ship_info->setData(array('is_default'=>$default));
        $tb_merch_ship_info->update("id = ".$id." and seller_id = 0");
        $this->redirect('ship_info_list');
	}
	/**
	 * @brief 保存添加/修改发货信息
	 */
	public function ship_info_update()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
    	$ship_name = IFilter::act(IReq::get('ship_name'));
    	$ship_user_name = IFilter::act(IReq::get('ship_user_name'));
    	$sex = IFilter::act(IReq::get('sex'),'int');
    	$province =IFilter::act(IReq::get('province'),'int');
    	$city = IFilter::act(IReq::get('city'),'int');
    	$area = IFilter::act(IReq::get('area'),'int');
    	$address = IFilter::act(IReq::get('address'));
    	$postcode = IFilter::act(IReq::get('postcode'),'int');
    	$mobile = IFilter::act(IReq::get('mobile'));
    	$telphone = IFilter::act(IReq::get('telphone'));
    	$is_default = IFilter::act(IReq::get('is_default'),'int');

    	$tb_merch_ship_info = new IModel('merch_ship_info');

    	//判断是否已经有了一个默认地址
    	if(isset($is_default) && $is_default==1)
    	{
    		$tb_merch_ship_info->setData(array('is_default' => 0));
    		$tb_merch_ship_info->update('seller_id = 0');
    	}
    	//设置存储数据
    	$arr['ship_name'] = $ship_name;
	    $arr['ship_user_name'] = $ship_user_name;
	    $arr['sex'] = $sex;
    	$arr['province'] = $province;
    	$arr['city'] =$city;
    	$arr['area'] =$area;
    	$arr['address'] = $address;
    	$arr['postcode'] = $postcode;
    	$arr['mobile'] = $mobile;
    	$arr['telphone'] =$telphone;
    	$arr['is_default'] = $is_default;
    	$arr['seller_id'] = 0;

    	$tb_merch_ship_info->setData($arr);
    	//判断是添加还是修改
    	if($id)
    	{
    		$tb_merch_ship_info->update('id='.$id." and seller_id = 0");
    	}
    	else
    	{
    		$tb_merch_ship_info->add();
    	}
		$this->redirect('ship_info_list');
	}
	/**
	 * @brief 删除发货信息到回收站中
	 */
	public function ship_info_del()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');

		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
    	$tb_merch_ship_info->setData(array('is_del' => 1));
		if($id)
		{
			$tb_merch_ship_info->update(Util::joinStr($id)." and seller_id = 0");
			$this->redirect('ship_info_list');
		}
		else
		{
			$this->redirect('ship_info_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}
	/**
	 * @brief 还原回收站的信息到列表
	 */
	public function recycle_restore()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
    	$tb_merch_ship_info->setData(array('is_del' => 0));
		if($id)
		{
			$tb_merch_ship_info->update(Util::joinStr($id)." and seller_id = 0");
			$this->redirect('ship_recycle_list');
		}
		else
		{
			$this->redirect('ship_recycle_list',false);
		}
	}
	/**
	 * @brief 删除收货地址的信息
	 */
	public function recycle_del()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
		if($id)
		{
			$tb_merch_ship_info->del(Util::joinStr($id).' and seller_id = 0');
			$this->redirect('ship_recycle_list');
		}
		else
		{
			$this->redirect('ship_recycle_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}

	//订单导出excel 参考订单列表
	public function order_report()
	{
		//搜索条件
		$search = IReq::get('search');

		//条件筛选处理
		list($join,$where) = order_class::getSearchCondition($search);

		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,d.name as distribute_name,p.name as payment_name";
		$orderHandle->join   = $join;
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();

		$reportObj = new report('order');
		$reportObj->setTitle(array("订单编号","下单日期","完成日期","配送方式","收货人","收货地址","电话","订单金额","退款金额","商户手续费","支付方式","支付状态","发货状态","商品信息","订单备注"));

		//订单退款单
		$refundDB = new IModel('refundment_doc');

		//汇总数据
		$orderAmountSum = 0;
		$refundAmountSum = 0;
		$servicefeeAmountSum = 0;
		foreach($orderList as $k => $val)
		{
			$orderGoods = Order_class::getOrderGoods($val['id']);
			$strGoods   = "";
			foreach($orderGoods as $good)
			{
				$strGoods .= "商品编号：".$good['goodsno']." 商品名称：".$good['name']." 商品数量：".$good['goods_nums'];
				if ( isset($good['value']) && $good['value'] )
				{
					$strGoods .= " 规格：".$good['value'];
				}
				$strGoods .= ";";
			}

			$refundRow = $refundDB->getObj('pay_status = 2 and order_id = '.$val['id'],"SUM(`amount`) as refund_amount");
			$insertData = array(
				$val['order_no'],
				$val['create_time'],
				$val['completion_time'],
				$val['distribute_name'],
				$val['accept_name'],
				join(' ',area::name($val['province'],$val['city'],$val['area'])).$val['address'],
				$val['telphone'].'&nbsp;'.$val['mobile'],
				$val['order_amount'],
				$refundRow['refund_amount'],
				$val['servicefee_amount'],
				$val['payment_name'],
				Order_Class::getOrderPayStatusText($val),
				Order_Class::getOrderDistributionStatusText($val),
				$strGoods,
				$val['note'],
			);
			$reportObj->setData($insertData);

			//统计汇总数据
			$orderAmountSum += $val['order_amount'];
			$refundAmountSum += $refundRow['refund_amount'];
			$servicefeeAmountSum += $val['servicefee_amount'];
		}

		//插入汇总
		$reportObj->setTail(["统计汇总","","","","","","",$orderAmountSum,$refundAmountSum,$servicefeeAmountSum,"","","","",""]);
		$reportObj->toDownload();
	}

	//商品筛选页面
	function search()
	{
		$this->setRenderData($_GET);
		$this->redirect('search');
	}

	//快递单编辑页面
	public function expresswaybill_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$expressRow = Api::run('getExpresswaybillById',array('id' => $id));
		if(!$expressRow)
		{
			IError::show(403,'快递公司信息不存在');
		}
		$this->expressRow = $expressRow;
		$this->redirect('expresswaybill_edit');
	}

	//快递单更新操作
	public function expresswaybill_update()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$is_open = IFilter::act(IReq::get('is_open'),'int');
		$updateData = array(
			'is_open' => $is_open,
		);

		$expressDB = new IModel('expresswaybill');
		$expressRow= $expressDB->getObj($id);
		if(!$expressRow)
		{
			IError::show(403,'快递公司信息不存在');
		}

		if(isset($expressRow['config']) && $expressRow['config'])
		{
			$configArray = JSON::decode($expressRow['config']);
			foreach($configArray as $key => $item)
			{
				$configArray[$key] = IFilter::act(IReq::get($key));
			}
			$updateData['config'] = JSON::encode($configArray);
		}
		$expressDB->setData($updateData);
		$expressDB->update($id);
		$this->redirect('print_template');
	}

	//快递单模板选择
	public function expresswaybill_template()
	{
		$expressData = Api::run('getExpresswaybillIsOpen');
		if(!$expressData)
		{
			IError::show(403,'当前系统无可用的面单打印物流公司');
		}

		$id = IFilter::act(IReq::get('id'),'int');
		if(!$id)
		{
			IError::show(403,'缺少订单ID编号');
		}
		$id = is_array($id) ? $id : array($id);

		//发货地址信息
		$this->shipList = Api::run('getShipInfoList',array('is_del' => 0),'is_default-',10);
		$this->orderList = Api::run('getOrderListWithArea',$id);
		$this->expressList = $expressData;
		$this->redirect('expresswaybill_template');
	}

	//下单物流公司API接口ajax操作
	public function expresswaybill_ajax()
	{
		$orderId   = IFilter::act(IReq::get('id'),'int');
		$expressId = IFilter::act(IReq::get('expressId'),'int');
		$shipId    = IFilter::act(IReq::get('shipId'),'int');

		if(!$orderId || !$expressId || !$shipId)
		{
			die(array('status' => 'fail','error' => '缺少订单ID编号、快递公司ID、发货地址信息'));
		}

		$expressObj = new expresswaybill($orderId,$expressId,$shipId);
		$result = $expressObj->run();
		die(JSON::encode($result));
	}

	//快递单打印
	public function expresswaybill_print()
	{
		$orderId = explode("_",IReq::get('id'));
		$orderId = IFilter::act($orderId,'int');
		$printSet= IFilter::act(IReq::get('printSet'));
		if(!$orderId || !$printSet)
		{
			IError::show(403,'缺少订单ID编号或者打印机名称');
		}
		expresswaybill::batPrint($orderId,$printSet);
	}

    /**
     * 验证服务短信验证码
     */
    public function check_code_ajax()
    {
        $result = ['success' => false,'msg' => '操作失败，请重试'];
        $code   = strtoupper(IFilter::act(IReq::get('code')));
		$seller_id = IFilter::act(IReq::get('seller_id'),'int');
        if($code)
        {
            switch($code[0])
            {
                case "S":
                {
                    $result = Api::run('getCodeInfo',$code);

                    //验证成功
                    if(isset($result['success']) && $result['success'] == true)
                    {
						$goodsCodeRelationDB = new IModel('order_code_relation');
                        $goodsCodeRelationDB->setData(array(
                            'use_time' => ITime::getDateTime(),
                            'is_used'  => 1
                        ));

                        if(!$goodsCodeRelationDB->update('code = "'.$code.'"'))
                        {
                            $result = array('success' => false,'msg' => '核销更新失败，请重试');
                        }
                        else
                        {
							$sendor = $seller_id ? 'seller' : 'admin';

							$orderGoodsDB = new IModel('order_goods');
							$orderGoodsRow = $orderGoodsDB->getObj('order_id = '.$result['data']['order_id'],'id');
							$order_goods_relation = [$orderGoodsRow['id']];

							order_class::sendDeliveryGoods($result['data']['order_id'],$order_goods_relation,$sendor);
                            plugin::trigger('checkOrderCodeFinish',$code);
                        }
                    }
                }
                break;

                case "T":
                {
                    $result = Api::run('getTakeselfInfo',$code);

                    //验证成功
                    if(isset($result['success']) && $result['success'] == true)
                    {
                        $db = new IModel('order');
                        $db->setData(['status' => 5,'completion_time' => ITime::getDateTime()]);

                        if(!$db->update('checkcode = "'.$code.'"'))
                        {
                            $result = array('success' => false,'msg' => '自提更新失败，请重试');
                        }
                        else
                        {
                            $orderRow = $result['data'];

            				//生成订单日志
							$sendor = $seller_id ? '商家' : '管理员';
            				$tb_order_log = new IModel('order_log');
            				$tb_order_log->setData(array(
            					'order_id' => $orderRow['id'],
            					'user'     => $sendor,
            					'action'   => '核销完成',
            					'result'   => '成功',
            					'note'     => '订单【'.$orderRow['order_no'].'】自提成功',
            					'addtime'  => ITime::getDateTime(),
            				));
            				$tb_order_log->add();

        					//完成订单并且进行支付
        					Order_Class::updateOrderStatus($orderRow['order_no']);

        					//增加用户评论商品机会
        					Order_Class::addGoodsCommentChange($orderRow['id']);

                            //生成操作日志
        					$logObj = new log('db');
        					$logObj->write('operation',array("自提码验证","订单更新为完成",'订单号：'.$orderRow['order_no']));

                            //发送事件通知
                            plugin::trigger('takeselfFinish',$code);
                        }
                    }
                }
                break;
            }
        }
        die(JSON::encode($result));
    }

    //根据消费码获取信息
    public function get_code_info_ajax()
    {
        $result = ["success" => false,"msg" => "验证码不存在"];
        $code = strtoupper(IFilter::act(IReq::get('code')));
        if($code)
        {
            switch($code[0])
            {
                case "T":
                {
                    $result = Api::run('getTakeselfInfo',$code);
                }
                break;

                case "S":
                {
                    $result = Api::run('getCodeInfo',$code);
                }
                break;
            }
        }
        die(JSON::encode($result));
    }

    //换货单删除
    public function exchange_doc_del()
    {
		$id = IFilter::act(IReq::get('id'),'int');
		$id = is_array($id) ? join(",",$id) : $id;
		if($id)
		{
			$db = new IModel('exchange_doc');
			$db->del("id IN ($id)");

    		$logObj = new log('db');
    		$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"换货申请单被删除",'移除的ID：'.$id));
		}
		$this->redirect('exchange_list');
    }

    //维修单删除
    public function fix_doc_del()
    {
		$id = IFilter::act(IReq::get('id'),'int');
		$id = is_array($id) ? join(",",$id) : $id;
		if($id)
		{
			$db = new IModel('fix_doc');
			$db->del("id IN ($id)");

    		$logObj = new log('db');
    		$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"维修申请单被删除",'移除的ID：'.$id));
		}
		$this->redirect('fix_list');
    }

	/**
	 * @brief 换货单删除功能_删除到回收站
	 */
    public function exchange_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$db = new IModel('exchange_doc');
    	$db->setData(array('if_del'=>1));
    	if($id)
		{
			$db->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"换货单移除到回收站内",'换货单ID：'.join(',',$id)));
		}
		$this->redirect('order_exchange_list');
    }
	/**
	 * @brief 换货单删除功能_删除回收站中的数据，彻底删除
	 */
    public function exchange_recycle_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$db = new IModel('exchange_doc');
    	if($id)
		{
			$db->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除了回收站内的换货单",'换货单ID：'.join(',',$id)));
		}
		$this->redirect('exchange_recycle_list');
    }

	/**
	 * @brief 维修单删除功能_删除到回收站
	 */
    public function fix_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$db = new IModel('fix_doc');
    	$db->setData(array('if_del'=>1));
    	if($id)
		{
			$db->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"维修单移除到回收站内",'维修单ID：'.join(',',$id)));
		}
		$this->redirect('order_fix_list');
    }
	/**
	 * @brief 维修单删除功能_删除回收站中的数据，彻底删除
	 */
    public function fix_recycle_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	$db = new IModel('fix_doc');
    	if($id)
		{
			$db->del(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除了回收站内的维修单",'维修单ID：'.join(',',$id)));
		}
		$this->redirect('fix_recycle_list');
    }

    //换货申请详情
	public function exchange_doc_show()
	{
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IModel('exchange_doc');
	 		$data = $db->getObj($id);
	 		if($data)
	 		{
	 			$this->setRenderData($data);
	 			$this->redirect('exchange_doc_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('exchange_list');
	}

    //维修申请详情
	public function fix_doc_show()
	{
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IModel('fix_doc');
	 		$data = $db->getObj($id);
	 		if($data)
	 		{
	 			$this->setRenderData($data);
	 			$this->redirect('fix_doc_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('fix_list');
	}

	/**
	 * @brief 更新申请换货
	 */
	public function exchange_doc_show_save()
	{
		$id           = IFilter::act(IReq::get('id'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status       = IFilter::act(IReq::get('status'),'int');
		if($id)
		{
		    $data = [
    			'status'       => $status,
    			'dispose_idea' => $dispose_idea,
    			'dispose_time' => ITime::getDateTime(),
    			'admin_id'     => $this->admin['admin_id'],
		    ];

		    if($status == 2)
		    {
		        $data['seller_freight_id']    = IFilter::act(IReq::get('seller_freight_id'),'int');
		        $data['seller_delivery_code'] = IFilter::act(IReq::get('seller_delivery_code'));
		        $data['seller_send_time']     = ITime::getDateTime();
		    }

			$db = new IModel('exchange_doc');
			$db->setData($data);
			$db->update($id);

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"修改了换货申请",'修改的ID：'.$id));

			//处理换货申请
			plugin::trigger('exchangeDocUpdate',$id);
		}
        $status == 3 ? $this->redirect('exchange_list') : $this->redirect('order_exchange_list');
	}

	/**
	 * @brief 更新申请维修单
	 */
	public function fix_doc_show_save()
	{
		$id           = IFilter::act(IReq::get('id'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status       = IFilter::act(IReq::get('status'),'int');
		if($id)
		{
		    $data = [
    			'status'       => $status,
    			'dispose_idea' => $dispose_idea,
    			'dispose_time' => ITime::getDateTime(),
    			'admin_id'     => $this->admin['admin_id'],
		    ];

		    if($status == 2)
		    {
		        $data['seller_freight_id']    = IFilter::act(IReq::get('seller_freight_id'),'int');
		        $data['seller_delivery_code'] = IFilter::act(IReq::get('seller_delivery_code'));
		        $data['seller_send_time']     = ITime::getDateTime();
		    }

			$db = new IModel('fix_doc');
			$db->setData($data);
			$db->update($id);

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"修改了维修申请",'修改的ID：'.$id));

			//处理维修申请
			plugin::trigger('fixDocUpdate',$id);
		}
		$status == 3 ? $this->redirect('fix_list') : $this->redirect('order_fix_list');
	}

	/**
	 * @brief 还原换货单列表
	 */
    public function exchange_recycle_restore()
    {
    	$id = IFilter::act(IReq::get('id'),'int');

    	$tb_order = new IModel('exchange_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"还原了回收站内的换货单",'换货单ID：'.join(',',$id)));

			$this->redirect('exchange_recycle_list');
		}
		else
		{
			$this->redirect('exchange_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }

	/**
	 * @brief 还原维修单列表
	 */
    public function fix_recycle_restore()
    {
    	$id = IFilter::act(IReq::get('id'),'int');

    	$tb_order = new IModel('fix_doc');
    	$tb_order->setData(array('if_del'=>0));
    	if($id)
		{
			$tb_order->update(Util::joinStr($id));

			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"还原了回收站内的维修单",'维修ID：'.join(',',$id)));

			$this->redirect('fix_recycle_list');
		}
		else
		{
			$this->redirect('fix_recycle_list',false);
			Util::showMessage('请选择要还原的数据');
		}
    }

	/**
	 * @brief查看维修单
	 */
	public function fix_show()
	{
	 	//获得post传来的退款单id值
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IQuery('fix_doc as c');
	 		$db->join   = 'left join user as u on u.id = c.user_id';
	 		$db->fields = 'u.username,c.*';
	 		$db->where  = 'c.id='.$id;
	 		$list = $db->find();
	 		if($list)
	 		{
	 			$data = current($list);
	 			$this->setRenderData($data);
	 			$this->redirect('fix_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('order_fix_list');
	}

	/**
	 * @brief查看换货单
	 */
	public function exchange_show()
	{
	 	//获得post传来的退款单id值
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IQuery('exchange_doc as c');
	 		$db->join   ='left join user as u on u.id = c.user_id';
	 		$db->fields = 'u.username,c.*';
	 		$db->where  = 'c.id='.$id;
	 		$list = $db->find();
	 		if($list)
	 		{
	 			$data = current($list);
	 			$this->setRenderData($data);
	 			$this->redirect('exchange_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('order_exchange_list');
	}

	//更新发货单
	public function delivery_doc_update()
	{
	    $id = IFilter::act(IReq::get('id'),'int');
	    $freight_id = IFilter::act(IReq::get('freight_id'),'int');
	    $delivery_code = IFilter::act(IReq::get('delivery_code'));

	    $db = new IModel('delivery_doc');
	    $db->setData(["freight_id" => $freight_id,"delivery_code" => $delivery_code]);
        if($db->update($id))
        {
			$logObj = new log('db');
			$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"更新了发货单",'更新ID：'.$id));
        }
        $this->redirect('order_delivery_list');
	}

	//导出订单商品excel
	public function order_goods_report()
	{
		//搜索条件
		$search = IReq::get('search');

		//条件筛选处理
		list($join,$where) = order_class::getSearchCondition($search);
		$join .= " left join order_goods as og on og.order_id = o.id ";

		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,og.real_price,og.goods_nums,og.goods_weight,og.goods_array";
		$orderHandle->join   = $join;
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();

		$reportObj = new report('order_goods');
		$reportObj->setTitle(["商品名称","商品货号","商品规格","商品单价","购买数量","价格小计","重量小计","订单编号","下单日期","收货人","收货地址","电话","收货时间"]);
		foreach($orderList as $k => $val)
		{
			//从json中拆分商品信息
			$goodsInfo = JSON::decode($val['goods_array']);

			$insertData = [
				$goodsInfo['name'],
				$goodsInfo['goodsno'],
				$goodsInfo['value'],
				$val['real_price'],
				$val['goods_nums'],
				round($val['real_price']*$val['goods_nums'],2),
				$val['goods_weight'],
				$val['order_no'],
				$val['create_time'],
				$val['accept_name'],
				join(' ',area::name($val['province'],$val['city'],$val['area'])).$val['address'],
				$val['mobile'],
				$val['accept_time'],
			];
			$reportObj->setData($insertData);
		}

		$reportObj->toDownload();
	}
}