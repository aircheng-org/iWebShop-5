<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file goods_class.php
 * @brief 商品管理类库
 * @author nswe
 * @date 2014/8/18 11:53:43
 * @version 2.6

 * @update 2019/5/31 21:43:53
 * @author nswe
 * @note 新增preorder时间预订类商品处理
 */
class goods_class
{
	//算账类库
	private static $countsumInstance = null;

	//商户ID
	public $seller_id = '';

	//构造函数
	public function __construct($seller_id = '')
	{
		$this->seller_id = $seller_id;
	}

	/**
	 * 获取商品价格
	 * @param int $id 商品ID或者货品ID
	 * @param float $sell_price 商品销售价
	 * @param string $type goods商品;product:货品;
	 */
	public static function price($id,$sell_price,$type = 'goods')
	{
		if(self::$countsumInstance == null)
		{
			self::$countsumInstance = new CountSum();
		}
		$price = self::$countsumInstance->getGroupPrice($id,$type);
		return $price ? $price : $sell_price;
	}

	/**
	 * 生成商品货号
	 * @return string 货号
	 */
	public static function createGoodsNo()
	{
		$config = new Config('site_config');
		return $config->goods_no_pre.substr(time(),-5).rand(10,99);
	}

	/**
	 * @brief 修改商品数据
	 * @param int $id 商品ID
	 * @param array $postData 商品所需数据,键名分为"_"前缀和非"_"前缀，非"_"前缀的是goods表的字段
	 */
	public function update($id,$postData)
	{
		$goodsUpdateData = array();//更新到goods表的字段数据
		$nowDataTime     = ITime::getDateTime();

		foreach($postData as $key => $val)
		{
			//数据过滤分组
			if(strpos($key,'attr_id_') !== false)
			{
				$goodsAttrData[ltrim($key,'attr_id_')] = IFilter::act($val);
			}
			//对应goods表字段 排除虚拟商品数组字段
			else if($key[0] != '_' && is_string($val))
			{
				$goodsUpdateData[$key] = IFilter::act($val,'text');
			}
		}

		//判断货号是否重复
		if(count($postData['_goods_no']) != count(array_unique($postData['_goods_no'])))
		{
		    die('货号必须唯一，禁止重复');
		}

		//商家发布商品默认设置
		if($this->seller_id)
		{
			$goodsUpdateData['seller_id'] = $this->seller_id;
			$goodsUpdateData['is_del'] = $goodsUpdateData['is_del'] == 2 ? 2 : 3;

			//如果商户是VIP则无需审核商品
			if($goodsUpdateData['is_del'] == 3)
			{
				$sellerDB = new IModel('seller');
				$sellerRow= $sellerDB->getObj('id = '.$this->seller_id);
				if($sellerRow['is_vip'] == 1)
				{
					$goodsUpdateData['is_del'] = 0;
				}
			}
		}

		//上架或者下架处理
		if(isset($goodsUpdateData['is_del']))
		{
			//上架
			if($goodsUpdateData['is_del'] == 0)
			{
				$goodsUpdateData['up_time']   = $nowDataTime;
				$goodsUpdateData['down_time'] = null;
			}
			//下架
			else if($goodsUpdateData['is_del'] == 2)
			{
				$goodsUpdateData['up_time']  = null;
				$goodsUpdateData['down_time']= $nowDataTime;
			}
			//审核或者删除
			else
			{
				$goodsUpdateData['up_time']   = null;
				$goodsUpdateData['down_time'] = null;
			}
		}

		//是否存在货品
		$goodsUpdateData['spec_array'] = '';
		if(isset($postData['_spec_array']))
		{
			//生成goods中的spec_array字段数据
			$goods_spec_array = [];
			foreach($postData['_spec_array'] as $key => $val)
			{
				foreach($val as $v)
				{
					$tempSpec = JSON::decode($v);
					if(!$tempSpec['id'] || !$tempSpec['name'])
					{
						break;
					}

					if(!isset($goods_spec_array[$tempSpec['id']]))
					{
						$goods_spec_array[$tempSpec['id']] = array('id' => $tempSpec['id'],'name' => $tempSpec['name'],'value' => array());
					}

					if(!in_array([$tempSpec['value'] => $tempSpec['image']],$goods_spec_array[$tempSpec['id']]['value']))
					{
						$goods_spec_array[$tempSpec['id']]['value'][] = [$tempSpec['value'] => $tempSpec['image']];
					}
				}
			}

			if($goods_spec_array)
			{
				$goodsUpdateData['spec_array'] = IFilter::act(JSON::encode($goods_spec_array));
			}
		}

		//用sell_price最小的货品填充商品表
		$defaultKey = array_search(min($postData['_sell_price']),$postData['_sell_price']);

		//赋值goods表默认数据
		$goodsUpdateData['name']         = IFilter::act($goodsUpdateData['name'],'text');
		$goodsUpdateData['goods_no']     = isset($postData['_goods_no'][$defaultKey])     ? IFilter::act($postData['_goods_no'][$defaultKey])             : '';
		$goodsUpdateData['market_price'] = isset($postData['_market_price'][$defaultKey]) ? IFilter::act($postData['_market_price'][$defaultKey],'float') : 0;
		$goodsUpdateData['sell_price']   = isset($postData['_sell_price'][$defaultKey])   ? IFilter::act($postData['_sell_price'][$defaultKey],'float')   : 0;
		$goodsUpdateData['cost_price']   = isset($postData['_cost_price'][$defaultKey])   ? IFilter::act($postData['_cost_price'][$defaultKey],'float')   : 0;
		$goodsUpdateData['weight']       = isset($postData['_weight'][$defaultKey])       ? IFilter::act($postData['_weight'][$defaultKey],'float')       : 0;
		$goodsUpdateData['store_nums']   = IFilter::act(min($postData['_store_nums']),'int');

		if(isset($postData['_imgList']) && $postData['_imgList'])
		{
			$postData['_imgList']   = trim($postData['_imgList'],',');
			$postData['_imgList']   = explode(",",$postData['_imgList']);
			$postData['_imgList']   = array_filter($postData['_imgList']);
			$goodsUpdateData['img'] = $goodsUpdateData['img'] ? $goodsUpdateData['img'] : current($postData['_imgList']);
		}

		if(isset($postData['config']) && $postData['config'])
		{
		    $goodsUpdateData['config'] = JSON::encode($postData['config']);
		}

		//处理商品
		$goodsDB = new IModel('goods');
		if($id)
		{
		    $isInsert = false;
			$goodsDB->setData($goodsUpdateData);

			$where = " id = {$id} ";
			if($this->seller_id)
			{
				$where .= " and seller_id = ".$this->seller_id;
			}

			if($goodsDB->update($where) === false)
			{
				$goodsDB->rollback();
				die("更新商品错误");
			}
		}
		else
		{
		    $isInsert = true;
			$goodsUpdateData['create_time'] = $nowDataTime;
			$goodsDB->setData($goodsUpdateData);
			$id = $goodsDB->add();
			if(!$id)
			{
				$goodsDB->rollback();
				die("添加商品失败");
			}
		}

		//商品添加成功后更新虚拟属性
		$this->updateGoodsType($id,$postData,$isInsert);

		//处理商品属性
		$goodsAttrDB = new IModel('goods_attribute');
		$goodsAttrDB->del('goods_id = '.$id);
		if($goodsUpdateData['model_id'] > 0 && isset($goodsAttrData) && $goodsAttrData)
		{
			foreach($goodsAttrData as $key => $val)
			{
				$attrData = array(
					'goods_id' => $id,
					'model_id' => $goodsUpdateData['model_id'],
					'attribute_id' => $key,
					'attribute_value' => is_array($val) ? join(',',$val) : $val
				);
				$goodsAttrDB->setData($attrData);
				$goodsAttrDB->add();
			}
		}

		//处理货品数据信息
		$productsDB = new IModel('products');
		if(isset($postData['_spec_array']))
		{
			//是否存在货品
			$proList = $productsDB->query('goods_id = '.$id,'id,products_no');

			//货号和ID关系
			$relationPro = array();
			if($proList)
			{
				foreach($proList as $key => $val)
				{
					$relationPro[$val['products_no']] = $val['id'];
				}
			}

			//创建货品信息
			$productIdArray = array();//post数据次序和ID关系
			foreach($postData['_goods_no'] as $key => $rs)
			{
				$productsData = array(
					'goods_id'     => $id,
					'products_no'  => IFilter::act($postData['_goods_no'][$key]),
					'store_nums'   => IFilter::act($postData['_store_nums'][$key],'int'),
					'market_price' => IFilter::act($postData['_market_price'][$key],'float'),
					'sell_price'   => IFilter::act($postData['_sell_price'][$key],'float'),
					'cost_price'   => IFilter::act($postData['_cost_price'][$key],'float'),
					'weight'       => IFilter::act($postData['_weight'][$key],'float'),
					'spec_array'   => "[".join(',',IFilter::act($this->specArraySort($postData['_spec_array'][$key])))."]"
				);
				$productsData['id'] = 'NULL';//新建货品
				if(isset($relationPro[$rs]))
				{
					$productsData['id'] = $relationPro[$rs];
					unset($relationPro[$rs]);
				}
				$productsDB->setData($productsData,'id');
				$productIdArray[$key] = $productsDB->replace();
			}
			//清理残余的货品
			if($relationPro)
			{
				$productsDB->del('id in ('.join(",",$relationPro).')');
			}
		}
		else
		{
			$productsDB->del('goods_id = '.$id);
		}

		//处理商品分类
		$categoryDB = new IModel('category_extend');
		$categoryDB->del('goods_id = '.$id);
		if(isset($postData['_goods_category']) && $postData['_goods_category'])
		{
			foreach($postData['_goods_category'] as $item)
			{
				$item = IFilter::act($item,'int');
				$categoryDB->setData(array('goods_id' => $id,'category_id' => $item));
				$categoryDB->add();
			}
		}

		//处理商家站内商品分类
		if($this->seller_id)
		{
			$categorySellerDB = new IModel('category_extend_seller');
			$categorySellerDB->del('goods_id = '.$id);

			if(isset($postData['_goods_category_seller']) && $postData['_goods_category_seller'])
			{
				foreach($postData['_goods_category_seller'] as $item)
				{
					$item = IFilter::act($item,'int');
					$categorySellerDB->setData(array('goods_id' => $id,'category_id' => $item));
					$categorySellerDB->add();
				}
			}
		}

		//处理商品促销
		$commendDB = new IModel('commend_goods');
		$commendDB->del('goods_id = '.$id);
		if(isset($postData['_goods_commend']) && $postData['_goods_commend'])
		{
			foreach($postData['_goods_commend'] as $item)
			{
				$item = IFilter::act($item,'int');
				$commendDB->setData(array('goods_id' => $id,'commend_id' => $item));
				$commendDB->add();
			}
		}

		//处理商品关键词
		keywords::add($goodsUpdateData['search_words']);

		//处理商品图片
		$photoRelationDB = new IModel('goods_photo_relation');
		$photoRelationDB->del('goods_id = '.$id);
		if(isset($postData['_imgList']) && $postData['_imgList'])
		{
			$photoDB = new IModel('goods_photo');
			foreach($postData['_imgList'] as $key => $val)
			{
				$val = IFilter::act($val);
				$photoPic = $photoDB->getObj('img = "'.$val.'"','id');
				if($photoPic)
				{
					$photoRelationDB->setData(array('goods_id' => $id,'photo_id' => $photoPic['id']));
					$photoRelationDB->add();
				}
			}
		}

		//处理会员组的价格
		$groupPriceDB = new IModel('group_price');
		$groupPriceDB->del('goods_id = '.$id);
		if(isset($productIdArray) && $productIdArray)
		{
			foreach($productIdArray as $index => $value)
			{
				if(isset($postData['_groupPrice'][$index]) && $postData['_groupPrice'][$index])
				{
					$temp = JSON::decode($postData['_groupPrice'][$index]);
					foreach($temp as $k => $v)
					{
						$groupPriceDB->setData(array(
							'goods_id'   => $id,
							'product_id' => IFilter::act($value,'int'),
							'group_id'   => IFilter::act($k,'int'),
							'price'      => IFilter::act($v,'float'),
						));
						$groupPriceDB->add();
					}
				}
			}
		}
		else
		{
			if(isset($postData['_groupPrice'][0]) && $postData['_groupPrice'][0])
			{
				$temp = JSON::decode($postData['_groupPrice'][0]);
				foreach($temp as $k => $v)
				{
					$groupPriceDB->setData(array(
						'goods_id' => $id,
						'group_id' => IFilter::act($k,'int'),
						'price'    => IFilter::act($v,'float'),
					));
					$groupPriceDB->add();
				}
			}
		}

		return $id;
	}

