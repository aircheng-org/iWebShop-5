<?php
/**
 * @copyright Copyright(c) 2014 aircheng.com
 * @file expresswaybill.php
 * @author nswe
 * @date 2018/6/9 8:39:07
 * @version 5.2
 */

/**
 * @class expresswaybill
 * @brief 快递单打印类库
 */
class expresswaybill
{
	//系统中打印机名称
	private $printSet = "";

	//要发货和打印的订单数据
	private $orderList = array();

	//物流公司信息
	private $expressRow = array();

	//发货地址信息
	private $shipRow = array();

	/**
	 * @brief 构造函数
	 * @param $orderId string or array 订单ID集合
	 * @param $expressId int 快递物流ID
	 * @param $shipId int 发货地址ID
	 */
	public function __construct($orderId,$expressId,$shipId)
	{
		$id = is_array($orderId) ? $orderId : array($orderId);
		$orderList = Api::run('getOrderListWithArea',$id);
		if(!$orderList)
		{
			return array('status' => 'fail','error' => '订单信息不存在');
		}

		$expressRow = Api::run('getExpresswaybillById',array('id' => $expressId));
		if(!$expressRow)
		{
			return array('status' => 'fail','error' => '物流公司信息不存在');
		}

		$shipRow = Api::run('getShipInfoRowById',array('id' => $shipId));
		if(!$shipRow)
		{
			return array('status' => 'fail','error' => '发货地址信息不存在');
		}
		$areaData = area::name($shipRow['province'],$shipRow['city'],$shipRow['area']);
 		$shipRow['province_str'] = $areaData[$shipRow['province']];
 		$shipRow['city_str']     = $areaData[$shipRow['city']];
 		$shipRow['area_str']     = $areaData[$shipRow['area']];

		$this->expressRow = $expressRow;
		$this->orderList  = $orderList;
		$this->shipRow    = $shipRow;
	}

	//运行快递下单接口
	public function run()
	{
		$orderResult = array();
		foreach($this->orderList as $key => $item)
		{
			//如果此订单已经是发货状态，并且发货单中存在快递模板信息，则可以去打印
			if($item['distribution_status'] > 0)
			{
				$deliveryData = Api::run('getDeliveryDocByOrderId',array('order_id' => $item['id']));
				foreach($deliveryData as $k => $v)
				{
					if($v['express_template'])
					{
						$orderResult[$item['id']] = array('status' => 'success','template' => $v['express_template']);
						break;
					}
				}

				if(isset($orderResult[$item['id']]))
				{
					continue;
				}
			}

			//统计订单总重量,kg
			$orderWeight = 0;
			$orderGoodsRelation = array();
			$orderGoodsList = Api::run('getOrderGoodsRowByOrderId',array('id' => $item['id']));
			foreach($orderGoodsList as $orderGoodsRow)
			{
				$orderGoodsRelation[] = $orderGoodsRow['id'];
				$orderWeight += $orderGoodsRow['goods_weight'] * $orderGoodsRow['goods_nums'];
			}
			$orderWeight = common::weight($orderWeight,'kg');

			//物流公司个性化数据
			$costomData = JSON::decode($this->expressRow['config']);

			//构造电子面单提交信息
			$postData = array(
				'freightCode' => $this->expressRow['freight_type'],//快递公司编码
				'orderNo'     => $item['order_no'],//订单号
				'weight'      => $orderWeight,//订单总重量

				//个别物流公司需要单独申请账号信息
				'CustomerName' => isset($costomData['CustomerName']) ? $costomData['CustomerName'] : "", //电子面单客户账号（与快递网点申请）
				'CustomerPwd'  => isset($costomData['CustomerPwd'])  ? $costomData['CustomerPwd']  : "", //电子面单密码
				'SendSite'     => isset($costomData['SendSite'])     ? $costomData['SendSite']     : "", //收件网点标识
				'MonthCode'    => isset($costomData['MonthCode'])    ? $costomData['MonthCode']    : "", //月结账号

				//发件人信息
				'senderName'         => $this->shipRow['ship_user_name'],
				'senderMobile'       => $this->shipRow['mobile'],
				'senderProvinceName' => $this->shipRow['province_str'],
				'senderCityName'     => $this->shipRow['city_str'],
				'senderExpAreaName'  => $this->shipRow['area_str'],
				'senderAddress'      => $this->shipRow['address'],

				//收件人信息
				'receiverName'         => $item['accept_name'],
				'receiverMobile'       => $item['mobile'],
				'receiverProvinceName' => $item['province_str'],
				'receiverCityName'     => $item['city_str'],
				'receiverExpAreaName'  => $item['area_str'],
				'receiverAddress'      => $item['address'],
			);
			$result = Api::cloud('expressbill_kdn',$postData);

			//返回成功
			if($result['status'] == 'success' && $result['result'])
			{
				$_POST = array(
					'express_template' => $result['result']['template'],
			 		'delivery_code'    => $result['result']['deliveryCode'],
			 		'freight_id'       => $this->expressRow['freight_id'],
				);
				$sendor = IWeb::$app->getController()->seller ? "seller" : "admin";
				$sendResult = order_class::sendDeliveryGoods($item['id'],$orderGoodsRelation,$sendor);
				$orderResult[$item['id']] = $sendResult === true ? array('status' => 'success','template' => $result['result']['template']) : array('status' => 'fail','error' => $sendResult);
			}
			else
			{
				$orderResult[$item['id']] = array('status' => 'fail','error' => $result['error']);
			}
		}
		return $orderResult;
	}

	//提醒信息
	public static function notice()
	{
		$result = '
			<p>注意：此功能基于付费打印服务，另外您所提交的订单数据必须真实有效，如果提交后发现信息错误或想撤销打印请务必及时联系相应的物流公司做更改或取消，如果被物流公司投诉我们将封闭您的账号，并作相应处罚。订单不能重复发货</p>
		';
		return $result;
	}

	/**
	 * @brief 打印快递单模板数据
	 * @param array $id 订单ID
	 * @param string $printSet 打印机设备名称
	 */
	public static function batPrint($id,$printSet)
	{
		$orderNO   = array();
		$orderDB   = new IModel('order');
		$orderData = $orderDB->query("id in (".join(',',$id).")","order_no");
		foreach($orderData as $item)
		{
			$orderNO[] = $item['order_no'];
		}
		Api::cloud("expressbill_print",['orderNo' => join('_',$orderNO),'printSet' => $printSet,'ip' => IClient::getIp()],0);
	}
}