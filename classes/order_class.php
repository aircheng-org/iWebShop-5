<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file Order_Class.php
 * @brief 订单中相关的
 * @author relay
 * @date 2011-02-24
 * @version 0.6
 */
class Order_Class
{
	/**
	 * @brief 产生订单ID
	 * @return string 订单ID
	 * @note 前缀表示含义： B表示商家转账；T表示用户提现；
	 */
	public static function createOrderNum()
	{
		$newOrderNo = date('YmdHis').rand(1000,9999);

		$orderDB = new IModel('order');
		if($orderDB->getObj('order_no = "'.$newOrderNo.'"'))
		{
			return self::createOrderNum();
		}
		return $newOrderNo;
	}

    /**
     * @brief 产生虚拟服务验证码
     * @return string 服务验证码ID
     */
    public static function createCodeNum()
    {
        $newCoderNo = "S".IHash::random(7);
        $goodsCodeRelationDB = new IModel('order_code_relation');
        if($goodsCodeRelationDB->getObj('code = "'.$newCoderNo.'"'))
        {
            return self::createCodeNum();
        }
        return $newCoderNo;
    }

    /**
     * @brief 产生自提码
     * @return string 自提码ID
     */
    public static function createTakeselfNum()
    {
        $newCoderNo = "T".IHash::random(7);
        $orderDB = new IModel('order');
        if($orderDB->getObj('checkcode = "'.$newCoderNo.'"'))
        {
            return self::createTakeselfNum();
        }
        return $newCoderNo;
    }

	/**
	 * 添加评论商品的机会
	 * @param $order_id 订单ID
	 */
	public static function addGoodsCommentChange($order_id)
	{
		//获取订单对象
		$orderDB  = new IModel('order');
		$orderRow = $orderDB->getObj('id = '.$order_id);

		//获取此订单中的商品种类
		$orderGoodsDB        = new IQuery('order_goods');
		$orderGoodsDB->where = 'order_id = '.$order_id.' and is_send = 1';
		$orderList           = $orderGoodsDB->find();

		//可以允许进行商品评论
		$commentDB = new IModel('comment');
		$goodsDB   = new IModel('goods');

		//对每类商品进行评论开启
		foreach($orderList as $val)
		{
			//判断此订单商品是否已经加入评论表
			$issetComment = $commentDB->getObj("order_goods_id = ".$val['id']);
			if($issetComment)
			{
				continue;
			}

			$issetGoods = $goodsDB->getObj('id = '.$val['goods_id']);
			if($issetGoods)
			{
				$attr = array(
					'goods_id'       => $val['goods_id'],
					'order_no'       => $orderRow['order_no'],
					'user_id'        => $orderRow['user_id'],
					'time'           => ITime::getDateTime(),
					'seller_id'      => $val['seller_id'],
					'order_goods_id' => $val['id'],
				);
				$commentDB->setData($attr);
				$commentDB->add();
			}
		}
	}

	/**
	 * 支付成功后修改订单状态
	 * @param $orderNo  string 订单编号
	 * @param $admin_id int    管理员ID
	 * @param $note     string 收款的备注
	 * @return false or int order_id
	 */
	public static function updateOrderStatus($orderNo,$admin_id = '',$note = '')
	{
	    //是否自动发货
	    $isAutoSend = false;

		//获取订单信息
		$orderObj  = new IModel('order');
		$orderRow  = $orderObj->getObj('order_no = "'.$orderNo.'"');

		if(empty($orderRow))
		{
			return false;
		}

		if($orderRow['pay_status'] == 1)
		{
			return $orderRow['id'];
		}
		else if($orderRow['pay_status'] == 0)
		{
			$dataArray = array(
				'status'     => ($orderRow['status'] == 5) ? 5 : 2,
				'pay_time'   => ITime::getDateTime(),
				'pay_status' => 1,
			);

			$orderObj->setData($dataArray);
			$is_success = $orderObj->update('order_no = "'.$orderNo.'"');
			if($is_success == '')
			{
				return false;
			}

			//删除订单中使用的道具
			$ticket_id = trim($orderRow['prop']);
			if($ticket_id != '')
			{
				$propObj  = new IModel('prop');
				$propData = array('is_userd' => 1);
				$propObj->setData($propData);
				$propObj->update('id = '.$ticket_id);
			}

			//注册用户进行奖励
			if($orderRow['user_id'])
			{
				$user_id = $orderRow['user_id'];

				//获取用户信息
				$memberObj  = new IModel('member');
				$memberRow  = $memberObj->getObj('user_id = '.$user_id,'prop,group_id');

				//(1)删除订单中使用的道具
				if($ticket_id != '')
				{
					$finnalTicket = str_replace(','.$ticket_id.',',',',','.trim($memberRow['prop'],',').',');
					$memberData   = array('prop' => $finnalTicket);
					$memberObj->setData($memberData);
					$memberObj->update('user_id = '.$user_id);
				}

				if($memberRow)
				{
					//(2)进行促销活动奖励(普通订单方式)
					if($orderRow['type'] == '' && $orderRow['prorule_ids'])
					{
				    	$proObj = new ProRule();
				    	$awardText = $proObj->setAwardByIds($orderRow['prorule_ids'],$user_id,$orderRow['id']);
				    	if($awardText)
				    	{
					    	$orderObj->setData(array('note' => "concat(note,'".$awardText."')"));
					    	$orderObj->update('order_no = "'.$orderNo.'"','note');
				    	}
					}

			    	//(3)增加经验值
			    	plugin::trigger('expUpdate',$user_id,$orderRow['exp']);

					//(4)增加积分
					$pointConfig = array(
						'user_id' => $user_id,
						'point'   => $orderRow['point'],
						'log'     => '成功购买了订单号：'.$orderRow['order_no'].'中的商品,奖励积分'.$orderRow['point'],
					);
					$pointObj = new Point();
					$pointObj->update($pointConfig);
				}
			}

			//插入收款单
			$collectionDocObj = new IModel('collection_doc');
			$collectionData   = array(
				'order_id'   => $orderRow['id'],
				'user_id'    => $orderRow['user_id'],
				'amount'     => $orderRow['order_amount'],
				'time'       => ITime::getDateTime(),
				'payment_id' => $orderRow['pay_type'],
				'pay_status' => 1,
				'if_del'     => 0,
				'note'       => $note,
				'admin_id'   => $admin_id ? $admin_id : 0
			);

			$collectionDocObj->setData($collectionData);
			$collectionDocObj->add();

			//促销活动订单
			if($orderRow['type'])
			{
				Active::payCallback($orderNo,$orderRow['type']);
			}

            //获取订单商品信息
			$orderGoodsDB = new IModel('order_goods');
			$orderGoodsList = $orderGoodsDB->query('order_id = '.$orderRow['id']);
			$orderGoodsListId = [];
			foreach($orderGoodsList as $key => $val)
			{
				$orderGoodsListId[] = $val['id'];
			}

            switch($orderRow['goods_type'])
            {
                //服务验证码
                case 'code':
                {
                    $goodsCodeRelationObj = new IModel('order_code_relation');
                    foreach($orderGoodsList as $k => $v)
                    {
                        for($i = 0; $i < $v['goods_nums']; $i++)
                        {
                            $code = self::createCodeNum();
                            $goodsCodeRelationObj->setData(array(
                                'order_id'    => $v['order_id'],
                                'goods_id'    => $v['goods_id'],
                                'code'        => $code,
                                'seller_id'   => $v['seller_id'],
                                'user_id'     => $orderRow['user_id'],
                                'create_time' => ITime::getDateTime(),
                            ));
                            $goodsCodeRelationObj->add();
                            $goods_array = JSON::decode($v['goods_array']);
                        }
                    }
                }
                break;

                //下载服务
                case 'download':
                {
                    $goodsDownloadRelationObj = new IModel('order_download_relation');
                    foreach($orderGoodsList as $k => $v)
                    {
                        for($i = 0; $i < $v['goods_nums']; $i++)
                        {
                            $goodsDownloadRelationObj->setData(array(
                                'order_id'    => $v['order_id'],
                                'goods_id'    => $v['goods_id'],
                                'seller_id'   => $v['seller_id'],
                                'user_id'     => $orderRow['user_id'],
                                'create_time' => ITime::getDateTime(),
                            ));
                            $goodsDownloadRelationObj->add();
                        }
                    }

                    //自动设置发货
                    $isAutoSend = true;
                }
                break;

                //时间类
                case "preorder":
                {

                }
                break;
            }

            //自提方式下设置自动发货和生成验证码
            if($orderRow['takeself'] > 0)
            {
                $code = self::createTakeselfNum();
                $orderObj->setData(array("checkcode" => $code));
                $orderObj->update($orderRow['id']);
                $orderRow['checkcode'] = $code;
                $isAutoSend = true;
            }

			//非货到付款的支付方式和部分商品类型
			if($orderRow['pay_type'] > 0 || !in_array($orderRow['goods_type'],['preorder']))
			{
				//减少库存量
				self::updateStore($orderGoodsListId,'reduce');
			}

            //自动发货
            if($isAutoSend == true)
            {
                self::sendDeliveryGoods($orderRow['id'],$orderGoodsListId,'system');
            }

			//线上支付完成发送事件
			plugin::trigger('orderPayFinish',$orderRow);
			return $orderRow['id'];
		}
		else
		{
			return false;
		}
	}