    /*
     * 虚拟商品处理
     * @param $id 商品id
     * @param $postData post数据
     * @param $isInsert 是否新建
     */
	public function updateGoodsType($id,$postData,$isInsert)
	{
        switch ($postData['type'])
        {
            case 'default':
            {

            }
            break;

            case 'code':
            {

            }
            break;

            case 'download':
            {
                $goodsExtendDownloadDB = new IModel('goods_extend_download');
                $data = array(
                    'end_time'  => IFilter::act($postData['download']['end_time'],'date'),
                    'limit_num' => IFilter::act($postData['download']['limit_num'],'int'),
                );

                //处理上传
                $uploadInstance = new IUpload(100000,array('zip'));
                $uploadDir      = IWeb::$app->config['upload'].'/download/'.date('Ymd');
                $uploadInstance->setDir($uploadDir);
                $uploadResult   = $uploadInstance->execute();

                if(isset($uploadResult['download']['file']))
                {
                    $result = $uploadResult['download']['file'];
                    if($result['flag'] == 1)
                    {
                        $data['url'] = stripos($result['fileSrc'],"http") === 0 ? $result['fileSrc'] : $result['fileSrc'];
                    }
                    else if($result['flag'] == -4)
                    {
                        if($isInsert == true)
                        {
                            $goodsExtendDownloadDB->rollback();
                            IError::show(403,"下载类商品的附件必须上传");
                        }
                    }
                    else
                    {
                        $goodsExtendDownloadDB->rollback();
                        IError::show(403,$result['error']);
                    }
                }

                if($isInsert == true)
                {
                    $data['seller_id'] = $this->seller_id ? $this->seller_id : 0;
                    $data['goods_id']  = $id;
                    $goodsExtendDownloadDB->setData($data);
                    $res = $goodsExtendDownloadDB->add();
                }
                else
                {
                    $goodsExtendDownloadDB->setData($data);
                    $where = 'goods_id = '.$id;
                    if($this->seller_id)
                    {
                        $where .= ' and seller_id = '.$this->seller_id;
                    }
                    $res = $goodsExtendDownloadDB->update($where);
                }

                if($res === false)
                {
                    $goodsExtendDownloadDB->rollback();
                    IError::show(403,'资料写入失败');
                }
            }
            break;

            case 'preorder':
            {

            }
            break;
        }
    }

