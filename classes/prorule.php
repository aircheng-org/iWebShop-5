<?php
/**
 * @copyright (c) 2018 aircheng.com
 * @file ProRule.php
 * @brief 促销规则处理类
 * @author chendeshan
 * @date 2018-7-30
 * @version 5.2
 */

/**
 * @class ProRule
 * @brief 促销活动规则奖励
          奖励方式分为 (1)现金奖励,(2)赠品奖励,(3)预存款奖励
          (1)现金奖励就是直接减少订单总额中的金钱数
          (2)赠品奖励就是订单支持成功后，系统自动发送的赠品
          (3)预存款奖励就是直接充值到用户帐户预存款中
 */
class ProRule
{
	//商品总金额
	private $sum;

	//用户组
	private $user_group = null;

	//现金促销规则奖励仅一次 true:1次;false:多次不限
	public $isCashOnce = true;

	//赠品促销规则奖励仅一次 true:1次;false:多次不限
	public $isGiftOnce = false;

	//现金促销规则奖励方式 1减金额 2奖励折扣
	private $cash_award_type = array(1,2);

	//赠品促销规则奖励方式 3赠送积分 4赠送优惠券 5赠送赠品 6免运费 8赠送经验值
	private $gift_award_type = array(3,4,5,6,8);

	//商家ID
	private $seller_id = 0;

	/**
	 * @brief 构造函数 初始化商品金额
	 * @param float $sum       商品金额
	 * @param int   $seller_id 商家ID
	 */
	public function __construct($sum = 0,$seller_id = 0)
	{
		//商品金额必须为数字
		if(!is_numeric($sum))
		{
			IError::show(403,'order sum must a num');
		}
		$this->sum       = $sum;
		$this->seller_id = IFilter::act($seller_id,'int');
	}

	/**
	 * @brief 设置用户组
	 * @param string 用户组
	 */
	public function setUserGroup($groupId)
	{
		$this->user_group = $groupId;
	}

	/**
	 * @brief 获取现金促销规则优惠后的金额
	 * @return float 优惠后金额
	 */
	public function getSum()
	{
		//获取现金奖励信息
		$cashInfo = $this->getAwardInfo($this->cash_award_type,$this->isCashOnce);

		if($cashInfo)
		{
			//执行现金奖励运算
			return $this->cashAction($cashInfo);
		}
		else
		{
			return $this->sum;
		}
	}

	/**
	 * @brief 进行赠品促销规则的奖励
	 * @param int $user_id 用户的ID值
	 */
	public function setAward($user_id)
	{
		//获取赠品奖励信息
		$giftInfo = $this->getAwardInfo($this->gift_award_type,$this->isGiftOnce);
		return $this->giftAction($giftInfo,$user_id);
	}

	/**
	 * @brief 获取促销规则的数据
	 * @return array plan:活动方案名称; info:具体促销信息;
	 */
	public function getInfo()
	{
		$explain  = array();

		$giftInfo = $this->getAwardInfo($this->gift_award_type,$this->isGiftOnce);
		$cashInfo = $this->getAwardInfo($this->cash_award_type,$this->isCashOnce);

		$allInfo  = array_merge($cashInfo,$giftInfo);

		//增加商户名标识
		$hostTag = "";
		if($this->seller_id)
		{
			$sellerDB = new IModel('seller');
			$sellerRow= $sellerDB->getObj('id = '.$this->seller_id);
			$hostTag  = "【".$sellerRow['true_name']."】";
		}
		foreach($allInfo as $key => $val)
		{
			$explain[$key]['plan'] = $hostTag.$val['name'];
			$explain[$key]['info'] = $this->typeExplain($val['award_type'],$val['condition'],$val['award_value']);
		}
		return $explain;
	}