	/**
	 * 订单商品数量更新操作[公共]
	 * @param array $orderGoodsId ID数据
	 * @param string $type 增加或者减少 add 或者 reduce
	 */
	public static function updateStore($orderGoodsId,$type = 'add')
	{
		if(!is_array($orderGoodsId))
		{
			$orderGoodsId = array($orderGoodsId);
		}

		$newStoreNums     = 0;
		$updateGoodsId    = array();
		$orderGoodsObj    = new IModel('order_goods');
		$goodsObj         = new IModel('goods');
		$productObj       = new IModel('products');
		$goodsList        = $orderGoodsObj->query('id in('.join(",",$orderGoodsId).') and is_send = 0','goods_id,product_id,goods_nums,seller_id');

		foreach($goodsList as $key => $val)
		{
			//货品库存更新
			if($val['product_id'] != 0)
			{
				$productObj->lock = 'for update';
				$productsRow = $productObj->getObj('id = '.$val['product_id'],'store_nums');
				$productObj->lock = '';
				if(!$productsRow)
				{
					continue;
				}

				//同步更新所属商品的库存量
				if(in_array($val['goods_id'],$updateGoodsId) == false)
				{
					$updateGoodsId[] = $val['goods_id'];
				}

				$updateSql = $type == 'add' ? array('store_nums' => 'store_nums + '.$val['goods_nums']) : array('store_nums' => 'store_nums - '.$val['goods_nums']);
				$productObj->setData($updateSql);
				$productObj->update('id = '.$val['product_id'],'store_nums');
			}
			//商品库存更新
			else
			{
				$goodsObj->lock = 'for update';
				$goodsRow = $goodsObj->getObj('id = '.$val['goods_id'],'store_nums');
				$goodsObj->lock = '';
				if(!$goodsRow)
				{
					continue;
				}

				$updateSql = $type == 'add' ? array('store_nums' => 'store_nums + '.$val['goods_nums']) : array('store_nums' => 'store_nums - '.$val['goods_nums']);
				$goodsObj->setData($updateSql);
				$goodsObj->update('id = '.$val['goods_id'],'store_nums');
			}
			//库存减少销售量增加，两者成反比
			$saleData = ($type == 'add') ? -$val['goods_nums'] : $val['goods_nums'];

			//更新goods商品销售量sale字段
			$goodsObj->setData(array('sale' => 'sale + '.$saleData));
			$goodsObj->update('id = '.$val['goods_id'],'sale');

			//更新seller商家销售量sale字段
			$sellerDB = new IModel('seller');
			$sellerDB->setData(array('sale' => 'sale + '.$saleData));
			$sellerDB->update('id = '.$val['seller_id'],'sale');
		}

		//更新统计goods的库存
		if($updateGoodsId)
		{
			foreach($updateGoodsId as $val)
			{
				$totalRow = $productObj->getObj('goods_id = '.$val,'MIN(store_nums) as store');
				$goodsObj->setData(array('store_nums' => $totalRow['store']));
				$goodsObj->update('id = '.$val);
			}
		}
	}

	/**
	 * @brief 获取订单扩展数据资料
	 * @param $order_id int 订单的id
	 * @param $user_id int 用户id
	 * @return array()
	 */
	public function getOrderShow($order_id,$user_id = 0,$seller_id = 0)
	{
		$where = 'id = '.$order_id;
		if($user_id !== 0)
		{
			$where .= ' and user_id = '.$user_id;
		}

		if($seller_id !== 0)
		{
			$where .= ' and seller_id = '.$seller_id;
		}

		$data = array();

		//获得对象
		$tb_order = new IModel('order');
 		$data = $tb_order->getObj($where);
 		if($data)
 		{
	 		$data['order_id'] = $order_id;

	 		//获取配送方式
	 		$tb_delivery = new IModel('delivery');
	 		$delivery_info = $tb_delivery->getObj('id='.$data['distribution']);
	 		if($delivery_info)
	 		{
	 			$data['delivery'] = $delivery_info['name'];
	 		}

 			//自提点读取
 			if($data['takeself'])
 			{
 				$data['takeself'] = self::getTakeselfInfo($data['takeself']);
 			}

	 		$areaData = area::name($data['province'],$data['city'],$data['area']);
	 		if(isset($areaData[$data['province']]) && isset($areaData[$data['city']]) && isset($areaData[$data['area']]))
	 		{
		 		$data['province_str'] = $areaData[$data['province']];
		 		$data['city_str']     = $areaData[$data['city']];
		 		$data['area_str']     = $areaData[$data['area']];
	 		}

	        //物流单号
	    	$tb_delivery_doc = new IQuery('delivery_doc as dd');
	    	$tb_delivery_doc->join   = 'left join freight_company as fc on dd.freight_id = fc.id';
	    	$tb_delivery_doc->fields = 'dd.id,dd.delivery_code,fc.freight_name';
	    	$tb_delivery_doc->where  = 'order_id = '.$order_id;
	    	$delivery_info = $tb_delivery_doc->find();
	    	if($delivery_info)
	    	{
	    		$temp = array('freight_name' => array(),'delivery_code' => array(),'delivery_id' => array());
	    		foreach($delivery_info as $key => $val)
	    		{
	    			$temp['delivery_id'][]   = $val['id'];
	    			$temp['freight_name'][]  = $val['freight_name'];
	    			$temp['delivery_code'][] = $val['delivery_code'];
	    		}
	    		$data['freight']['id']            = current($temp['delivery_id']);
    			$data['freight']['freight_name']  = join(",",$temp['freight_name']);
    			$data['freight']['delivery_code'] = join(",",$temp['delivery_code']);
	    	}

	 		//获取支付方式
 			$data['payment'] = "";
 			$data['paynote'] = "";
	 		$tb_payment = new IModel('payment');
	 		$payment_info = $tb_payment->getObj('id='.$data['pay_type']);
	 		if($payment_info)
	 		{
	 			$data['payment'] = $payment_info['name'];
	 			$data['paynote'] = $payment_info['note'];
	 		}

	 		//获取商品总重量和总金额
	 		$tb_order_goods = new IModel('order_goods');
	 		$order_goods_info = $tb_order_goods->query('order_id='.$order_id);
	 		$data['goods_amount'] = 0;
	 		$data['goods_weight'] = 0;

	 		if($order_goods_info)
	 		{
	 			foreach ($order_goods_info as $value)
	 			{
	 				$data['goods_amount'] += $value['real_price']   * $value['goods_nums'];
	 				$data['goods_weight'] += $value['goods_weight'] * $value['goods_nums'];
	 			}
	 		}

	 		//获取用户信息
	 		$query = new IQuery('user as u');
	 		$query->join = ' left join member as m on u.id=m.user_id ';
	 		$query->fields = 'u.username,m.email,m.mobile,m.contact_addr,m.true_name';
	 		$query->where = 'u.id='.$data['user_id'];
	 		$user_info = $query->find();
	 		if($user_info)
	 		{
	 			$user_info = current($user_info);
	 			$data['username']     = $user_info['username'];
	 			$data['email']        = $user_info['email'];
	 			$data['u_mobile']     = $user_info['mobile'];
	 			$data['contact_addr'] = $user_info['contact_addr'];
	 			$data['true_name']    = $user_info['true_name'];
	 		}
	 		//数据处理用于显示
	 		$data['goods_weight'] = common::formatWeight($data['goods_weight']);
 		}
 		return $data;
	}

