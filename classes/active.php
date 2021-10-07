<?php
/**
 * @copyright Copyright(c) 2015-2018 aircheng.com
 * @file active.php
 * @brief 促销活动处理类
 * @author nswe
 * @date 2018/4/12 19:26:27
 * @version 5.1
 */
class Active
{
	//活动的类型,groupon(团购),time(限时抢购),costpoint(积分兑换商品),assemble(拼团)
	private $promo;

	//参加活动的用户ID
	private $user_id;

	//活动的ID编号
	private $active_id;

	//商品ID 或 货品ID
	private $id;

	//goods 或 product
	private $type;

	//购买数量
	private $buy_num;

	//原始的商品或者货品数据
	public $originalGoodsInfo;

	//活动价格
	public $activePrice;

    //所需积分
    public $spendPoint = 0;

	//活动类型和名称的对应关系
	public static $typeToNameMapping = array('assemble' => '拼团','groupon' => '团购','time' => '限时抢购','costpoint' => '积分兑换');

	/**
	 * @brief 构造函数创建活动
	 * @param $promo string 活动的类型,groupon(团购),time(限时抢购),costpoint(积分兑换),assemble(拼团)
	 * @param $activeId int 活动的ID编号
	 * @param $user_id int 用户的ID编号
	 * @param $id  int 根据$type的不同而表示：商品id,货品id
	 * @param $type string 商品：goods; 货品：product
	 * @param $buy_num int 购买的数量；默认1
	 */
	public function __construct($promo,$active_id,$user_id = 0,$id,$type = "goods",$buy_num = 1)
	{
		$this->promo     = $promo;
		$this->active_id = $active_id;
		$this->user_id   = intval($user_id);
		$this->id        = $id;
		$this->type      = $type;
		$this->buy_num   = $buy_num;
	}

