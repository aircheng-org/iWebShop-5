<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file _initData.php
 * @brief 初始化数据
 * @author nswe
 * @date 2016/6/8 10:08:23
 * @version 4.5
 */
class _initData extends pluginBase
{
	public function reg()
	{
	    IInterceptor::reg("_initData@onControllerRedirect");

		//防止回倒按钮引起的重复提交订单
		plugin::reg("onFinishView@simple@cart3",function()
		{
			$backUrl = IUrl::creatUrl('/ucenter/order');
echo <<< EOF
<script type="text/javascript">
if(history.replaceState)
{
	history.replaceState(null, null, "{$backUrl}");
}
</script>
EOF;
		});

		//促销规则
		plugin::reg("onFinishView@simple@cart",function()
		{
			$promotionData = self::controller()->promotion;
			if(!$promotionData)
			{
				return;
			}

			$promotionData = JSON::encode($promotionData);
echo <<< EOF
<script type="text/javascript">
$(function()
{
	var promotionData = {$promotionData};
	if(promotionData)
	{
		for(var i in promotionData)
		{
			$('#cart_prompt_box').append( template.render('promotionTemplate',{"item":promotionData[i]}) );
		}
		$('#cart_prompt').show();
	}
});
</script>
EOF;
		});

		plugin::reg("onCreateController",$this,"webSiteConfig");
		plugin::reg("onFinishView",$this,"jsGlobal");
		plugin::reg("onBeforeCreateAction@block@orderCheck",function(){
			self::controller()->orderCheck = function()
			{
				$orderNo = IFilter::act(IReq::get('order_no'));

				//充值方式
				if(stripos($orderNo,'recharge') !== false)
				{
					$tradenoArray = explode('recharge',$orderNo);
					$recharge_no  = isset($tradenoArray[1]) ? $tradenoArray[1] : 0;
					$rechargeObj = new IModel('online_recharge');
					$rechargeRow = $rechargeObj->getObj('recharge_no = "'.$recharge_no.'"','status');
					if($rechargeRow && isset($rechargeRow['status']) && $rechargeRow['status'] == 1)
					{
						die(JSON::encode(array('result' => 1)));
					}
				}
				else
				{
					$orderDB = new IModel('order');
					$orderRow= $orderDB->getObj('order_no = "'.$orderNo.'"','pay_status');
					if($orderRow && isset($orderRow['pay_status']) && $orderRow['pay_status'] == 1)
					{
						die(JSON::encode(array('result' => 1)));
					}
				}
				die(JSON::encode(array('result' => 0)));
			};
		});

		//登录后台首页自动计算商家待结算的货款
		plugin::reg("onBeforeCreateAction@system@default,onBeforeCreateAction@market@order_goods_merge,onBeforeCreateAction@seller@index",$this,"countServicefeeAmount");
	}

	//计算商户手续费
	public function countServicefeeAmount()
	{
		$orderDB = new IModel('order');
		$orderList = $orderDB->query('status in (5,7) and servicefee_amount = 0 and pay_status = 1 and pay_type > 0 and is_checkout = 0 and seller_id > 0 and TO_DAYS(NOW()) - TO_DAYS(completion_time) >= '.intval(IWeb::$app->getController()->_siteConfig->low_bill));
		CountSum::countSellerOrderServicefee($orderList);
	}

	//初始化网站配置数据
	public function webSiteConfig()
	{
		$configObj = new Config("site_config");
		self::controller()->_siteConfig = $configObj;
	}

	//初始化js全局变量
	public function jsGlobal()
	{
		//全局JS提示信息
		$_msg = IReq::get('_msg') ? IFilter::act(IReq::get('_msg')) : "";
		if($_msg)
		{
			//默认语言包
			$msgArray = array(
				"success" => "操作成功",
				"fail"    => "操作失败",
			);
			$_msg = isset($msgArray[$_msg]) ? $msgArray[$_msg] : $_msg;
			if($_msg)
			{
echo <<< EOF
<script type="text/javascript">
alert("{$_msg}");
</script>
EOF;
			}
		}

		//全局JS函数和变量
		$url       = IUrl::creatUrl('_controller_/_action_/_paramKey_/_paramVal_');
		$themePath = IWeb::$app->getController()->getWebViewPath();
		$skinPath  = IWeb::$app->getController()->getWebSkinPath();
		$webroot   = IUrl::creatUrl();

echo <<< EOF
<script type="text/javascript">
_webUrl = "$url";_themePath = "$themePath";_skinPath = "$skinPath";_webRoot = "$webroot";

if($('[data-oembed-url]') && $('[data-oembed-url]').length > 0)
{
	$('[data-oembed-url]').each(function()
	{
		$(this).find('source').attr("src",$(this).attr('data-oembed-url'));
		$(this).find('video').load();
	});
}
</script>
EOF;
	}

    /**
     * @brief 控制器跳转URL拦截
     * @param string $nextUrl 跳转的URL地址
     * @return string
     */
	public static function onControllerRedirect($nextUrl)
	{
	    if(stripos($nextUrl,'/_msg/'))
	    {
	        $replaceStr = stripos($nextUrl,'?') ? "&_msg=" : "?_msg=";
	        $nextUrl    = str_replace("/_msg/",$replaceStr,$nextUrl);
	    }
	    else if(stripos($nextUrl,'/message/'))
	    {
	        $replaceStr = stripos($nextUrl,'?') ? "&message=" : "?message=";
	        $nextUrl    = str_replace("/message/",$replaceStr,$nextUrl);
	    }
	    return $nextUrl;
	}
}