	/**
	 * 获取自提点基本信息
	 * @param $id int 自提点id
	 */
	public static function getTakeselfInfo($id)
	{
		$takeselfObj = new IModel('takeself');
		$takeselfRow = $takeselfObj->getObj('id = '.$id);
		if(!$takeselfRow)
		{
			return '';
		}

		$temp = area::name($takeselfRow['province'],$takeselfRow['city'],$takeselfRow['area']);
		$takeselfRow['province_str'] = $temp[$takeselfRow['province']];
		$takeselfRow['city_str']     = $temp[$takeselfRow['city']];
		$takeselfRow['area_str']     = $temp[$takeselfRow['area']];
		return $takeselfRow;
	}

	/**
	 * @brief 把订单商品同步到order_goods表中
	 * @param $order_id 订单ID
	 * @param $goodsInfo 商品和货品信息（购物车数据结构,countSum 最终生成的格式）
	 */
	public function insertOrderGoods($order_id,$goodsResult = array())
	{
		$orderGoodsObj = new IModel('order_goods');
		if(isset($goodsResult['goodsList']) && $goodsResult['goodsList'])
		{
			//清理旧的关联数据
			$orderGoodsObj->del('order_id = '.$order_id);

			$goodsArray = array(
				'order_id' => $order_id
			);

			foreach($goodsResult['goodsList'] as $key => $val)
			{
				//拼接商品名称和规格数据
				$specArray = ['name' => $val['name'],'goodsno' => $val['goods_no'],'value' => ''];

				if(isset($val['spec_array']))
				{
					$specData = JSON::decode($val['spec_array']);
					if($specData)
					{
						foreach($specData as $svalue)
						{
							$specArray['value'] .= $svalue['name'].':'.$svalue['value'].',';
						}
						$specArray['value'] = trim($specArray['value'],',');
					}
				}

				$goodsArray['product_id']  = $val['product_id'];
				$goodsArray['goods_id']    = $val['goods_id'];
				$goodsArray['img']         = $val['img'];
				$goodsArray['goods_price'] = $val['sell_price'];
				$goodsArray['real_price']  = $val['sell_price'] - $val['reduce'];
				$goodsArray['goods_nums']  = $val['count'];
				$goodsArray['goods_weight']= $val['weight'];
				$goodsArray['goods_array'] = IFilter::addSlash(JSON::encode($specArray));
				$goodsArray['seller_id']   = $val['seller_id'];
				$orderGoodsObj->setData($goodsArray);
				if(!$orderGoodsObj->add())
				{
					$orderGoodsObj->rollback();
					return '订单和商品关系插入报错';
				}

				if($val['type'] == 'preorder')
				{
                    $orderGoodsRow = $goodsArray;
                    preg_match("@\d{4}-\d{2}-\d{2}~\d{4}-\d{2}-\d{2}@",$orderGoodsRow['goods_array'],$match);
                    if($match)
                    {
                        $date = explode('~',current($match));
                    }
                    $orderPreorderDB = new IModel('order_extend_preorder');
                    $orderPreorderDB->setData([
                        'order_id'   => $orderGoodsRow['order_id'],
                        'goods_id'   => $orderGoodsRow['goods_id'],
                        'product_id' => $orderGoodsRow['product_id'],
                        'goods_nums' => $orderGoodsRow['goods_nums'],
                        'start_date' => $date[0],
                        'end_date'   => $date[1],
                    ]);
                    $orderPreorderDB->add();
				}
			}
			return true;
		}
		else
		{
			$orderGoodsObj->rollback();
			return '商品数据不存在';
		}
	}

	/**
	 * 获取订单状态
	 * @param $orderRow array('status' => '订单状态','pay_type' => '支付方式ID','distribution_status' => '配送状态','pay_status' => '支付状态')
	 * @return int 订单状态值 0:未知; 1:未付款等待发货(货到付款); 2:等待付款(线上支付); 3:已发货(已付款); 4:已付款等待发货; 5:已取消; 6:已完成(已付款,已收货); 7:全部退款; 8:部分发货(货到付款+已经付款); 9:部分退款(未发货+部分发货); 10:部分退款(全部发货); 11:已发货(货到付款); 12:未处理的退款申请 13:等待自提 14:等待成团 15:等待核销 16:等待服务
	 */
	public static function getOrderStatus($orderRow)
	{
		//1,刚生成订单,未付款
		if($orderRow['status'] == 1)
		{
			//选择货到付款
			if($orderRow['pay_type'] == 0)
			{
				if($orderRow['distribution_status'] == 0)
				{
					return 1;
				}
				else if($orderRow['distribution_status'] == 1)
				{
					return 11;
				}
				else if($orderRow['distribution_status'] == 2)
				{
					return 8;
				}
			}
			//选择在线支付
			else
			{
				return 2;
			}
		}
		//2,已经付款
		else if($orderRow['status'] == 2)
		{
			$refundDB  = new IModel('refundment_doc');
			$refundRow = $refundDB->getObj('order_no = "'.$orderRow['order_no'].'" and if_del = 0 and pay_status not in (1,2)');
			if($refundRow)
			{
				return 12;
			}

			if($orderRow['type'] == 'assemble')
			{
			    $activeDB = new IModel('assemble_active');
			    $activeRow= $activeDB->getObj('order_no = "'.$orderRow['order_no'].'"');
			    if($activeRow)
			    {
    			    $commanderDB = new IModel('assemble_commander');
    			    $commanderRow= $commanderDB->getObj($activeRow['assemble_commander_id']);
    			    if($commanderRow['is_finish'] == 0)
    			    {
    			        return 14;
    			    }
			    }
			}

			if($orderRow['distribution_status'] == 0)
			{
				//服务类商品订单
				if($orderRow['goods_type'] == 'code')
				{
					return 15;
				}
				//时间类商品订单
				else if($orderRow['goods_type'] == 'preorder')
				{
					return 16;
				}
				return 4;
			}
			else if($orderRow['distribution_status'] == 1)
			{
			    //如果是自提方式
			    if(isset($orderRow['takeself']) && $orderRow['takeself'] > 0)
			    {
			        return 13;
			    }
			    else
			    {
			        return 3;
			    }
			}
			else if($orderRow['distribution_status'] == 2)
			{
				return 8;
			}
		}
		//3,取消或者作废订单
		else if($orderRow['status'] == 3 || $orderRow['status'] == 4)
		{
			return 5;
		}
		//4,完成订单
		else if($orderRow['status'] == 5)
		{
			return 6;
		}
		//5,退款
		else if($orderRow['status'] == 6)
		{
			return 7;
		}
		//6,部分退款
		else if($orderRow['status'] == 7)
		{
			//发货
			if($orderRow['distribution_status'] == 1)
			{
				return 10;
			}
			//未发货
			else
			{
				return 9;
			}
		}
		return 0;
	}

