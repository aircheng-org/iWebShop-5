<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file order.php
 * @brief 订单API
 * @author nswe
 * @date 2018/4/17 8:43:13
 * @version 5.1
 */
class APIOrder
{
	/**
	 * @brief 获取带有地域文字信息的订单数据
	 * @param array $idArray 订单ID数组
	 * @return array 订单列表数据
	 */
	public function getOrderListWithArea($idArray)
	{
		$idArray     = IFilter::act($idArray,'int');
		$orderObj    = new IModel('order');
		$areaIdArray = array();
		$where       = 'id in ('.join(',',$idArray).')';

		//如果不是管理员的权限，则强制增加seller_id的判断条件，防止越权查看订单信息
		if(!IWeb::$app->getController()->admin['admin_id'])
		{
			$where .= ' and seller_id = '.IWeb::$app->getController()->seller['seller_id'];
		}
		$orderList = $orderObj->query($where);

		if(!$orderList)
		{
			IError::show(403,"无查阅订单权限");
		}

		foreach($orderList as $key => $val)
		{
			$temp = area::name($val['province'],$val['city'],$val['area']);
			$orderList[$key]['province_str'] = $temp[$val['province']];
			$orderList[$key]['city_str']     = $temp[$val['city']];
			$orderList[$key]['area_str']     = $temp[$val['area']];
		}
		return $orderList;
	}

	//获取消费码信息
	public function getCodeInfo($code)
	{
	    $code = IFilter::act($code);
        $goodsCodeRelationDB = new IModel('order_code_relation');
        $data = $goodsCodeRelationDB->getObj("code = '{$code}'");

        if($data)
        {
            if($data['is_used'] == 1)
            {
                return array('success' => false,'msg' => '消费码已使用过！使用时间:'.$data['use_time']);
            }
            else
            {
                $orderDB = new IModel('order');
                $orderRow = $orderDB->getObj($data['order_id']);

                $orderGoodsDB = new IModel('order_goods');
                $orderGoodsRow= $orderGoodsDB->getObj('order_id = '.$data['order_id'].' and goods_id = '.$data['goods_id']);
                if($orderGoodsRow && $orderRow && in_array($orderRow['status'],[1,2,5]))
                {
                    $goodsArray = JSON::decode($orderGoodsRow['goods_array']);
                    return array('success' => true,'msg' => $goodsArray['name'].$goodsArray['value'],'data' => $data);
                }
                else
                {
                    return array('success' => false,'msg' => '信息不存在');
                }
            }
        }
        return array('success' => false,'msg' => '未找到此消费码');
	}

	//获取自提码信息
	public function getTakeselfInfo($code)
	{
	    $code = IFilter::act($code);
	    $db = new IModel('order');
	    $orderRow = $db->getObj('checkcode = "'.$code.'" and status = 2');
	    if($orderRow)
	    {
	        return ['success' => true,'msg' => '自提码正确','data' => $orderRow];
	    }
	    return ['success' => false,'msg' => '未找到此自提码'];
	}

	//获取批量合并付款订单
	public function getBatchOrder($tradeNo)
	{
		$orderDB = new IModel('order');
		$orderList = $orderDB->query('trade_no = "'.$tradeNo.'"','order_no','id desc');
		if(count($orderList) >= 2)
		{
			return $orderList;
		}
		else
		{
			return [];
		}
	}
}