	/**
	* @brief 删除与商品相关表中的数据
	*/
	public function del($goods_id)
	{
		$goodsWhere = " id = '{$goods_id}' ";
		if($this->seller_id)
		{
			$goodsWhere .= " and seller_id = ".$this->seller_id;
		}

		//读取商品图片关系准备删除商品相册
		$tb_photo_relation = new IModel('goods_photo_relation');
		$photoMD5Data      = $tb_photo_relation->query('goods_id = '.$goods_id);

		$tb_photo          = new IModel('goods_photo');
		foreach($photoMD5Data as $key => $md5)
		{
			//图片是否被其他商品共享占用
			$isUserd = $tb_photo_relation->getObj('photo_id = "'.$md5['photo_id'].'" and goods_id != '.$goods_id);
			if(!$isUserd)
			{
			    //删除商品相册图片
				$imgData = $tb_photo->getObj('id = "'.$md5['photo_id'].'"');
				isset($imgData['img']) ? IFile::unlink($imgData['img']) : "";
				$tb_photo->del('id = "'.$md5['photo_id'].'"');
			}
		}
		$tb_photo_relation->del('goods_id = '.$goods_id);

		//删除商品详情内图片
		$tb_goods = new IModel('goods');
		$goodsRow = $tb_goods->getObj($goodsWhere,"content");
		if(isset($goodsRow['content']) && $goodsRow['content'])
		{
			preg_match_all('%src="(/.*?(?:.jpg|.png|.gif))"%i',$goodsRow['content'],$result);
			if($result && isset($result[1]) && is_array($result[1]))
			{
				foreach($result[1] as $detailPic)
				{
					$detailPic = IWeb::$app->getBasePath().stristr($detailPic,IWeb::$app->config['upload']);
					is_file($detailPic) ? IFile::unlink($detailPic) : "";
				}
			}
		}

        //删除与商品相关的操作表
		$goodsExtTable = array("category_extend_seller","category_extend","commend_goods","refer","products","notify_registry","group_price","goods_attribute","discussion","comment","favorite","goods_rate","goods_extend_download","goods_extend_preorder_discount","goods_extend_preorder_disnums");
		foreach($goodsExtTable as $tableName)
		{
			$tableDB = new IModel($tableName);
			$tableDB->del('goods_id = '.$goods_id);
		}

        //删除商品表信息
		$tb_goods->del($goodsWhere);
	}
	/**
	 * 获取编辑商品所有数据
	 * @param int $id 商品ID
	 */
	public function edit($id)
	{
        $id     = IFilter::act($id,'int');
		$goodsWhere = " id = {$id} ";
		if($this->seller_id)
		{
			$goodsWhere .= " and seller_id = ".$this->seller_id;
		}

		//获取商品
		$obj_goods = new IModel('goods');
		$goods_info = $obj_goods->getObj($goodsWhere);
		if(!$goods_info)
		{
            return null;
		}

		//获取商品的会员价格
		$groupPriceDB = new IModel('group_price');
		$goodsPrice   = $groupPriceDB->query("goods_id = ".$goods_info['id']." and product_id is NULL ");
		$temp = array();
		foreach($goodsPrice as $key => $val)
		{
			$temp[$val['group_id']] = $val['price'];
		}
		$goods_info['groupPrice'] = $temp ? JSON::encode($temp) : '';

		//赋值到FORM用于渲染
		$data = array('form' => $goods_info);

		//获取货品
		$productObj = new IModel('products');
		$product_info = $productObj->query('goods_id = '.$id,"*","id asc");
		if($product_info)
		{
			//获取货品会员价格
			foreach($product_info as $k => $rs)
			{
				$temp = array();
				$productPrice = $groupPriceDB->query('product_id = '.$rs['id']);
				foreach($productPrice as $key => $val)
				{
					$temp[$val['group_id']] = $val['price'];
				}
				$product_info[$k]['groupPrice'] = $temp ? JSON::encode($temp) : '';
			}
			$data['product'] = $product_info;
		}

		//加载推荐类型
		$tb_commend_goods = new IModel('commend_goods');
		$commend_goods = $tb_commend_goods->query('goods_id='.$goods_info['id'],'commend_id');
		if($commend_goods)
		{
			foreach($commend_goods as $value)
			{
				$data['goods_commend'][] = $value['commend_id'];
			}
		}

		//相册
		$tb_goods_photo = new IQuery('goods_photo_relation as ghr');
		$tb_goods_photo->join = 'left join goods_photo as gh on ghr.photo_id=gh.id';
		$tb_goods_photo->fields = 'gh.img';
		$tb_goods_photo->where = 'ghr.goods_id='.$goods_info['id'];
		$tb_goods_photo->order = 'ghr.id asc';
		$data['goods_photo'] = $tb_goods_photo->find();

		//扩展基本属性
		$goodsAttr = new IQuery('goods_attribute');
		$goodsAttr->where = "goods_id=".$goods_info['id']." and attribute_id != '' ";
		$attrInfo = $goodsAttr->find();
		if($attrInfo)
		{
			foreach($attrInfo as $item)
			{
				//key：属性名；val：属性值,多个属性值以","分割
				$data['goods_attr'][$item['attribute_id']] = $item['attribute_value'];
			}
		}

		//商品分类
		$categoryExtend = new IQuery('category_extend');
		$categoryExtend->where = 'goods_id = '.$goods_info['id'];
		$categoryExtend->fields = 'category_id';
		$cateData = $categoryExtend->find();
		if($cateData)
		{
			foreach($cateData as $item)
			{
				$data['goods_category'][] = $item['category_id'];
			}
		}

		//商家店内分类
		if($this->seller_id)
		{
			$categoryExtend = new IQuery('category_extend_seller');
			$categoryExtend->where  = 'goods_id = '.$goods_info['id'];
			$categoryExtend->fields = 'category_id';
			$cateData = $categoryExtend->find();
			if($cateData)
			{
				foreach($cateData as $item)
				{
					$data['goods_category_seller'][] = $item['category_id'];
				}
			}
		}

		//虚拟商品
		switch ($goods_info['type'])
        {
            case 'default':
            {

            }
            break;

            case 'code':
            {

            }
            break;

            case 'download':
            {
                $goodsExtendDownloadDB = new IModel('goods_extend_download');
                $where = 'goods_id = '.$id;
                if($this->seller_id)
                {
                    $where .= ' and seller_id = '.$this->seller_id;
                }
                $goodsExtendDownloadRow = $goodsExtendDownloadDB->getObj($where,'url,end_time,limit_num');
                if($goodsExtendDownloadRow)
                {
                    $data['form']['download[file]']      = $goodsExtendDownloadRow['url'];
                    $data['form']['download[end_time]']  = $goodsExtendDownloadRow['end_time'];
                    $data['form']['download[limit_num]'] = $goodsExtendDownloadRow['limit_num'];
                }
            }
            break;
        }

		return $data;
	}
	/**
	 * @param
	 * @return array
	 * @brief 无限极分类递归函数
	 */
	public static function sortdata($catArray, $id = 0 , $prefix = '')
	{
		static $formatCat = array();
		static $floor     = 0;

		foreach($catArray as $key => $val)
		{
			if($val['parent_id'] == $id)
			{
				$str         = self::nstr($prefix,$floor);
				$val['name'] = $str.$val['name'];

				$val['floor'] = $floor;
				$formatCat[]  = $val;

				unset($catArray[$key]);

				$floor++;
				self::sortdata($catArray, $val['id'] ,$prefix);
				$floor--;
			}
		}
		return $formatCat;
	}

