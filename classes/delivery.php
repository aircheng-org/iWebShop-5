<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file article.php
 * @brief 订单中配送方式的计算
 * @author relay
 * @date 2011-02-24
 * @version 0.6
 */
class Delivery
{
	//用户ID
	public static $user_id = 0;

	//首重重量
	private static $firstWeight  = 0;

	//次重重量
	private static $secondWeight = 0;

	/**
	 * 根据重量计算给定价格
	 * @param $weight float 总重量
	 * @param $firstFee float 首重费用
	 * @param $second float 次重费用
	 */
	private static function getFeeByWeight($weight,$firstFee,$secondFee)
	{
		//当商品重量小于或等于首重的时候
		if($weight <= self::$firstWeight)
		{
			return $firstFee;
		}

		//当商品重量大于首重时，根据次重进行累加计算
		$num = ceil(($weight - self::$firstWeight)/self::$secondWeight);
		return $firstFee + $secondFee * $num;
	}

	/**
	 * @brief 配送方式计算管理模块
	 * @param $province    int 省份的ID
	 * @param $delivery_id int 配送方式ID
	 * @param $goods_id    array 商品ID
	 * @param $product_id  array 货品ID
	 * @param $num         array 商品数量
	 * @return array(
	 *  id => 配送方式ID,
	 *  name => 配送方式NAME,
	 *  description => 配送方式描述,
	 *	if_delivery => 0:支持配送;1:不支持配送,
	 *  price => 实际运费总额,
	 *  protect_price => 商品保价总额,
	 *  org_price => 原始运费总额,
	 *	seller_price => array(商家ID => 实际运费),
	 *	seller_protect_price => array(商家ID => 商品保价),
	 *  seller_org_price => array(商家ID => 原始运费),
	 *  reason => 不能配送原因
	 *	)
	 */
	public static function getDelivery($province,$delivery_id,$goods_id,$product_id = 0,$num = 1)
	{
		//获取默认的配送方式信息
		$delivery    = new IModel('delivery');
		$deliveryDefaultRow = $delivery->getObj('is_delete = 0 and status = 1 and id = '.$delivery_id);
		if(!$deliveryDefaultRow)
		{
			return "配送方式不存在";
		}

		//最终返回结果
		$result = array(
			'id'                   => $deliveryDefaultRow['id'],
			'name'                 => $deliveryDefaultRow['name'],
			'description'          => $deliveryDefaultRow['description'],
			'if_delivery'          => 0,
			'org_price'            => 0,
			'price'                => 0,
			'protect_price'        => 0,
			'seller_org_price'     => [],
			'seller_price'         => [],
			'seller_protect_price' => [],
			'reason'               => "",
		);

		//读取全部商品,array('goodsSum' => 商品总价,'weight' => 商品总重量)
		$sellerGoods   = [];
		$goods_id      = is_array($goods_id)  ? $goods_id   : array($goods_id);
		$product_id    = is_array($product_id)? $product_id : array($product_id);
		$num           = is_array($num)       ? $num        : array($num);
		foreach($goods_id as $key => $gid)
		{
			$pid      = $product_id[$key];
			$gnum     = $num[$key];
			if($pid > 0)
			{
				$goodsRow = Api::run("getProductInfo",array('#id#',$pid));
				if(!$goodsRow)
				{
					return "计算商品运费货品ID【".$pid."】信息不存在";
				}
			}
			else
			{
				$goodsRow = Api::run("getGoodsInfo",array('#id#',$gid));
				if(!$goodsRow)
				{
					return "计算商品运费商品ID【".$gid."】信息不存在";
				}
			}

			if(!isset($sellerGoods[$goodsRow['seller_id']]))
			{
				$sellerGoods[$goodsRow['seller_id']] = array('goodsSum' => 0,'weight' => 0,'isDeliveryFee' => 1,'isPayFirst' => 0);//个别商品免运费但是要看订单中其他商品设置
			}

			//商品免运费
			if(isset($goodsRow['is_delivery_fee']) && $goodsRow['is_delivery_fee'] == 1)
			{
				$goodsRow['weight'] = 0;
			}
			else
			{
				$sellerGoods[$goodsRow['seller_id']]['isDeliveryFee'] = 0;
			}

			//商品特殊活动处理
			if(in_array($goodsRow['promo'],['assemble','costpoint']))
			{
			    $sellerGoods[$goodsRow['seller_id']]['isPayFirst'] = 1;
			}

			$sellerGoods[$goodsRow['seller_id']]['weight']  += $goodsRow['weight']     * $gnum;
			$sellerGoods[$goodsRow['seller_id']]['goodsSum']+= $goodsRow['sell_price'] * $gnum;
		}

		//根据商家不同计算运费
		$deliveryExtendDB = new IModel('delivery_extend');
		foreach($sellerGoods as $seller_id => $data)
		{
			$weight        = $data['weight'];//计算运费
			$goodsSum      = $data['goodsSum'];//计算保价
			$isDeliveryFee = $data['isDeliveryFee'];//是否都是免运费商品
			$isPayFirst    = $data['isPayFirst'];//是否先付款后发货

			//使用商家配置的物流运费
			$seller_id         = IFilter::act($seller_id,'int');
			$deliverySellerRow = $deliveryExtendDB->getObj('delivery_id = '.$delivery_id.' and seller_id = '.$seller_id);
			$deliveryRow       = $deliverySellerRow ? $deliverySellerRow : $deliveryDefaultRow;

	 		//设置首重和次重
	 		self::$firstWeight          = $deliveryRow['first_weight'];
	 		self::$secondWeight         = $deliveryRow['second_weight'];
			$deliveryRow['if_delivery'] = '0';

            //必须先付款，不支持货到付款
			if($isPayFirst == 1 && $deliveryDefaultRow['type'] == 1)
			{
 				$deliveryRow['price']       = '0';
 				$deliveryRow['if_delivery'] = '1';
 				$deliveryRow['reason']      = '不支持此配送';
			}
			else
			{
    	 		//当配送方式是统一配置的时候，不进行区分地区价格
    	 		if($deliveryRow['price_type'] == 0)
    	 		{
    	 			$deliveryRow['price'] = self::getFeeByWeight($weight,$deliveryRow['first_price'],$deliveryRow['second_price']);
    	 		}
    	 		//当配送方式为指定区域和价格的时候
    	 		else
    	 		{
    				$matchKey = '';
    				$flag     = false;

    				//每项都是以';'隔开的省份ID
    				$area_groupid = unserialize($deliveryRow['area_groupid']);
    				if($area_groupid)
    				{
    					foreach($area_groupid as $key => $item)
    					{
    						//匹配到了特殊的省份运费价格
    						if(strpos($item,';'.$province.';') !== false)
    						{
    							$matchKey = $key;
    							$flag     = true;
    							break;
    						}
    					}
    				}

    				//匹配到了特殊的省份运费价格
    				if($flag)
    				{
    					//获取当前省份特殊的运费价格
    					$firstprice  = unserialize($deliveryRow['firstprice']);
    					$secondprice = unserialize($deliveryRow['secondprice']);

    					$deliveryRow['price'] = self::getFeeByWeight($weight,$firstprice[$matchKey],$secondprice[$matchKey]);
    				}
    				else
    				{
    	     			//判断是否设置默认费用了
    	     			if($deliveryRow['open_default'] == 1)
    	     			{
    	     				$deliveryRow['price'] = self::getFeeByWeight($weight,$deliveryRow['first_price'],$deliveryRow['second_price']);
    	     			}
    	     			else
    	     			{
    	     				$deliveryRow['price']       = '0';
    	     				$deliveryRow['if_delivery'] = '1';
    	     				$deliveryRow['reason']      = '地区无法配送';
    	     			}
    				}
    	 		}
			}

	 		$deliveryRow['org_price'] = $deliveryRow['price'];

	 		//计算保价
	 		if($deliveryRow['is_save_price'] == 1)
	 		{
	 			$tempProtectPrice             = $goodsSum * ($deliveryRow['save_rate'] * 0.01);
	 			$deliveryRow['protect_price'] = ($tempProtectPrice <= $deliveryRow['low_price']) ? $deliveryRow['low_price'] : $tempProtectPrice;
	 		}
	 		else
	 		{
	 			$deliveryRow['protect_price'] = 0;
	 		}

			//无法送达
	 		if($deliveryRow['if_delivery'] == 1)
	 		{
	 			$deliveryRow['id']          = $deliveryDefaultRow['id'];
				$deliveryRow['name']        = $deliveryDefaultRow['name'];
				$deliveryRow['description'] = $deliveryDefaultRow['description'];
				return $deliveryRow;
	 		}

			//更新最终数据
			$result['org_price']         += $deliveryRow['org_price'];
	 		$result['price']             += $isDeliveryFee == 1 ? 0 : $deliveryRow['price'];
	 		$result['protect_price']     += $deliveryRow['protect_price'];

			$result['seller_org_price'][$seller_id]     = $deliveryRow['org_price'];
	 		$result['seller_price'][$seller_id]         = $deliveryRow['price'];
	 		$result['seller_protect_price'][$seller_id] = $deliveryRow['protect_price'];
		}
     	return $result;
	}
}