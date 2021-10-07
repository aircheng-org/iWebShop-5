<?php
/**
 * @copyright (c) 2015 aircheng.com
 * @file _hsms.php
 * @brief 短信通知和内容模板插件
 * @author nswe
 * @date 2018/11/30 7:54:21
 * @version 5.3
 */

 /**
 * @class _hsms
 * @brief 短信通知和内容模板插件
 */
class _hsms extends pluginBase
{
    //短信模板
    private static $msgTemplate = [
        "notify" => "尊敬的用户，您需要购买的 <{goodsName}> 现已全面到货，机不可失，从速购买！{url}",
        "findPassword" => "您的验证码为:{mobile_code},请注意保管!",
        "checkCode" => "您的验证码为:{mobile_code},请注意保管!",
    ];

	/**
	 * @brief 系统内置短信发送，适用于: _hsms::abc($mobile,$content);
	 * @param string $funcname 短信编号
	 * @param array $arguments 手机号和替换内容变量
	 */
	public static function __callStatic($funcname, $arguments)
	{
	    if(isset(self::$msgTemplate[$funcname]) && count($arguments) >= 1)
	    {
	        $content = self::$msgTemplate[$funcname];

	        //内容替换
	        if(isset($arguments[1]))
	        {
	            $content = strtr($content,$arguments[1]);
	        }
	        return hsms::send($arguments[0],$content,0);
	    }
		return false;
	}

    //获取用户手机号码
    public function getMobileByUser($user_id)
    {
        $db = new IModel('member');
        $row= $db->getObj('user_id = '.intval($user_id));
        if($row && $row['mobile'])
        {
            return $row['mobile'];
        }
        return '';
    }

    //获取商家（seller_id=0为管理员）手机号码
    public function getMobileBySeller($seller_id)
    {
        if($seller_id > 0)
        {
            $db     = new IModel('seller');
            $row    = $db->getObj($seller_id);
            $mobile = $row && $row['mobile'] ? $row['mobile'] : "";
        }
        else
        {
			$config = new Config('site_config');
			$mobile = $config->mobile ? $config->mobile : "";
        }
        return $mobile;
    }

    //获取订单中的用户手机
    public function getMobileByOrder($order_id)
    {
        $db = new IModel('order');
        $row= $db->getObj(intval($order_id));
        if($row && $row['mobile'])
        {
            return $row['mobile'];
        }
        return '';
    }

    //事件注册
    public function reg()
    {
        //商家状态更新通知
        plugin::reg('updateSellerStatus',$this,"updateSellerStatus");

        //商家注册成功
        plugin::reg('sellerRegFinish',$this,"sellerRegFinish");

        //订单付款
        plugin::reg("orderPayFinish",$this,"orderPayFinish");

        //发货实体出库
		plugin::reg("orderSendDeliveryFinish",$this,"orderSendDeliveryFinish");

        //退款申请
        plugin::reg("refundsApplyFinish",$this,"refundsApplyFinish");

        //退款同意
        plugin::reg("refundFinish",$this,"refundFinish");

        //退款拒绝
        plugin::reg("refundDocUpdate",$this,"refundDocUpdate");

        //换货申请
        plugin::reg("exchangeApplyFinish",$this,"exchangeApplyFinish");

        //换货同意或者拒绝
        plugin::reg("exchangeDocUpdate",$this,"exchangeDocUpdate");

        //维修申请
        plugin::reg("fixApplyFinish",$this,"fixApplyFinish");

        //维修同意或者拒绝
        plugin::reg("fixDocUpdate",$this,"fixDocUpdate");

        //核销服务消费码
        plugin::reg("checkOrderCodeFinish",$this,"checkOrderCodeFinish");

        //提现申请
        plugin::reg("withdrawApplyFinish",$this,"withdrawApplyFinish");

        //提现结果更新
        plugin::reg("withdrawStatusUpdate",$this,"withdrawStatusUpdate");

        //在线充值
        plugin::reg("onlineRechargeFinish",$this,"onlineRechargeFinish");
    }

    /*******************************以下为事件处理*************************************/
    /**
     * @brief 商家状态更新[商家接受]
     * @param int $seller_id 商家ID
     */
    public function updateSellerStatus($seller_id)
    {
        $sellerDB  = new IModel('seller');
        $sellerRow = $sellerDB->getObj($seller_id);

        //锁定
        if($sellerRow['is_lock'] == 1)
        {
            $remark = "您的商家账号被封，请尽快联系平台管理员查明原因";
        }
        //正常
        else
        {
            $remark = "恭喜您审核通过成为我们的商家，请严格遵守平台规定";
        }

        $mobile = $this->getMobileBySeller($seller_id);
        $data = [
            "您的商家账号状态更新",
            $sellerRow['true_name'],
            $remark,
        ];
        Hsms::send($mobile,join(",",$data),0);
    }