	//获取订单支付状态
	public static function getOrderPayStatusText($orderRow)
	{
		if($orderRow['status'] == '6')
		{
			return '全部退款';
		}

		if($orderRow['status'] == '7')
		{
			return '部分退款';
		}

		if($orderRow['pay_status'] == 0)
		{
			return '未付款';
		}

		if($orderRow['pay_status'] == 1)
		{
			return '已付款';
		}
		return '未知';
	}

	//获取订单类型
	public static function getOrderTypeText($orderRow)
	{
	    return $orderRow['type'] ? Active::name($orderRow['type'])."订单" : "普通订单";
	}

	//获取订单配送状态
	public static function getOrderDistributionStatusText($orderRow)
	{
		switch($orderRow['goods_type'])
		{
			case "code":
			{
				if($orderRow['distribution_status'] == 1)
				{
					return '已核销';
				}
				else
				{
					return '未核销';
				}
			}
			break;

			case "download":
			{
				if($orderRow['distribution_status'] == 1)
				{
					return '允许下载';
				}
				else
				{
					return '不可下载';
				}
			}
			break;

			case "preorder":
			{
				if($orderRow['distribution_status'] == 1)
				{
					return '已服务';
				}
				else
				{
					return '未服务';
				}
			}
			break;

			default:
			{
				if($orderRow['status'] == 5)
				{
					return '已收货';
				}
				else if($orderRow['distribution_status'] == 1)
				{
					return '已发货';
				}
				else if($orderRow['distribution_status'] == 0)
				{
					return '未发货';
				}
				else if($orderRow['distribution_status'] == 2)
				{
					return '部分发货';
				}
			}
		}
	}

	/**
	 * 获取订单状态问题说明
	 * @param $statusCode int 订单的状态码
	 * @return string 订单状态说明
	 */
	public static function orderStatusText($statusCode)
	{
		$result = array(
			0 => '未知',
			1 => '等待发货',
			2 => '等待付款',
			3 => '已发货',
			4 => '等待发货',
			5 => '已取消',
			6 => '已完成',
			7 => '已退款',
			8 => '部分发货',
			9 => '部分退款',
			10=> '部分退款',
			11=> '已发货',
			12=> '申请退款',
			13=> '等待自提',
			14=> '等待成团',
			15=> '等待核销',
			16=> '等待服务',
		);
		return isset($result[$statusCode]) ? $result[$statusCode] : '';
	}

	/**
	 * @breif 订单的流向
	 * @param $orderRow array 订单数据
	 * @return array('时间' => '事件')
	 */
	public static function orderStep($orderRow)
	{
		$result = array();

		//1,创建订单
		$result[$orderRow['create_time']] = '订单创建';

		//2,订单支付
		if($orderRow['pay_status'] > 0)
		{
			$result[$orderRow['pay_time']] = '订单付款  '.$orderRow['order_amount'];
		}

		//3,订单配送
        if($orderRow['distribution_status'] > 0 && $orderRow['goods_type'] == 'default')
        {
        	$result[$orderRow['send_time']] = '订单发货完成';
    	}

		//4,订单完成
        if($orderRow['status'] == 5)
        {
        	$result[$orderRow['completion_time']] = '订单完成';
        }
        ksort($result);
        return $result;
	}