	/**
	 * @brief 根据商品分类的父类ID进行数据归类
	 * @param array $categoryData 商品category表的结构数组
	 * @return array
	 */
	public static function categoryParentStruct($categoryData)
	{
		$result = array();
		foreach($categoryData as $key => $val)
		{
			if(isset($result[$val['parent_id']]) && is_array($result[$val['parent_id']]))
			{
				$result[$val['parent_id']][] = $val;
			}
			else
			{
				$result[$val['parent_id']] = array($val);
			}
		}
		return $result;
	}

	/**
	 * @brief 计算商品的价格区间
	 * @param $min          最小价格
	 * @param $max          最大价格
	 * @param $showPriceNum 展示分组最大数量
	 * @return array        价格区间分组
	 */
	public static function getGoodsPrice($min,$max,$showPriceNum = 5)
	{
		$goodsPrice = array("min" => $min,"max" => $max);
		if($goodsPrice['min'] == null && $goodsPrice['max'] == null)
		{
			return array();
		}

		//商品价格计算
		$perPrice = ceil(($goodsPrice['max'] - $goodsPrice['min'])/$showPriceNum);
		$result   = array();
		if($perPrice > 0)
		{
			$result    = array('0-'.$perPrice);
			$stepPrice = $perPrice;
			for($addPrice = $stepPrice+1; $addPrice < $goodsPrice['max'];)
			{
				if(count($result) == $showPriceNum)
				{
					break;
				}
				$stepPrice = $addPrice + $perPrice;
				$stepPrice = substr(intval($stepPrice),0,1).str_repeat('9',(strlen(intval($stepPrice)) - 1));
				$result[]  = $addPrice.'-'.$stepPrice;
				$addPrice  = $stepPrice + 1;
			}
			//置换max价格
			$result[count($result)-1] = str_replace("-".$stepPrice,"-".ceil($goodsPrice['max']),$result[count($result)-1]);
		}
		return $result;
	}