    /**
     * @brief 商家注册[管理员接受]
     * @param int $seller_id 商家ID
     */
    public function sellerRegFinish($seller_id)
    {
        $sellerDB  = new IModel('seller');
        $sellerRow = $sellerDB->getObj($seller_id);
        if($sellerRow)
        {
            $mobile = $this->getMobileBySeller(0);
            $data   = [
                "您有新的商家入驻申请",
                "商家名称：".$sellerRow['true_name'],
                "联系方式：".$sellerRow['mobile'],
                $sellerRow['phone'],
                "请尽快登录管理员后台进行相关资质审核",
            ];
            Hsms::send($mobile,join(",",$data),0);
        }
    }

    /**
     * @brief【模板消息】付款完成[用户、商家接受]
     * @param array $orderRow 订单信息
     */
	public function orderPayFinish($orderRow)
	{
	    //自提码发送
	    if($orderRow['takeself'] > 0)
	    {
	        $takeselfDB = new IModel('takeself');
	        $takeselfRow = $takeselfDB->getObj($orderRow['takeself']);
	        if($takeselfRow)
	        {
	            $mobile = $this->getMobileByOrder($orderRow['id']);
                $data   = [
                    "购买成功！请到自提点领取",
                    "订单号：".$orderRow['order_no'],
                    "自提点：".$takeselfRow['name'],
                    "地址：".$takeselfRow['address'],
                    "电话：".$takeselfRow['mobile'],
                    $takeselfRow['phone'],
                    "妥善保管自提码：".$orderRow['checkcode'],
                ];
                Hsms::send($mobile,join(",",$data),0);
	        }
	    }
	    //服务类型消费码
	    else if($orderRow['goods_type'] == 'code')
	    {
	        //获取消费码
	        $codeArray = array();
	        $goodsCodeRelationObj = new IModel('order_code_relation');
	        $codeList = $goodsCodeRelationObj->query('order_id = '.$orderRow['id']);
	        foreach($codeList as $codeItem)
	        {
	            $codeArray[] = $codeItem['code'];
	        }

	        //获取商品信息
	        $goodsRow = Api::run('getGoodsInfo',array('id' => $codeItem['goods_id']));

	        $mobile = $this->getMobileByOrder($orderRow['id']);
	        $data   = [
	            "购买成功！凭消费码到店服务",
                $goodsRow['name'],
                "金额：".$orderRow['order_amount'],
                "消费码：".join(",",$codeArray),
                "妥善保管不要泄露",
	        ];
            Hsms::send($mobile,join(",",$data),0);
	    }
	    //实体类型
	    else if($orderRow['goods_type'] == 'default')
	    {
    	    $goodsInfo  = array();
    	    $goodsList  = Api::run('getOrderGoodsRowByOrderId',array('id' => $orderRow['id']));
    	    foreach($goodsList as $item)
    	    {
    	        $goodsTemp = JSON::decode($item['goods_array']);
    	        if($goodsTemp['value'])
    	        {
    	            $goodsInfo[] = $goodsTemp['name']."(".$goodsTemp['value'].")";
    	        }
    	        else
    	        {
    	            $goodsInfo[] = $goodsTemp['name'];
    	        }
    	    }
    	    $goodsInfo = join(",",$goodsInfo);

    	    //给用户发消息
    	    $mobile = $this->getMobileByOrder($orderRow['id']);
    	    $data   = [
    	        "订单付款成功",
                "金额：".$orderRow['order_amount'],
                "商品信息：".$goodsInfo,
                "我们会尽快处理，请稍后",
    	    ];
    	    Hsms::send($mobile,join(",",$data),0);

    	    //给商家发消息
    	    $mobile = $this->getMobileBySeller($orderRow['seller_id']);
    	    $data   = [
    	        "您有新的订单",
                "金额：".$orderRow['order_amount'],
                "商品信息：".$goodsInfo,
                "尽快进行订单处理",
    	    ];
    	    Hsms::send($mobile,join(",",$data),0);
	    }
	}