	/**
	 * @brief 奖励类型解释
	 * @param int 类型id值
	 * @param string 满足条件
	 * @param string 奖励数据
	 * @return string 类型说明
	 */
	private function typeExplain($awardType,$condition,$awardValue)
	{
		switch($awardType)
		{
			case "1":
			{
				return '购物满￥'.$condition.' 优惠￥'.$awardValue;
			}
			break;

			case "2":
			{
				return '购物满￥'.$condition.' 优惠'.$awardValue.'%';
			}
			break;

			case "3":
			{
				return '购物满￥'.$condition.' 增加'.$awardValue.'积分';
			}
			break;

			case "4":
			{
				$ticketObj = new IModel('ticket');
				$where     = 'id = '.$awardValue;
				$ticketRow = $ticketObj->getObj($where);
				return '购物满￥'.$condition.' 立得￥'.$ticketRow['value'].'优惠券';
			}
			break;

			case "5":
			{
				return '购物满￥'.$condition.' 送赠品';
			}
			break;

			case "6":
			{
				if($awardValue)
				{
					return '购物满￥'.$condition.' 部分地区免运费';
				}
				else
				{
					return '购物满￥'.$condition.' 免运费';
				}
			}
			break;

			case "8":
			{
				return '购物满￥'.$condition.' 立加'.$awardValue.'经验';
			}
			break;

			case "10":
		    {
		        return '在线充值满￥'.$condition.' 赠送预存款￥'.$awardValue;
		    }
		    break;

			default:
			{
				return null;
			}
			break;
		}
	}