	//处理商品列表显示缩进
	public static function nstr($str,$num=0)
	{
		$return = '';
		for($i=0;$i<$num;$i++)
		{
			$return .= $str;
		}
		return $return;
	}

	/**
	 * @brief  根据分类ID获取其全部父分类数据(自下向上的获取数据)
	 * @param  int   $catId  分类ID
	 * @return array $result array(array(父分类1_ID => 父分类2_NAME),....array(子分类ID => 子分类NAME))
	 */
	public static function catRecursion($catId)
	{
		$result = array();
		$catDB  = new IModel('category');
		$catRow = $catDB->getObj("id = '{$catId}'");
		while(true)
		{
			if($catRow)
			{
				array_unshift($result,array('id' => $catRow['id'],'name' => $catRow['name']));
				$catRow = $catDB->getObj('id = '.$catRow['parent_id']);
			}
			else
			{
				break;
			}
		}
		return $result;
	}

	/**
	 * @brief 获取子分类可以无限递归获取子分类
	 * @param int $catId 分类ID
	 * @param int $level 层级数
	 * @return string 所有分类的ID拼接字符串
	 */
	public static function catChild($catId,$level = 1)
	{
		if($level == 0)
		{
			return $catId;
		}

		$temp   = array();
		$result = array($catId);
		$catDB  = new IModel('category');

		while(true)
		{
			$id = current($result);
			if(!$id)
			{
				break;
			}
			$temp = $catDB->query('parent_id = '.$id);
			foreach($temp as $key => $val)
			{
				if(!in_array($val['id'],$result))
				{
					$result[] = $val['id'];
				}
			}
			next($result);
		}
		return join(',',$result);
	}

	/**
	 * @brief 返回商品状态
	 * @param int $is_del 商品状态
	 * @return string 状态文字
	 */
	public static function statusText($is_del)
	{
		$data = array('0' => '上架','1' => '删除','2' => '下架','3' => '等审');
		return isset($data[$is_del]) ? $data[$is_del] : '';
	}

	public static function getGoodsCategory($goods_id)
	{
		$gcQuery         = new IQuery('category_extend as ce');
		$gcQuery->join   = "left join category as c on c.id = ce.category_id";
		$gcQuery->where  = "ce.goods_id = '{$goods_id}'";
		$gcQuery->fields = 'c.name';

		$gcList = $gcQuery->find();
		$strCategoryNames = [];
		foreach($gcList as $val)
		{
			$strCategoryNames[] = $val['name'];
		}
		unset($gcQuery,$gcList);
		return $strCategoryNames;
	}

	/**
	 * @brief 返回检索条件相关信息
	 * @param int $search 条件数组
	 * @return array 查询条件（$join,$where）数据组
	 */
	public static function getSearchCondition($search)
	{
		$join  = array();
		$where = array();

		if(isset($search['type']) && isset($search['content']) && $search['content'])
		{
			switch($search['type'])
			{
				case "goods_no":
				{
					$productDB = new IModel('products');
					$productRow= $productDB->getObj('products_no = "'.$search['content'].'"');
					if($productRow)
					{
						$where[] = "go.id = ".$productRow['goods_id'];
					}
					else
					{
						$where[] = "go.goods_no = '".$search['content']."'";
					}
				}
				break;

				case "true_name":
				{
					$sellerDB = new IModel('seller');
					$sellerRow= $sellerDB->getObj('true_name like "%'.$search['content'].'%"');
					$seller_id= isset($sellerRow['id']) ? $sellerRow['id'] : "NULL";
					$where[]  = "go.seller_id = ".$seller_id;
				}
				break;

				default:
				{
					$where[] = "go.name like '%".$search['content']."%'";
				}
			}
		}

        //根据当前商家ID获取商品信息
		if(IWeb::$app->getController()->seller)
		{
			$seller_id = IWeb::$app->getController()->seller['seller_id'];
			$where[] = "go.seller_id = ".$seller_id;
		}

		if(isset($search['category_id']) && $search['category_id'])
		{
			$category_id = IFilter::act($search['category_id'],'int');
			$join[]  = "left join category_extend as ce on ce.goods_id = go.id";
			$where[] = "ce.category_id = ".$category_id;
		}

		if(isset($search['is_del']) && $search['is_del'] !== '')
		{
			$is_del  = IFilter::act($search['is_del'],'int');
			$where[] = "go.is_del = ".$is_del;
		}
		else
		{
			$where[] = "go.is_del != 1";
		}

		if(isset($search['store_nums']) && $search['store_nums'] !== '')
		{
			if(is_numeric($search['store_nums']))
			{
				$where[] = "go.store_nums <= ".$search['store_nums'];
			}
			else if(stripos($search['store_nums'],'-') !== false)
			{
				$storeArray = explode("-",$search['store_nums']);
				$storeArray = IFilter::act($storeArray,'int');
				$where[]    = "go.store_nums between ".$storeArray[0]." and ".$storeArray[1];
			}
			else if(stripos($search['store_nums'],'+') !== false)
			{
				$store_nums = trim($search['store_nums'],"+");
				$store_nums = IFilter::act($store_nums,'int');
				$where[] = "go.store_nums > ".$store_nums;
			}
		}

		if(isset($search['commend_id']) && $search['commend_id'])
		{
			$commend_id = IFilter::act($search['commend_id'],'int');
			$join[]     = "left join commend_goods as cg on go.id = cg.goods_id";
			$where[]    = "cg.commend_id = ".$commend_id;
		}

		if(isset($search['is_seller']) && $search['is_seller'])
		{
			$where[] = $search['is_seller'] == 'yes' ? "go.seller_id > 0" : "go.seller_id = 0";
		}

		if(isset($search['brand_id']) && $search['brand_id'])
		{
			$brand_id = IFilter::act($search['brand_id'],'int');
			$where[]  = "go.brand_id = ".$brand_id;
		}

		if(isset($search['seller_price_down']) && $search['seller_price_down'])
		{
			$seller_price_down = IFilter::act($search['seller_price_down'], 'float');
			$where[] = "go.sell_price >= ".$seller_price_down;
		}

		if(isset($search['seller_price_up']) && $search['seller_price_up'])
		{
			$seller_price_up = IFilter::act($search['seller_price_up'], 'float');
			$where[] = "go.sell_price <= ".$seller_price_up;
		}

		if(isset($search['create_time_start']) && $search['create_time_start'])
		{
			$create_time_start = IFilter::act($search['create_time_start'], 'date');
			$where[] = "go.create_time >= '".$create_time_start."'";
		}

		if(isset($search['create_time_end']) && $search['create_time_end'])
		{
			$create_time_end = IFilter::act($search['create_time_end'], 'date');
			$where[] = "go.create_time <= '".$create_time_end." 23:59:59'";
		}
		$results = array(join("  ",$join),join(" and ",$where));
		unset($join,$where);
		return $results;
	}