    /**
     * @brief 【模板消息】退款申请[用户、商家接受]
     * @param int $id 申请退款单ID
     */
	public function refundsApplyFinish($id)
	{
	    $refundDB = new IModel('refundment_doc');
	    $refundRow= $refundDB->getObj($id);

        $orderAmount= 0;
	    $goodsInfo  = array();
	    $goodsList  = Api::run('getOrderGoodsRowById',array('id' => $refundRow['order_goods_id']));
	    foreach($goodsList as $item)
	    {
	        $goodsSpec    = JSON::decode($item['goods_array']);
	        $orderAmount += $item['real_price']*$item['goods_nums'];
	        if($goodsSpec['value'])
	        {
	            $goodsInfo[]  = $goodsSpec['name']."(".$goodsSpec['value'].")";
	        }
	        else
	        {
	            $goodsInfo[]  = $goodsSpec['name'];
	        }
	    }
	    $orderNo = $refundRow['order_no'];
	    $goodsInfo = join(",",$goodsInfo);

	    //给用户发消息
	    $mobile = $this->getMobileByOrder($refundRow['order_id']);
	    $data   = [
	        "退款申请",
	        "金额：".$orderAmount,
	        "退款商品：".$goodsInfo,
	        "订单号：".$orderNo,
	        "我们将尽快处理您的退款申请",
	    ];
	    Hsms::send($mobile,join(",",$data),0);

	    //给商家发消息
	    $mobile = $this->getMobileBySeller($refundRow['seller_id']);
	    $data   = [
	        "您有新的退款申请",
	        "金额：".$orderAmount,
	        "退款商品：".$goodsInfo,
	        "订单号：".$orderNo,
	        "请尽快处理退款申请",
	    ];
	    Hsms::send($mobile,join(",",$data),0);
	}

    /**
     * @brief 【模板消息】退款完成[用户、商家接受]
     * @param int $id 申请退款单ID
     */
	public function refundFinish($id)
	{
	    $refundDB = new IModel('refundment_doc');
	    $refundRow= $refundDB->getObj($id);

	    $orderAmount = $refundRow['amount'];
        $goodsInfo   = array();
	    $goodsList   = Api::run('getOrderGoodsRowById',array('id' => $refundRow['order_goods_id']));
	    foreach($goodsList as $item)
	    {
	        $goodsSpec = JSON::decode($item['goods_array']);
	        if($goodsSpec['value'])
	        {
	            $goodsInfo[]  = $goodsSpec['name']."(".$goodsSpec['value'].")";
	        }
	        else
	        {
	            $goodsInfo[]  = $goodsSpec['name'];
	        }
	    }
	    $orderNo = $refundRow['order_no'];
	    $goodsInfo = join(",",$goodsInfo);
	    $way = "退款方式：".order_class::refundWay($refundRow['way']);

	    //给用户发消息
	    $mobile = $this->getMobileByOrder($refundRow['order_id']);
	    $data   = [
	        "退款成功",
	        "金额：".$orderAmount,
	        "退款商品：".$goodsInfo,
	        "订单号：".$orderNo,
	        $way,
	    ];
	    Hsms::send($mobile,join(",",$data),0);

        //给商户发消息
        $mobile = $this->getMobileBySeller($refundRow['seller_id']);
	    $data   = [
	        "退款成功",
	        "金额：".$orderAmount,
	        "退款商品：".$goodsInfo,
	        "订单号：".$orderNo,
	        $way,
	    ];
	    Hsms::send($mobile,join(",",$data),0);
	}

    /**
     * @brief 【模板消息】发货通知[用户接受]
     * @param int $deliveryId 发货单ID
     */
	public function orderSendDeliveryFinish($deliveryId)
	{
	    $deliveryDB = new IQuery('delivery_doc as dd');
	    $deliveryDB->join = 'left join freight_company as fc on dd.freight_id = fc.id';
	    $deliveryDB->where= 'dd.id = '.$deliveryId;
	    $deliveryRow = $deliveryDB->find();
	    $deliveryRow = current($deliveryRow);

	    //给用户发消息
        $mobile = $this->getMobileByOrder($deliveryRow['order_id']);
	    $data   = [
	        "商品已经发货",
	        "物流公司：".$deliveryRow['freight_name'],
	        "快递单号：".$deliveryRow['delivery_code'],
	        "请您耐心等待",
	    ];
	    Hsms::send($mobile,join(",",$data),0);
	}