	/**
	 * @brief 检查活动的合法性
	 * @param int $order_id 订单ID
	 * @return string(有错误) or true(处理正确)
	 */
	public function checkValid($order_id = '')
	{
		if(!$this->id)
		{
			return "商品ID不存在";
		}
		$goodsData = ($this->type == 'product') ? Api::run('getProductInfo',array('id' => $this->id)) : Api::run('getGoodsInfo',array('id' => $this->id));

		//库存判断
		if(!$goodsData || $this->buy_num <= 0 || $this->buy_num > $goodsData['store_nums'])
		{
			return "购买的数量不正确或大于商品的库存量";
		}

		$this->originalGoodsInfo = $goodsData;
		$this->activePrice       = $goodsData['sell_price'];
		$goods_id                = $goodsData['goods_id'];

		//具体促销活动的合法性判断
		switch($this->promo)
		{
			//拼团
			case "assemble":
			{
				if(!$this->user_id)
				{
					return "参加拼团活动请您先登录";
				}
				$assembleRow = Api::run('getAssembleRowById',array("id" => $this->active_id));

				if($assembleRow)
				{
					if($assembleRow['goods_id'] != $goodsData['goods_id'])
					{
						return "该商品没有参与拼团活动";
					}
					$this->activePrice = $assembleRow['assemble_price'];
				}
				else
				{
					return "当前时间段内不存在此拼团活动，该活动已结束。";
				}
				return true;
			}
			break;

			//团购
			case "groupon":
			{
				if(!$this->user_id)
				{
					return "参加团购活动请您先登录";
				}

				$regimentRow = Api::run('getRegimentRowById',array("id" => $this->active_id));
				if($regimentRow)
				{
					if($regimentRow['goods_id'] != $goodsData['goods_id'])
					{
						return "该商品没有参与团购活动";
					}

					if($regimentRow['store_nums'] <= $regimentRow['sum_count'])
					{
						return "团购商品已经销售一空";
					}

					if($this->buy_num + $regimentRow['sum_count'] > $regimentRow['store_nums'])
					{
						return "当前团购库存不足，无法购买";
					}

					//检查团购订单
					$orderDB   = new IModel('order as o,order_goods as og');
					$orderData = $orderDB->query('o.user_id = '.$this->user_id.' and o.type = "groupon" and o.id = og.order_id and active_id = '.$this->active_id);
					$hasBugNum = 0;
					foreach($orderData as $key => $val)
					{
						//此ID的订单不作为统计判断,用于已下单后支付时候的判断情况
						if($order_id && $val['order_id'] == $order_id)
						{
							continue;
						}
						$orderStatus = Order_class::getOrderStatus($val);
						if(in_array($orderStatus,[2,1,11,12]))
						{
							return "您参与的该团购订单还没有完成";
						}

						if(in_array($orderStatus,array(3,4,6)))
						{
							$hasBugNum += $val['goods_nums'];
						}
					}

					//批量购买(薄利多销)
					if($regimentRow['limit_min_count'] > 0)
					{
						if($this->buy_num < $regimentRow['limit_min_count'])
						{
							return "购买数量必须超过 ".$regimentRow['limit_min_count']." 件才能下单";
						}
					}

					//限制购买(限购，要多人参与)
					if($regimentRow['limit_max_count'] > 0)
					{
						if($this->buy_num > $regimentRow['limit_max_count'])
						{
							return "购买数量不能超过 ".$regimentRow['limit_max_count']." 件";
						}

						if(($hasBugNum + $this->buy_num) > $regimentRow['limit_max_count'])
						{
							return "此团购为限购活动，您累计购买数量不能超过".$regimentRow['limit_max_count'];
						}
					}

					if($this->buy_num > $regimentRow['store_nums'])
					{
						return "购买数量超过了团购剩余量";
					}

					$this->activePrice = $regimentRow['regiment_price'];
				}
				else
				{
					return "当前时间段内不存在此团购活动";
				}
				return true;
			}
			break;

			//抢购
			case "time":
			{
				$promotionRow = Api::run('getPromotionRowById',array("id" => $this->active_id));
				if($promotionRow)
				{
					if($promotionRow['condition'] != $goodsData['goods_id'])
					{
						return "该商品没有参与抢购活动";
					}

					$memberObj = new IModel('member');
					$memberRow = $memberObj->getObj('user_id = '.$this->user_id,'group_id');
					if($promotionRow['user_group'] == '' || (isset($memberRow['group_id']) && stripos(','.$promotionRow['user_group'].',',','.$memberRow['group_id'].',')!==false))
					{
						$this->activePrice = $promotionRow['award_value'];
					}
					else
					{
						return "此活动仅限指定的用户组";
					}
				}
				else
				{
					return "不存在此限时抢购活动";
				}
				return true;
			}
			break;

            //积分兑换
            case "costpoint":
            {
                if(!$this->user_id)
                {
                    return "参加积分兑换请您先登录";
                }

                $promotionRow = Api::run('getCostPointRowById',array("id" => $this->active_id));
                if($promotionRow)
                {
                    if($promotionRow['goods_id'] != $goodsData['goods_id'])
                    {
                        return "该商品没有参与积分兑换活动";
                    }

                    $memberDB  = new IModel('member');
                    $memberRow = $memberDB->getObj('user_id = '.$this->user_id,'point,group_id');
                    if(!$memberRow)
                    {
                        return "用户信息不存在";
                    }

                    if($memberRow['point'] < $promotionRow['point'] * $this->buy_num)
                    {
                        return "用户积分不足";
                    }

                    if($promotionRow['user_group'] == '' || (isset($memberRow['group_id']) && stripos(','.$promotionRow['user_group'].',',','.$memberRow['group_id'].',')!==false))
                    {
                        $this->activePrice = 0;
                        $this->spendPoint  = $this->buy_num * $promotionRow['point'];
                    }
                    else
                    {
                        return "此活动仅限指定的用户组";
                    }
                }
                else
                {
                    return "不存在此积分兑换活动";
                }
                return true;
            }
            break;
		}
		return "未知促销活动";
	}

	//获取活动名称
	public static function name($promo)
	{
		$result = self::$typeToNameMapping;
		return isset($result[$promo]) ? $result[$promo] : '';
	}

