<?php
/**
 * @copyright (c) 2018 aircheng.com
 * @file orderTip.php
 * @brief 订单提醒插件
 * @author cuijun
 * @date 2018/4/6 11:26:32
 * @version 5.1
 */
class orderTip extends pluginBase
{
    private $seller_id = 0; //商家id
    private $loopTime  = 15000;//轮训时间间隔(毫秒)

    public static function name()
    {
        return "新订单自动提醒";
    }

    public static function description()
    {
        return "登录后有新的待处理订单的时候,右下角弹出订单信息并且有声音提示";
    }

    public function reg()
    {
    	//视图监听是否存在新订单提醒
        plugin::reg("onFinishView", $this, 'tipNewOrderShow');

		//获取待处理订单数据
        plugin::reg("onBeforeCreateAction@plugins@getNewOrder", function () {
            self::controller()->getNewOrder = function () {
                $this->getNewOrder();
            };
        });
        plugin::reg("onBeforeCreateAction@seller@getNewOrder", function () {
            self::controller()->getNewOrder = function () {
                $this->getNewOrder();
            };
        });
    }

    /**
     * 消息提醒
     */
    public function tipNewOrderShow()
    {
    	//管理员登录
        if (IWeb::$app->getController()->admin['admin_id'] != null)
        {
            $this->seller_id = 0;
            $ajaxUrl = IUrl::creatUrl('/plugins/getNewOrder');
        }
        //供应商登录
        elseif (IWeb::$app->getController()->seller['seller_id'] != null)
        {
            $this->seller_id = IWeb::$app->getController()->seller['seller_id'];
            $ajaxUrl = IUrl::creatUrl('/seller/getNewOrder');
        }
        else
        {
            return;
        }

        $nowTime = ITime::getDateTime();
        $randerData = array(
			"ajaxUrl"   => $ajaxUrl,
			'nowTime'   => $nowTime,
			'seller_id' => $this->seller_id,
			'loopTime'  => $this->loopTime,
			'mp3'       => IUrl::creatUrl().'plugins/orderTip/music/order_tip.mp3',
        );
        $this->view('orderTip', $randerData);
    }

    /**
     * 获取最新订单
     */
    public function getNewOrder()
    {
        $seller_id = IFilter::act(IReq::get('seller_id'), 'int');
        $time      = IFilter::act(IReq::get('nowTime'));
        $orderDB   = new IModel('order');
        $where     = "seller_id = ".$seller_id." and pay_time >= '".$time."'";
        $orderRow  = $orderDB->getObj($where, 'id,order_no,order_amount,pay_status,distribution_status,status,pay_type', 'id desc');
        if($orderRow)
        {
			//管理员登录
	        if (IWeb::$app->getController()->admin['admin_id'] != null)
	        {
	            $orderShowUrl = IUrl::creatUrl('/order/order_show/id/' . $orderRow['id']);
	        }
	        //供应商登录
	        elseif(IWeb::$app->getController()->seller['seller_id'] != null)
	        {
	            $orderShowUrl = IUrl::creatUrl('/seller/order_show/id/' . $orderRow['id']);
	        }

            $os    = array(0 => '货到付款', 1 => '已支付');
            $text  = "订单号：<a href='" . $orderShowUrl . "'>{$orderRow['order_no']}</a>" . "<br />";
            $text .= "订单金额：{$orderRow['order_amount']}" . "<br />";
            $text .= "订单状态：{$os[$orderRow['pay_status']]}" . "<br />";
            die(JSON::encode(array('text' => $text, 'jumpUrl' => $orderShowUrl)));
        }
    }
}