	/**
	 * @brief 商品发货接口
	 * @param string $order_id 订单id
	 * @param array $order_goods_relation 订单与商品关联id
	 * @param string $sendor 操作者所属 admin,seller
	 */
	public static function sendDeliveryGoods($order_id,$order_goods_relation,$sendor = 'admin')
	{
		$orderRow = Api::run('getOrderRowById',array('order_id' => $order_id));
		$order_no = $orderRow['order_no'];

		//检查此订单是否存在未处理的退款申请
		$refundDB = new IModel('refundment_doc');
		$refundRow= $refundDB->getObj('order_no = "'.$order_no.'" and pay_status = 0 and if_del = 0');
		if($refundRow)
		{
			return "此订单有未处理的退款申请";
		}

	 	$paramArray = array(
	 		'order_id'      => $order_id,
	 		'user_id'       => IReq::get('user_id')       ? IFilter::act(IReq::get('user_id'),'int')   : $orderRow['user_id'],
	 		'name'          => IReq::get('name')          ? IFilter::act(IReq::get('name'))            : $orderRow['accept_name'],
	 		'postcode'      => IReq::get('postcode')      ? IFilter::act(IReq::get('postcode'),'int')  : $orderRow['postcode'],
	 		'telphone'      => IReq::get('telphone')      ? IFilter::act(IReq::get('telphone'),'phone'): $orderRow['telphone'],
	 		'province'      => IReq::get('province')      ? IFilter::act(IReq::get('province'),'int')  : $orderRow['province'],
	 		'city'          => IReq::get('city')          ? IFilter::act(IReq::get('city'),'int')      : $orderRow['city'],
	 		'area'          => IReq::get('area')          ? IFilter::act(IReq::get('area'),'int')      : $orderRow['area'],
	 		'address'       => IReq::get('address')       ? IFilter::act(IReq::get('address'))         : $orderRow['address'],
	 		'mobile'        => IReq::get('mobile')        ? IFilter::act(IReq::get('mobile'),'mobile') : $orderRow['mobile'],
	 		'freight'       => IReq::get('freight')       ? IFilter::act(IReq::get('freight'),'float') : $orderRow['real_freight'],
	 		'delivery_type' => IReq::get('delivery_type') ? IFilter::act(IReq::get('delivery_type'))   : $orderRow['distribution'],

	 		'freight_id'       => IFilter::act(IReq::get('freight_id'),'int'),
	 		'time'             => ITime::getDateTime(),
	 		'note'             => IFilter::act(IReq::get('note'),'text'),
	 		'delivery_code'    => IFilter::act(IReq::get('delivery_code')),
	 		'express_template' => IReq::get('express_template'),
	 	);

	 	switch($sendor)
	 	{
	 		case "admin":
	 		{
	 			$sendor_id = IWeb::$app->getController()->admin['admin_id'];
	 			$paramArray['admin_id'] = $sendor_id;

	 			$adminDB = new IModel('admin');
	 			$sendorData = $adminDB->getObj('id = '.$sendor_id);
	 			$sendorName = $sendorData['admin_name'];
	 			$sendorSort = '平台管理员';
	 		}
	 		break;

	 		case "seller":
	 		{
	 			$sendor_id = IWeb::$app->getController()->seller['seller_id'];
	 			$paramArray['seller_id'] = $sendor_id;

	 			$sellerDB = new IModel('seller');
	 			$sendorData = $sellerDB->getObj('id = '.$sendor_id);
	 			$sendorName = $sendorData['true_name'];
	 			$sendorSort = '加盟商户';
	 		}
	 		break;

	 		case "system":
	 		{
	 		    $sendorSort = "系统";
				$sendorName = "系统";
	 		}
	 		break;
	 	}

		//订单对象
		$tb_order = new IModel('order');
	 	$orderUpdate = array(
	 		'send_time' => ITime::getDateTime(),
	 	);
        $deliveryId = 0;

    	//1,自提点领取
    	if($orderRow['takeself'] > 0)
    	{
    		//订单日志内容
			$logArray = array(
				'order_id' => $order_id,
				'user'     => $sendorSort,
				'action'   => '自提点自动备货完成',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】修改发货状态',
				'addtime'  => ITime::getDateTime(),
			);
    	}
    	//2,虚拟服务类
    	else if(isset($orderRow['goods_type']) && $orderRow['goods_type'] == 'code')
    	{
    	    $orderUpdate['status'] = 5;
			$orderUpdate['completion_time'] = ITime::getDateTime();

	    	//订单日志内容
			$logArray = array(
				'order_id' => $order_id,
				'user'     => $sendorSort,
				'action'   => '服务类商品核销',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】核销出货',
				'addtime'  => ITime::getDateTime(),
			);
    	}
    	//3,知识付费下载类
    	else if(isset($orderRow['goods_type']) && $orderRow['goods_type'] == 'download')
    	{
    	    $orderUpdate['status'] = 5;
			$orderUpdate['completion_time'] = ITime::getDateTime();

	    	//订单日志内容
			$logArray = array(
				'order_id' => $order_id,
				'user'     => $sendorSort,
				'action'   => '开放下载',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】自动开放下载',
				'addtime'  => ITime::getDateTime(),
			);
    	}
    	//5,时间预定类
    	else if(isset($orderRow['goods_type']) && $orderRow['goods_type'] == 'preorder')
    	{
    	    $orderUpdate['status'] = 5;
			$orderUpdate['completion_time'] = ITime::getDateTime();

	    	//订单日志内容
			$logArray = array(
				'order_id' => $order_id,
				'user'     => $sendorSort,
				'action'   => '完成服务',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】完成服务',
				'addtime'  => ITime::getDateTime(),
			);
    	}
    	//4,普通发货物流
    	else
    	{
	    	//订单日志内容
			$logArray = array(
				'order_id' => $order_id,
				'user'     => $sendorName,
				'action'   => '发货',
				'result'   => '成功',
				'note'     => '订单【'.$order_no.'】由【'.$sendorSort.'】'.$sendorName.'发货',
				'addtime'  => ITime::getDateTime(),
			);

    	 	//如果存在物流公司和快递号则生成发货单
    	 	if($paramArray['freight_id'] && $paramArray['delivery_code'])
    	 	{
    		 	$tb_delivery_doc = new IModel('delivery_doc');
    		 	$tb_delivery_doc->setData($paramArray);
    		 	$deliveryId = $tb_delivery_doc->add();

    		 	$freightDB = new IModel('freight_company');
    		 	$freightRow= $freightDB->getObj($paramArray['freight_id']);

        	 	//订阅物流跟踪
        	 	freight_facade::subscribe($freightRow['freight_type'],$paramArray['delivery_code']);

        	 	//发货成功事件
        	 	plugin::trigger('orderSendDeliveryFinish',$deliveryId);
    	 	}
    	}

		//订单日志记录
    	$tb_order_log = new IModel('order_log');
    	$tb_order_log->setData($logArray);
    	$sendResult = $tb_order_log->add();
    	if(!$sendResult)
    	{
    		$tb_order_log->rollback();
    		return '订单日志生成错误';
    	}

		//如果支付方式为货到付款，则减少库存
		if($orderRow['pay_type'] == 0)
		{
		 	//减少库存量
		 	self::updateStore($order_goods_relation,'reduce');
		}

		//更新发货状态
	 	$orderGoodsDB = new IModel('order_goods');
	 	$orderGoodsRow = $orderGoodsDB->getObj('is_send in (0,3) and order_id = '.$order_id,'count(*) as num');
		$sendStatus = 2;//部分发货
	 	if(count($order_goods_relation) >= $orderGoodsRow['num'])
	 	{
	 		$sendStatus = 1;//全部发货
	 	}
	 	foreach($order_goods_relation as $key => $val)
	 	{
	 		//商家发货检查商品所有权
	 		if(isset($paramArray['seller_id']))
	 		{
	 			$orderGoodsData = $orderGoodsDB->getObj("id = ".$val);
	 			$goodsDB = new IModel('goods');
	 			$sellerResult = $goodsDB->getObj("id = ".$orderGoodsData['goods_id']." and seller_id = ".$paramArray['seller_id']);
	 			if(!$sellerResult)
	 			{
	 				$goodsDB->rollback();
	 				return '发货的商品信息与商家不符合';
	 			}
	 		}

	 		$orderGoodsDB->setData(array(
	 			"is_send"     => 1,
	 			"delivery_id" => $deliveryId,
	 		));

	 		if($orderGoodsDB->update(" id = {$val} ") === false)
	 		{
	 			$orderGoodsDB->rollback();
	 			return '订单商品发货状态修改失败';
	 		}
	 	}

	 	//更新发货状态
	 	$orderUpdate['distribution_status'] = $sendStatus;

 		//如果全部发货之前已存在 "部分退款" 那么更新订单状态以允许“确认收货”按钮可用
 		if($orderRow['status'] == 7 && $sendStatus == 1)
 		{
 			$orderUpdate['status'] = 2;
 		}
	 	$tb_order->setData($orderUpdate);
	 	if(!$tb_order->update('id='.$order_id))
	 	{
			//发货及完成
			if($orderUpdate['status'] == 5)
			{
				//增加用户评论商品机会
				Order_Class::addGoodsCommentChange($order_id);
			}

	 		$tb_order->rollback();
	 		return '订单更新失败';
	 	}
    	return true;
	}

