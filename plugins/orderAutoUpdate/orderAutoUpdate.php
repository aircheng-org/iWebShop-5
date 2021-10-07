<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file orderAutoUpdate.php
 * @brief 订单自动更新
 * @notice 未付款取消，发货后自动确认收货
 * @author nswe
 * @date 2016/2/28 10:57:26
 * @version 4.4
 */
class orderAutoUpdate extends pluginBase
{
	//注册事件
	public function reg()
	{
		plugin::reg("onCreateAction@order@order_list",$this,"orderUpdate");
		plugin::reg("onCreateAction@ucenter@order",$this,"orderUpdate");
	}

	//订单自动更新
	public function orderUpdate()
	{
		//获取配置信息
		$configData = $this->config();

		//按照分钟计算
		$order_cancel_time = (isset($configData['order_cancel_time']) && $configData['order_cancel_time']) ? intval($configData['order_cancel_time']) : 7*24*60;
		$order_finish_time = (isset($configData['order_finish_time']) && $configData['order_finish_time']) ? intval($configData['order_finish_time']) : 20*24*60;
		$order_del_time    = (isset($configData['order_del_time'])    && $configData['order_del_time'])    ? intval($configData['order_del_time'])    : 7*24*60;

        $orderModel = new IModel('order');
        //删除订单
        if($order_del_time > 0)
        {
            $result = $orderModel->del(" status = 1 and if_del = 0 and pay_type > 0 and timestampdiff(minute,create_time,NOW()) >= {$order_del_time} ");
        }
		$orderCancelData = $order_cancel_time > 0 ? $orderModel->query(" status = 1 and if_del = 0 and pay_type > 0 and timestampdiff(minute,create_time,NOW()) >= {$order_cancel_time} ","id,order_no,4 as type_data") : array();
		$orderCreateData = $order_finish_time > 0 ? $orderModel->query(" status in(1,2) and if_del = 0 and distribution_status = 1 and timestampdiff(minute,send_time,NOW()) >= {$order_finish_time} and takeself = 0 and goods_type = 'default'","id,order_no,5 as type_data") : array();

		$resultData = array_merge($orderCreateData,$orderCancelData);
		if($resultData)
		{
			foreach($resultData as $key => $val)
			{
				$type     = $val['type_data'];
				$order_id = $val['id'];
				$order_no = $val['order_no'];

				//oerder表的对象
				$tb_order = new IModel('order');
				$updateCols = array('status' => $type);
				if($type == 5)
				{
					$updateCols['completion_time'] = ITime::getDateTime();
				}
				$tb_order->setData($updateCols);
				$tb_order->update('id='.$order_id);

				//生成订单日志
				$tb_order_log = new IModel('order_log');

				//订单自动完成
				if($type=='5')
				{
					$action = '完成';
					$note   = '订单【'.$order_no.'】完成成功';

					//完成订单并且进行支付
					Order_Class::updateOrderStatus($order_no);

					//增加用户评论商品机会
					Order_Class::addGoodsCommentChange($order_id);

					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为完成",'订单号：'.$order_no));
				}
				//订单自动作废
				else
				{
					$action = '作废';
					$note   = '订单【'.$order_no.'】作废成功';

					//订单重置取消
					Order_class::resetOrderProp($order_id);

					$logObj = new log('db');
					$logObj->write('operation',array("系统自动","订单更新为作废",'订单号：'.$order_no));
				}

				$tb_order_log->setData(array(
					'order_id' => $order_id,
					'user'     => "系统自动",
					'action'   => $action,
					'result'   => '成功',
					'note'     => $note,
					'addtime'  => ITime::getDateTime(),
				));
				$tb_order_log->add();
			}
		}
	}

	/**
	 * @brief 默认插件参数信息，写入到plugin表config_param字段
	 * @return array
	 */
	public static function configName()
	{
		return array(
			"order_finish_time" => array("name" => "已发货订单X(分钟)自动完成","type" => "text","pattern" => "int"),
			"order_cancel_time" => array("name" => "未付款订单X(分钟)自动取消","type" => "text","pattern" => "int"),
			"order_del_time"    => array("name" => "未付款订单X(分钟)自动删除","type" => "text","pattern" => "int"),
		);
	}

	/**
	 * @brief 插件名字
	 * @return string
	 */
	public static function name()
	{
		return "订单自动化";
	}

	/**
	 * @brief 插件描述
	 * @return string
	 */
	public static function description()
	{
		return "1，已经发货的订单会在X分钟后自动完成；2，未付款的订单会在X分钟后自动取消；3，未付款的订单会在X分钟后自动删除";
	}
}