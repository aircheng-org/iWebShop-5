<?php
/**
 * @copyright (c) 2016 aircheng.com
 * @file kuaishangtong.php
 * @brief 快商通客服插件
 * @author nswe
 * @date 2016/5/19 8:44:53
 * @version 4.5
 */
class kuaishangtong extends pluginBase
{
	public static function name()
	{
		return "快商通在线客服";
	}

	public static function description()
	{
		return "快商通提供智能营销客服系统，免费电话，微信在线客服，手机在线客服，全方位满足企业需求，凭借十年的好口碑不仅荣登央视荧屏，<a href='http://agent.kuaishang.cn/agent/member/index/425.htm' target='_blank'>免费注册</a>";
	}

	public static function install()
	{
		$onlineDB = new IModel('kuaishangtong');
		if($onlineDB->exists())
		{
			return true;
		}
		$data = array(
			"comment" => self::name(),
			"column"  => array(
				"id" => array("type" => "int(11) unsigned",'auto_increment' => 1),
				"content" => array("type" => "text","comment" => "快商通参数json数据格式,float:浮动代码;link:连接代码;"),
				"seller_id" => array("type" => "int(11) unsigned","default" => "0","comment" => "商家ID，如果为0表示平台自营客服")
			),
			"index" => array("primary" => "id","key" => "seller_id"),
		);
		$onlineDB->setData($data);
		return $onlineDB->createTable();
	}

	public static function uninstall()
	{
		$onlineDB = new IModel('kuaishangtong');
		return $onlineDB->dropTable();
	}

	public function reg()
	{
		//后台管理
		plugin::reg("onSystemMenuCreate",function(){
			$link = "/plugins/kuaishangtong_edit";
			$link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'kuaishangtong'});";
			Menu::$menu["插件"]["插件管理"][$link] = $this->name();
		});
		plugin::reg("onBeforeCreateAction@plugins@kuaishangtong_edit",function(){
			self::controller()->kuaishangtong_edit = function(){$this->online_edit();};
		});
		plugin::reg("onBeforeCreateAction@plugins@kuaishangtong_update",function(){
			self::controller()->kuaishangtong_update = function(){$this->online_update();};
		});

		//商家管理
		plugin::reg("onSellerMenuCreate",function(){
			$link = "/seller/kuaishangtong_edit";
			$link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'kuaishangtong'});";
			menuSeller::$menu["配置模块"][$link] = $this->name();
		});
		plugin::reg("onBeforeCreateAction@seller@kuaishangtong_edit",function(){
			self::controller()->kuaishangtong_edit = function(){$this->online_edit();};
		});
		plugin::reg("onBeforeCreateAction@seller@kuaishangtong_update",function(){
			self::controller()->kuaishangtong_update = function(){$this->online_update();};
		});

		plugin::reg("onServiceButton",$this,"showButton");
		plugin::reg("onFinishView@site@index",$this,"showFloat");
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

	//浮动窗口
	public function showFloat($seller_id = 0)
	{
		$seller_id = IFilter::act($seller_id,'int');
		$content   = $this->getData($seller_id);
		if(!$content || !$content['float'] || !IFilter::act($content['float'],'url') || (IClient::isMini() == true && $content['is_mini_open'] == 0))
		{
			return;
		}

		echo '<script type="text/javascript" src="'.$content['float'].'" charset="utf-8"></script>';
	}

	/**
	 * @brief 客服按钮
	 * @param string $seller_id 商家ID，如果为0表示平台
	 */
	public function showButton($seller_id = 0)
	{
		$seller_id = IFilter::act($seller_id,'int');
		$content   = $this->getData($seller_id);
		if(!$content || !$content['link'] || !IFilter::act($content['link'],'url'))
		{
			return;
		}
		echo "<a href='".$content['link']."' target='_blank' style='color:#c22;font-weight:bold'>联系客服</a>";
	}

	//获取客服数据
	private function getData($seller_id)
	{
		$seller_id = IFilter::act($seller_id,'int');
		$sonline   = new IModel('kuaishangtong');
		$onlineRow = $sonline->getObj('seller_id = '.$seller_id);
		if(!$onlineRow || !$onlineRow['content'])
		{
			return array();
		}
		return JSON::decode($onlineRow['content']);
	}

	//编辑客服
	public function online_edit()
	{
		$seller_id = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
		$data = $this->getData($seller_id);
		if($seller_id)
		{
			$this->view('kuaishangtong_edit',array("content" => $data,'submitUrl' => '/seller/kuaishangtong_update'));
		}
		else
		{
			$this->view('kuaishangtong_edit',array("content" => $data,'submitUrl' => '/plugins/kuaishangtong_update'));
		}
	}

	//保存客服信息
	public function online_update()
	{
		$seller_id = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
		$link  = IFilter::act(IReq::get('link'),'url');
		$link  = stripos($link,"kuaishang") === false ? "" : $link;

		$float = str_replace(array('<script type="text/javascript" src="','" charset="utf-8"></script>'),"",IReq::get('float'));
		$float = IFilter::act($float,'url');
		$float = stripos($float,"kuaishang") === false ? "" : $float;

		$is_mini_open = IFilter::act(IReq::get('is_mini_open'),'int');

		$data = array(
			'float' => $float,
			'link'  => $link,
			'is_mini_open' => $is_mini_open,
		);
		$dataJson = JSON::encode($data);

		//保存数据库
		$onlineDB = new IModel('kuaishangtong');
		if($onlineDB->getObj('seller_id = '.$seller_id))
		{
			$onlineDB->setData(array('content' => $dataJson));
			$onlineDB->update('seller_id = '.$seller_id);
		}
		else
		{
			$onlineDB->setData(array(
				'content' => $dataJson,
				'seller_id' => $seller_id,
			));
			$onlineDB->add();
		}
		die("保存成功<script>parent.tips('保存成功');parent.art.dialog.list['kuaishangtong'].close();</script>");
	}
}