	/**
	 * @brief 订单生成后的回调
	 * @param $orderNo string 订单号
	 * @param $dataArray array 数据参数
	 * @author wenjie
	 */
	public static function orderCallback($orderNo,$dataArray)
	{
	    switch($dataArray['type'])
	    {
	        //拼团
	        case "assemble":
	        {
                $assemble_commander_id = IFilter::act(IReq::get('assemble_commander_id'),'int');

                //加入某团活动
                if($assemble_commander_id)
                {
                    $commanderDB = new IModel('assemble_commander');
                    $commanderDB->lock = 'for update';
                    $commanderRow= $commanderDB->getObj($assemble_commander_id);
                    $commanderDB->lock = '';

                    $commanderDB->setData(['member_nums' => 'member_nums + 1']);
                    $isResult = $commanderDB->update('id = '.$assemble_commander_id.' and is_finish = 0 and limit_nums > member_nums and user_id != '.$dataArray['user_id'],'member_nums');
                    if(!$isResult)
                    {
                        $commanderDB->rollback();
                        IError::show("该拼团已无法加入，请选择其他团");
                    }

                    //插入拼团组员报名表
                    $activeDB = new IModel('assemble_active');
                    $activeData = [
                        'user_id'               => $dataArray['user_id'],
                        'order_no'              => $orderNo,
                        'assemble_commander_id' => $assemble_commander_id,
                        'create_time'           => ITime::getDateTime(),
                    ];
                    $activeDB->setData($activeData);
                    $activeDB->add();
                }
	        }
	        break;
	    }
	}

	/**
	 * @brief 订单付款后的回调
	 * @param $orderNo string 订单号
	 * @param $orderType string 订单类型
	 */
	public static function payCallback($orderNo,$orderType)
	{
		switch($orderType)
		{
			//拼团
			case "assemble":
			{
                $assembleDB  = new IModel('assemble');
				$commanderDB = new IModel('assemble_commander');
				$activeDB    = new IModel('assemble_active');

				//销售数量统计
				$orderDB = new IModel('order as o,order_goods as og');
				$orderRow= $orderDB->getObj("o.order_no = '{$orderNo}' and o.id = og.order_id","og.goods_nums,o.active_id,o.user_id");
				if($orderRow)
				{
					$assembleDB->setData(array('sum_count' => 'sum_count + '.$orderRow['goods_nums']));
					$assembleDB->update($orderRow['active_id'],array('sum_count'));
				}

				//已经加入某团
				$activeRow = $activeDB->getObj('order_no = "'.$orderNo.'"');
				if($activeRow)
				{
				    $activeDB->setData(['is_pay' => 1]);
				    $activeDB->update($activeRow['id']);

				    $commanderRow = $commanderDB->getObj($activeRow['assemble_commander_id'],'limit_nums');

				    //更新团长表状态
				    $countNums = $activeDB->getObj('assemble_commander_id = '.$activeRow['assemble_commander_id'].' and is_pay = 1','count(*) as nums');
				    if($countNums['nums'] == $commanderRow['limit_nums'])
				    {
				        $commanderDB->setData(['is_finish' => 1]);
				        $commanderDB->update($activeRow['assemble_commander_id']);
				    }
				}
				//自己开团
				else
				{
				    //获取用户信息
				    $userDB = new IModel('user');
				    $userRow= $userDB->getObj($orderRow['user_id']);

                    //获取拼团主表信息
				    $assembleRow = $assembleDB->getObj($orderRow['active_id']);

                    //创建团长数据
				    $commanderData = [
				        'assemble_id' => $orderRow['active_id'],
				        'user_id'     => $orderRow['user_id'],
				        'user_name'   => $userRow['username'],
				        'user_ico'    => $userRow['head_ico'],
				        'limit_nums'  => $assembleRow['limit_nums'],
				        'create_time' => ITime::getDateTime(),
				    ];
				    $commanderDB->setData($commanderData);
				    $assemble_commander_id = $commanderDB->add();

                    //插入拼团组员报名表
                    $activeData = [
                        'user_id'               => $orderRow['user_id'],
                        'order_no'              => $orderNo,
                        'assemble_commander_id' => $assemble_commander_id,
                        'create_time'           => ITime::getDateTime(),
                        'is_pay'                => 1,
                    ];
                    $activeDB->setData($activeData);
                    $activeDB->add();
				}
			}
			break;

			//团购
			case "groupon":
			{
				$tableModel = new IModel('order as o,order_goods as og');
				$orderRow   = $tableModel->getObj("o.order_no = '{$orderNo}' and o.id = og.order_id","og.goods_nums,o.active_id");
				if($orderRow)
				{
					$regimentModel = new IModel('regiment');
					$regimentModel->setData(array('sum_count' => 'sum_count + '.$orderRow['goods_nums']));
					$regimentModel->update($orderRow['active_id'],array('sum_count'));
				}
			}
			break;

			//抢购
			case "time":
			{

			}
			break;

            //积分兑换
            case "costpoint":
            {
                $tableModel = new IModel('order');
                $orderRow   = $tableModel->getObj("order_no = '{$orderNo}'","spend_point,user_id,order_no");
                if($orderRow)
                {
                    $user_id = $orderRow['user_id'];
                    $pointConfig = array(
                        'user_id' => $user_id,
                        'point'   => -$orderRow['spend_point'],
                        'log'     => '成功购买订单号：'.$orderRow['order_no'].'中的商品,消耗积分'.$orderRow['spend_point'],
                    );
                    $pointObj = new Point();
                    $pointObj->update($pointConfig);
                }
            }
            break;

            default:
            {
                IError::show("无法识别活动类型");
            }
            break;
		}
	}

