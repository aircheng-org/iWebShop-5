<?php
/**
 * @copyright (c) 2014 aircheng
 * @file statistics.php
 * @brief 统计分析类库
 * @author nswe
 * @date 2014/7/27 11:09:43
 * @version 1.0.0
 */
class statistics
{
	//日期单位
	public static $dateUnit = '';

	//日期格式
	public static $format = 'Y-m-d';

	/**
	 * @brief 日期的智能处理
	 * @param string $start 开始日期 Y-m-d
	 * @param string $end   结束日期 Y-m-d
	 */
	public static function dateParse($start = '',$end = '')
	{
		//默认没有时间条件,查询之前7天的数据
		if(!$start && !$end)
		{
			$diffSec = 86400 * 6;
			$beforeDate = ITime::pass(-$diffSec,self::$format);
			return array($beforeDate,ITime::getNow(self::$format));
		}

		//有时间条件
		if($start && $end)
		{
			return array($start,$end);
		}
		else if($start)
		{
			return array($start,$start);
		}
		else if($end)
		{
			return array($end,$end);
		}
	}

	/**
	 * @brief 根据条件分组
	 * @param int 相差的秒数
	 * @return string y年,m月,d日
	 */
	private static function groupByCondition($diffSec)
	{
		$diffSec = abs($diffSec);
		//按天分组，小于30个天
		if($diffSec <= 86400 * 30)
		{
			return 'd';
		}
		//按月分组，小于24个月
		else if($diffSec <= 86400 * 30 * 24)
		{
			return 'm';
		}
		//按年分组
		else
		{
			return 'y';
		}
	}

	/**
	 * @brief 处理条件
	 * @param IQuery $db 数据库IQuery对象
	 * @param string $timeCols 时间字段名称
	 * @param string $start 开始日期 Y-m-d
	 * @param string $end   结束日期 Y-m-d
	 */
	private static function ParseCondition($db,$timeCols = 'time',$start = '',$end = '')
	{
		$result     = array();

		//获取时间段
		$date       = self::dateParse($start,$end);
		$startArray = explode('-',$date[0]);
		$endArray   = explode('-',$date[1]);
		$diffSec    = ITime::getDiffSec($date[0],$date[1]);

		switch(self::groupByCondition($diffSec))
		{
			//按照年
			case "y":
			{
				$startCondition = $startArray[0];
				$endCondition   = $endArray[0]+1;
				$db->fields .= ',DATE_FORMAT(`'.$timeCols.'`,"%Y") as xValue';
				$db->group   = "DATE_FORMAT(`".$timeCols."`,'%Y') having `".$timeCols."` >= '{$startCondition}-00-00' and `".$timeCols."` < '{$endCondition}-00-00'";
			}
			break;

			//按照月
			case "m":
			{
				$startCondition = $startArray[0].'-'.$startArray[1];
				$endCondition   = $endArray[1] == 12 ? ($endArray[0]+1) : $endArray[0].'-'.($endArray[1]+1);
				$db->fields .= ',DATE_FORMAT(`'.$timeCols.'`,"%Y-%m") as xValue';
				$db->group   = "DATE_FORMAT(`".$timeCols."`,'%Y-%m') having `".$timeCols."` >= '{$startCondition}-00' and `".$timeCols."` < '{$endCondition}-00'";
			}
			break;

			//按照日
			case "d":
			{
				$startCondition = $startArray[0].'-'.$startArray[1].'-'.$startArray[2];
				$endCondition   = $endArray[0].'-'.$endArray[1].'-'.$endArray[2].' 23:59:59';
				$db->fields .= ',DATE_FORMAT(`'.$timeCols.'`,"%m-%d") as xValue';
				$db->group   = "DATE_FORMAT(`".$timeCols."`,'%Y-%m-%d') having `".$timeCols."` >= '{$startCondition}' and `".$timeCols."` < '{$endCondition}'";
			}
			break;
		}
		$data = $db->find();
		foreach($data as $key => $val)
		{
			$result[$val['xValue']] = intval($val['yValue']);
		}
		return $result;
	}

	/**
	 * @brief 统计注册用户的数据
	 * @param string $start 开始日期 Y-m-d
	 * @param string $end   结束日期 Y-m-d
	 * @return array array(日期时间 => 注册的人数);
	 */
	public static function userReg($start = '',$end = '')
	{
		$db = new IQuery('member');
		$db->fields = 'count(*) as yValue,`time`';
		return self::ParseCondition($db,'time',$start,$end);
	}

