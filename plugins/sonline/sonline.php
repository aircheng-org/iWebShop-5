<?php
/**
 * @copyright (c) 2016 aircheng.com
 * @file sonline.php
 * @brief qq客服插件
 * @author nswe
 * @date 2016/3/9 22:06:19
 * @version 4.4
 */
class sonline extends pluginBase
{
	public static function name()
	{
		return "QQ客服插件";
	}

	public static function description()
	{
		return "网站和商家可以分别设置多个QQ客服，在商家主页，商品详情页面都有QQ咨询按钮";
	}

	public static function install()
	{
		$onlineDB = new IModel('online_service');
		if($onlineDB->exists())
		{
			return true;
		}
		$data = array(
			"comment" => self::name(),
			"column"  => array(
				"id" => array("type" => "int(11) unsigned",'auto_increment' => 1),
				"qq" => array("type" => "text","comment" => "客服QQ名称和号码json数据格式"),
				"seller_id" => array("type" => "int(11) unsigned","default" => "0","comment" => "商家ID，如果为0表示平台自营客服")
			),
			"index" => array("primary" => "id","key" => "seller_id"),
		);
		$onlineDB->setData($data);
		return $onlineDB->createTable();
	}

	public static function uninstall()
	{
		$onlineDB = new IModel('online_service');
		return $onlineDB->dropTable();
	}

	public function reg()
	{
		//后台管理
		plugin::reg("onSystemMenuCreate",function(){
			$link = "/plugins/qq_edit";
			$link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'qq'});";
			Menu::$menu["插件"]["插件管理"][$link] = $this->name();
		});
		plugin::reg("onBeforeCreateAction@plugins@qq_edit",function(){
			self::controller()->qq_edit = function(){$this->online_edit();};
		});
		plugin::reg("onBeforeCreateAction@plugins@online_update",function(){
			self::controller()->online_update = function(){$this->online_update();};
		});

		//商家管理
		plugin::reg("onSellerMenuCreate",function(){
			$link = "/seller/qq_edit";
			$link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'qq'});";
			menuSeller::$menu["配置模块"][$link] = $this->name();
		});
		plugin::reg("onBeforeCreateAction@seller@qq_edit",function(){
			self::controller()->qq_edit = function(){$this->online_edit();};
		});
		plugin::reg("onBeforeCreateAction@seller@online_update",function(){
			self::controller()->online_update = function(){$this->online_update();};
		});

		//前台显示部分仅显示pc
		if(IClient::getDevice() == IClient::PC)
		{
			plugin::reg("onFinishView@site@index",$this,"showFloat");
			plugin::reg("onServiceButton",$this,"showButton");
			plugin::reg("onFinishView@site@products",function(){
				$goods_id = IFilter::act(IReq::get('id'),'int');
				$goodsDB  = new IModel('goods');
				$goodsRow = $goodsDB->getObj('id = '.$goods_id);
				if($goodsRow)
				{
					$this->showFloat($goodsRow['seller_id']);
				}
			});
			plugin::reg("onFinishView@site@home",function(){$this->showFloat(IReq::get('id'));});
		}
	}

	public static function configName()
	{
		return array(
			"color" => array("name" => "颜色风格","type" => "select","value" => array("蓝色" => "blue","红色" => "red","绿色" => "green","灰色" => "gray")),
			"DefaultsOpen" => array("name" => "显示方式","type" => "select","value" => array("展开" => "true","隐藏" => "false")),
			"Position" => array("name" => "显示位置","type" => "select","value" => array("左侧" => "left","右侧" => "right")),
		);
	}

	//QQ浮动窗口
	public function showFloat($seller_id = 0)
	{
		$seller_id = IFilter::act($seller_id,'int');
		if($seller_id)
		{
			$sellerDB = new IModel('seller');
			$sellerRow= $sellerDB->getObj('id = '.$seller_id);
			$tel      = $sellerRow['phone'];
		}
		else
		{
			$site_config = new Config('site_config');
			$tel         = $site_config->phone;
		}

		$webPath   = $this->webPath();
		$configData= $this->config();
		$color     = $configData['color'];
		$isOpen    = $configData['DefaultsOpen'];
		$position  = $configData['Position'];
		$qqArray   = $this->getData($seller_id);
		$tempArray = array();
		foreach($qqArray as $val)
		{
			if(!$val['qq'] || !$val['name'])
			{
				continue;
			}
			$tempArray[] = $val['qq']."|".$val['name'];
		}

		if(!$tempArray)
		{
			return null;
		}
		$qqString = join(',',$tempArray);

echo <<< OEF
<link rel="stylesheet" href="{$webPath}style/{$color}.css" />
<script type="text/javascript" src="{$webPath}js/jquery.Sonline.js"></script>
<script type='text/javascript'>
$(function(){
	$().Sonline({
		"Tel":"{$tel}",
		"Qqlist":"{$qqString}",
		"DefaultsOpen":{$isOpen},
		"Position":"{$position}",
	});
});
</script>
OEF;
	}

	/**
	 * @brief 显示QQ客服按钮
	 * @param string $seller_id 商家ID，如果为0表示平台
	 */
	public function showButton($seller_id = 0)
	{
		$seller_id = IFilter::act($seller_id,'int');
		$qqData    = $this->getData($seller_id);
		if(!$qqData)
		{
			return;
		}
		$simpleRow = current($qqData);
		$qqNum     = $simpleRow['qq'];

		if(!$qqNum)
		{
			return;
		}

echo <<< OEF
	<a href="http://wpa.qq.com/msgrd?v=3&uin={$qqNum}&site=qq&menu=yes" target="_blank">
		<img border="0" alt="立即联系" src="http://wpa.qq.com/pa?p=2:{$qqNum}:41 &r=0.22914223582483828">
	</a>
OEF;
	}

	//获取QQ客服数据
	private function getData($seller_id)
	{
		$seller_id= IFilter::act($seller_id,'int');
		$sonline  = new IModel('online_service');
		$onlineRow= $sonline->getObj('seller_id = '.$seller_id);
		if(!$onlineRow || !$onlineRow['qq'])
		{
			return array();
		}
		return JSON::decode($onlineRow['qq']);
	}

	//编辑客服
	public function online_edit()
	{
		$seller_id = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
		$data = $this->getData($seller_id);
		if($seller_id)
		{
			$this->view('qq_edit',array("qq" => $data,"submitUrl" => "/seller/online_update"));
		}
		else
		{
			$this->view('qq_edit',array("qq" => $data,"submitUrl" => "/plugins/online_update"));
		}
	}

	//保存客服信息
	public function online_update()
	{
		$seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
		$serviceName = IFilter::act(IReq::get('service_name'));
		$serviceQQ   = IFilter::act(IReq::get('service_qq'));
		if(!$serviceName || !$serviceQQ)
		{
			$this->online_edit();
			return;
		}

		$data        = array();
		foreach($serviceName as $key => $val)
		{
			$data[] = array('name' => $serviceName[$key],'qq' => $serviceQQ[$key]);
		}
		$dataJson = JSON::encode($data);

		//保存数据库
		$onlineDB = new IModel('online_service');
		if($onlineDB->getObj('seller_id = '.$seller_id))
		{
			$onlineDB->setData(array('qq' => $dataJson));
			$onlineDB->update('seller_id = '.$seller_id);
		}
		else
		{
			$onlineDB->setData(array(
				'qq' => $dataJson,
				'seller_id' => $seller_id,
			));
			$onlineDB->add();
		}
		die("保存成功<script>parent.tips('保存成功');parent.art.dialog.list['qq'].close();</script>");
	}
}