	/**
	 * @brief 是否减免订单的运费
	 * @return bool true:减免; false:不减免
	 */
	public function isFreeFreight()
	{
		$proList = $this->satisfyPromotion(6);
		if($proList)
		{
			$proRow = current($proList);
			return $proRow['award_value'] ? $proRow['award_value'] : true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 根据商品金额获取所满足的$award_type类别促销规则信息
	 * @param int $award_type 奖励类别 1减金额 2奖励折扣 3赠送积分 4赠送优惠券 5赠送赠品 6免运费 8赠送经验
	 * @return array 促销规则信息
	 */
	private function satisfyPromotion($award_type = null)
	{
		$datetime = ITime::getDateTime();
		$proObj   = new IModel('promotion');
		$where    = '`condition` between 0 and '.$this->sum.' and type = 0 and is_close = 0 and start_time <= "'.$datetime.'" and end_time >= "'.$datetime.'" and seller_id = '.$this->seller_id;

		//奖励类别分析
		if($award_type != null)
		{
			$where.=' and award_type in ('.$award_type.')';
		}

		//用户组
		if($this->user_group)
		{
			$where.=' and (user_group = "" or FIND_IN_SET('.$this->user_group.',user_group))';
		}
		else
		{
			$where.=' and user_group = "" ';
		}
		$proList = $proObj->query($where,'*','CAST( `condition` as signed ) desc');
		return $proList;
	}

	/**
	 * @brief 现金促销规则奖励操作
	 * @param array 现金促销规则奖励信息
	 * @return float 处理后金额
	 */
	private function cashAction($cashArray)
	{
		$sum = $this->sum;
		foreach($cashArray as $val)
		{
			$award_type  = $val['award_type'];
			$award_value = $val['award_value'];

			switch($award_type)
			{
				//减少总额数
				case "1":
				{
					$sum = $sum - $award_value;
				}
				break;

				//减少百分比
				case "2":
				{
					$sum = $sum - ($sum * ($award_value/100));
				}
				break;
			}
		}
		return $sum;
	}

	/**
	 * @brief 赠品促销规则奖励操作
	 * @param array 赠品促销规则奖励信息
	 */
	private function giftAction($giftArray,$user_id)
	{
		$giftInfo = '';
		foreach($giftArray as $key => $val)
		{
			$award_type  = $val['award_type'];
			$award_value = $val['award_value'];
			switch($award_type)
			{
				//积分
				case "3":
				{
					$pointConfig = array(
						'user_id' => $user_id,
						'point'   => $award_value,
						'log'     => $val['name'],
					);
					$pointObj = new Point;
					$pointObj->update($pointConfig);
				}
				break;

				//优惠券
				case "4":
				{
					/*(1)修改prop表*/
					$ticketObj = new IModel('ticket');
					$where     = 'id = '.$award_value.' and NOW() BETWEEN start_time and end_time';
					$ticketRow = $ticketObj->getObj($where);
					if($ticketRow)
					{
					    $insert_id = ticket::create($ticketRow);
					    ticket::bindByUser($insert_id,$user_id);
						$giftInfo .= "【优惠券：".$ticketRow['name']."】";
					}
				}
				break;

				//赠送经验
				case "8":
				{
					plugin::trigger('expUpdate',$user_id,$award_value);
				}
				break;
			}
		}
		return $giftInfo;
	}

	/**
	 * @brief 获取奖励信息
	 * @param array $award_type 奖励类型数组值
	 * @param bool  $is_once    奖励方案是否允许叠加
	 * @return array            奖励信息
	 */
	private function getAwardInfo($award_type,$is_once)
	{
		$awardInfo = array();

		//获取所有现金促销规则奖励信息
		$award_type_str = join(',',$award_type);
		$allAwardInfo   = $this->satisfyPromotion($award_type_str);

		//当现金奖励仅为一次时，奖励优惠最大化
		if($allAwardInfo)
		{
			if($is_once == true)
			{
				$awardInfo[0] = current($allAwardInfo);
			}
			else
			{
				$awardInfo = $allAwardInfo;
			}
		}
		return $awardInfo;
	}

	//根据ID获取促销活动数据
	public function getPromotionByIds($ids)
	{
		$proObj  = new IModel('promotion');
		$where   = 'id in ('.$ids.')';
		return $proObj->query($where,'*','CAST( `condition` as signed ) desc');
	}

	//获取促销规则IDS串
	public function getAwardIds($award_type = '',$is_once = '')
	{
		$result    = array();
		$award_type= $award_type ? $award_type : $this->gift_award_type;
		$is_once   = $is_once    ? $is_once    : $this->isGiftOnce;
		$data      = $this->getAwardInfo($award_type,$is_once);
		foreach($data as $key => $val)
		{
			$result[] = $val['id'];
		}
		return join(',',$result);
	}

	//根据ID奖励执行
	public function setAwardByIds($ids,$user_id)
	{
		$giftArray = $this->getPromotionByIds($ids);
		return $this->giftAction($giftArray,$user_id);
	}

	//获取新用户注册促销的活动内容
	public function regPromotion()
	{
		$datetime = ITime::getDateTime();
		$proObj   = new IModel('promotion');
		$where    = 'type = 5 and is_close = 0 and start_time <= "'.$datetime.'" and end_time >= "'.$datetime.'" and seller_id = '.$this->seller_id;

		//用户组
		if($this->user_group != null)
		{
			$where.=' and (user_group = "" or FIND_IN_SET('.$this->user_group.',user_group))';
		}
		else
		{
			$where.=' and user_group = "" ';
		}
		$proList = $proObj->query($where);
		return $proList;
	}

	/**
	 * @brief 在线充值赠送预存款的活动内容
	 * @param integer $money 充值金额
	 */
	public function payPromotion($money)
	{
	    $money    = (float)$money;
	    $datetime = ITime::getDateTime();
	    $proObj   = new IModel('promotion');
	    $where    = "`condition`<=$money and type = 6 and award_type = 10 and is_close = 0 and start_time <= '$datetime' ";
	    $where   .= "and end_time >= '$datetime' and seller_id = ".$this->seller_id;

	    //用户组
	    if($this->user_group)
	    {
	        $where.=' and (user_group = "" or FIND_IN_SET('.$this->user_group.',user_group))';
	    }
	    else
	    {
	        $where.=' and user_group = "" ';
	    }

	    $proList = $proObj->query($where,'*','CAST( `condition` as signed ) desc',1);
	    return $proList;
	}

	/**
	 * @brief 在线充值奖励操作
	 * @param integer $money 充值金额
	 * @param integer $user_id 用户ID
	 */
	public function setRechargeAward($money, $user_id)
	{
	    $proList = $this->payPromotion($money);
	    if ($proList)
	    {
	        foreach ($proList as $val)
	        {
	            $award_value = $val['award_value'];
	            $award_type = $val['award_type'];
	            switch ($award_type)
	            {
	                case '10':
                    {
                        // 赠送预存款
                        $log = new AccountLog();
                        $note = $this->typeExplain($award_type, $val['condition'], $award_value);
                        $logConfig = array(
                            'user_id' => $user_id,
                            'event' => 'recharge_award',
                            'num' => $award_value,
                            'note' => $note,
                        );
                        $log->write($logConfig);
                    }
                    break;
	            }
	        }
	    }
	}
}