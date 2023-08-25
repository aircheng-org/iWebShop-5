<?php
/**
 * @brief 商家模块
 * @class Seller
 * @author chendeshan
 * @datetime 2014/7/19 15:18:56
 */
class Seller extends IController implements sellerAuthorization
{
	public $layout = 'seller';

	/**
	 * @brief 初始化检查
	 */
	public function init()
	{

	}
	/**
	 * @brief 商品添加和修改视图
	 */
	public function goods_edit()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');

		//初始化数据
		$goods_class = new goods_class($this->seller['seller_id']);

		//获取所有商品扩展相关数据
		$data = $goods_class->edit($goods_id);

		if($goods_id && !$data)
		{
			die("没有找到相关商品！");
		}

        if($data)
        {
            $data['type'] = $data['form']['type'];
        }
        else
        {
            $data = array('type' => IReq::get('type') ? IReq::get('type') : "default");
        }

		$this->setRenderData($data);
		$this->redirect('goods_edit');
	}
	//商品更新动作
	public function goods_update()
	{
		$id       = IFilter::act(IReq::get('id'),'int');
		$callback = IReq::get('callback');
		$callback = strpos($callback,'goods_list') === false ? '' : $callback;

		//检查表单提交状态
		if(!$_POST)
		{
			die('请确认表单提交正确');
		}

		//初始化商品数据
		unset($_POST['id']);
		unset($_POST['callback']);

		$goodsObject = new goods_class($this->seller['seller_id']);
		$goodsObject->update($id,$_POST);

		$callback ? $this->redirect($callback) : $this->redirect("goods_list");
	}
	//商品列表
	public function goods_list()
	{
		$search      = IFilter::act(IReq::get('search'));
		$seller_id   = $this->seller['seller_id'];
		$searchParam = http_build_query(array('search' => $search));

		list($join,$condition) = goods_class::getSearchCondition($search);

		$where = "go.seller_id = ".$seller_id;
		$where.= $condition ? " and ".$condition : "";
		$page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$goodHandle = new IQuery('goods as go');
		$goodHandle->order  = "go.id desc";
		$goodHandle->fields = "distinct go.id,go.name,go.sell_price,go.market_price,go.store_nums,go.img,go.is_del,go.seller_id,go.is_share,go.sort,go.type,go.spec_array";
		$goodHandle->where  = $where;
		$goodHandle->page	= $page;
		$goodHandle->join	= $join;

		$this->goodHandle = $goodHandle;

		$goods_info = array(
			'seller_id'   => $seller_id,
			'searchParam' => $searchParam,
		);
		$this->setRenderData($goods_info);
		$this->redirect('goods_list');
	}

	//商品导出excel
	public function goods_report()
	{
		$seller_id = $this->seller['seller_id'];
		$search = IFilter::act(IReq::get('search'));
		list($join,$condition) = goods_class::getSearchCondition($search);
		$where  = 'go.seller_id = '.$seller_id;
		$where .= $condition ? " and ".$condition : "";

		$goodHandle = new IQuery('goods as go');
		$goodHandle->order  = "go.id desc";
		$goodHandle->fields = "go.*";
		$goodHandle->where  = $where;
		$goodHandle->join	= $join;
		$goodList = $goodHandle->find();

		$reportObj = new report('goods');
		$catMax    = 1;
		foreach($goodList as $k => $val)
		{
		    $catArray = goods_class::getGoodsCategory($val['id']);
		    $itemCount= count($catArray);
		    if($itemCount > $catMax)
		    {
		        $catMax = $itemCount;
		    }

			$insertData = array(
				$val['name'],
				$val['sell_price'],
				$val['store_nums'],
				$val['sale'],
				$val['create_time'],
				goods_class::statusText($val['is_del']),
			);
			$insertData = array_merge($insertData,$catArray);
			$reportObj->setData($insertData);
		}

		$titleArray = ["商品名称","售价","库存","销量","发布时间","状态"];

		for($i = 1;$catMax >= $i;$i++)
		{
		    $titleArray[] = "分类".$i;
		}

		$reportObj->setTitle($titleArray);
		$reportObj->toDownload();
	}

	//商品删除
	public function goods_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'),'int');

	    //生成goods对象
	    $goods = new goods_class();
	    $goods->seller_id = $this->seller['seller_id'];

	    if($id)
		{
			if(is_array($id))
			{
				foreach($id as $key => $val)
				{
					$goods->del($val);
				}
			}
			else
			{
				$goods->del($id);
			}
		}
		$this->redirect("goods_list");
	}


	//商品状态修改
	public function goods_status()
	{
	    $id        = IFilter::act(IReq::get('id'),'int');
		$is_del    = IFilter::act(IReq::get('is_del'),'int');
		$is_del    = $is_del === 0 ? 3 : $is_del; //不能等于0直接上架
		$seller_id = $this->seller['seller_id'];

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('is_del' => $is_del));

	    if($id)
		{
			$id = is_array($id) ? join(",",$id) : $id;
			$goodsDB->update("id in (".$id.") and seller_id = ".$seller_id);
		}
		$this->redirect("goods_list");
	}

	//规格删除
	public function spec_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$idString = is_array($id) ? join(',',$id) : $id;
			$specObj  = new IModel('spec');
			$specObj->del("id in ( {$idString} ) and seller_id = ".$this->seller['seller_id']);
			$this->redirect('spec_list');
		}
		else
		{
			$this->redirect('spec_list',false);
			Util::showMessage('请选择要删除的规格');
		}
	}
	//修改排序
	public function ajax_sort()
	{
		$id   = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('sort' => $sort));
		$goodsDB->update("id = {$id} and seller_id = ".$this->seller['seller_id']);
	}

	//咨询回复
	public function refer_reply()
	{
		$rid     = IFilter::act(IReq::get('refer_id'),'int');
		$content = IFilter::act(IReq::get('content'),'text');

		if($rid && $content)
		{
			$tb_refer = new IModel('refer');
			$seller_id = $this->seller['seller_id'];//商户id
			$data = array(
				'answer' => $content,
				'reply_time' => ITime::getDateTime(),
				'seller_id' => $seller_id,
				'status' => 1
			);
			$tb_refer->setData($data);
			$tb_refer->update("id=".$rid);
		}
		$this->redirect('refer_list');
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
			$data = $order_show->getOrderShow($order_id,0,$this->seller['seller_id']);
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
	 * @brief 发货订单页面
	 */
	public function order_deliver()
	{
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data     = array();

		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id,0,$this->seller['seller_id']);
			if($data)
			{
				$this->setRenderData($data);
				$this->redirect('order_deliver');
			}
		}
		if(!$data)
		{
			IError::show("订单信息不存在");
		}
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
	 		die('请选择要发货的商品');
	 	}

	 	$result = Order_Class::sendDeliveryGoods($order_id,$sendgoods,'seller');
	 	if($result === true)
	 	{
	 		$this->redirect('order_list');
	 	}
	 	else
	 	{
	 		IError::show($result);
	 	}
	}
	/**
	 * @brief 订单列表
	 */
	public function order_list()
	{
		$seller_id   = $this->seller['seller_id'];
		$search      = IFilter::act(IReq::get('search'));
		$searchParam = http_build_query(array('search' => $search));

		list($join,$condition) = order_class::getSearchCondition($search);
		$where  = "o.seller_id = ".$seller_id." and o.if_del = 0 and o.status not in(3,4)";
		$where .= $condition ? " and ".$condition : "";
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->where  = $where;
		$orderHandle->page	 = $page;
		$this->orderHandle   = $orderHandle;
		$order_info = array(
			'seller_id'   => $seller_id,
			'searchParam' => $searchParam,
		);
		$this->setRenderData($order_info);
		$this->redirect('order_list');
	}

	//订单导出 Excel
	public function order_report()
	{
		$search    = IFilter::act(IReq::get('search'));
		$seller_id = $this->seller['seller_id'];
		list($join,$condition) = order_class::getSearchCondition($search);

		$where  = "o.seller_id = ".$seller_id." and o.if_del = 0 and o.status not in(3,4)";
		$where .= $condition ? " and ".$condition : "";

		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,p.name as payment_name,d.name as distribute_name";
		$orderHandle->join   = "left join payment as p on p.id = o.pay_type left join delivery as d on d.id = o.distribution";
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();

		$reportObj = new report('order');
		$reportObj->setTitle(array("订单编号","下单日期","完成日期","配送方式","收货人","收货地址","电话","订单金额","退款金额","实际支付","支付方式","支付状态","发货状态","商品信息","订单备注"));

		//订单退款单
		$refundDB = new IModel('refundment_doc');

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
				$strGoods .= "<br />";
			}

			$refundRow = $refundDB->getObj('pay_status = 2 and order_id = '.$val['id'],"SUM(`amount`) as refund_amount");
			$insertData = array(
				$val['order_no'],
				$val['create_time'],
				$val['completion_time'],
				$val['distribute_name'],
				$val['accept_name'],
				join('&nbsp;',area::name($val['province'],$val['city'],$val['area'])).$val['address'],
				$val['telphone'].'&nbsp;'.$val['mobile'],
				$val['order_amount'],
				$refundRow['refund_amount'],
				$val['real_amount'],
				$val['payment_name'],
				Order_Class::getOrderPayStatusText($val),
				Order_Class::getOrderDistributionStatusText($val),
				$strGoods,
				$val['note'],
			);
			$reportObj->setData($insertData);
		}
		$reportObj->toDownload();
	}

	//修改商户信息
	public function seller_edit()
	{
		$seller_id = $this->seller['seller_id'];
		$sellerDB        = new IModel('seller');
		$this->sellerRow = $sellerDB->getObj('id = '.$seller_id);
		$this->redirect('seller_edit');
	}

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_add()
	{
		$seller_id   = $this->seller['seller_id'];
		$email       = IFilter::act(IReq::get('email'));
		$password    = IFilter::act(IReq::get('password'));
		$repassword  = IFilter::act(IReq::get('repassword'));
		$phone       = IFilter::act(IReq::get('phone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$account     = IFilter::act(IReq::get('account'));
		$server_num  = IFilter::act(IReq::get('server_num'));
		$home_url    = IFilter::act(IReq::get('home_url'));
		$tax         = IFilter::act(IReq::get('tax'),'float');

		if(!$seller_id && $password == '')
		{
			$errorMsg = '请输入密码！';
		}

		if($password != $repassword)
		{
			$errorMsg = '两次输入的密码不一致！';
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
			'account'   => $account,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'server_num'=> $server_num,
			'home_url'  => $home_url,
			'tax'      => $tax,
		);

		//logo图片处理
		if(isset($_FILES['logo']['name']) && $_FILES['logo']['name']!='')
		{
			$uploadDir = IWeb::$app->config['upload'].'/seller';
			$uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['logo']['img']) && $photoInfo['logo']['img'])
			{
				$sellerRow['logo'] = $photoInfo['logo']['img'];
			}
		}

		//创建商家操作类
		$sellerDB   = new IModel("seller");

		//修改密码
		if($password)
		{
			$sellerRow['password'] = md5($password);
		}

		$sellerDB->setData($sellerRow);
		$sellerDB->update("id = ".$seller_id);

		$this->redirect('seller_edit');
	}

	//[团购]添加修改[单页]
	function regiment_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$regimentObj = new IModel('regiment');
			$where       = 'id = '.$id.' and seller_id = '.$this->seller['seller_id'];
			$regimentRow = $regimentObj->getObj($where);
			if(!$regimentRow)
			{
				$this->redirect('regiment_list');
			}

			//促销商品
			$goodsObj = new IModel('goods');
			$goodsRow = $goodsObj->getObj('id = '.$regimentRow['goods_id']);

			$result = array(
				'isError' => false,
				'data'    => $goodsRow,
			);
			$regimentRow['goodsRow'] = JSON::encode($result);
			$this->regimentRow = $regimentRow;
		}
		$this->redirect('regiment_edit');
	}

	//[团购]删除
	function regiment_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			if(is_array($id))
			{
				$id = join(',',$id);
			}
			Active::goodsActiveDel($id,'groupon');
			$regObj = new IModel('regiment');
			$where = 'id in ('.$id.') and seller_id = '.$this->seller['seller_id'];
			$regObj->del($where);
			$this->redirect('regiment_list');
		}
		else
		{
			$this->redirect('regiment_list',false);
			Util::showMessage('请选择要删除的id值');
		}
	}

	//[团购]添加修改[动作]
	function regiment_edit_act()
	{
		$id      = IFilter::act(IReq::get('id'),'int');
		$goodsId = IFilter::act(IReq::get('goods_id'),'int');
		$isClose = IFilter::act(IReq::get('is_close','post'),'int');

		$dataArray = array(
			'id'        	=> $id,
			'title'     	=> IFilter::act(IReq::get('title','post')),
			'start_time'	=> IFilter::act(IReq::get('start_time','post'),'datetime'),
			'end_time'  	=> IFilter::act(IReq::get('end_time','post'),'datetime'),
			'is_close'      => $isClose == 0 ? 2 : 1,
			'intro'     	=> IFilter::act(IReq::get('intro','post')),
			'goods_id'      => $goodsId,
			'store_nums'    => IFilter::act(IReq::get('store_nums','post')),
			'limit_min_count' => IFilter::act(IReq::get('limit_min_count','post'),'int'),
			'limit_max_count' => IFilter::act(IReq::get('limit_max_count','post'),'int'),
			'regiment_price'=> IFilter::act(IReq::get('regiment_price','post'),'float'),
			'seller_id'     => $this->seller['seller_id'],
		);

		$dataArray['limit_min_count'] = $dataArray['limit_min_count'] <= 0 ? 1 : $dataArray['limit_min_count'];
		$dataArray['limit_max_count'] = $dataArray['limit_max_count'] <= 0 ? $dataArray['store_nums'] : $dataArray['limit_max_count'];

		if($goodsId)
		{
			$goodsObj = new IModel('goods');
			$where    = 'id = '.$goodsId.' and seller_id = '.$this->seller['seller_id'];
			$goodsRow = $goodsObj->getObj($where);

			//商品信息不存在
			if(!$goodsRow)
			{
				$this->regimentRow = $dataArray;
				$this->redirect('regiment_edit',false);
				Util::showMessage('请选择商户自己的商品');
			}

            $dataArray['img'] = $goodsRow['img'];

			//处理上传图片
			if(isset($_FILES['img']['name']) && $_FILES['img']['name'] != '')
			{
				$uploadDir = IWeb::$app->config['upload'].'/regiment';
				$uploadObj = new PhotoUpload($uploadDir);
				$photoInfo = $uploadObj->run();
				$dataArray['img'] = $photoInfo['img']['img'];
			}
			$dataArray['sell_price'] = $goodsRow['sell_price'];
		}
		else
		{
			$this->regimentRow = $dataArray;
			$this->redirect('regiment_edit',false);
			Util::showMessage('请选择要关联的商品');
		}

		$regimentObj = new IModel('regiment');
		$regimentObj->setData($dataArray);

		if($id)
		{
			$where = 'id = '.$id.' and seller_id = '.$this->seller['seller_id'];

			//判断是否有更改权限
			if($regimentObj->getObj($where))
			{
                $regimentObj->update($where);
            }
		}
		else
		{
            $id = $regimentObj->add();
		}
		$result = Active::goodsActiveEdit($id,'groupon');
		if($result && is_string($result))
		{
		    $regimentObj->rollback();
		    IError::show($result);
		}
		$this->redirect('regiment_list');
	}

    /**
     * @brief 商户订单结算明细
     */
	public function order_goods_list()
	{
        $search = IFilter::act(IReq::get('search'),'strict');
		$where[] = 'seller_id = '.$this->seller['seller_id'];
		if(isset($search['start_time']) && $search['start_time'])
		{
			$where[] = 'completion_time >= "'.$search['start_time'].'"';
		}

		if(isset($search['end_time']) && $search['end_time'])
		{
			$where[] = 'completion_time <= "'.$search['end_time'].'"';
		}

		if(isset($search['is_checkout']) && $search['is_checkout'] !== '')
		{
			$where[] = 'is_checkout = "'.$search['is_checkout'].'"';
		}

		//结算账单
		$billId = IFilter::act(IReq::get('bill_id'),'int');
		if($billId)
		{
			$billDB = new IModel('bill');
			$billRow= $billDB->getObj($billId);
			$where[] = 'id in ('.$billRow['order_ids'].')';
			$this->bill = $billRow;
		}

		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$orderGoodsQuery = CountSum::getSellerGoodsFeeQuery();
		$orderGoodsQuery->page  = $page;
		$orderGoodsQuery->where = $orderGoodsQuery->getWhere()." and ".join(" and ",$where);
		$this->query = $orderGoodsQuery;
		$this->redirect('order_goods_list');
	}

	/**
	 * @brief 显示评论信息
	 */
	function comment_edit()
	{
		$cid = IFilter::act(IReq::get('cid'),'int');

		if(!$cid)
		{
			$this->comment_list();
			return false;
		}
		$query = new IQuery("comment as c");
		$query->join = "left join goods as goods on c.goods_id = goods.id left join user as u on c.user_id = u.id";
		$query->fields = "c.*,u.username,goods.name,goods.seller_id";
		$query->where = "c.id=".$cid." and goods.seller_id = ".$this->seller['seller_id'];
		$items = $query->find();

		if($items)
		{
			$this->comment = current($items);
			$this->redirect('comment_edit');
		}
		else
		{
			$this->comment_list();
			$msg = '没有找到相关记录！';
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 回复评论
	 */
	function comment_update()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$recontent = IFilter::act(IReq::get('recontents'));
		if($id)
		{
			$commentDB = new IQuery('comment as c');
			$commentDB->join = 'left join goods as go on go.id = c.goods_id';
			$commentDB->where= 'c.id = '.$id.' and go.seller_id = '.$this->seller['seller_id'];
			$checkList = $commentDB->find();
			if(!$checkList)
			{
				IError::show(403,'该商品不属于您，无法对其评论进行回复');
			}

			$updateData = array(
				'recontents' => $recontent,
				'recomment_time' => ITime::getDateTime(),
			);
			$commentDB = new IModel('comment');
			$commentDB->setData($updateData);
			$commentDB->update('id = '.$id);
		}
		$this->redirect('comment_list');
	}

	//商品退款详情
	function refundment_show()
	{
	 	$refundment_id = IFilter::act(IReq::get('id'),'int');
	 	if($refundment_id)
	 	{
	 		$tb_refundment = new IQuery('refundment_doc as c');
	 		$tb_refundment->join = 'left join order as o on c.order_id=o.id';
	 		$tb_refundment->fields = 'c.*,o.*,c.id as id,c.pay_status as pay_status';
	 		$tb_refundment->where = 'c.id='.$refundment_id.' and c.seller_id = '.$this->seller['seller_id'];
	 		$refundment_info = $tb_refundment->find();
	 		if($refundment_info)
	 		{
	 			$data = current($refundment_info);
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('refundment_list');
	}

	//商品退款操作
	function refundment_update()
	{
		$id           = IFilter::act(IReq::get('id'),'int');
		$pay_status   = IFilter::act(IReq::get('pay_status'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'));
		$amount       = IFilter::act(IReq::get('amount'),'float');
		$order_id     = IFilter::act(IReq::get('order_id'),'int');
		$way          = IFilter::act(IReq::get('way'));

		//商户处理退款
		if($id && Order_Class::isSellerRefund($id,$this->seller['seller_id']) == 2)
		{
			$updateData = array(
				'dispose_time' => ITime::getDateTime(),
				'dispose_idea' => $dispose_idea,
				'pay_status'   => $pay_status == 1 ? 1 : 0,
				'amount'       => $amount,
			);
			$tb_refundment_doc = new IModel('refundment_doc');
			$tb_refundment_doc->setData($updateData);
			$tb_refundment_doc->update('id = '.$id.' and pay_status = 0');

			//事件通知
			plugin::trigger('refundDocUpdate',$id);

			if($pay_status == 2)
			{
        		try
        		{
        			$result = Order_Class::refund($id,$this->seller['seller_id'],'seller',$way);
        		}
        		catch(exception $e)
        		{
        			$result = $e->getMessage();
        		}

				if(is_string($result))
				{
					$tb_refundment_doc->rollback();
					IError::show(403,$result);
				}
			}

			//拒绝退款记录日志
			if($pay_status == 1)
			{
				$tb_order_log = new IModel('order_log');
				$tb_order_log->setData(array(
					'order_id' => $order_id,
					'user'     => $this->seller['seller_name'],
					'action'   => '拒绝退款',
					'result'   => '成功',
					'note'     => $dispose_idea,
					'addtime'  => ITime::getDateTime(),
				));
				$tb_order_log->add();
			}
		}
		$this->redirect('/seller/order_show/id/'.$order_id);
	}

	//商品复制
	function goods_copy()
	{
		$idArray = explode(',',IReq::get('id'));
		$idArray = IFilter::act($idArray,'int');

		$goodsDB     = new IModel('goods');
		$goodsAttrDB = new IModel('goods_attribute');
		$goodsPhotoRelationDB = new IModel('goods_photo_relation');
		$productsDB = new IModel('products');

		$goodsData = $goodsDB->query('id in ('.join(',',$idArray).') and is_share = 1 and is_del = 0 and seller_id = 0','*');
		if($goodsData)
		{
			foreach($goodsData as $key => $val)
			{
				//判断是否重复
				if( $goodsDB->getObj('seller_id = '.$this->seller['seller_id'].' and name = "'.$val['name'].'"') )
				{
					die('商品不能重复复制');
				}

				$oldId = $val['id'];

				//商品数据
				unset($val['id'],$val['visit'],$val['favorite'],$val['sort'],$val['comments'],$val['sale'],$val['grade'],$val['is_share']);
				$val['seller_id'] = $this->seller['seller_id'];
				$val['goods_no'] .= '-'.$this->seller['seller_id'];
				$val['name']      = IFilter::act($val['name'],'text');
				$val['content']   = IFilter::act($val['content'],'text');
				$val['spec_array']= IFilter::act($val['spec_array']);

				$goodsDB->setData($val);
				$goods_id = $goodsDB->add();

				//商品属性
				$attrData = $goodsAttrDB->query('goods_id = '.$oldId);
				if($attrData)
				{
					foreach($attrData as $k => $v)
					{
						unset($v['id']);
						$v['goods_id'] = $goods_id;
						$goodsAttrDB->setData($v);
						$goodsAttrDB->add();
					}
				}

				//商品图片
				$photoData = $goodsPhotoRelationDB->query('goods_id = '.$oldId);
				if($photoData)
				{
					foreach($photoData as $k => $v)
					{
						unset($v['id']);
						$v['goods_id'] = $goods_id;
						$goodsPhotoRelationDB->setData($v);
						$goodsPhotoRelationDB->add();
					}
				}

				//货品
				$productsData = $productsDB->query('goods_id = '.$oldId);
				if($productsData)
				{
					foreach($productsData as $k => $v)
					{
						unset($v['id']);
						$v['products_no'].= '-'.$this->seller['seller_id'];
						$v['goods_id']    = $goods_id;
						$v['spec_array']  = IFilter::act($v['spec_array']);
						$productsDB->setData($v);
						$productsDB->add();
					}
				}
			}
			die('success');
		}
		else
		{
			die('复制的商品不存在');
		}
	}

	/**
	 * @brief 添加/修改发货信息
	 */
	public function ship_info_edit()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get("sid"),'int');
    	if($id)
    	{
    		$tb_ship   = new IModel("merch_ship_info");
    		$ship_info = $tb_ship->getObj("id=".$id." and seller_id = ".$this->seller['seller_id']);
    		if($ship_info)
    		{
    			$this->data = $ship_info;
    		}
    		else
    		{
    			die('数据不存在');
    		}
    	}
    	$this->setRenderData($this->data);
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
            $tb_merch_ship_info->update("seller_id = ".$this->seller['seller_id']);
        }
        $tb_merch_ship_info->setData(array('is_default' => $default));
        $tb_merch_ship_info->update("id = ".$id." and seller_id = ".$this->seller['seller_id']);
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
    	if($is_default==1)
    	{
    		$tb_merch_ship_info->setData(array('is_default' => 0));
    		$tb_merch_ship_info->update('seller_id = '.$this->seller['seller_id']);
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
    	$arr['is_del'] = 0;
    	$arr['seller_id'] = $this->seller['seller_id'];

    	$tb_merch_ship_info->setData($arr);
    	//判断是添加还是修改
    	if($id)
    	{
    		$tb_merch_ship_info->update('id='.$id.' and seller_id = '.$this->seller['seller_id']);
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
		if($id)
		{
			$tb_merch_ship_info->del(Util::joinStr($id)." and seller_id = ".$this->seller['seller_id']);
			$this->redirect('ship_info_list');
		}
		else
		{
			$this->redirect('ship_info_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}

	/**
	 * @brief 配送方式修改
	 */
    public function delivery_edit()
	{
		$data = [];
		$id   = IFilter::act(IReq::get('id'),'int');

        if($id)
        {
            $delivery = new IModel('delivery_extend');
            $data = $delivery->getObj('delivery_id = '.$id.' and seller_id = '.$this->seller['seller_id']);
		}
		else
		{
			die('缺少配送方式');
		}

        if($id)
        {
            $delivery = new IModel('delivery');
            $data = $delivery->getObj('id = '.$id);

			$area_groupid = unserialize($data['area_groupid']);
			$firstprice   = unserialize($data['firstprice']);
			$secondprice  = unserialize($data['secondprice']);

			if($area_groupid)
			{
				foreach($area_groupid as $key => $item)
				{
					$areaNameString = '';
					$areaArray = explode(";",trim($item,";"));
					foreach($areaArray as $v)
					{
						$areaData = area::name($v);
						$areaNameString .= current($areaData).'  ';
					}

					$data['areaConfig'][] = [
						'area_groupid' => $area_groupid[$key],
						'firstprice'   => $firstprice[$key],
						'secondprice'  => $secondprice[$key],
						'area_names'   => $areaNameString,
					];
				}
			}
		}

		$this->setRenderData(['data' => $data]);
        $this->redirect('delivery_edit');
	}

	/**
	 * 配送方式修改
	 */
    public function delivery_update()
    {
        //首重重量
        $first_weight = IFilter::act(IReq::get('first_weight'),'float');
        //续重重量
        $second_weight = IFilter::act(IReq::get('second_weight'),'float');
        //首重价格
        $first_price = IFilter::act(IReq::get('first_price'),'float');
        //续重价格
        $second_price = IFilter::act(IReq::get('second_price'),'float');
        //是否支持物流保价
        $is_save_price = IFilter::act(IReq::get('is_save_price'),'int');
        //地区费用类型
        $price_type = IFilter::act(IReq::get('price_type'),'int');
        //启用默认费用
        $open_default = IFilter::act(IReq::get('open_default'),'int');
        //支持的配送地区ID
        $area_groupid = serialize(IReq::get('area_groupid'));
        //配送地址对应的首重价格
        $firstprice = serialize(IReq::get('firstprice'));
        //配送地区对应的续重价格
        $secondprice = serialize(IReq::get('secondprice'));
        //保价费率
        $save_rate = IFilter::act(IReq::get('save_rate'),'float');
        //最低保价
        $low_price = IFilter::act(IReq::get('low_price'),'float');
		//配送ID
        $delivery_id = IFilter::act(IReq::get('deliveryId'),'int');

        $deliveryDB  = new IModel('delivery');
        $deliveryRow = $deliveryDB->getObj('id = '.$delivery_id);
        if(!$deliveryRow)
        {
        	die('配送方式不存在');
        }

        //如果选择指定地区配送就必须要选择地区
        if($price_type == 1 && !$area_groupid)
        {
			die('请设置配送地区');
        }

        $data = array(
        	'first_weight' => $first_weight,
        	'second_weight'=> $second_weight,
        	'first_price'  => $first_price,
        	'second_price' => $second_price,
        	'is_save_price'=> $is_save_price,
        	'price_type'   => $price_type,
        	'open_default' => $open_default,
        	'area_groupid' => $area_groupid,
        	'firstprice'   => $firstprice,
        	'secondprice'  => $secondprice,
        	'save_rate'    => $save_rate,
        	'low_price'    => $low_price,
        	'seller_id'    => $this->seller['seller_id'],
        	'delivery_id'  => $delivery_id,
        );
        $deliveryExtendDB = new IModel('delivery_extend');
        $deliveryExtendDB->setData($data);
        $deliveryObj = $deliveryExtendDB->getObj("delivery_id = ".$delivery_id." and seller_id = ".$this->seller['seller_id']);
        //已经存在了
        if($deliveryObj)
        {
        	$deliveryExtendDB->update('delivery_id = '.$delivery_id.' and seller_id = '.$this->seller['seller_id']);
        }
        else
        {
        	$deliveryExtendDB->add();
        }
		$this->redirect('delivery');
    }

	//[促销活动] 添加修改 [单页]
	function pro_rule_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$promotionObj = new IModel('promotion');
			$where = 'id = '.$id.' and seller_id='.$this->seller['seller_id'];
			$this->promotionRow = $promotionObj->getObj($where);
		}
		$this->redirect('pro_rule_edit');
	}

	//[促销活动] 添加修改 [动作]
	function pro_rule_edit_act()
	{
		$id           = IFilter::act(IReq::get('id'),'int');
		$user_group   = IFilter::act(IReq::get('user_group','post'));
		$promotionObj = new IModel('promotion');
		if(is_string($user_group))
		{
			$user_group_str = $user_group;
		}
		else
		{
			$user_group_str = ",".join(',',$user_group).",";
		}

		$dataArray = array(
			'name'       => IFilter::act(IReq::get('name','post')),
			'condition'  => IFilter::act(IReq::get('condition','post')),
			'is_close'   => IFilter::act(IReq::get('is_close','post')),
			'start_time' => IFilter::act(IReq::get('start_time','post')),
			'end_time'   => IFilter::act(IReq::get('end_time','post')),
			'intro'      => IFilter::act(IReq::get('intro','post')),
			'award_type' => IFilter::act(IReq::get('award_type','post')),
			'type'       => 0,
			'user_group' => $user_group_str,
			'award_value'=> IFilter::act(IReq::get('award_value','post')),
			'seller_id'  => $this->seller['seller_id'],
		);

		if(!in_array($dataArray['award_type'],array(1,2,6)))
		{
			IError::show('促销类型不符合规范',403);
		}

		$promotionObj->setData($dataArray);

		if($id)
		{
			$where = 'id = '.$id;
			$promotionObj->update($where);
		}
		else
		{
			$promotionObj->add();
		}
		$this->redirect('pro_rule_list');
	}

	//[促销活动] 删除
	function pro_rule_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$promotionObj = new IModel('promotion');
			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
			}
			else
			{
				$where = 'id = '.$id;
			}
			$promotionObj->del($where.' and seller_id = '.$this->seller['seller_id']);
			$this->redirect('pro_rule_list');
		}
		else
		{
			$this->redirect('pro_rule_list',false);
			Util::showMessage('请选择要删除的促销活动');
		}
	}

	//修改订单价格
	public function order_discount()
	{
		$order_id = IFilter::act(IReq::get('order_id'),'int');
		$discount = IFilter::act(IReq::get('discount'),'float');
		$orderDB  = new IModel('order');
		$orderRow = $orderDB->getObj('id = '.$order_id.' and status = 1 and distribution_status = 0 and seller_id = '.$this->seller['seller_id']);
		if($orderRow)
		{
			//还原价格
			$newOrderAmount = ($orderRow['order_amount'] - $orderRow['discount']) + $discount;
			$newOrderAmount = $newOrderAmount <= 0 ? 0 : $newOrderAmount;
			if($newOrderAmount == 0)
			{
				die(JSON::encode(array('result' => false,'message' => '订单减价折扣不能低于订单总额')));
			}
			$orderDB->setData(array('discount' => $discount,'order_amount' => $newOrderAmount));
			if($orderDB->update('id = '.$order_id))
			{
				die(JSON::encode(array('result' => true,'orderAmount' => $newOrderAmount)));
			}
		}
		die(JSON::encode(array('result' => false)));
	}

	// 消息通知
	public function message_list()
	{
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$seller_messObject = new seller_mess($this->seller['seller_id']);
		$msgIds = $seller_messObject->getAllMsgIds();
		$msgIds = empty($msgIds) ? 0 : $msgIds;
		$needReadNum = $seller_messObject->needReadNum();

		$seller_messageHandle = new IQuery('seller_message');
		$seller_messageHandle->where = "id in(".$msgIds.")";
		$seller_messageHandle->order= "id desc";
		$seller_messageHandle->page = $page;

		$this->needReadNum = $needReadNum;
		$this->seller_messObject = $seller_messObject;
		$this->seller_messageHandle = $seller_messageHandle;

		$this->redirect("message_list");
	}

	// 消息详情
	public function message_show()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$messageRow = null;
		if($id)
		{
			$seller_messObject = new seller_mess($this->seller['seller_id']);
			$seller_messObject->writeMessage($id, 1);
			$messageRow = $seller_messObject->read($id);
		}

		if(!$messageRow)
		{
			die('信息不存在');
		}
		$this->setRenderData(array('messageRow' => $messageRow));
		$this->redirect('message_show');
	}

	// 消息删除
	public function message_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if ($id)
		{
			$seller_messObject = new seller_mess($this->seller['seller_id']);
			if (is_array($id)) {
				foreach ($id as $val)
				{
					$seller_messObject->delMessage($val);
				}
			}else {
				$seller_messObject->delMessage($id);
			}
		}
		$this->redirect('message_list');
	}

	//订单备注
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
	 	$tb_order->update('id = '.$order_id.' and seller_id = '.$this->seller['seller_id']);
	 	$this->redirect("/seller/order_show/id/".$order_id,true);
	}

	/**
	 * @brief 删除咨询信息
	 */
	function refer_del()
	{
		$refer_ids = IFilter::act(IReq::get('id'),'int');
		$refer_ids = is_array($refer_ids) ? $refer_ids : array($refer_ids);
		if($refer_ids)
		{
			$ids = join(',',$refer_ids);
			if($ids)
			{
				//查询咨询的商品是否属于当前商户
				$referDB        = new IQuery('refer as re,goods as go');
				$referDB->where = "re.id in (".$ids.") and re.goods_id = go.id and go.seller_id = ".$this->seller['seller_id'];
				$referDB->fields= "re.id";
				$referGoods     = $referDB->find();
				$referModel     = new IModel('refer');
				foreach($referGoods as $reId)
				{
					$referModel->del("id = ".$reId['id']);
				}
			}
		}
		$this->redirect('refer_list');
	}

	//店内分类列表
	function category_list()
	{
		$isCache = false;
		$tb_category = new IModel('category_seller');
		$cacheObj = new ICache('file');
		$data = $cacheObj->get('sort_seller_data');
		if(!$data)
		{
			$goods = new goods_class();
			$data = $goods->sortdata($tb_category->query('seller_id = '.$this->seller['seller_id'],'*','sort asc'));
			$isCache ? $cacheObj->set('sort_seller_data',$data) : "";
		}
		$this->data = array('category' => $data);
		$this->setRenderData($this->data);
		$this->redirect('category_list',false);
	}

	//店内分类编辑
	function category_edit()
	{
		$category_id = IFilter::act(IReq::get('cid'),'int');
		if($category_id)
		{
			$categoryObj = new IModel('category_seller');
			$this->categoryRow = $categoryObj->getObj('id = '.$category_id.' and seller_id = '.$this->seller['seller_id']);
		}
		$this->redirect('category_edit');
	}

	//店内分类更新
	function category_save()
	{
		//获得post值
		$category_id = IFilter::act(IReq::get('id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$parent_id = IFilter::act(IReq::get('parent_id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');
		$title = IFilter::act(IReq::get('title'));
		$keywords = IFilter::act(IReq::get('keywords'));
		$descript = IFilter::act(IReq::get('descript'));

		$childString = goods_class::catChild($category_id);//父类ID不能死循环设置成其子分类
		if($parent_id > 0 && stripos(",".$childString.",",",".$parent_id.",") !== false)
		{
			$this->redirect('/seller/category_list/_msg/父分类设置错误');
			return;
		}

		$tb_category = new IModel('category_seller');
		$category_info = array(
			'name'      => $name,
			'parent_id' => $parent_id,
			'sort'      => $sort,
			'keywords'  => $keywords,
			'descript'  => $descript,
			'title'     => $title,
			'seller_id' => $this->seller['seller_id'],
		);
		$tb_category->setData($category_info);
		if($category_id)									//保存修改分类信息
		{
			$where = "id=".$category_id;
			$tb_category->update($where);
		}
		else												//添加新商品分类
		{
			$tb_category->add();
		}
		$this->redirect('category_list');
	}

	//店内分类删除
	function category_del()
	{
		$category_id = IFilter::act(IReq::get('cid'),'int');
		if($category_id)
		{
			$tb_category = new IModel('category_seller');
			$catRow      = $tb_category->getObj('parent_id = '.$category_id);

			//要删除的分类下还有子节点
			if($catRow)
			{
				$this->category_list();
				Util::showMessage('无法删除此分类，此分类下还有子分类，或者回收站内还留有子分类');
				exit;
			}

			if($tb_category->del('id = '.$category_id.' and seller_id = '.$this->seller['seller_id']))
			{
				$tb_category_extend  = new IModel('category_extend_seller');
				$tb_category_extend->del('category_id = '.$category_id);
				$this->redirect('category_list');
			}
			else
			{
				$this->category_list();
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->category_list();
			$msg = "没有找到相关分类记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 分类排序
	 */
	function category_sort()
	{
		$category_id = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');

		$flag = 0;
		if($category_id)
		{
			$tb_category = new IModel('category_seller');
			$category_info = $tb_category->getObj('id='.$category_id);
			if(count($category_info)>0)
			{
				if($category_info['sort']!=$sort)
				{
					$tb_category->setData(array('sort'=>$sort));
					if($tb_category->update('id='.$category_id.' and seller_id = '.$this->seller['seller_id']))
					{
						$flag = 1;
					}
				}
			}
		}
		echo $flag;
	}

	/**
	 * @brief 商品分类ajax调整
	 */
	public function categoryAjax()
	{
		$id        = IFilter::act(IReq::get('id'),'int');
		$parent_id = IFilter::act(IReq::get('parent_id'),'int');
		if($id && is_array($id))
		{
			foreach($id as $category_id)
			{
				$childString = goods_class::catChild($category_id);//父类ID不能死循环设置成其子分类
				if($parent_id > 0 && stripos(",".$childString.",",",".$parent_id.",") !== false)
				{
					die(JSON::encode(array('result' => 'fail')));
				}
			}

			$catDB = new IModel('category_seller');
			$catDB->setData(array('parent_id' => $parent_id));
			$result = $catDB->update('id in ('.join(",",$id).') and seller_id = '.$this->seller['seller_id']);
			if($result)
			{
				die(JSON::encode(array('result' => 'success')));
			}
		}
		die(JSON::encode(array('result' => 'fail')));
	}

	//获取共享商品
	public function share_list()
	{
		$where       = Util::search(IReq::get('search'));
		$searchArray = array('search' => IReq::get('search'));
		$join        = isset($searchArray['search']['ce.category_id=']) ? " left join category_extend as ce on ce.goods_id = go.id " : "";

        $page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query = new IQuery("goods as go");
        $query->join   = $join;
        $query->where  = 'go.is_share = 1 and go.is_del = 0 and go.seller_id = 0 and '.$where;
        $query->fields = 'distinct go.id, go.*';
        $query->page   = $page;
		$this->query    = $query;
		$this->redirect('share_list');
	}

    /**
     * @brief 评论信息列表
     */
    function comment_list()
    {
        $search = IFilter::act(IReq::get('search'),'strict');
        $where = Util::search($search);
        $page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query = new IQuery("comment AS c");
        $query->join  = 'left join goods as goods on c.goods_id = goods.id left join user as u on c.user_id = u.id';
        $query->where  = "c.status = 1 and goods.seller_id = ".$this->seller['seller_id']." and " .$where;
        $query->fields = 'c.id,c.time,u.id as userid,u.username,goods.id as goods_id,goods.name as goods_name,c.recomment_time';
        $query->order = 'c.id desc';
        $query->page   = $page;
        $this->query    = $query;
        $this->redirect('comment_list');
    }

    //快递公司列表
    function expresswaybill_list()
    {
    	//根据默认快递公司信息进行更新
		$expressDB = new IModel('expresswaybill');
		$baseData  = $expressDB->query('seller_id = 0');
		foreach($baseData as $key => $item)
		{
			$expressRow = $expressDB->getObj('seller_id = '.$this->seller['seller_id'].' and freight_type = "'.$item['freight_type'].'"');
			if($expressRow)
			{
				continue;
			}

			$data = array(
				'seller_id'    => $this->seller['seller_id'],
				'is_open'      => $item['config'] ? "0" : "1",
				'freight_type' => $item['freight_type'],
				'freight_name' => $item['freight_name'],
				'url'          => $item['url'],
				'config'       => $item['config'],
				'description'  => $item['description'],
			);
			$expressDB->setData($data);
			$expressDB->add();
		}
    	$this->redirect('expresswaybill_list');
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

    //快递公司更新
    function expresswaybill_update()
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
		$expressDB->update("id = ".$id." and seller_id = ".$this->seller['seller_id']);
		$this->redirect('expresswaybill_list');
    }

	//商品退款详情
	function exchange_show()
	{
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IModel('exchange_doc');
	 		$row= $db->getObj('id = '.$id.' and seller_id = '.$this->seller['seller_id']);
	 		if($row)
	 		{
	 			$this->setRenderData($row);
	 			$this->redirect('exchange_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('exchange_list');
	}

	//换货更新
    function exchange_update()
    {
        $order_id     = IFilter::act(IReq::get('order_id'),'int');
		$id           = IFilter::act(IReq::get('id'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status       = IFilter::act(IReq::get('status'),'int');
		if($id)
		{
		    $data = [
    			'status'       => $status,
    			'dispose_idea' => $dispose_idea,
    			'dispose_time' => ITime::getDateTime(),
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

			//处理换货申请
			plugin::trigger('exchangeDocUpdate',$id);
		}
		$this->redirect('/seller/order_show/id/'.$order_id);
    }

	//商品退款详情
	function fix_show()
	{
	 	$id = IFilter::act(IReq::get('id'),'int');
	 	if($id)
	 	{
	 		$db = new IModel('fix_doc');
	 		$row= $db->getObj('id = '.$id.' and seller_id = '.$this->seller['seller_id']);
	 		if($row)
	 		{
	 			$this->setRenderData($row);
	 			$this->redirect('fix_show');
	 			return;
	 		}
	 	}
	 	$this->redirect('fix_list');
	}

    //维修更新
	function fix_update()
	{
        $order_id     = IFilter::act(IReq::get('order_id'),'int');
		$id           = IFilter::act(IReq::get('id'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'),'text');
		$status       = IFilter::act(IReq::get('status'),'int');
		if($id)
		{
		    $data = [
    			'status'       => $status,
    			'dispose_idea' => $dispose_idea,
    			'dispose_time' => ITime::getDateTime(),
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

			//处理维修申请
			plugin::trigger('fixDocUpdate',$id);
		}
		$this->redirect('/seller/order_show/id/'.$order_id);
	}

    /**
	 * 自提点添加和修改
	 */
	public function takeself_update()
	{
		$id       = IFilter::act(IReq::get('id'),'int');
    	$name     = IFilter::act(IReq::get('name'));
    	$sort     = IFilter::act(IReq::get('sort'),'int');
    	$province = IFilter::act(IReq::get('province'),'int');
    	$city     = IFilter::act(IReq::get('city'),'int');
    	$area     = IFilter::act(IReq::get('area'),'int');
    	$address  = IFilter::act(IReq::get('address'));
		$phone    = IFilter::act(IReq::get('phone'),'phone');
		$mobile   = IFilter::act(IReq::get('mobile'),'mobile');

		$takeselfDB = new IModel('takeself');
	    $data = array(
        	'name'         => $name,
        	'sort'         => $sort,
        	'province'     => $province,
        	'city'         => $city,
        	'area'         => $area,
        	'address'      => $address,
        	'phone'        => $phone,
        	'mobile'       => $mobile,
        	'seller_id'    => $this->seller['seller_id']
        );

		//附件上传$_FILE
		if($_FILES)
		{
		    $uploadDir = IWeb::$app->config['upload'].'/takeself';
			$uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();

			//logo图片处理
			if(isset($photoInfo['logo']['img']) && $photoInfo['logo']['img'])
			{
				$data['logo'] = $photoInfo['logo']['img'];
			}
		}

        $takeselfDB->setData($data);
        if($id)
    	{
    		$takeselfDB->update('id='.$id.' and seller_id = '.$this->seller['seller_id']);
    	}
    	else
    	{
    		$takeselfDB->add();
    	}

	    $this->redirect("takeself_list");
	}


     /**
	 * 自提点添加和修改视图
	 */
	public function takeself_edit()
	{
	    $id = IFilter::act(IReq::get('id'),'int');
	    if($id)
	    {
			$takeselfDB        = new IModel('takeself');
			$this->takeselfRow = $takeselfDB->getObj('id = '.$id.' and seller_id = '.$this->seller['seller_id'] );
        }
		$this->redirect("takeself_edit");
	}

    //删除自提点
	public function takeself_operate()
	{
		$id = IFilter::act(IReq::get('id'));
        if(is_array($id))
        {
        	$id = join(',',$id);
        }

        if($id)
        {
			$takeself = new IModel('takeself');

			//清理图片
			$data = $takeself->query('id in ('.$id.')','logo');
			foreach($data as $val)
			{
    			if(file_exists($val['logo']))
    			{
    				unlink($val['logo']);
    			}
			}

			$takeself->del('id in('.$id.') and seller_id = '.$this->seller['seller_id']);
			$this->redirect('takeself_list');
        }
        else
        {
        	$this->redirect('takeself_list',false);
        	Util::showMessage('请选择要操作的选项');
        }
	}

    //首页幻灯图更新
    function banner_update()
    {
        $config_slide = array();
        $banner_name  = IFilter::act(IReq::get('banner_name'));
        $banner_url   = IFilter::act(IReq::get('banner_url'));
        $banner_img   = IFilter::act(IReq::get('banner_img'));
        $banner_type  = IFilter::act(IReq::get('type'));
        $form_index   = IFilter::act(IReq::get('form_index'));
        if($banner_name)
        {
            foreach($banner_name as $key => $value)
            {
                $config_slide[$key]['name']      = $banner_name[$key];
                $config_slide[$key]['url']       = $banner_url[$key];
                $config_slide[$key]['img']       = $banner_img[$key];
                $config_slide[$key]['type']      = $banner_type;
                $config_slide[$key]['seller_id'] = $this->seller['seller_id'];
            }

            //本地图片上传
            if(isset($_FILES['banner_pic']))
            {
                $uploadDir = IWeb::$app->config['upload'].'/banner';
                $uploadObj = new PhotoUpload($uploadDir);
                $uploadObj->setIterance(false);
                $bannerInfo = $uploadObj->run();

                if(isset($bannerInfo['banner_pic']))
                {
                    foreach($bannerInfo['banner_pic'] as $key => $value)
                    {
                        if($value['flag'] == 1)
                        {
                            $config_slide[$key]['img'] = $value['img'];
                        }
                    }
                }
            }
        }

        //首页幻灯存储数据库
        $bannerObj = new IModel('banner');
        $bannerObj->del("type = '$banner_type' and seller_id = ".$this->seller['seller_id']);
        if($config_slide)
        {
            foreach($config_slide as $dataArray)
            {
                $bannerObj->setData($dataArray);
                $bannerObj->add();
            }
        }
        $this->redirect('/seller/conf_banner/form_index/'.$form_index);
    }

	//[拼团]添加修改[单页]
	function assemble_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$assembleObj = new IModel('assemble');
			$where       = 'id = '.$id;
			$assembleRow = $assembleObj->getObj($where);
			if(!$assembleRow)
			{
				$this->redirect('assemble_list');
				return;
			}

			//促销商品
			$goodsObj = new IModel('goods');
			$goodsRow = $goodsObj->getObj('id = '.$assembleRow['goods_id']);

			$result = array(
				'isError' => false,
				'data'    => $goodsRow,
			);
			$assembleRow['goodsRow'] = JSON::encode($result);
			$this->assembleRow = $assembleRow;
		}
		$this->redirect('assemble_edit');
	}

	//[拼团更新]添加修改[动作]
	function assemble_update()
	{
		$id      = IFilter::act(IReq::get('id'),'int');
		$goodsId = IFilter::act(IReq::get('goods_id'),'int');
		$isClose = IFilter::act(IReq::get('is_close','post'),'int');

		$dataArray = array(
			'id'        	=> $id,
			'title'     	=> IFilter::act(IReq::get('title','post')),
			'is_close'      => $isClose == 0 ? 2 : 1,
			'intro'     	=> IFilter::act(IReq::get('intro','post')),
			'goods_id'      => $goodsId,
			'assemble_price'=> IFilter::act(IReq::get('assemble_price','post'),'float'),
			'seller_id'     => $this->seller['seller_id'],
			'limit_nums'   	=> IFilter::act(IReq::get('limit_nums','post')),
		);

		if($goodsId)
		{
			$goodsObj = new IModel('goods');
			$where    = 'id = '.$goodsId.' and seller_id = '.$this->seller['seller_id'];
			$goodsRow = $goodsObj->getObj($where);

			//商品信息不存在
			if(!$goodsRow)
			{
				$this->assembleRow = $dataArray;
				$this->redirect('assemble_edit',false);
				Util::showMessage('请选择商户自己的商品');
			}

			$dataArray['img'] = $goodsRow['img'];

			//处理上传图片
			if(isset($_FILES['img']['name']) && $_FILES['img']['name'] != '')
			{
				$uploadDir = IWeb::$app->config['upload'].'/assemble';
				$uploadObj = new PhotoUpload($uploadDir);
				$photoInfo = $uploadObj->run();
				$dataArray['img'] = $photoInfo['img']['img'];
			}
			$dataArray['sell_price'] = $goodsRow['sell_price'];
		}
		else
		{
			$this->assembleRow = $dataArray;
			$this->redirect('assemble_edit',false);
			Util::showMessage('请选择要关联的商品');
		}

		$assembleObj = new IModel('assemble');
		$assembleObj->setData($dataArray);

		if($id)
		{
			$where = 'id = '.$id.' and seller_id = '.$this->seller['seller_id'];

			//判断是否有更改权限
			if($assembleObj->getObj($where))
			{
				$assembleObj->update($where);
			}
		}
		else
		{
			$id = $assembleObj->add();
		}
		$result = Active::goodsActiveEdit($id,'assemble');
		if($result && is_string($result))
		{
		    $assembleObj->rollback();
		    IError::show($result);
		}
		$this->redirect('assemble_list');
	}

	//[拼团]删除
	function assemble_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			if(is_array($id))
			{
				$id = join(',',$id);
			}
			Active::goodsActiveDel($id,'assemble');
			$asCommandObj = new IModel('assemble_commander');
			$asCommandObj->del('assemble_id in ('.$id.')');

			$assebleObj = new IModel('assemble');
			$assebleObj->del('id in ('.$id.')');
			$this->redirect('assemble_list');
		}
		else
		{
			$this->redirect('assemble_list',false);
			Util::showMessage('请选择要删除的id值');
		}
	}

    //修改优惠券状态is_close和is_send
    function ticket_status()
    {
        $status    = IFilter::act(IReq::get('status'));
        $id        = IFilter::act(IReq::get('id'),'int');
        $ticket_id = IFilter::act(IReq::get('ticket_id'));

        if($id && $status != null && $ticket_id != null)
        {
            $ticketObj = new IModel('prop');
            if(is_array($id))
            {
                foreach($id as $val)
                {
                    $where = 'id = '.$val .' and seller_id = '.$this->seller['seller_id'];
                    $ticketRow = $ticketObj->getObj($where,$status);
                    if($ticketRow[$status]==1)
                    {
                        $ticketObj->setData(array($status => 0));
                    }
                    else
                    {
                        $ticketObj->setData(array($status => 1));
                    }
                    $ticketObj->update($where);
                }
            }
            else
            {
                $where = 'id = '.$id. ' and seller_id = '.$this->seller['seller_id'];
                $ticketRow = $ticketObj->getObj($where,$status);
                if($ticketRow[$status]==1)
                {
                    $ticketObj->setData(array($status => 0));
                }
                else
                {
                    $ticketObj->setData(array($status => 1));
                }
                $ticketObj->update($where);
            }
            $this->redirect('ticket_more_list/ticket_id/'.$ticket_id);
        }
        else
        {
            $this->ticket_id = $ticket_id;
            $this->redirect('ticket_more_list',false);
            Util::showMessage('请选择要修改的id值');
        }
    }

    //[优惠券]添加,修改[单页]
    function ticket_edit()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        if($id)
        {
            $ticketObj       = new IModel('ticket');
            $where           = 'id = '.$id . ' and seller_id = '.$this->seller['seller_id'];
            $this->ticketRow = $ticketObj->getObj($where);
        }
        $this->redirect('ticket_edit');
    }

    //[优惠券]添加,修改[动作]
    function ticket_edit_act()
    {
        $id        = IFilter::act(IReq::get('id'),'int');
        $type      = IFilter::act(IReq::get('type'));
        $ticketObj = new IModel('ticket');
        switch($type)
        {
            case 0:
            {
                $condition = '';
            }
            break;

            case 1:
            {
                $gid = IFilter::act(IReq::get('goods_id'),'int');
                if(!$gid || !is_array($gid))
                {
                    IError::show(403,'商品信息没有设置');
                }
                $condition = $gid[0];
            }
            break;

            case 2:
            {
                $category = IFilter::act(IReq::get('category'),'int');
                if(!$category || !is_array($category))
                {
                    IError::show(403,'商品分类信息没有设置');
                }
                $condition = $category[0];
            }
            break;

            default:
                IError::show(403,'优惠券类型错误!');
        }

        $dataArray = array(
			'name'      => IFilter::act(IReq::get('name','post')),
			'start_time'=> IFilter::act(IReq::get('start_time','post'),'datetime'),
			'end_time'  => IFilter::act(IReq::get('end_time','post'),'datetime'),
            'seller_id' => $this->seller['seller_id'],
            'point'     => IFilter::act(IReq::get('point','post'),'int'),
        );
        //修改优惠券 则类型 条件 优惠券面额值 不能修改
        if(!$id)
        {
            $dataArray['type']       = $type;
            $dataArray['condition']  = $condition;
            $dataArray['value']      = IFilter::act(IReq::get('value','post'));
            $dataArray['limit_sum']  = IFilter::act(IReq::get('limit_sum','post'),'float');
        }
        $ticketObj->setData($dataArray);
        if($id)
        {
            $where = 'id = '.$id. ' and seller_id = '.$this->seller['seller_id'];
            $ticketObj->update($where);
        }
        else
        {
            $ticketObj->add();
        }
        $this->redirect('ticket_list');
    }

    //[优惠券]生成[动作]
    function ticket_create()
    {
        $propObj   = new IModel('prop');
        $prop_num  = intval(IReq::get('num'));
        $ticket_id = intval(IReq::get('ticket_id'));

        if($prop_num && $ticket_id)
        {
            $prop_num  = ($prop_num > 5000) ? 5000 : $prop_num;
            $ticketObj = new IModel('ticket');
            $where     = 'id = '.$ticket_id;
            $ticketRow = $ticketObj->getObj($where);

            for($item = 0; $item < intval($prop_num); $item++)
            {
			    $insertId = ticket::create($ticketRow);
				if(!$insertId)
				{
					$item--;
					continue;
				}
            }
        }
        $this->redirect('ticket_list');
    }

    //[优惠券]删除
    function ticket_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        if($id)
        {
            $ticketObj = new IModel('ticket');
            $propObj   = new IModel('prop');
            $propRow   = $propObj->getObj(" `type` = 0 and `condition` = {$id} and (is_close = 2 or (is_userd = 0 and is_send = 1)) and seller_id = ".$this->seller['seller_id']);

            if($propRow)
            {
                $this->redirect('ticket_list',false);
                Util::showMessage('无法删除优惠券，其下还有正在使用的优惠券');
                exit;
            }

            $where = "id = {$id} and seller_id = ".$this->seller['seller_id'];
            if($ticketObj->del($where))
            {
                $where = " `type` = 0 and `condition` = {$id} and seller_id = ".$this->seller['seller_id'];
                $propObj->del($where);
            }
            $this->redirect('ticket_list');
        }
        else
        {
            $this->redirect('ticket_list',false);
            Util::showMessage('请选择要删除的id值');
        }
    }

    //[优惠券详细]删除
    function ticket_more_del()
    {
        $id        = IFilter::act(IReq::get('id'),'int');
        $ticket_id = IFilter::act(IReq::get('ticket_id'),'int');
        if($id)
        {
            $propObj   = new IModel('prop');
            if(is_array($id))
            {
                $idStr = join(',',$id);
                $where = ' id in ('.$idStr.')';
            }
            else
            {
                $where = 'id = '.$id;
            }
            $where .= ' and seller_id = '.$this->seller['seller_id'];
            $propObj->del($where);
            $this->redirect('ticket_more_list/ticket_id/'.$ticket_id);
        }
        else
        {
            $this->ticket_id = $ticket_id;
            $this->redirect('ticket_more_list',false);
            Util::showMessage('请选择要删除的id值');
        }
    }

    //[优惠券详细] 列表
    function ticket_more_list()
    {
        $this->ticket_id = IFilter::act(IReq::get('ticket_id'),'int');
        $this->redirect('ticket_more_list');
    }

    //[优惠券] 输出excel表格
    function ticket_excel()
    {
        //优惠券excel表存放地址
        $ticket_id = IFilter::act(IReq::get('id'));

        if($ticket_id)
        {
            $propObj = new IModel('prop');
            $where   = 'type = 0 and seller_id = '.$this->seller['seller_id'];
            $ticket_id_array = is_array($ticket_id) ? $ticket_id : array($ticket_id);

            //当优惠券数量没有时不允许备份excel
            foreach($ticket_id_array as $key => $tid)
            {
                if(statistics::getTicketCount($tid) == 0)
                {
                    unset($ticket_id_array[$key]);
                }
            }

            if($ticket_id_array)
            {
                $id_num_str = join('","',$ticket_id_array);
            }
            else
            {
                $this->redirect('ticket_list',false);
                Util::showMessage('实体优惠券数量为0张，无法备份');
                exit;
            }

            $where.= ' and `condition` in("'.$id_num_str.'") and seller_id = '.$this->seller['seller_id'];
            $propList = $propObj->query($where,'*','`condition` asc',10000);

            $ticketFile = "ticket_".join("_",$ticket_id_array);
            $reportObj = new report($ticketFile);
            $reportObj->setTitle(array("名称","卡号","密码","面值","已被使用","是否关闭","是否发送","开始时间","结束时间"));
            foreach($propList as $key => $val)
            {
                $is_userd = ($val['is_userd']=='1') ? '是':'否';
                $is_close = ($val['is_close']=='1') ? '是':'否';
                $is_send  = ($val['is_send']=='1') ? '是':'否';

                $insertData = array(
                    $val['name'],
                    $val['card_name'],
                    $val['card_pwd'],
                    $val['value'].'元',
                    $is_userd,
                    $is_close,
                    $is_send,
                    $val['start_time'],
                    $val['end_time'],
                );
                $reportObj->setData($insertData);
            }
            $reportObj->toDownload();
        }
        else
        {
            $this->redirect('ticket_list',false);
            Util::showMessage('请选择要操作的文件');
        }
    }
}