	/**
	 * @biref 是否可以发货操作
	 * @param array $orderRow 订单对象
	 */
	public static function isGoDelivery($orderRow)
	{
		$status = self::getOrderStatus($orderRow);
		if(in_array($status,[1,4,8,9,13,14,15,16]))
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief 获取商品发送状态
	 */
	public static function goodsSendStatus($orderGoodsRow)
	{
		$is_send = $orderGoodsRow['is_send'];
		$orderDB = new IModel('order');
		$orderRow= $orderDB->getObj($orderGoodsRow['order_id'],'pay_status,goods_type');
		if($orderRow['goods_type'] == 'default')
		{
			$data = ['未发货','已发货','已退货','部分退货'];
		}
		else
		{
			$data = ['未付款','已完成','已退款','部分退款'];

			if($orderRow['pay_status'] == 1)
			{
				switch($orderRow['goods_type'])
				{
					case "code":
					{
						$data[0] = '待核销';
						$data[1] = '已核销';
					}
					break;

					case "preorder":
					{
						$data[0] = '待服务';
						$data[1] = '已服务';
					}
					break;
				}
			}
		}
		return isset($data[$is_send]) ? $data[$is_send] : '';
	}

	//获取订单商品信息
	public static function getOrderGoods($order_id)
	{
		$orderGoodsObj        = new IQuery('order_goods');
		$orderGoodsObj->where = "order_id = ".$order_id;
		$orderGoodsObj->fields = 'id,goods_array,goods_id,product_id,goods_nums';
		$orderGoodsList = $orderGoodsObj->find();
		$goodList = array();
		foreach($orderGoodsList as $good)
		{
			$temp = JSON::decode($good['goods_array']);
			$temp['goods_nums'] = $good['goods_nums'];
			$goodList[] = $temp;
		}
		return $goodList;
	}

	/**
	 * @brief 返回检索条件相关信息
	 * @param int $search 条件数组
	 * @return array 查询条件（$join,$where）数据组
	 */
	public static function getSearchCondition($search)
	{
		$join[]  = "left join delivery as d on o.distribution = d.id left join payment as p on o.pay_type = p.id";
		$where[] = "if_del = 0";

		if(isset($search['type']) && isset($search['content']) && $search['content'])
		{
			switch($search['type'])
			{
				case "true_name":
				{
					$sellerObj = new IModel('seller');
					$sellerRow = $sellerObj->getObj('true_name = "'.$search['content'].'"');
					if($sellerRow)
					{
						$where[] = "o.seller_id = ".$sellerRow['id'];
					}
				}
				break;

				case "accept_name":
				{
					$where[] = "o.accept_name = '".$search['content']."'";
				}
				break;

				case "accept_mobile":
				{
					$where[] = "o.mobile = '".$search['content']."'";
				}
				break;

				default:
				{
					$where[] = "o.order_no = '".$search['content']."'";
				}
			}
		}

		if(isset($search['is_seller']) && $search['is_seller'])
		{
			$where[] = $search['is_seller'] == 'yes' ? "o.seller_id > 0" : "o.seller_id = 0";
		}

		if(isset($search['pay_status']) && $search['pay_status'] !== '')
		{
			$pay_status = IFilter::act($search['pay_status'], 'int');
			$where[] = "o.pay_status = ".$pay_status;
		}

		if(isset($search['distribution_status']) && $search['distribution_status'] !== '')
		{
			$distribution_status = IFilter::act($search['distribution_status'], 'int');
			$where[] = "o.distribution_status = ".$distribution_status;
		}

		if(isset($search['status']) && $search['status'] !== '')
		{
			$status = IFilter::act($search['status'], 'int');
			$where[] = "o.status = ".$status;
		}

		if(isset($search['order_amount_down']) && $search['order_amount_down'])
		{
			$seller_price_down = IFilter::act($search['order_amount_down'], 'float');
			$where[] = "o.order_amount >= ".$seller_price_down;
		}

		if(isset($search['order_amount_up']) && $search['order_amount_up'])
		{
			$order_amount_up = IFilter::act($search['order_amount_up'], 'float');
			$where[] = "o.order_amount <= ".$order_amount_up;
		}

		if(isset($search['create_time_start']) && $search['create_time_start'])
		{
			$create_time_start = IFilter::act($search['create_time_start'], 'date');
			$where[] = "o.create_time >= '".$create_time_start."'";
		}

		if(isset($search['create_time_end']) && $search['create_time_end'])
		{
			$create_time_end = IFilter::act($search['create_time_end'], 'date');
			$where[] = "o.create_time <= '".$create_time_end." 23:59:59'";
		}

		if(isset($search['send_time_start']) && $search['send_time_start'])
		{
			$send_time_start = IFilter::act($search['send_time_start'], 'date');
			$where[] = "o.send_time >= '".$send_time_start."'";
		}

		if(isset($search['send_time_end']) && $search['send_time_end'])
		{
			$send_time_end = IFilter::act($search['send_time_end'], 'date');
			$where[] = "o.send_time <= '".$send_time_end." 23:59:59'";
		}

		if(isset($search['completion_time_start']) && $search['completion_time_start'])
		{
			$completion_time_start = IFilter::act($search['completion_time_start'], 'date');
			$where[] = "o.completion_time >= '".$completion_time_start."'";
		}

		if(isset($search['completion_time_end']) && $search['completion_time_end'])
		{
			$completion_time_end = IFilter::act($search['completion_time_end'], 'date');
			$where[] = "o.completion_time <= '".$completion_time_end." 23:59:59'";
		}

		$results = array(join("  ",$join),join(" and ",$where));
		unset($join,$where);
		return $results;
	}

	/**
	 * @brief 是否允许售后申请(产生各项申请单据)
	 * @param array  $orderRow 订单表的数据结构
	 * @param array  $orderGoodsIds 订单与商品关系表ID数组
	 * @param string $type 售后类型 refunds:退款;fix:维修;exchange:换货;cancel:取消
	 * @return boolean true or false
	 */
	public static function isRefundmentApply($orderRow,$orderGoodsIds,$type = 'refunds')
	{
	    if(!$orderRow)
	    {
	        return "订单信息不存在";
	    }

		if(!is_array($orderGoodsIds))
		{
			return "退款商品ID数据类型错误";
		}

		//取消订单
		if($type == 'cancel')
		{
		    if(self::isCancel($orderRow) == false)
		    {
		        return "订单取消失败";
		    }
		}
		//售后订单
		else if(self::isRefund($orderRow) == false)
		{
		    return "订单不符合申请售后条件";
		}

		$order_id = $orderRow['id'];
		switch($type)
		{
			case "refunds":
			{
				//判断是否已经生成了结算申请或者已经结算了
				if($orderRow['is_checkout'] == 1)
				{
					return '此订单金额已被商家结算完毕，请直接与商家联系退款';
				}

				$refundsDB = new IModel('refundment_doc');
				if($refundsDB->getObj('order_id = '.$order_id.' and pay_status not in (2,1)'))
				{
					return "您已经提交了退款申请，请耐心等待";
				}
			}
			break;

			case "fix":
			{
				$fixDB = new IModel('fix_doc');
				if($fixDB->getObj('order_id = '.$order_id.' and status not in (2,1)'))
				{
					return "您已经提交了维修申请，请耐心等待";
				}
			}
			break;

			case "exchange":
			{
				$exchangeDB = new IModel('exchange_doc');
				if($exchangeDB->getObj('order_id = '.$order_id.' and status not in (2,1)'))
				{
					return "您已经提交了换货申请，请耐心等待";
				}
			}
			break;
		}

		//申请退款的商品已经是退款状态
		$goodsOrderDB = new IModel('order_goods');
		if($goodsOrderDB->getObj('id in ('.join(",",$orderGoodsIds).') and is_send = 2'))
		{
			return "商品已经退款了";
		}
		return true;
	}

	/**
	 * @brief 售后状态
	 * @param int $status 售后状态数值
	 * @return string 状态描述
	 */
	public static function refundmentText($status)
	{
		$result = array('0' => '申请中', '1' => '拒绝', '2' => '已完成','3' => '等买家发货','4' => '等商家处理');
		return isset($result[$status]) ? $result[$status] : '';
	}

	/**
	 * @brief 还原重置订单所使用的道具
	 * @param int $order 订单ID
	 */
	public static function resetOrderProp($order_id)
	{
		$orderDB   = new IModel('order');
		$orderList = $orderDB->query('id in ( '.$order_id.' )  and prop is not null');
		foreach($orderList as $key => $orderRow)
		{
			if(isset($orderRow['prop']) && $orderRow['prop'])
			{
				$propDB = new IModel('prop');
				$propDB->setData(array('is_close' => 0,'is_userd' => 0));
				$propDB->update('id = '.$orderRow['prop']);

				//订单付款状态要把优惠券ID添加到member表的prop字段中
				if($orderRow['pay_status'] == 1)
				{
				    ticket::bindByUser($orderRow['prop'],$orderRow['user_id']);
				}
			}
		}
	}

	/**
	 * @brief 商家对退款申请的处理权限
	 * @param int $refundId 退款单ID
	 * @param int $seller_id 商家ID
	 * @return int 退款权限状态, 0:无权查看；1:只读；2：可读可写
	 */
	public static function isSellerRefund($refundId,$seller_id)
	{
		$refundDB = new IModel('refundment_doc');
		$refundRow= $refundDB->getObj('id = '.$refundId.' and seller_id = '.$seller_id,'order_id');

		if($refundRow && $refundRow['pay_status'] == 0)
		{
			$orderDB = new IModel('order');
			$orderRow= $orderDB->getObj('id = '.$refundRow['order_id'],'is_checkout');
			if($orderRow['is_checkout'] == 1)
			{
				return 1;
			}
			else
			{
				return 2;
			}
		}
		return 0;
	}

	/**
	 * @brief 订单退款操作
	 * @param int    $refundId 退款单ID
	 * @param int    $authorId 操作人ID
	 * @param string $type admin:管理员;seller:商家
	 * @param int    $way 退款方式， balance:退款预存款; other:其他方式退款; origin,原路退回
	 * @return boolean
	 */
	public static function refund($refundId,$authorId,$type = 'admin',$way = 'balance')
	{
		plugin::trigger('refundBefore',$refundId);
		$orderGoodsDB   = new IModel('order_goods');
		$refundDB       = new IModel('refundment_doc');
		$goodsDB        = new IModel('goods');
		$memberDB       = new IModel('member');
		$tb_order       = new IModel('order');

		$where = 'id = '.$refundId;
		if($type == "seller")
		{
			$where .= ' and seller_id = '.$authorId;
		}

		$refundsRow = $refundDB->getObj($where);
		$order_id   = $refundsRow['order_id'];
		$order_no   = $refundsRow['order_no'];
		$user_id    = $refundsRow['user_id'];

		if(!$refundsRow)
		{
			return "退款申请信息不存在";
		}

		if(!$refundsRow['order_goods_id'] || !$refundsRow['order_goods_nums'])
		{
			return "退款信息不全，请重新申请退款";
		}

		//当前退款的商品是否之前从未退款
		$orderGoodsList = $orderGoodsDB->query('id in ('.$refundsRow['order_goods_id'].') and order_id = '.$refundsRow['order_id']);
		foreach($orderGoodsList as $item)
		{
			if($item['is_send'] == 2)
			{
				return "商品不能重复退款";
			}
		}

		//获取订单数据
		$orderRow = $tb_order->getObj('id = '.$order_id);
		if($orderRow['pay_status'] == 0)
		{
			return "订单未付款";
		}

		//判断是否已经生成了结算申请或者已经结算了
		$billObj = new IModel('bill');
		$billRow = $billObj->getObj('FIND_IN_SET('.$order_id.',order_ids)');
		if($billRow)
		{
			return '此订单金额已被商家结算完毕，请直接与商家联系退款';
		}

		//根据退款商品的数量判断是否为全部退款或部分退款
		$orderGoodsTotal = $orderGoodsDB->getObj('order_id = '.$refundsRow['order_id'],'SUM(`goods_nums`) as total,SUM(`refunds_nums`) as refundTotal');
		$refundsNums     = array_sum(explode(',',$refundsRow['order_goods_nums'])) + $orderGoodsTotal['refundTotal'];
		if($orderGoodsTotal['total'] < $refundsNums)
		{
			return '退款商品数量已超出请重新申请';
		}

		$amount = $refundsRow['amount'];

		//1,全部退款
		if($orderGoodsTotal['total'] == $refundsNums)
		{
			$orderStatus = 6;

			//自动计算订单剩余的退款金额,把各项已经退款去除
			if($amount == 0)
			{
				$amount = $orderRow['order_amount'];

				//检查之前已经退款的订单
				$hasRefundData = $refundDB->query("order_id = ".$refundsRow['order_id']." and pay_status = 2","amount");
				if($hasRefundData)
				{
					foreach($hasRefundData as $value)
					{
						$amount -= $value['amount'];
					}
				}
			}
		}
		//2,部分退款
		else
		{
			$orderStatus = 7;

			//自动计算选择的商品退款金额
			if($amount == 0)
			{
				foreach($orderGoodsList as $val)
				{
					$refundsItemNum = self::refundsDocNums($refundsRow,$val['id']);
					$amount += $refundsItemNum * $val['real_price'];
				}
			}
		}

		//校验订单金额
		$totalRefundSum = $amount;
		$hasRefundSum   = $refundDB->getObj('order_id = '.$order_id.' and pay_status = 2','SUM(amount) as sumAmount');
		if($hasRefundSum && isset($hasRefundSum['sumAmount']) && $hasRefundSum['sumAmount'])
		{
			$totalRefundSum += $hasRefundSum['sumAmount'];
		}

		if($totalRefundSum > $orderRow['order_amount'])
		{
			return "退款金额不能大于实际用户支付的订单金额";
		}

		//如果是商家自己处理的货到付款订单必须用其他方式退款,防止商家和买家刷预存款
		if($orderRow['pay_type'] == 0 && $type == "seller")
		{
			$way = 'other';
		}

		//处理退款金额最终流向
		$wayResult = false;
		switch($way)
		{
			//用户预存款
			case "balance":
			{
				//获取用户信息
				$memberObj = $memberDB->getObj('user_id = '.$user_id,'user_id');
				if(!$memberObj)
				{
					return "退款到预存款的用户不存在";
				}
				//用户预存款进行的操作记入account_log表
				$log = new AccountLog();
				$config = array(
					'user_id'  => $user_id,
					'event'    => 'drawback', //withdraw:提现,pay:预存款支付,recharge:充值,drawback:退款到预存款
					'num'      => $amount, //整形或者浮点，正为增加，负为减少
					'order_no' => $order_no // drawback类型的log需要这个值
				);

				if($type == 'admin')
				{
					$config['admin_id'] = $authorId;
				}
				else if($type == 'seller')
				{
					$config['seller_id'] = $authorId;
				}
				$wayResult = $log->write($config);
				$wayResult = $wayResult > 0 ? true : false;
			}
			break;

			//其他方式
			case "other":
			{
				$wayResult = true;
			}
			break;

			//原路退回
			case "origin":
			{
				//更新退款金额
				$refundsRow['amount'] = $amount;

				$payment_id = $orderRow['pay_type'];
				$paymentInstance = Payment::createPaymentInstance($payment_id);
				if(!method_exists($paymentInstance,"doRefund"))
				{
					return '此订单的支付方式不支持原路退回，请更换其他退款方式';
				}
				$refundInfo = Payment::getRefundInfo($payment_id,$orderRow,$refundsRow);
				$wayResult  = $paymentInstance->doRefund($refundInfo);
			}
			break;
		}

		if($wayResult && is_string($wayResult))
		{
			return $wayResult;
		}

		if($wayResult !== true)
		{
			return "退款失败";
		}

		//更新订单状态
		$tb_order->setData(['status' => $orderStatus]);
		$tb_order->update('id='.$order_id);

		//累计各项数据进行还原操作
		$reduceExp   = 0;
		$reducePoint = 0;

		foreach($orderGoodsList as $val)
		{
			$refundsItemNum = self::refundsDocNums($refundsRow,$val['id']);

			//库存增加
			self::updateStore($val['id'],'add');

			//更新退款状态
			$is_send = $refundsItemNum == $val['goods_nums'] ? 2 : 3;
			$orderGoodsDB->setData(['is_send' => $is_send, 'refunds_nums' => $refundsItemNum]);
			$orderGoodsDB->update('id = '.$val['id']);

			//退款积分,经验
			$goodsRow = $goodsDB->getObj('id = '.$val['goods_id']);
			if($goodsRow)
			{
    			$reduceExp   += $goodsRow['exp']  * $refundsItemNum;
    			$reducePoint += $goodsRow['point']* $refundsItemNum;
			}
		}

		/**
		 * 当订单为全部退款的状态且未手动输入退款金额(需要系统自动计算退款金额)的时候
		 * 退款金额 = 订单支付总金额 + 运费(是否发货) - 此订单之前已经退款金额
		 *
		 * 进行用户的预存款增加操作,订单中的积分,经验的减少操作
		 */
		if($orderStatus == 6)
		{
			Order_class::resetOrderProp($order_id);

			//促销活动订单
			if($orderRow['type'])
			{
				Active::refundCallback($orderRow['order_no'],$orderRow['type']);
			}

			$reduceExp   = $orderRow['exp'];
			$reducePoint = $orderRow['point'];
		}

		//更新退款表
		$updateData = array(
			'amount'       => $amount,
			'pay_status'   => 2,
			'dispose_time' => ITime::getDateTime(),
			'way'          => $way,
		);
		$refundDB->setData($updateData);
		$refundResult = $refundDB->update('id = '.$refundId);
		if(!$refundResult)
		{
			$refundDB->rollback();
			return '退款表更新失败';
		}

		//非游客订单退款
		if($user_id)
		{
			//更新用户的经验值
			plugin::trigger('expUpdate',$user_id,-$reduceExp);

			//积分记录日志
			$pointConfig = array(
				'user_id' => $user_id,
				'point'   => '-'.$reducePoint,
				'log'     => '退款订单号：'.$orderRow['order_no'].'中的商品,减掉积分 -'.$reducePoint,
			);
			$pointObj = new Point();
			$pointObj->update($pointConfig);
		}

		//生成订单日志
		if($type == 'admin')
		{
			$adminObj  = new IModel('admin');
			$adminRow  = $adminObj->getObj('id = '.$authorId);
			$authorName= $adminRow['admin_name'];
		}
		else if($type == 'seller')
		{
			$sellerObj = new IModel('seller');
			$sellerRow = $sellerObj->getObj('id = '.$authorId);
			$authorName= $sellerRow['seller_name'];
		}
		$tb_order_log = new IModel('order_log');
		$tb_order_log->setData(array(
			'order_id' => $order_id,
			'user'     => $authorName,
			'action'   => '退款',
			'result'   => '成功',
			'note'     => '订单【'.$order_no.'】退款，退款金额：￥'.$amount,
			'addtime'  => ITime::getDateTime(),
		));
		$refundLogResult = $tb_order_log->add();

		//发送退款成功的事件
		plugin::trigger("refundFinish",$refundId);
		return $refundLogResult;
	}

	/**
	 * @brief 检查订单是否重复
	 * @param array $checkData 检查的订单数据
	 * @param array $goodsList 购买的商品数据信息
	 */
	public static function checkRepeat($checkData,$goodsList)
	{
    	$checkWhere = array();
    	foreach($checkData as $key => $val)
    	{
    		if(!$val)
    		{
				return "请完整填写收件人信息";
    		}
    		$checkWhere[] = "`".$key."` = '".$val."'";
    	}
    	$checkWhere[] = " NOW() < date_add(create_time,INTERVAL 2 MINUTE) "; //在有限时间段内生成的订单
    	$checkWhere[] = " status = 1 and pay_status != 1 ";//是否付款
		$where = join(" and ",$checkWhere);

		//查询订单数据库
		$orderObj  = new IModel('order');
    	$orderList = $orderObj->query($where);

    	//有重复下单的嫌疑
    	if($orderList)
    	{
    		//当前购买的
    		$nowBuy = "";
    		foreach($goodsList as $key => $val)
    		{
    			$nowBuy .= $val['goods_id']."@".$val['product_id'];
    		}

			//已经购买的
			$orderGoodsDB = new IModel('order_goods');
			foreach($orderList as $key => $val)
			{
	    		$isBuyed = "";
	    		$orderGoodsList = $orderGoodsDB->query("order_id = ".$val['id']);
	    		foreach($orderGoodsList as $k => $item)
	    		{
	    			$isBuyed .= $item['goods_id']."@".$item['product_id'];
	    		}

	    		if($nowBuy == $isBuyed)
	    		{
					return "您所提交的订单重复，频率太高，请稍候再试...";
	    		}
			}
    	}
    	return true;
	}

	/**
	 * @brief  设置批量子订单
	 * @param  array $orderKey   批量订单KEY
	 * @param  array $orderArray 订单号数组
	 * @return boolean
	 */
	public static function setBatch($orderKey,$orderArray)
	{
		$cacheObj = new ICache('file');
		return $cacheObj->set($orderKey,$orderArray);
	}

	/**
	 * @brief  获取批量子订单
	 * @param  array $orderKey 批量订单KEY
	 * @return array 订单号数组array('订单号' => '金额')
	 */
	public static function getBatch($orderKey)
	{
		$result   = array();//订单号=>订单金额

		$cacheObj = new ICache('file');
		$orderList= $cacheObj->get($orderKey);
		if($orderList)
		{
			$orderDB = new IModel('order');
			foreach($orderList as $key => $val)
			{
				$orderRow = $orderDB->getObj('order_no = "'.$val.'"');
				if($orderRow)
				{
					$result[$val] = $orderRow['order_amount'];
				}
			}
		}
		return $result;
	}

	/**
	 * @brief 获取退款方式文字
	 * @param string $code 编码
	 */
	public static function refundWay($code)
	{
		$result = array('balance' => '预存款退款','other' => '其他方式','origin' => '原路退款');
		return isset($result[$code]) ? $result[$code] : "未知";
	}

	//记录订单物流轨迹
	public static function saveTrace($delivery_code,$content)
	{
		$orderDeTraceDB = new IModel('delivery_trace');
		$updateData     = array(
			'delivery_code' => $delivery_code,
			'content'       => $content,
		);
		$orderDeTraceDB->setData($updateData);
		return $orderDeTraceDB->replace();
	}

	//读取订单物流轨迹
	public static function readTrace($delivery_code)
	{
		$delivery_code  = IFilter::act($delivery_code);
		$orderDeTraceDB = new IModel('delivery_trace');
		return $orderDeTraceDB->getObj('delivery_code = "'.$delivery_code.'"');
	}

	//订单是否可以取消
	public static function isCancel($orderRow)
	{
	    $db = new IModel('refundment_doc');
	    $isRefund = $db->getObj('order_id = '.$orderRow['id'].' and pay_status in (0,3,4)');
	    if(in_array($orderRow['status'],[1,2]) && !$isRefund && $orderRow['distribution_status'] == 0)
	    {
	        return true;
	    }
	    return false;
	}

    //订单是否可以确认收货
	public static function isConfirm($orderRow)
	{
	    $db = new IModel('refundment_doc');
	    $isRefund = $db->getObj('order_id = '.$orderRow['id'].' and pay_status in (0,3,4)');
	    if(in_array($orderRow['status'],[1,2]) && $orderRow['distribution_status'] == 1 && $orderRow['takeself'] == 0 && !$isRefund)
	    {
	        return true;
	    }
	    return false;
	}

    //订单是否可以付款
	public static function isGoPay($orderRow)
	{
	    if(in_array($orderRow['status'],[1]) && $orderRow['pay_type'] > 0 && $orderRow['pay_status'] == 0)
	    {
	        return true;
	    }
	    return false;
	}

    //订单是否可以申请售后
	public static function isRefund($orderRow)
	{
		//订单完成时间超过了限制售后时间
		$low_refunds = IWeb::$app->getController()->_siteConfig->low_refunds;
		if($orderRow['completion_time'] && $low_refunds)
		{
			$passDate = strtotime($orderRow['completion_time']) + $low_refunds * 86400;
			if(time() >= $passDate)
			{
				return false;
			}
		}

	    if(in_array($orderRow['status'],[5,7]) || ($orderRow['status'] == 2 && $orderRow['takeself'] > 0))
	    {
	        return true;
	    }
	    return false;
	}

	//可退款的数量
	public static function refundsApplyNums($orderGoodsRow)
	{
		return $orderGoodsRow['goods_nums'] - $orderGoodsRow['refunds_nums'];
	}

	//退款单中退货数量
	public static function refundsDocNums($refundRow,$orderGoodsId)
	{
		$refundsInfo = array_combine(explode(',',$refundRow['order_goods_id']),explode(',',$refundRow['order_goods_nums']));
		return isset($refundsInfo[$orderGoodsId]) ? $refundsInfo[$orderGoodsId] : 1;
	}
}