	/**
	 * @brief 检查商品或者货品的库存是否充足
	 * @param $buy_num 检查数量
	 * @param $goods_id 商品id
	 * @param $product_id 货品id
	 * @result array() true:满足数量; false:不满足数量
	 */
	public static function checkStore($buy_num,$goods_id,$product_id = 0)
	{
		$data = $product_id ? Api::run('getProductInfo',array('#id#',$product_id)) : Api::run('getGoodsInfo',array('#id#',$goods_id));

		//库存判断
		if(!$data || $buy_num <= 0 || $buy_num > $data['store_nums'])
		{
			return false;
		}
		return true;
	}

	/**
	 * @brief 商品根据折扣更新价格
	 * @param string or int $goods_id 商品id
	 * @param float $discount 折扣
	 * @param string $discountType 打折的类型： percent 百分比, constant 常数
	 * @param string reduce or add 减少或者增加
	 */
	public static function goodsDiscount($goods_id,$discount,$discountType = "percent",$type = "reduce")
	{
		//减少
		if($type == "reduce")
		{
			if($discountType == "percent")
			{
				$updateData = array("sell_price" => "sell_price * ".$discount/100);
			}
			else
			{
				$updateData = array("sell_price" => "sell_price - ".$discount);
			}
		}
		//增加
		else
		{
			if($discountType == "percent")
			{
				$updateData = array("sell_price" => "sell_price / ".$discount/100);
			}
			else
			{
				$updateData = array("sell_price" => "sell_price + ".$discount);
			}
		}

		//更新商品
		$goodsDB = new IModel('goods');
		$goodsDB->setData($updateData);
		$goodsDB->update("id in (".$goods_id.")","sell_price");

		//更新货品
		$productDB = new IModel('products');
		$productDB->setData($updateData);
		$productDB->update("goods_id in (".$goods_id.")","sell_price");
	}