	//获取活动数据
	public function data()
	{
		switch($this->promo)
		{
			case "assemble":
			{
				$data = Api::run("getAssembleRowById",array("id" => $this->active_id));
				if($data && $data['goods_id'] ==  $this->id)
				{
				    //指定某团ID
				    $acid = IFilter::act(IReq::get('acid'),'int');
				    $commanderDB = new IModel('assemble_commander');
				    if($acid)
				    {
				        $commanderRow = $commanderDB->getObj('id = '.$acid.' and is_finish = 0 and limit_nums > member_nums and user_id != '.$this->user_id);
				    }
				    //系统推荐某团,排除自己是团长的团
				    else
				    {
        			    $commanderRow= $commanderDB->getObj('assemble_id = '.$this->active_id.' and is_finish = 0 and limit_nums > member_nums and user_id != '.$this->user_id,'*','id asc');
				    }
				    $data['commanderUser'] = $commanderRow;
					return $data;
				}
				return "拼团活动不存在";
			}
			break;

			case "groupon":
			{
				$data = Api::run("getRegimentRowById",array("id" => $this->active_id));
				if($data && $data['goods_id'] ==  $this->id)
				{
					return $data;
				}
				return "团购活动不存在";
			}
			break;

			case "time":
			{
				$data = Api::run("getPromotionRowById",array("id" => $this->active_id));
				if($data && $data['condition'] == $this->id)
				{
					return $data;
				}
				return "限时抢购活动不存在";
			}
			break;

            case "costpoint":
            {
                $data = Api::run("getCostPointRowById",array("id" => $this->active_id));
                if($data && $data['goods_id'] == $this->id)
                {
                    return $data;
                }
                return "积分兑换活动不存在";
            }
            break;

            default:
            {
                IError::show("无法识别活动类型");
            }
            break;
		}
	}

	/**
	 * @brief 订单退款后的回调
	 * @param $orderNo string 订单号
	 * @param $orderType 订单类型
	 */
	public static function refundCallback($orderNo,$orderType)
	{
		switch($orderType)
		{
			//拼团
			case "assemble":
			{
				$tableModel = new IModel('order as o,order_goods as og');
				$orderRow   = $tableModel->getObj("o.order_no = '{$orderNo}' and o.id = og.order_id","og.goods_nums,o.active_id");
				if($orderRow)
				{
					$assembleActiveDB  = new IModel('assemble_active');
					$assembleActiveRow = $assembleActiveDB->getObj('order_no = "'.$orderNo.'"');
					if(!$assembleActiveRow)
					{
					    return '未发现拼团报名数据';
					}

					$assembleCommanderDB = new IModel('assemble_commander');
					$assembleCommanderRow= $assembleCommanderDB->getObj($assembleActiveRow['assemble_commander_id']);
					if(!$assembleCommanderRow)
					{
					    return '未发现拼团组数据';
					}

					//如果已经成团了则忽略
					if($assembleCommanderRow['is_finish'] == 1)
					{
					    return true;
					}

					//如果未成团则减掉拼团组数量或者删除组
					if($assembleCommanderRow['member_nums'] > 1)
					{
					    $assembleCommanderDB->setData(['member_nums' => 'member_nums - 1']);
					    $assembleCommanderDB->update($assembleActiveRow['assemble_commander_id'],'member_nums');
					}
					else
					{
					    $assembleCommanderDB->del($assembleActiveRow['assemble_commander_id']);
					}

					//删除拼团活动报名表
					$assembleActiveDB->del('order_no = "'.$orderNo.'"');

                    //更新拼团数量统计
					$assembleModel = new IModel('assemble');
					$assembleModel->setData(array('sum_count' => 'sum_count - '.$orderRow['goods_nums']));
					$assembleModel->update($orderRow['active_id'],'sum_count');
				}
			}
			break;

			//团购
			case "groupon":
			{
				$tableModel = new IModel('order as o,order_goods as og');
				$orderRow   = $tableModel->getObj("o.order_no = '{$orderNo}' and o.id = og.order_id","og.goods_nums,o.active_id");
				if($orderRow)
				{
					$regimentModel = new IModel('regiment');
					$regimentModel->setData(array('sum_count' => 'sum_count - '.$orderRow['goods_nums']));
					$regimentModel->update('id = '.$orderRow['active_id'],array('sum_count'));
				}
			}
			break;

			//抢购
			case "time":
			{

			}
			break;

            //积分兑换
            case "costpoint":
            {
                $tableModel = new IModel('order');
                $orderRow   = $tableModel->getObj("order_no = '{$orderNo}'","spend_point,user_id,order_no");
                if($orderRow)
                {
                    $user_id = $orderRow['user_id'];
                    $pointConfig = array(
                        'user_id' => $user_id,
                        'point'   => $orderRow['spend_point'],//需要返还的积分
                        'log'     => '退款订单号：'.$orderRow['order_no'].'中的商品,退还积分'.$orderRow['spend_point'],
                    );
                    $pointObj = new Point();
                    $pointObj->update($pointConfig);
                }
            }
            break;

            default:
            {
                IError::show("无法识别活动类型");
            }
            break;
		}
	}

