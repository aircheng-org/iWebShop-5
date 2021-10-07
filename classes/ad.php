<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file ad.php
 * @brief 关于广告管理
 * @author chendeshan
 * @date 2011-02-14
 * @version 0.6
 *
 * @update 更新广告数据的兼容性，防止被屏蔽功能
 * @date 2018/6/27 9:42:25
 * @version 5.2
 */

/**
 * @class article
 * @brief 广告管理模块
 */
class Ad
{
	//是否加载过js
	private static $isLoadJs = false;

	//当广告无数据时，是否加载默认的广告位数据
	private static $isDefault = false;

	/**
	 * @brief 广告类型展示解析
	 * @param $type int 类型
	 * @return string 类型字符串
	 */
	public static function showType($type)
	{
		switch($type)
		{
			case "1":
			$str = '图片';
			break;

			case "2":
			$str = 'flash';
			break;

			case "3":
			$str = '文字';
			break;

			case "4":
			$str = '代码';
			break;

			case "5":
			$str = '幻灯片';
			break;
		}
		return $str;
	}

	/**
	 * @brief 展示制定广告位的广告内容
	 * @param $position mixed 广告位ID 或者 广告位名称
	 * @param $goods_cat_id 商品分类ID
	 * @param $seller_id 商家ID
	 * @return string
	 */
	public static function show($position,$goods_cat_id = 0,$seller_id = 0)
	{
		$positionObject = array();
		$adArray        = array();

		$positionObject = self::getPositionInfo($position,$seller_id);
		if($positionObject)
		{
			$adList = self::getAdList($positionObject['id'],$goods_cat_id);
			foreach($adList as $key => $val)
			{
				$val['width']  = $positionObject['width'];
				$val['height'] = $positionObject['height'];
				$adArray[] = self::display($val);
			}
		}

		//有广告内容数据
		if($adArray)
		{
			$positionJson = JSON::encode($positionObject);
			$adJson       = JSON::encode($adArray);

			//引入 adloader js类库
			if(self::$isLoadJs == false)
			{
			    self::adJs();
				self::$isLoadJs = true;
			}
			$adPositionJsId = md5("AD_{$position}_{$goods_cat_id}");

//生成HTML代码
echo <<< OEF
			<div id='{$adPositionJsId}' class='admanage'></div>
			<script>(new adLoader()).load({$positionJson},{$adJson},"{$adPositionJsId}");</script>
OEF;
		}
		//获取默认广告数据
		else if(self::$isDefault == true)
		{
			$thumbImg = Api::run('getAdRow',$position.$goods_cat_id);
			if($thumbImg)
			{
				preg_match("/([\d%]+)\*(\d+)/",$position,$match);
				if(isset($match[2]))
				{
					$width  = stripos($match[1],"%") ? $match[1] : $match[1].'px';
					$height = stripos($match[2],"%") ? $match[2] : $match[2].'px';
					echo '<div class="admanage"><img src="'.$thumbImg.'" style="width:'.$width.';height:'.$height.'" /></div>';
				}
			}
		}
	}

