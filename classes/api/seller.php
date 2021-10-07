<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file seller.php
 * @brief 商家API
 * @author chendeshan
 * @date 2014/10/12 13:59:44
 * @version 2.7
 */
class APISeller
{
	//商户信息
	public function getSellerInfo($id)
	{
		$id    = IFilter::act($id,'int');
		$query = new IModel('seller');
		$info  = $query->getObj("id=".$id." and is_del = 0 and is_lock = 0");
		return $info;
	}

	//获取店内分类的某个子分类或兄弟分类
	public function catTreeSeller($sellerId,$catId = 0)
	{
		if(!$sellerId)
		{
			return array();
		}

		$sellerId  = IFilter::act($sellerId,'int');
		$catId     = IFilter::act($catId,'int');
		$result    = array();
		$catDB     = new IModel('category_seller');
		$childList = $catDB->query("seller_id = '{$sellerId}' and parent_id = '{$catId}'","*","sort asc");
		if(!$childList && $catRow = $catDB->getObj("id = '{$catId}'"))
		{
			$childList = $catDB->query('seller_id = '.$sellerId.' and parent_id = '.$catRow['parent_id'],"*","sort asc");
		}
		return $childList;
	}

	//获取商家销售额
	public function getSellerSellAmount($seller_id,$startTime = '',$endTime = '')
	{
	    //查询订单付款金额
	    $orderDB = new IModel('order');
	    $where   = 'seller_id = '.$seller_id.' and pay_status = 1 and pay_type > 0';
	    if($startTime && $endTime)
	    {
	        $where .= " and pay_time between '".$startTime."' and '".$endTime."' ";
	    }
	    $orderRow= $orderDB->getObj($where,'sum(`order_amount`) as amount');

        //查询退款单金额
	    $refundDB = new IModel('refundment_doc');
	    $where = 'seller_id = '.$seller_id.' and pay_status = 2';
	    if($startTime && $endTime)
	    {
	        $where .= " and dispose_time between '".$startTime."' and '".$endTime."' ";
	    }
        $refundRow= $refundDB->getObj($where,'sum(`amount`) as amount');

        //收款金额减掉退款金额
        return $orderRow['amount'] - $refundRow['amount'];
	}

	//获取商家可以提现的订单数量
	public function getSellerOrderNumCheckout($seller_id)
	{
	    //查询订单付款金额
	    $orderDB = new IModel('order');
	    $where   = 'seller_id = '.$seller_id.' and pay_status = 1 and pay_type > 0 and is_checkout = 0';

        //限制用户必须收货[X]天后才会有统计数据
        $low_bill = IWeb::$app->getController()->_siteConfig->low_bill;
        if($low_bill)
        {
            $where .= ' and TO_DAYS(NOW()) - TO_DAYS(completion_time) >= '.$low_bill;
        }

	    $orderRow = $orderDB->getObj($where,'count(*) as nums');
	    return intval($orderRow['nums']);
	}
}