	/**
	 * @brief 统计平均消费数据,读取 已经支付的订单状态，总金额/总订单量
	 * @param string $start 开始日期 Y-m-d
	 * @param string $end   结束日期 Y-m-d
	 * @return array array(日期时间 => 消费金额);
	 */
	public static function spandAvg($start = '',$end = '')
	{
		$db = new IQuery('order');
		$db->fields = 'sum(order_amount)/count(*) as yValue,`create_time`';
		$db->where  = 'status = 5';
		return self::ParseCondition($db,'create_time',$start,$end);
	}

	/**
	 * @brief 统计销售额数据
	 * @param string $start 开始日期 Y-m-d
	 * @param string $end   结束日期 Y-m-d
	 * @return array key => 日期时间,value => 销售金额
	 */
	public static function spandAmount($start = '',$end = '')
	{
		$seller_id = IFilter::act(IReq::get('seller_id'),'int');

		$where     = 'status = 5';
		$where    .= $seller_id ? ' and seller_id = '.$seller_id : "";

		$db = new IQuery('order');
		$db->fields = 'sum(order_amount) as yValue,`create_time`';
		$db->where  = $where;
		return self::ParseCondition($db,'create_time',$start,$end);
	}

	/**
	 * @brief 获取商家销售额统计数据
	 * @param int $seller_id 商家ID
	 * @param string $start 开始日期 Y-m-d
	 * @param string $end   结束日期 Y-m-d
	 * @return array key => 日期时间,value => 销售金额
	 */
	public static function sellerAmount($seller_id,$start = '',$end = '')
	{
		$db = new IQuery('order');
		$db->fields = 'sum(order_amount) as yValue,`create_time`';
		$db->where  = "seller_id = {$seller_id} and pay_status = 1";
		return self::ParseCondition($db,'create_time',$start,$end);
	}

	/**
	 * @brief 商户的商品销售量
	 * @param int $seller_id 商家ID
	 * @return int
	 */
	public static function sellCountSeller($seller_id)
	{
		$sellerDB = new IModel('seller');
		$dataRow = $sellerDB->getObj("id = {$seller_id}",'sale');
		return isset($dataRow['sale']) ? intval($dataRow['sale']) : 0;
	}

	/**
	 * @brief 商户的评分
	 * @param int $seller_id 商家ID
	 * @return int
	 */
	public static function gradeSeller($seller_id)
	{
		$sellerDB = new IModel('seller');
		$dataRow = $sellerDB->getObj("id = {$seller_id}",'(grade/comments) as num');
		return isset($dataRow['num']) ? round($dataRow['num']) : 0;
	}

	/**
	 * @brief 统计用户待评论数据
	 * @return int
	 */
	public static function countUserWaitComment()
	{
		$commentDB = new IModel('comment');
		$data      = $commentDB->getObj('user_id = '.IWeb::$app->getController()->user['user_id'].' and status = 0','count(id) as num');
		return $data['num'];
	}

	/**
	 * @brief 统计用户待付款数据
	 * @return int
	 */
	public static function countUserWaitPay()
	{
		$orderDB = new IModel('order');
		$data    = $orderDB->getObj('user_id = '.IWeb::$app->getController()->user['user_id'].' and status = 1 and if_del = 0','count(id) as num');
		return $data['num'];
	}

	/**
	 * @brief 统计用户待确认数据
	 * @return int
	 */
	public static function countUserWaitCommit()
	{
		$orderDB = new IModel('order');
		$data    = $orderDB->getObj('user_id = '.IWeb::$app->getController()->user['user_id'].' and status = 2 and distribution_status = 1 and if_del = 0','count(id) as num');
		return $data['num'];
	}

	/**
	 * @brief 统计用户待退款的申请
	 * @return int
	 */
	public static function countUserWaitRefund()
	{
		$orderDB = new IModel('refundment_doc');
		$data    = $orderDB->getObj('user_id = '.IWeb::$app->getController()->user['user_id'].' and if_del = 0 and pay_status = 0','count(id) as num');
		return $data['num'];
	}

	//[优惠券]获取优惠券数量
	public static function getTicketCount($id)
	{
		$propObj   = new IModel('prop');
		$where     = '`condition` = "'.$id.'"';
		$propCount = $propObj->getObj($where,'count(*) as count');
		return $propCount['count'];
	}
}