    /**
     * @brief 退款被拒绝[用户接受]
     * @param int $id 申请退款单ID
     */
    public function refundDocUpdate($id)
    {
	    $refundDB = new IModel('refundment_doc');
	    $row = $refundDB->getObj($id);

	    if($row)
	    {
	        switch($row['pay_status'])
	        {
	            //拒绝
	            case 1:
	            {
                    $mobile = $this->getMobileByOrder($row['order_id']);
                    $data   = [
                        "您退款申请被驳回",
                        "订单号：".$row['order_no'],
                        "处理时间：".$row['dispose_time'],
                        "被拒原因：".$row['dispose_idea'],
                        "如果仍存在问题请咨询客服",
                    ];
                    Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //买家返还物流
	            case 3:
	            {
	                $mobile = $this->getMobileByOrder($row['order_id']);
    	            $data = [
    	                "退款申请需要返还",
    	                "订单号：".$row['order_no'],
    	                "处理状态：".Order_Class::refundmentText($row['pay_status']),
    	                "需要您把商品返还给商家，并且把相关物流信息更新到个人中心的售后服务里面",
    	            ];
    	            Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //卖家重发物流
	            case 4:
	            {
	                $mobile = $this->getMobileBySeller($row['seller_id']);
    	            $data = [
    	                "换货申请返还物流更新",
    	                "订单号：".$row['order_no'],
    	                "处理状态：".Order_Class::refundmentText($row['pay_status']),
    	                "买家已经更新了物流信息，注意查收后进行退款",
    	            ];
    	            Hsms::send($mobile,join(",",$data),0);
	            }
	            break;
	        }
	    }
    }

    /**
     * @brief 消费码核销成功
     * @param string $code 消费码
     */
    public function checkOrderCodeFinish($code)
    {
        $db = new IModel('order_code_relation');
        $codeRow = $db->getObj('code = "'.$code.'"');
        if($codeRow && $codeRow['is_used'] == 1)
        {
            $goodsRow = Api::run('getGoodsInfo',array('id' => $codeRow['goods_id']));
            $mobile = $this->getMobileByOrder($codeRow['order_id']);
            $data   = [
                "消费码使用成功",
                $goodsRow['name'],
                "您的消费码：".$code,
                "消费成功，欢迎下次光临",
            ];
            Hsms::send($mobile,join(",",$data),0);
        }
    }

    /**
     * @brief 提现申请[用户，管理员接受]
     * @param int $id 提现ID
     */
    public function withdrawApplyFinish($id)
    {
        $db = new IModel('withdraw');
        $row= $db->getObj($id);

        //用户发送
        $mobile = $this->getMobileByUser($row['user_id']);
        $data   = [
            "提现申请提交成功",
            "金额：".$row['amount'],
            "请您耐心等待审核结果",
        ];
        Hsms::send($mobile,join(",",$data),0);

        //管理员发送
        $mobile = $this->getMobileBySeller(0);
        $data   = [
            "您有新的提现申请需要处理",
            "金额：".$row['amount'],
            "申请内容：".$row['note'],
        ];
        Hsms::send($mobile,join(",",$data),0);
    }

    /**
     * @brief 提现结果更新[用户接受]
     * @param int $id 提现ID
     */
    public function withdrawStatusUpdate($id)
    {
        $db = new IModel('withdraw');
        $row= $db->getObj($id);

        //拒绝
        if($row['status'] == '-1')
        {
            $data = [
                "提现审核被拒",
                "金额：".$row['amount'],
                "您的提现申请被拒绝，如有问题请联系网站管理员",
            ];
        }
        //同意
        else if($row['status'] == '2')
        {
            $data = [
                "提现审核成功",
                "金额：".$row['amount'],
                "我们会尽快把钱转到您指定的账户中，请注意查收",
            ];
        }
        $mobile = $this->getMobileByUser($row['user_id']);
        Hsms::send($mobile,join(",",$data),0);
    }

    /**
     * @brief 在线充值成功
     * @param string $recharge_no 充值订单号
     */
    public function onlineRechargeFinish($recharge_no)
    {
		$rechargeObj = new IModel('online_recharge');
		$rechargeRow = $rechargeObj->getObj('recharge_no = "'.$recharge_no.'"');
		if($rechargeRow && $rechargeRow['status'] == 1)
		{
            $mobile = $this->getMobileByUser($rechargeRow['user_id']);
            $data   = [
                "在线充值成功",
                "金额：".$rechargeRow['account'],
                "充值方式：".$rechargeRow['payment_name'],
                "请登录您的个人中心查看预存款",
            ];
            Hsms::send($mobile,join(",",$data),0);
		}
    }

    //换货申请
    public function exchangeApplyFinish($id)
    {
	    $db = new IModel('exchange_doc');
	    $row= $db->getObj($id);

	    //给用户发消息
	    $data = [
	        "换货申请",
	        "订单号：".$row['order_no'],
	        "我们将尽快处理您的换货申请",
	    ];
        $mobile = $this->getMobileByOrder($row['order_id']);
        Hsms::send($mobile,join(",",$data),0);

	    //给商家发消息
	    $data = [
	        "您有新的换货申请",
	        "订单号：".$row['order_no'],
	        "请尽快处理申请",
	    ];
        $mobile = $this->getMobileBySeller($row['seller_id']);
        Hsms::send($mobile,join(",",$data),0);
    }

    //换货更新同意或者拒绝
    public function exchangeDocUpdate($id)
    {
	    $db = new IModel('exchange_doc');
	    $row= $db->getObj($id);

	    if($row)
	    {
	        switch($row['status'])
	        {
	            //拒绝
	            case 1:
	            {
    	            $data = [
    	                "换货申请被拒绝",
    	                "订单号：".$row['order_no'],
    	                $row['dispose_idea'],
    	            ];
                    $mobile = $this->getMobileByOrder($row['order_id']);
                    Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //成功
	            case 2:
	            {
    	            $data = [
    	                "换货申请已通过",
    	                "订单号：".$row['order_no'],
    	                $row['dispose_idea'],
    	            ];
                    $mobile = $this->getMobileByOrder($row['order_id']);
                    Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //买家返还物流
	            case 3:
	            {
    	            $data = [
    	                "换货申请需要返还",
    	                "订单号：".$row['order_no'],
    	                "需要您把商品返还给商家，并且把相关物流信息更新到个人中心的售后服务里面",
    	            ];
                    $mobile = $this->getMobileByOrder($row['order_id']);
                    Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //卖家重发物流
	            case 4:
	            {
    	            $data = [
    	                "换货申请返还物流更新",
    	                "订单号：".$row['order_no'],
    	                "买家已经更新了物流信息，注意查收后进行商品重发",
    	            ];
                    $mobile = $this->getMobileBySeller($row['seller_id']);
                    Hsms::send($mobile,join(",",$data),0);
	            }
	            break;
	        }
	    }
    }

    //维修申请
    public function fixApplyFinish($id)
    {
	    $db = new IModel('fix_doc');
	    $row= $db->getObj($id);

	    //给用户发消息
	    $data = [
	        "维修申请",
	        "订单号：".$row['order_no'],
	        "我们将尽快处理您的维修申请",
	    ];
        $mobile = $this->getMobileByOrder($row['order_id']);
        Hsms::send($mobile,join(",",$data),0);

	    //给商家发消息
	    $data = [
	        "您有新的维修申请",
	        "订单号：".$row['order_no'],
	        "请尽快处理申请",
	    ];
        $mobile = $this->getMobileBySeller($row['seller_id']);
        Hsms::send($mobile,join(",",$data),0);
    }

    //维修更新同意或者拒绝
    public function fixDocUpdate($id)
    {
	    $db = new IModel('fix_doc');
	    $row= $db->getObj($id);

	    if($row)
	    {
	        switch($row['status'])
	        {
	            //拒绝
	            case 1:
	            {
    	            $data = [
    	                "维修申请被拒绝",
    	                "订单号：".$row['order_no'],
    	                "处理状态：".Order_Class::refundmentText($row['status']),
    	                $row['dispose_idea'],
    	            ];
    	            $mobile = $this->getMobileByOrder($row['order_id']);
    	            Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //成功
	            case 2:
	            {
    	            $data = [
    	                "维修申请已通过",
    	                "订单号：".$row['order_no'],
    	                "处理状态：".Order_Class::refundmentText($row['status']),
    	                $row['dispose_idea'],
    	            ];
    	            $mobile = $this->getMobileByOrder($row['order_id']);
    	            Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //买家返还物流
	            case 3:
	            {
    	            $data = [
    	                "维修申请需要返还",
    	                "订单号：".$row['order_no'],
    	                "处理状态：".Order_Class::refundmentText($row['status']),
    	                "需要您把商品返还给商家，并且把相关物流信息更新到个人中心的售后服务里面",
    	            ];
    	            $mobile = $this->getMobileByOrder($row['order_id']);
    	            Hsms::send($mobile,join(",",$data),0);
	            }
	            break;

                //卖家重发物流
	            case 4:
	            {
    	            $data = [
    	                "维修申请返还物流更新",
    	                "订单号：".$row['order_no'],
    	                "处理状态：".Order_Class::refundmentText($row['status']),
    	                "买家已经更新了物流信息，注意查收后进行商品重发",
    	            ];
    	            $mobile = $this->getMobileBySeller($row['seller_id']);
    	            Hsms::send($mobile,join(",",$data),0);
	            }
	            break;
	        }
	    }
    }
}