	/**
	 * @brief 批量修改商品数据
	 * @param array $idArray 商品ID数组
	 * @param array $paramData 商品设置数据
	 */
	public function multiUpdate($idArray,$paramData)
	{
		$goods_id   = implode(",", $idArray);
		$updateData = array();

		// 所属商户(只有管理员才可以设置)
		if ($this->seller_id == 0 && isset($paramData['sellerid']) && '-1' != $paramData['sellerid'])
		{
			$updateData['seller_id'] = IFilter::act($paramData['sellerid'], 'int');
		}
		// 市场价格
		$market_price = isset($paramData['market_price']) ? IFilter::act($paramData['market_price'], 'float') : 0;
		if (0 < $market_price)
		{
			$market_price_operator = $this->getOperator($paramData['market_price_type']);
			$market_price_unit = isset($paramData['market_price_unit']) ? IFilter::act($paramData['market_price_unit'], 'int') : 0;
			switch ($market_price_unit)
			{
			    case '0':
			        // 数字
			        $updateData['market_price'] = "market_price".$market_price_operator.$market_price;
			        break;
			    case '1':
			        // 百分比
			        $updateData['market_price'] = "market_price * (100".$market_price_operator.$market_price.") * 0.01";
			        break;
			}
		}
		// 销售价格
		$sell_price = isset($paramData['sell_price']) ? IFilter::act($paramData['sell_price'], 'float') : 0;
		if (0 < $sell_price)
		{
			$sell_price_operator = $this->getOperator($paramData['sell_price_type']);
			$sell_price_unit = isset($paramData['sell_price_unit']) ? IFilter::act($paramData['sell_price_unit'], 'int') : 0;
			switch ($sell_price_unit)
			{
			    case '0':
			        // 数字
			        $updateData['sell_price'] = "sell_price".$sell_price_operator.$sell_price;
			        break;
			    case '1':
			        // 百分比
			        $updateData['sell_price'] = "sell_price * (100".$sell_price_operator.$sell_price.") * 0.01";
			        break;
			}
		}
		// 成本价格
		$cost_price = isset($paramData['cost_price']) ? IFilter::act($paramData['cost_price'], 'float') : 0;
		if (0 < $cost_price)
		{
			$cost_price_operator = $this->getOperator($paramData['cost_price_type']);
			$cost_price_unit = isset($paramData['cost_price_unit']) ? IFilter::act($paramData['cost_price_unit'], 'int') : 0;
			switch ($cost_price_unit)
			{
			    case '0':
			        // 数字
			        $updateData['cost_price'] = "cost_price".$cost_price_operator.$cost_price;
			        break;
			    case '1':
			        // 百分比
			        $updateData['cost_price'] = "cost_price * (100".$cost_price_operator.$cost_price.") * 0.01";
			        break;
			}
		}
		// 库存
		$store_nums = isset($paramData['store_nums']) ? IFilter::act($paramData['store_nums'], 'int') : 0;
		if (0 < $store_nums)
		{
			$store_nums_operator = $this->getOperator($paramData['store_nums_type']);
			$store_nums_unit = isset($paramData['store_nums_unit']) ? IFilter::act($paramData['store_nums_unit'], 'int') : 0;
			switch ($store_nums_unit)
			{
			    case '0':
			        // 数字
			        $updateData['store_nums'] = "store_nums".$store_nums_operator.$store_nums;
			        break;
			    case '1':
			        // 百分比，返回不大于X的最大整数值
			        $updateData['store_nums'] = "FLOOR(store_nums * (100".$store_nums_operator.$store_nums.") * 0.01)";
			        break;
			}
		}
		// 积分
		$point = isset($paramData['point']) ? IFilter::act($paramData['point'], 'int') : 0;
		if (0 < $point)
		{
		    $point_operator = $this->getOperator($paramData['point_type']);
		    $point_unit = isset($paramData['point_unit']) ? IFilter::act($paramData['point_unit'], 'int') : 0;
		    switch ($point_unit)
		    {
		        case '0':
		            // 数字
		            $updateData['point'] = "point".$point_operator.$point;
		            break;
		        case '1':
		            // 百分比，返回不大于X的最大整数值
		            $updateData['point'] = "FLOOR(point * (100".$point_operator.$point.") * 0.01)";
		            break;
		    }
		}
		// 经验
		$exp = isset($paramData['exp']) ? IFilter::act($paramData['exp'], 'int') : 0;
		if (0 < $exp)
		{
			$exp_operator = $this->getOperator($paramData['exp_type']);
			$exp_unit = isset($paramData['exp_unit']) ? IFilter::act($paramData['exp_unit'], 'int') : 0;
			switch ($exp_unit)
			{
			    case '0':
			        // 数字
			        $updateData['exp'] = "exp".$exp_operator.$exp;
			        break;
			    case '1':
			        // 百分比，返回不大于X的最大整数值
			        $updateData['exp'] = "FLOOR(exp * (100".$exp_operator.$exp.") * 0.01)";
			        break;
			}
		}
		// 商品品牌
		if ('-1' != $paramData['brand_id'])
		{
			$updateData['brand_id'] = IFilter::act($paramData['brand_id'], 'int');
		}

		// 批量更新商品
		if ($updateData)
		{
			$except = array('market_price','sell_price','cost_price','store_nums','point','exp');
			$goodsDB = new IModel('goods');
			$goodsDB->setData($updateData);
			$where = "id in (".$goods_id.")";
			$where.= $this->seller_id ? " and seller_id = ".$this->seller_id : "";
			$result = $goodsDB->update($where,$except);

			// 批量更新货品表
			$exceptProducts = array('store_nums','market_price','sell_price','cost_price');
			$updateDataProducts = array();
			foreach ($updateData as $key => $value)
			{
				if (in_array($key, $exceptProducts))
				{
					$updateDataProducts[$key] = $value;
				}
			}
			if (0 < count($updateDataProducts))
			{
				$productsDB = new IModel('products');
				$productsDB->setData($updateDataProducts);
				$whereProducts = "goods_id in (".$goods_id.")";
				$resultProducts = $productsDB->update($whereProducts, $exceptProducts);

				$productObj = new IQuery('products as pro');
				$productObj->where = $whereProducts;
				$productObj->fields = "pro.goods_id, min(pro.store_nums) AS min_store_nums, min(pro.market_price) as min_market_price, min(pro.sell_price) as min_sell_price, min(pro.cost_price) as min_cost_price";
				$productObj->group = "pro.goods_id";

				$productList = $productObj->find();

				foreach ($productList as $key => $val)
				{
					$tempData = array(
						'store_nums' => $val['min_store_nums'],
						'market_price' => $val['min_market_price'],
						'sell_price' => $val['min_sell_price'],
						'cost_price' => $val['min_cost_price']
					);
					$goodsDB->setData($tempData);
					$tempWhere = "id=".$val['goods_id']."";
					$tempResult = $goodsDB->update($tempWhere);
				}
			}
		}

		// 商品分类
		if (isset($paramData['category']) && $paramData['category'])
		{
			$categoryDB = new IModel('category_extend');
			$categoryDB->del('goods_id in ('.$goods_id.')');
			$categoryArray = IFilter::act($paramData['category'], 'int');
			foreach ($idArray as $gid)
			{
				foreach ($categoryArray as $category_id)
				{
					$categoryDB->setData(array('goods_id' => $gid, 'category_id' => $category_id));
					$categoryDB->add();
				}
			}
		}

		// 店内分类
		if(isset($paramData['_goods_category_seller']) && $paramData['_goods_category_seller'])
		{
			$categoryDB = new IModel('category_extend_seller');
			$categoryDB->del('goods_id in ('.$goods_id.')');
			$categoryArray = IFilter::act($paramData['_goods_category_seller'], 'int');
			foreach ($idArray as $gid)
			{
				foreach ($categoryArray as $category_id)
				{
					$categoryDB->setData(array('goods_id' => $gid, 'category_id' => $category_id));
					$categoryDB->add();
				}
			}
		}
		return true;
	}

	/**
	 * @brief 获取运算符号
	 * @param string $type 	运算类型 1-增加 2-减少
	 * @return string 		运算符号
	 */
	protected function getOperator($type)
	{
		return '2'==$type ? '-' : '+';
	}