	/**
	 * addby wenjie 20190702
	 * @brief 拼团活动的状态
	 * @param array $row 表数据
	 * @param string
	 */
	public static function statusAssemble($row)
	{
		if($row['is_close'] == 1)
		{
			return '关闭';
		}

		if($row['is_close'] == 2)
		{
			return '待审';
		}

        $goodsRow = Api::run("getGoodsInfo",array('id' => $row['goods_id']));
		if(!$goodsRow)
		{
			return '商品不存在';
		}

		if($goodsRow['promo'] == '' || $goodsRow['active_id'] == 0)
		{
			return '状态错误';
		}
		return '正常';
	}

	/**
	 * @brief 团购活动的状态
	 * @param array $row 表数据
	 * @param string
	 */
	public static function statusRegiment($row)
	{
		if($row['is_close'] == 1)
		{
			return '关闭';
		}

		if($row['is_close'] == 2)
		{
			return '待审';
		}

		$nowTime = time();
		if($nowTime < strtotime($row['start_time']))
		{
			return '未开始';
		}

		if($nowTime > strtotime($row['end_time']))
		{
			return '已过期';
		}

        $goodsRow = Api::run("getGoodsInfo",array('id' => $row['goods_id']));
		if(!$goodsRow)
		{
			return '商品不存在';
		}

		if($goodsRow['promo'] == '' || $goodsRow['active_id'] == 0)
		{
			return '状态错误';
		}
		return '正常';
	}

	/**
	 * @brief 抢购活动的状态
	 * @param array $row 表数据
	 * @param string
	 */
	public static function statusTime($row)
	{
		if($row['is_close'] == 1)
		{
			return '关闭';
		}

		$nowTime = time();
		if($nowTime < strtotime($row['start_time']))
		{
			return '未开始';
		}

		if($nowTime > strtotime($row['end_time']))
		{
			return '已过期';
		}

        $goodsRow = Api::run("getGoodsInfo",array('id' => $row['condition']));
		if(!$goodsRow)
		{
			return '商品不存在';
		}

		if($goodsRow['promo'] == '' || $goodsRow['active_id'] == 0)
		{
			return '状态错误';
		}
        return '正常';
	}

    /**
     * @brief 积分兑换活动的状态
     * @param array $row 表数据
     * @param string
     */
    public static function statusCostPoint($row)
    {
        if($row['is_close'] == 1)
        {
            return '关闭';
        }

        $goodsRow = Api::run("getGoodsInfo",array('id' => $row['goods_id']));
        if(!$goodsRow)
        {
            return '商品不存在';
        }

		if($goodsRow['promo'] == '' || $goodsRow['active_id'] == 0)
		{
			return '状态错误';
		}
        return '正常';
    }

	//检测商品ID是否处于特价活动中
	public static function isSale($id)
	{
		$promoDB = new IModel('promotion');
		$promoRow= $promoDB->getObj('find_in_set('.$id.',intro) and is_close = 0');
		if($promoRow)
		{
			return $promoRow;
		}
		return false;
	}