	/**
	 * @brief 展示广告位数据
	 * @param $adData array 广告数据
	 * @return string
	 */
	private static function display($adData)
	{
		$result     = [];
		$size       = array();
		$configSize = array("width" => self::getSize($adData['width']),"height" => self::getSize($adData['height']));
		$configSize = array_filter($configSize);
		foreach($configSize as $key => $val)
		{
			$size[] = $key.":".$val;
		}
		switch($adData['type'])
		{
			//图片
			case 1:
			{
				$result = array
				(
					'type' => 1,
					'data' => '<img src="'.IUrl::creatUrl($adData['content']).'" style="cursor:pointer;'.join(";",$size).'"/>'
				);
			}
			break;

			//flash
			case 2:
			{
				$result = array
				(
					'type' => 2,
					'data' => '<object style="cursor:pointer;" classid="clsid:D27CDB6E-AE6D-11CF-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" '.join(";",$size).' hspace="0" vspace="0" border="0" align="left"><param name="movie" value="'.IUrl::creatUrl("").$adData['content'].'"><param name="quality" value="high"><param name="wmode" value="transparent"><param name="scale" value="noborder"><embed src="'.IUrl::creatUrl("").$adData['content'].'" quality="high" wmode="transparent" scale="noborder" '.$size.' hspace="0" vspace="0" border="0" align="left" type="application/x-shockwave-flash" luginspage="http://www.macromedia.com/go/getflashplayer"></embed></object>'
				);
			}
			break;

			//文字
			case 3:
			{
				$result = array
				(
					'type' => 3,
					'data' => '<label style="cursor:pointer;'.join(";",$size).'">'.$adData['content'].'</label>'
				);
			}
			break;

			//代码
			case 4:
			{
				$result = array
				(
					'type' => 4,
					'data' => '<label style="cursor:pointer;'.join(";",$size).'">'.$adData['content'].'</label>'
				);
			}
			break;
		}

		//有跳转链接
		if($adData['link'])
		{
			$result['data'] = "<a href='".IUrl::creatUrl($adData['link'])."'>".$result['data']."</a>";
		}

		return $result;
	}

	/**
	 * @brief 获取广告位置的信息
	 * @param $position mixed 广告位ID 或者 广告位名称
	 * @return array
	 */
	public static function getPositionInfo($position,$seller_id=0)
	{
		$adPositionDB = new IModel("ad_position");
		if(is_int($position))
		{
			return $adPositionDB->getObj("id={$position} AND `status`=1 and seller_id = ".$seller_id);
		}
		else
		{
			return $adPositionDB->getObj("name='{$position}' AND `status`=1 and seller_id = ".$seller_id);
		}
	}

	/**
	 * @brief 获取当前时间段正在使用的广告数据
	 * @param $position int 广告位ID
	 * @param $goods_cat_id 商品分类ID
	 * @return array
	 */
	public static function getAdList($position,$goods_cat_id = 0)
	{
		$now    = date("Y-m-d H:i:s",ITime::getNow());
		$adDB   = new IModel("ad_manage");
		return $adDB->query("position_id={$position} and goods_cat_id = {$goods_cat_id} and start_time < '{$now}' AND end_time > '{$now}' ORDER BY `order` ASC ");
	}

	//获取尺寸
	public static function getSize($value)
	{
		if(is_numeric($value) && $value > 0)
		{
			return $value."px";
		}
		return $value;
	}

	//广告类库
	private static function adJs()
	{
echo <<< OEF
        <script>
        //广告位执行类定义
        function adLoader()
        {
        	var _self        = this;
        	var _id          = null;
        	var adKey        = null;
        	var positionData = null;
        	var adData       = [];

        	/**
        	 * @brief 加载广告数据
        	 * @param positionJson 广告位数据
        	 * @param adArray      广告列表数据
        	 * @param boxId        广告容器ID
        	 */
        	this.load = function(positionJson,adArray,boxId)
        	{
        		_self.positionData = positionJson;
        		_self.adData       = adArray;
        		_self._id          = boxId;
        		_self.show();
        	}

        	/**
        	 * @brief 展示广告位
        	 */
        	this.show = function()
        	{
        		//顺序显示
        		if(_self.positionData.fashion == 1)
        		{
        			_self.adKey = (_self.adKey == null) ? 0 : _self.adKey+1;

        			if(_self.adKey >= _self.adData.length)
        			{
        				_self.adKey = 0;
        			}
        		}
        		//随机显示
        		else
        		{
        			var rand = parseInt(Math.random()*1000);
        			_self.adKey = rand % _self.adData.length;
        		}

        		var adRow = _self.adData[_self.adKey];
        		$('#'+_self._id).html(adRow.data);

        		//多个广告数据要依次展示
        		if(_self.adData.length > 1)
        		{
        			window.setTimeout(function(){_self.show();},5000);
        		}
        	}
        }
        </script>
OEF;
	}
}