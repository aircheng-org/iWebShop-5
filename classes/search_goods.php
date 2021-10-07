<?php
/**
 * @brief 检索商品类
 * @date 2013/12/1 18:34:35
 * @author chendeshan
 */
class search_goods
{
	//商品检索的属性过滤 array(key => array(id,name,value))
	public static $attrSearch = array();

	//商品检索的品牌过滤 array(key => array(id,name))
	public static $brandSearch = array();

	//商品检索的价格过滤
	public static $priceSearch = array();

	/**
	 * @brief 获取总的排序方式
	 * @return array(代号 => 名字)
	 */
	public static function getOrderType()
	{
		return array('sale' =>'销量','cpoint' =>'评分','price'=>'价格','new'=>'最新');
	}

	/**
	 * @brief 商品检索,可以直接读取 $_GET 全局变量:attr,order,brand,min_price,max_price
	 *        在检索商品过程中计算商品结果中的进一步属性和规格的筛选
	 * @param mixed $defaultWhere string(条件) or array('search' => '模糊查找','category_extend' => '商品分类ID','字段' => 对应数据)
	 * @param int $limit 读取数量
	 * @param bool $isCondition 是否筛选出商品的属性，价格等数据
	 * @return IQuery
	 */
	public static function find($defaultWhere = '',$limit = 21,$isCondition = true)
	{
		//排序字段
		$orderArray = array();

		//开始查询
		$goodsObj           = new IQuery("goods as go");
		$goodsObj->page     = isset($_GET['page']) ? intval($_GET['page']) : 1;
		$goodsObj->fields   = 'go.id,go.name,go.sell_price,go.market_price,go.store_nums,go.img,go.sale,go.grade,go.comments,go.favorite';
		$goodsObj->pagesize = $limit;
		$goodsObj->group    = 'go.id';

		/*where条件拼接*/
		//(1),当前产品分类
		$where = array('go.is_del = 0');
		$join  = array();

		//(2),商品属性,规格筛选
		$attrCond  = array();
		$attrArray = IReq::get('attr') ? IFilter::act(IReq::get('attr')) : array();
		if($attrArray)
		{
			foreach($attrArray as $attId => $attVal)
			{
				$attId = IFilter::act($attId,'int');
				if($attId && $attVal && !preg_match("|\s+|",$attVal))
				{
					$attrCond[] = 'attribute_id = '.$attId.' and FIND_IN_SET("'.$attVal.'",attribute_value)';
				}
			}

			if($attrCond)
			{
				$temp = array();
				foreach($attrCond as $key => $val)
				{
					$temp[] = " SELECT goods_id FROM goods_attribute as t{$key} WHERE {$val} ";
				}
				$join[]  = "left join (select goods_id from (".join(" UNION ALL ",$temp).") as temp group by temp.goods_id having count(*) > {$key}) as ga on ga.goods_id = go.id";
				$where[] = "ga.goods_id is NOT NULL";
			}
		}

		//(3),处理defaultWhere条件 goods, category_extend
		if($defaultWhere)
		{
			//兼容array 和 string 数据类型的goods条件筛选
			$goodsCondArray = array();
			if(is_string($defaultWhere))
			{
				$goodsCondArray[] = $defaultWhere;
			}
			else if(is_array($defaultWhere))
			{
				foreach($defaultWhere as $key => $val)
				{
					if($val === '' || $val === null)
					{
						continue;
					}

					//商品分类检索
					if($key == 'category_extend')
					{
						//没有点击搜索属性 $attrCond
						if($val)
						{
							$join[]  = "left join category_extend as ce on ce.goods_id = go.id";
							$where[] = "ce.category_id in (".$val.")";
						}
					}
					//搜索词模糊
					else if($key == 'search')
					{
						$wordWhere     = array();
						$wordLikeOrder = array();

						//进行分词
						if(IString::getStrLen($defaultWhere['search']) >= 4 || IString::getStrLen($defaultWhere['search']) <= 100)
						{
							$wordData = plugin::trigger("onSearchGoodsWordsPart",$defaultWhere['search']);
							if(isset($wordData['data']) && count($wordData['data']) >= 1)
							{
								foreach($wordData['data'] as $word)
								{
									$wordWhere[]     = ' go.name like "%'.$word.'%" ';
									$wordLikeOrder[] = $word;
								}

								//分词排序
								if($wordLikeOrder)
								{
									$orderTempArray = array();
									foreach($wordLikeOrder as $key => $val)
									{
										$orderTempArray[] = "(CASE WHEN go.name LIKE '%".$val."%' THEN ".$key." ELSE 100 END)";
									}
									$orderArray[] = " (".join('+',$orderTempArray).") asc ";
								}
							}
						}

						//存在分词结果
						if($wordWhere)
						{
							$goodsCondArray[] = ' ('.join(" and ",$wordWhere).' or find_in_set("'.$defaultWhere['search'].'",go.search_words)) ';
						}
						else
						{
							$goodsCondArray[] = ' (go.name like "%'.$defaultWhere['search'].'%" or find_in_set("'.$defaultWhere['search'].'",go.search_words)) ';
						}
					}
					//商户店内分类检索
					else if($key == 'category_extend_seller')
					{
						if($val)
						{
							$join[]  = "left join category_extend_seller as ce on ce.goods_id = go.id";
							$where[] = "ce.category_id in (".$val.")";
						}
					}
					//其他条件
					else
					{
						$goodsCondArray[] = $key.' = "'.$val.'"';
					}
				}
			}

			//goods 条件
			if($goodsCondArray)
			{
				$where[] = "(".join(" and ",$goodsCondArray).")";
			}
		}

		//(4),商品品牌
		$where[] = IReq::get('brand') ? 'go.brand_id = '.intval(IReq::get('brand')) : '';

		//(5),商品标签
		$tagId = IFilter::act(IReq::get('tag_id'),'int');
		if($tagId)
		{
			$commendDB = new IModel('commend_goods');
			$commendData = $commendDB->query('commend_id = '.$tagId);
			if($commendData)
			{
				$goodsIds = [];
				foreach($commendData as $commendRow)
				{
					$goodsIds[] = $commendRow['goods_id'];
				}
				$where[] = 'go.id in ('.join(',',$goodsIds).')';
			}
		}

		//商品属性进行检索
		if($isCondition == true)
		{
			$where = array_filter($where);

			/******属性 开始******/
			$attrTemp            = array();
			$goodsAttrDB         = new IQuery('goods_attribute as goAttr');
			$goodsAttrDB->fields = "goAttr.attribute_id,goAttr.attribute_value,att.name";
			$goodsAttrDB->join   = "left join goods as go on go.id = goAttr.goods_id left join attribute as att on att.id = goAttr.attribute_id ".join("  ",$join);
			$goodsAttrDB->where  = join(" and ",$where)." and att.search = 1";
			$goodsAttrData       = $goodsAttrDB->find();
			foreach($goodsAttrData as $key => $val)
			{
				//属性存在
				if($val['attribute_id'] && $val['name'] && $val['attribute_value'])
				{
					if(!isset($attrTemp[$val['name']]))
					{
						$attrTemp[$val['name']] = array(
							'id'    => $val['attribute_id'],
							'name'  => $val['name'],
							'value' => array(),
						);
					}
					else if($attrTemp[$val['name']]['id'] != $val['attribute_id'])
					{
						continue;
					}

					$checkSelectedArray = explode(",",$val['attribute_value']);//有复选情况
					foreach($checkSelectedArray as $k => $v)
					{
						if(!in_array( $v,$attrTemp[$val['name']]['value'] ))
						{
							$attrTemp[$val['name']]['value'][] = $v;
						}
					}
				}
			}
			self::$attrSearch = $attrTemp;
			/******属性 结束******/

			/******品牌 开始******/
			$brandQuery        = new IQuery('brand as b');
			$brandQuery->join  = "left join goods as go on go.brand_id = b.id ".join("  ",$join);
			$brandQuery->where = join(" and ",$where);
			$brandQuery->order = "b.sort asc";
			$brandQuery->fields= "distinct b.id,b.name";
			$brandQuery->limit = 10;
			self::$brandSearch = $brandQuery->find();
			/******品牌 结束******/

			/******价格 开始******/
			$priceDB = new IQuery('goods as go');
			$priceDB->fields = "sell_price";
			$priceDB->join   = join(" ",$join);
			$priceDB->where  = join(" and ",array_slice($where,1));
			$priceDB->limit  = 1;

			//最小价格
			$priceDB->order = "sell_price asc";
			$minPriceData = $priceDB->find();
			$minPrice     = $minPriceData ? $minPriceData[0]['sell_price'] : 0;

			//最大价格
			$priceDB->order = "sell_price desc";
			$maxPriceData = $priceDB->find();
			$maxPrice     = $maxPriceData ? $maxPriceData[0]['sell_price'] : 0;

			//计算价格区间
			self::$priceSearch = goods_class::getGoodsPrice($minPrice,$maxPrice);
			/******价格 结束******/
		}

		//(5),商品价格
		$where[] = floatval(IReq::get('min_price')) ? 'go.sell_price >= '.floatval(IReq::get('min_price')) : '';
		$where[] = floatval(IReq::get('max_price')) ? 'go.sell_price <= '.floatval(IReq::get('max_price')) : '';

		//排序类别
		$order = IReq::get('order');
		$by    = IReq::get('by') == "desc" ? "desc" : "asc";
		if($order == null)
		{
			//获取配置信息
			$siteConfigObj = new Config("site_config");
			$site_config   = $siteConfigObj->getInfo();
			$order         = isset($site_config['order_by']) ? $site_config['order_by'] :'';
		}

		switch($order)
		{
			//销售量
			case "sale":
			{
				$orderArray[] = ' go.sale '.$by;
			}
			break;

			//评分
			case "cpoint":
			{
				$orderArray[] = ' go.grade '.$by;
			}
			break;

			//最新上架
			case "new":
			{
				$orderArray[] = ' go.id '.$by;
			}
			break;

			//价格
			case "price":
			{
				$orderArray[] = ' go.sell_price '.$by;
			}
			break;

			//根据排序字段
			default:
			{
				$orderArray[] = ' go.sort asc ';
			}
		}

		//设置IQuery类的各个属性
		$goodsObj->join  = join(" ",array_filter($join));
		$goodsObj->where = join(" and ",array_filter($where));
		$goodsObj->order = join(',',array_filter($orderArray));
		return $goodsObj;
	}
}