	//货品products表spec_array排序
	public function specArraySort($specArray)
	{
		foreach($specArray as $key => $value)
		{
			$value        = JSON::decode($value);
			$temp         = array(
				'id'    => $value['id'],
				'value' => $value['value'],
				'name'  => $value['name'],
				'image'   => $value['image'],
			);
			$specArray[$key] = JSON::encode($temp);
		}
		return $specArray;
	}

    //[公共方法]通过解析products,goods表中的spec_array转化为格式：key:规格名称;value:规格值
    public static function show_spec($specJson,$param = array())
    {
    	$specArray = JSON::decode($specJson);
    	$spec      = array();
    	if($specArray)
    	{
    		$imgSize = isset($param['size']) ? $param['size'] : 25;
	    	foreach($specArray as $val)
	    	{
	    		//goods表规格数据
	    		if(is_array($val['value']))
	    		{
	    			foreach($val['value'] as $sval)
	    			{
	    				if(!isset($spec[$val['name']]))
	    				{
	    					$spec[$val['name']] = [];
	    				}

	    				list($value,$image)   = [key($sval),current($sval)];
	    				$spec[$val['name']][] = $image ? '<img src="'.IUrl::creatUrl($image).'" style="border: 1px solid #ddd;width:'.$imgSize.'px;height:'.$imgSize.'px;" title="'.$value.'" />' : $value;
	    			}
	    			$spec[$val['name']] = join("&nbsp;&nbsp;",$spec[$val['name']]);
	    		}
	    		//products表规格数据
	    		else
	    		{
	    		    $spec[$val['name']] = isset($val['image']) && $val['image'] ? '<img src="'.IUrl::creatUrl($val['image']).'" style="border: 1px solid #ddd;width:'.$imgSize.'px;height:'.$imgSize.'px;" title="'.$val['value'].'" />' : $val['value'];
	    		}
	    	}
    	}
    	return $spec;
    }

    //是否需要收货信息
    public static function isDelivery($type)
    {
        if(in_array($type,['default']))
        {
            return true;
        }
        return false;
    }

    //获取预订时间段内天数列表
    public static function preorderListDay($goods_id,$start_date,$end_date)
    {
        $goodsDB = new IModel('goods');
        $goodsRow= $goodsDB->getObj($goods_id,'config');
        $pay_date_mode = 0;
        if($goodsRow && isset($goodsRow['config']) && $goodsRow['config'])
        {
            $config = JSON::decode($goodsRow['config']);
            if($config && isset($config['pay_date_mode']))
            {
                $pay_date_mode = $config['pay_date_mode'];
            }
        }
        return ITime::listDay($start_date,$end_date,$pay_date_mode);
    }

    /**
     * @biref 获取预订时间段内价格
     * @param int $id 商品或者货品ID
     * @param string $type goods,product
     * @param array $listDay 日期段数据
     * @return array
     */
    public static function preorderPrice($id,$type,$listDay)
    {
        $result = [];

        if($type == 'goods')
        {
    	    $goodsDB = new IModel('goods');
    	    $dataRow = $goodsDB->getObj($id);
    	    $colsName= 'goods_id';
        }
        else
        {
    	    $goodsDB = new IModel('products');
    	    $dataRow = $goodsDB->getObj($id);
    	    $colsName= 'product_id';
        }

        //建立价格区间浮动
        $discountDayPool = [];
        $discountDB = new IModel('goods_extend_preorder_discount');
        $discountData = $discountDB->query($colsName.'='.$id.' and end_date >= NOW()');
        foreach($discountData as $item)
        {
            $discountTmpDay = ITime::listDay($item['start_date'],$item['end_date']);
            foreach($discountTmpDay as $day)
            {
                $discountDayPool[$day] = $item['discount'];
            }
        }

        foreach($listDay as $day)
        {
            $price = $dataRow['sell_price'];
            if(isset($discountDayPool[$day]) && $discountDayPool[$day] > 0)
            {
                $price = $discountDayPool[$day];
            }
            $result[$day] = $price;
        }

        return $result;
    }

    /**
     * @biref 获取预订时间段内价格
     * @param int $id 商品或者货品ID
     * @param string $type goods,product
     * @param int $num 预订数量
     * @param array $listDay 日期段数据
     * @return array
     */
    public static function preorderNums($id,$type,$num,$listDay)
    {
        $result = [];

        if($type == 'goods')
        {
    	    $goodsDB = new IModel('goods');
    	    $dataRow = $goodsDB->getObj($id);
    	    $colsName= 'goods_id';
        }
        else
        {
    	    $goodsDB = new IModel('products');
    	    $dataRow = $goodsDB->getObj($id);
    	    $colsName= 'product_id';
        }

        //库存占用区间【线下】
        $disnumsDayPool = [];
        $disnumsDB = new IModel('goods_extend_preorder_disnums');
        $disnumsData = $disnumsDB->query($colsName.'='.$id.' and end_date >= NOW()');
        foreach($disnumsData as $item)
        {
            $disnumsTmpDay = ITime::listDay($item['start_date'],$item['end_date']);
            foreach($disnumsTmpDay as $day)
            {
                $disnumsDayPool[$day] = $item['disnums'];
            }
        }

        //库存预订区间【线上】
        $preorderDB = new IModel('order_extend_preorder');
        $preorderData = $preorderDB->query($colsName.'='.$id.' and end_date >= NOW()');
        foreach($preorderData as $item)
        {
            $disnumsTmpDay = ITime::listDay($item['start_date'],$item['end_date']);
            foreach($disnumsTmpDay as $day)
            {
                if(isset($disnumsDayPool[$day]))
                {
                    $disnumsDayPool[$day] += $item['goods_nums'];
                }
                else
                {
                    $disnumsDayPool[$day] = $item['goods_nums'];
                }
            }
        }

        foreach($listDay as $day)
        {
            $maxStore = $dataRow['store_nums'];
            if(isset($disnumsDayPool[$day]))
            {
                $maxStore -= $disnumsDayPool[$day];
            }
            $result[$day] = $maxStore;
        }
        return $result;
    }
}