	//商品详情页面的活动视图路径
	public function productTemplate()
	{
		switch($this->promo)
		{
			case "assemble":
			{
				return "_products_assemble";
			}
			break;

			case "groupon":
			{
				return "_products_groupon";
			}
			break;

			case "time":
			{
				return "_products_time";
			}
			break;

			case "costpoint":
			{
				return "_products_costpoint";
			}
			break;
		}
	}

    /*
     * 编辑商品活动记录
     * @param $active_id int    活动ID
     * @param $promo     string 活动类型
     */
    public static function goodsActiveEdit($active_id,$promo)
    {
        $goods_id = '';
        switch($promo)
        {
            case "groupon":
            {
                $modelObj = new IModel('regiment');
                $data = $modelObj->getObj($active_id);
                if($data)
                {
                    $goods_id = $data['goods_id'];
                }
            }
            break;

			case "assemble":
            {
                $modelObj = new IModel('assemble');
                $data = $modelObj->getObj($active_id);
                if($data)
                {
                    $goods_id = $data['goods_id'];
                }
            }
			break;

            case "time":
            {
                $modelObj = new IModel('promotion');
                $data = $modelObj->getObj($active_id);
                if($data)
                {
                    $goods_id = $data['condition'];
                }
            }
            break;

            case "costpoint":
            {
                $modelObj = new IModel('cost_point');
                $data = $modelObj->getObj($active_id);
                if($data)
                {
                    $goods_id = $data['goods_id'];
                }
            }
            break;

            default:
            {
                IError::show("无法识别活动类型");
            }
            break;
        }

        if($goods_id)
        {
            $goodsObj = new IModel('goods');
            $goodsRow = $goodsObj->getObj($goods_id);
            if(!$goodsRow)
            {
                return '商品信息不存在';
            }

            if($goodsRow['promo'] && ($goodsRow['promo'] != $promo || $goodsRow['active_id'] != $active_id))
            {
                return '当前商品已经做了营销活动';
            }
            else
            {
                $dataArray = array(
                    'active_id'  => $active_id,
                    'promo'      => $promo,
                );
                $goodsObj->setData($dataArray);
                $goodsObj->update($goods_id);
                return true;
            }
        }
        return '商品ID未获取';
    }

    /*
     * 删除商品活动属性
     * @param $idString ID字符串逗号拼接
     * @param $promo 活动名称
     */
    public static function goodsActiveDel($idString,$promo)
    {
        $goodsObj  = new IModel('goods');
        $goods_ids = array();
        switch($promo)
        {
            case "groupon":
            {
                $modelObj = new IModel('regiment');
                $data = $modelObj->query('id in ('.$idString.')','goods_id');
                if($data)
                {
                    $goods_ids = array_column($data,'goods_id');
                }
            }
            break;

			case "assemble":
            {
                $modelObj = new IModel('assemble');
                $data = $modelObj->query('id in ('.$idString.')','goods_id');
                if($data)
                {
                    $goods_ids = array_column($data,'goods_id');
                }
            }
			break;

            case "time":
            {
                $modelObj = new IModel('promotion');
                $data = $modelObj->query('id in ('.$idString.')','`condition`');
                if($data)
                {
                    $goods_ids = array_column($data,'condition');
                }
            }
            break;

            case "costpoint":
            {
                $modelObj = new IModel('cost_point');
                $data = $modelObj->query('id in ('.$idString.')','goods_id');
                if($data)
                {
                    $goods_ids = array_column($data,'goods_id');
                }
            }
            break;

            default:
            {
                IError::show("无法识别活动类型");
            }
            break;
        }

        if($goods_ids)
        {
            $dataArray = array(
                'active_id'  => '',
                'promo'      => '',
            );
            $goodsObj->setData($dataArray);
            $goodsObj->update('id in ('.join(',',$goods_ids).')');
        }
    }

    //拼团推广URL
    public static function assembleUrl($order_no)
    {
        $activeDB = new IModel('assemble_active');
        $activeRow= $activeDB->getObj('order_no = "'.$order_no.'"');
        if(!$activeRow)
        {
            return false;
        }

        $commanderDB = new IModel('assemble_commander');
        $commanderRow= $commanderDB->getObj($activeRow['assemble_commander_id']);

        $assembleDB = new IModel('assemble');
        $assembleRow= $assembleDB->getObj($commanderRow['assemble_id']);

        return IUrl::getHost().IUrl::creatUrl('/site/products/id/'.$assembleRow['goods_id'].'/acid/'.$activeRow['assemble_commander_id']);
    }
}