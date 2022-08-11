<?php
/**
 * @brief 商品模块
 * @class Goods
 * @note  后台
 */
class Goods extends IController implements adminAuthorization
{
	public $checkRight  = 'all';
    public $layout = 'admin';
    public $data = array();

	public function init()
	{

	}
	/**
	 * @brief 商品添加中图片上传的方法
	 */
	public function goods_img_upload()
	{
	 	//调用文件上传类
		$photoObj = new PhotoUpload();
		$result   = current($photoObj->run());
		echo JSON::encode($result);
	}
    /**
	 * @brief 商品模型添加/修改
	 */
    public function model_update()
    {
    	// 获取POST数据
    	$model_id   = IFilter::act(IReq::get("model_id"),'int');
    	$model_name = IFilter::act(IReq::get("model_name"));
    	$attribute  = IFilter::act(IReq::get("attr"));

    	//初始化Model类对象
		$modelObj = new Model();

		//更新模型数据
		$result = $modelObj->model_update($model_id,$model_name,$attribute);

		if($result)
		{
			$this->redirect('model_list');
		}
		else
		{
			//处理post数据，渲染到前台
    		$result = $modelObj->postArrayChange($attribute);
			$this->data = array(
				'id'         => $model_id,
				'name'       => $model_name,
				'model_attr' => $result['model_attr'],
			);
    		$this->setRenderData($this->data);
			$this->redirect('model_edit',false);
		}
    }
	/**
	 * @brief 商品模型修改
	 */
    public function model_edit()
    {
    	// 获取POST数据
    	$id = IFilter::act(IReq::get("id"),'int');
    	if($id)
    	{
    		//初始化Model类对象
    		$modelObj = new Model();
    		//获取模型详细信息
			$model_info = $modelObj->get_model_info($id);
			//向前台渲染数据
			$this->setRenderData($model_info);
    	}
		$this->redirect('model_edit');
    }

	/**
	 * @brief 商品模型删除
	 */
    public function model_del()
    {
    	//获取POST数据
    	$id = IFilter::act(IReq::get("id"),'int');
    	$id = !is_array($id) ? array($id) : $id;

    	if($id)
    	{
	    	foreach($id as $key => $val)
	    	{
	    		//初始化goods_attribute表类对象
	    		$goods_attrObj = new IModel("goods_attribute");

	    		//获取商品属性表中的该模型下的数量
	    		$attrData = $goods_attrObj->query("model_id = ".$val);
	    		if($attrData)
	    		{
	    			$this->redirect('model_list',false);
	    			Util::showMessage("无法删除此模型，请确认该模型下以及回收站内都无商品");
	    		}

	    		//初始化Model表类对象
	    		$modelObj = new IModel("model");

	    		//删除商品模型
				$result = $modelObj->del("id = ".$val);
	    	}
    	}
		$this->redirect('model_list');
    }

	/**
	 * @breif 后台添加为每一件商品添加会员价
	 * */
	function member_price()
	{
		$this->layout = '';

		$goods_id   = IFilter::act(IReq::get('goods_id'),'int');
		$product_id = IFilter::act(IReq::get('product_id'),'int');
		$sell_price = IFilter::act(IReq::get('sell_price'),'float');

		$date = array(
			'sell_price' => $sell_price
		);

		if($goods_id)
		{
			$where  = 'goods_id = '.$goods_id;
			$where .= $product_id ? ' and product_id = '.$product_id : '';

			$priceRelationObject = new IModel('group_price');
			$priceData = $priceRelationObject->query($where);
			$date['price_relation'] = $priceData;
		}

		$this->setRenderData($date);
		$this->redirect('member_price');
	}
	/**
	 * @brief 商品添加和修改视图
	 */
	public function goods_edit()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');

		//初始化数据
		$goods_class = new goods_class();

		//获取所有商品扩展相关数据
		$data = $goods_class->edit($goods_id);

		if($goods_id && !$data)
		{
			die("没有找到相关商品！");
		}

        if($data)
        {
            $data['type'] = $data['form']['type'];
        }
        else
        {
            $data = array('type' => IReq::get('type') ? IReq::get('type') : "default");
        }
		$this->setRenderData($data);
		$this->redirect('goods_edit');
	}
	/**
	 * @brief 保存修改商品信息
	 */
	function goods_update()
	{
		$id       = IFilter::act(IReq::get('id'),'int');
		$callback = IReq::get('callback');
		$callback = strpos($callback,'goods_list') === false ? '' : $callback;

		//检查表单提交状态
		if(!$_POST)
		{
			die('请确认表单提交正确');
		}

		if($saleRow = Active::isSale($id))
		{
			die('当前商品正处于【营销->特价】中的【'.$saleRow['name'].'】活动中，请先关闭或者删除活动后才能进行修改');
		}

		//初始化商品数据
		unset($_POST['id']);
		unset($_POST['callback']);

		$goodsObject = new goods_class();
		$goodsObject->update($id,$_POST);

		//记录日志
		$logObj = new log('db');
		$logData = $id ? ["管理员:".$this->admin['admin_name'],"修改商品信息","商品ID：".$id."，名称：".IFilter::act(IReq::get('name'))] : ["管理员:".$this->admin['admin_name'],"添加商品","名称：".IFilter::act(IReq::get('name'))];
		$logObj->write('operation',$logData);

		$callback ? $this->redirect($callback) : $this->redirect("goods_list");
	}

	/**
	 * @brief 删除商品
	 */
	function goods_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'),'int');

	    //生成goods对象
	    $tb_goods = new IModel('goods');
	    $tb_goods->setData(array('is_del'=>1));
	    if($id)
		{
			$tb_goods->update(Util::joinStr($id));
		}
		else
		{
			die('请选择要删除的数据');
		}
		$this->redirect("goods_list");
	}
	/**
	 * @brief 商品上下架
	 */
	function goods_stats()
	{
		//post数据
	    $id   = IFilter::act(IReq::get('id'),'int');
	    $type = IFilter::act(IReq::get('type'));

	    //生成goods对象
	    $tb_goods = new IModel('goods');
	    if($type == 'up')
	    {
	    	$updateData = array('is_del' => 0,'up_time' => ITime::getDateTime(),'down_time' => null);
	    }
	    else if($type == 'down')
	    {
	    	$updateData = array('is_del' => 2,'up_time' => null,'down_time' => ITime::getDateTime());
	    }
	    else if($type == 'check')
	    {
	    	$updateData = array('is_del' => 3,'up_time' => null,'down_time' => null);
	    }

	    $tb_goods->setData($updateData);

	    if($id)
		{
			$tb_goods->update(Util::joinStr($id));
		}
		else
		{
			Util::showMessage('请选择要操作的数据');
		}

		if(IClient::isAjax() == false)
		{
			$this->redirect("goods_list");
		}
	}
	/**
	 * @brief 商品彻底删除
	 * */
	function goods_recycle_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'),'int');

	    //生成goods对象
	    $goods = new goods_class();
	    if($id)
		{
			if(is_array($id))
			{
				foreach($id as $key => $val)
				{
					$goods->del($val);
				}
			}
			else
			{
				$goods->del($id);
			}
		}

		$this->redirect("goods_recycle_list");
	}
	/**
	 * @brief 商品还原
	 * */
	function goods_recycle_restore()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'),'int');
	    //生成goods对象
	    $tb_goods = new IModel('goods');
	    $tb_goods->setData(array('is_del'=>0));
	    if($id)
		{
			$tb_goods->update(Util::joinStr($id));
		}
		else
		{
			Util::showMessage('请选择要删除的数据');
		}
		$this->redirect("goods_recycle_list");
	}

	/**
	 * @brief 商品列表
	 */
	function goods_list()
	{
		//搜索条件
		$search = IFilter::act(IReq::get('search'));
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		//条件筛选处理
		list($join,$where) = goods_class::getSearchCondition($search);
		$searchString      = http_build_query(array('search' => $search));

		//拼接sql
		$goodsHandle = new IQuery('goods as go');
		$goodsHandle->order  = "go.id desc";
		$goodsHandle->fields = "distinct go.id,go.name,go.sell_price,go.market_price,go.store_nums,go.img,go.is_del,go.seller_id,go.is_share,go.sort,go.promo,go.type,go.spec_array";
		$goodsHandle->page   = $page;
		$goodsHandle->where  = $where;
		$goodsHandle->join   = $join;
		$this->goodsHandle   = $goodsHandle;
		$this->setRenderData(array('search' => $searchString));
		$this->redirect("goods_list");
	}

	//商品导出 Excel
	public function goods_report()
	{
		//搜索条件
		$search = IFilter::act(IReq::get('search'));
		//条件筛选处理
		list($join,$where) = goods_class::getSearchCondition($search);
		$join .= ' left join products as pro on go.id = pro.goods_id ';
		//拼接sql
		$goodsHandle = new IQuery('goods as go');
		$goodsHandle->order    = "go.id desc";
		$goodsHandle->fields   = "go.id,goods_no,go.name,go.sell_price,go.store_nums,go.sale,go.is_del,go.create_time,go.seller_id,pro.products_no,pro.store_nums as p_store_nums,pro.sell_price as p_sell_price,pro.spec_array";
		$goodsHandle->join     = $join;
		$goodsHandle->where    = $where;
		$goodsList = $goodsHandle->find();

		//构建 Excel table;
		$reportObj = new report('goods');
        $catMax    = 1;
		foreach($goodsList as $k => $val)
		{
		    $catArray = goods_class::getGoodsCategory($val['id']);
		    $itemCount= count($catArray);
		    if($itemCount > $catMax)
		    {
		        $catMax = $itemCount;
		    }

			//处理规格
			$specArray = [];
			if($val['spec_array'])
			{
				$tempSpec = JSON::decode($val['spec_array']);
				foreach($tempSpec as $ss)
				{
					$specArray[] = $ss['name'].':'.$ss['value'];
				}
			}

			$insertData = [
				$val['name'],
				$val['products_no']  ? $val['products_no']  : $val['goods_no'],
				join(',',$specArray),
				$val['p_sell_price'] ? $val['p_sell_price'] : $val['sell_price'],
				$val['p_store_nums'] ? $val['p_store_nums'] : $val['store_nums'],
				$val['sale'],
				$val['create_time'],
				goods_class::statusText($val['is_del']),
				$val['seller_id'] ? "商家" : "自营",
			];
			$insertData = array_merge($insertData,$catArray);
			$reportObj->setData($insertData);
		}
		$titleArray = ["商品名称","货号","规格","售价","库存","销量","发布时间","状态","归属"];
		for($i = 1;$catMax >= $i;$i++)
		{
		    $titleArray[] = "分类".$i;
		}

		$reportObj->setTitle($titleArray);
		$reportObj->toDownload();
	}

	//商品数据导入
	public function goods_import()
	{
		//附件上传$_FILE
		if ($_FILES && isset($_FILES['goods_csv']))
		{
			//处理上传
			$uploadInstance = new IUpload(9999999, ['html']);
			$uploadDir      = 'upload/excel/' . date('Y-m-d');
			$uploadInstance->setDir($uploadDir);
			$result = $uploadInstance->execute();
			$result = current($result['goods_csv']);
			if(isset($result['error']) && $result['error'] != '上传成功')
			{
				$this->redirect('/goods/goods_list/_msg/'.$result['error']);
				return;
			}

			//解析内容
			$csvContent = file_get_contents($result['fileSrc']);
			$successCount = 0;

			preg_match("#<tr.*>.*</tr>#is",$csvContent,$match);
			if($match && isset($match[0]) && $match[0])
			{
				$goodsDB   = new IModel('goods');
				$productDB = new IModel('products');

				$matchArray = explode('</tr>',$match[0]);
				foreach($matchArray as $key => $line)
				{
					if(!$line)
					{
						continue;
					}

					$items = explode('</td>',$line);
					$goods_name = trim(strip_tags($items[0]));
					$goods_no   = trim(strip_tags($items[1]));
					$spec_array = trim(strip_tags($items[2]));
					$sell_price = trim(strip_tags($items[3]));
					$store_nums = trim(strip_tags($items[4]));

					//如果价格和库存非数字则忽略
					if(is_numeric($store_nums) == false || is_numeric($sell_price) == false)
					{
						continue;
					}

					//货品更新
					if($spec_array)
					{
						$productDB->setData(['store_nums' => $store_nums,'sell_price' => $sell_price]);
						$productDB->update('products_no = "'.$goods_no.'"');
					}
					//商品更新
					else
					{
						$goodsDB->setData(['store_nums' => $store_nums,'sell_price' => $sell_price]);
						$goodsDB->update('goods_no = "'.$goods_no.'"');
					}
					$successCount++;
				}
				$this->redirect('/goods/goods_list/_msg/共更新完成'.$successCount.'条记录');
			}
			else
			{
				$this->redirect('/goods/goods_list/_msg/无法解析文件');
			}
		}
		else
		{
			$this->redirect('/goods/goods_list/_msg/未选择上传文件');
		}
	}

	/**
	 * @brief 商品分类添加、修改
	 */
	function category_edit()
	{
		$category_id = IFilter::act(IReq::get('cid'),'int');
		if($category_id)
		{
			$categoryObj = new IModel('category');
			$this->categoryRow = $categoryObj->getObj('id = '.$category_id);
		}
		$this->redirect('category_edit');
	}

	/**
	 * @brief 保存商品分类
	 */
	function category_save()
	{
		//获得post值
		$category_id = IFilter::act(IReq::get('id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$parent_id = IFilter::act(IReq::get('parent_id'),'int');
		$visibility = IFilter::act(IReq::get('visibility'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');
		$title = IFilter::act(IReq::get('title'));
		$keywords = IFilter::act(IReq::get('keywords'));
		$descript = IFilter::act(IReq::get('descript'));

		$childString = goods_class::catChild($category_id);//父类ID不能死循环设置成其子分类
		if($parent_id > 0 && stripos(",".$childString.",",",".$parent_id.",") !== false)
		{
			$this->redirect('/goods/category_list/_msg/父分类设置错误');
			return;
		}

		$tb_category = new IModel('category');
		$category_info = array(
			'name'      => $name,
			'parent_id' => $parent_id,
			'sort'      => $sort,
			'visibility'=> $visibility,
			'keywords'  => $keywords,
			'descript'  => $descript,
			'title'     => $title
		);

		if(isset($_FILES['img']['name']) && $_FILES['img']['name'])
		{
		    $uploadDir = IWeb::$app->config['upload'].'/category';
			$uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['img']['img']))
			{
				$category_info['img'] = $photoInfo['img']['img'];
			}
		}

		$tb_category->setData($category_info);
		if($category_id)									//保存修改分类信息
		{
			$where = "id=".$category_id;
			$tb_category->update($where);
		}
		else												//添加新商品分类
		{
			$tb_category->add();
		}
		$this->redirect('category_list');
	}

	/**
	 * @brief 删除商品分类
	 */
	function category_del()
	{
		$category_id = IFilter::act(IReq::get('cat_id'),'int');
		if($category_id)
		{
			$category_id = is_array($category_id) ? join(',',$category_id) : $category_id;
			$tb_category = new IModel('category');
			$catRow      = $tb_category->getObj('parent_id in ( '.$category_id.')');

			//要删除的分类下还有子节点
			if($catRow)
			{
				$this->category_list();
				Util::showMessage('无法删除此分类，此分类下还有子分类，或者回收站内还留有子分类');
				exit;
			}

			if($tb_category->del('id in ('.$category_id.')'))
			{
				$tb_category_extend  = new IModel('category_extend');
				$tb_category_extend->del('category_id in  ('.$category_id.')');
				//删除分类手续费
				$categoryRateObj = new IModel('category_rate');
				$categoryRateObj->del('category_id in (' .$category_id.')');

				$this->redirect('category_list');
			}
			else
			{
				$this->category_list();
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->category_list();
			$msg = "没有找到相关分类记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 商品分类列表
	 */
	function category_list()
	{
		$isCache = false;
		$tb_category = new IModel('category');
		$cacheObj = new ICache('file');
		$data = $cacheObj->get('sortdata');
		if(!$data)
		{
			$goods = new goods_class();
			$data = $goods->sortdata($tb_category->query(false,'*','sort asc'));
			$isCache ? $cacheObj->set('sortdata',$data) : "";
		}
		$this->data['category'] = $data;
		$this->setRenderData($this->data);
		$this->redirect('category_list',false);
	}

	//修改规格页面
	function spec_edit()
	{
		$this->layout = '';

		$id        = IFilter::act(IReq::get('id'),'int');
		$seller_id = IFilter::act(IReq::get('seller_id'),'int');

		$dataRow = array(
			'id'        => '',
			'name'      => '',
			'type'      => '',
			'value'     => '',
			'note'      => '',
			'seller_id' => $seller_id,
		);

		if($id)
		{
			$obj     = new IModel('spec');
			$dataRow = $obj->getObj("id = {$id}");
		}

		$this->setRenderData($dataRow);
		$this->redirect('spec_edit');
	}

	//增加或者修改规格
    function spec_update()
    {
    	$id        = IFilter::act(IReq::get('id'),'int');
    	$name      = IFilter::act(IReq::get('name'));
    	$sort      = IFilter::act(IReq::get('sort'),'int');
    	$image     = IFilter::act(IReq::get('image'));
    	$note      = IFilter::act(IReq::get('note'));
    	$seller_id = IFilter::act(IReq::get('seller_id'),'int');
    	$value     = IFilter::act(IReq::get('value'));

		//组合规格值或者图片数据
		$data = JSON::encode(array_combine($value,$image));
		if(!$data)
		{
			die( JSON::encode(array('flag' => 'fail','message' => '规格值不能为空或者0，请填写正确文字')) );
		}

		if(!$name)
		{
			die( JSON::encode(array('flag' => 'fail','message' => '规格名称不能为空')) );
		}

    	$editData = array(
    		'id'        => $id,
    		'name'      => $name,
    		'value'     => $data,
    		'note'      => $note,
    		'seller_id' => $seller_id,
    		'sort'      => $sort,
    	);

		//执行操作
		$obj = new IModel('spec');
    	$obj->setData($editData);

    	//更新修改
    	if($id)
    	{
    		$where = 'id = '.$id;
    		if($seller_id)
    		{
    			$where .= ' and seller_id = '.$seller_id;
    		}
    		$result = $obj->update($where);
    	}
    	//添加插入
    	else
    	{
    		$result = $obj->add();
    	}

		//执行状态
    	if($result===false)
    	{
			die( JSON::encode(array('flag' => 'fail','message' => '数据库更新失败')) );
    	}
    	else
    	{
    		//获取自动增加ID 处理返回json便于视图使用
    		$editData['id']    = $id ? $id : $result;
    		$editData['id']    = strval($editData['id']);
    		$editData['value'] = IFilter::stripSlash($editData['value']);
    		die( JSON::encode(array('flag' => 'success','data' => $editData)) );
    	}
    }

	//批量删除规格
    function spec_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$obj = new IModel('spec');
			$obj->setData(array('is_del'=>1));
			$obj->update(Util::joinStr($id));
			$this->redirect('spec_list');
		}
		else
		{
			$this->redirect('spec_list',false);
			Util::showMessage('请选择要删除的规格');
		}
    }
	//彻底批量删除规格
    function spec_recycle_del()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$obj = new IModel('spec');
			$obj->del(Util::joinStr($id));
			$this->redirect('spec_recycle_list');
		}
		else
		{
			$this->redirect('spec_recycle_list',false);
			Util::showMessage('请选择要删除的规格');
		}
    }
	//批量还原规格
    function spec_recycle_restore()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$obj = new IModel('spec');
			$obj->setData(array('is_del'=>0));
			$obj->update(Util::joinStr($id));
			$this->redirect('spec_recycle_list');
		}
		else
		{
			$this->redirect('spec_recycle_list',false);
			Util::showMessage('请选择要还原的规格');
		}
    }
    //规格图片删除
    function spec_photo_del()
    {
    	$id = IFilter::act(IReq::get('id','post'),'int');
    	if($id)
    	{
    		$obj = new IModel('spec_photo');
    		foreach($id as $rs)
    		{
    			$photoRow = $obj->getObj('id = '.$rs,'address');
    			if(file_exists($photoRow['address']))
    			{
    				unlink($photoRow['address']);
    			}
    		}

	    	$where = ' id in ('.join(",",$id).')';
	    	$obj->del($where);
	    	$this->redirect('spec_photo');
    	}
    	else
    	{
    		$this->redirect('spec_photo',false);
    		Util::showMessage('请选择要删除的id值');
    	}
    }

	/**
	 * @brief 分类排序
	 */
	function category_sort()
	{
		$category_id = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');

		$flag = 0;
		if($category_id)
		{
			$tb_category = new IModel('category');
			$category_info = $tb_category->getObj('id='.$category_id);
			if(count($category_info)>0)
			{
				if($category_info['sort']!=$sort)
				{
					$tb_category->setData(array('sort'=>$sort));
					if($tb_category->update('id='.$category_id))
					{
						$flag = 1;
					}
				}
			}
		}
		echo $flag;
	}
	/**
	 * @brief 品牌分类排序
	 */
	public function brand_sort()
	{
		$brand_id = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');
		$flag = 0;
		if($brand_id)
		{
			$tb_brand = new IModel('brand');
			$brand_info = $tb_brand->getObj('id='.$brand_id);
			if(count($brand_info)>0)
			{
				if($brand_info['sort']!=$sort)
				{
					$tb_brand->setData(array('sort'=>$sort));
					if($tb_brand->update('id='.$brand_id))
					{
						$flag = 1;
					}
				}
			}
		}
		echo $flag;
	}

	//修改排序
	public function ajax_sort()
	{
		$id   = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('sort' => $sort));
		$goodsDB->update("id = {$id}");
	}

	//更新库存
	public function update_store()
	{
		$data     = IFilter::act(IReq::get('data'),'int'); //key => 商品ID或货品ID ; value => 库存数量
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');//存在即为货品
		$goodsSum = array_sum($data);

		if(!$data)
		{
			die(JSON::encode(array('result' => 'fail','data' => '商品数据不存在')));
		}

		//货品方式
		if($goods_id)
		{
			$productDB = new IModel('products');
			foreach($data as $key => $val)
			{
				$productDB->setData(array('store_nums' => $val));
				$productDB->update('id = '.$key);
			}
		}
		else
		{
			$goods_id = key($data);
		}

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('store_nums' => $goodsSum));
		$goodsDB->update('id = '.$goods_id);

		die(JSON::encode(array('result' => 'success','data' => $goodsSum)));
	}

	//更新商品价格
	public function update_price()
	{
		$data     = IFilter::act(IReq::get('data')); //key => 商品ID或货品ID ; value => 库存数量
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');//存在即为货品

		if(!$data)
		{
			die(JSON::encode(array('result' => 'fail','data' => '商品数据不存在')));
		}

		//货品方式
		if($goods_id)
		{
			$productDB  = new IModel('products');
			$updateData = current($data);
			foreach($data as $pid => $item)
			{
				$productDB->setData($item);
				$productDB->update("id = ".$pid);
			}
		}
		else
		{
			$goods_id   = key($data);
			$updateData = current($data);
		}

		$goodsDB = new IModel('goods');

		if($saleRow = Active::isSale($goods_id))
		{
			$goodsDB->rollback();
			die(JSON::encode(array('result' => 'fail','data' => '当前商品正处于【营销->特价】中的【'.$saleRow['name'].'】活动中，请先关闭或者删除活动后才能进行修改')));
		}

		$goodsDB->setData($updateData);
		$goodsDB->update('id = '.$goods_id);

		die(JSON::encode(array('result' => 'success','data' => number_format($updateData['sell_price'],2))));
	}

	//更新商品推荐标签
	public function update_commend()
	{
		$data = IFilter::act(IReq::get('data')); //key => 商品ID或货品ID ; value => commend值 1~4
		if(!$data)
		{
			die(JSON::encode(array('result' => 'fail','data' => '商品数据不存在')));
		}

		$goodsCommendDB = new IModel('commend_goods');

		//清理旧的commend数据
		$goodsIdArray = array_keys($data);
		$goodsCommendDB->del("goods_id in (".join(',',$goodsIdArray).")");

		//插入新的commend数据
		foreach($data as $id => $commend)
		{
			foreach($commend as $k => $value)
			{
				if($value > 0)
				{
					$goodsCommendDB->setData(array('commend_id' => $value,'goods_id' => $id));
					$goodsCommendDB->add();
				}
			}
		}
		die(JSON::encode(array('result' => 'success')));
	}

	//商品共享
	public function goods_share()
	{
		$idArray = explode(',',IReq::get('id'));
		$id      = IFilter::act($idArray,'int');

		$goodsDB = new IModel('goods');
		$goodsData = $goodsDB->query('id in ('.join(',',$id).')');

		foreach($goodsData as $key => $val)
		{
			$is_share = $val['is_share'] == 1 ? 0 : 1;
			$goodsDB->setData(array('is_share' => $is_share));
			$goodsDB->update('id = '.$val['id'].' and seller_id = 0');
		}
	}

	/**
	 * @brief 商品批量设置
	 */
	function goods_setting()
	{
		$idArray   = explode(',',IReq::get('id'));
		$id        = IFilter::act($idArray,'int');
		$seller_id = IFilter::act(IReq::get('seller_id'),'int');

		if (empty($id))
		{
			exit('请选择您要操作的商品');
		}
		$data = array();
		$data['goods_id']  = implode(",", $id);
		$data['seller_id'] = $seller_id;

		$this->layout = '';
		$this->setRenderData($data);
		$this->redirect('goods_setting');
	}

	/**
	 * @brief 保存商品批量设置
	 */
	function goods_setting_save()
	{
		$idArray   = explode(',',IReq::get('goods_id', 'post'));
		$seller_id = IFilter::act(IReq::get('seller_id'),'int');
		$idArray   = IFilter::act($idArray,'int');

		if (empty($idArray))
		{
			exit('请首先选择您要操作的商品');
		}

		$goodsObject = new goods_class($seller_id);
		$goodsObject->multiUpdate($idArray, $_POST);
		die('<script type="text/javascript">parent.artDialogCallback();</script>');
	}
	/**
	 * @brief 商品分类ajax调整
	 */
	public function categoryAjax()
	{
		$id        = IFilter::act(IReq::get('id'),'int');
		$parent_id = IFilter::act(IReq::get('parent_id'),'int');
		if($id && is_array($id))
		{
			foreach($id as $category_id)
			{
				$childString = goods_class::catChild($category_id);//父类ID不能死循环设置成其子分类
				if($parent_id > 0 && stripos(",".$childString.",",",".$parent_id.",") !== false)
				{
					die(JSON::encode(array('result' => 'fail')));
				}
			}

			$catDB     = new IModel('category');
			$catDB->setData(array('parent_id' => $parent_id));
			$result = $catDB->update('id in ('.join(",",$id).')');
			if($result)
			{
				die(JSON::encode(array('result' => 'success')));
			}
		}
		die(JSON::encode(array('result' => 'fail')));
	}

	//商品筛选页面
	function search()
	{
		$this->setRenderData($_GET);
		$this->redirect('search');
	}

	//列出筛选商品
	function search_result()
	{
		//搜索条件
		$search      = IFilter::act(IReq::get('search'));
		$page        = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$is_products = IFilter::act(IReq::get('is_products'));
		$type        = IReq::get('type') == "checkbox" ? "checkbox" : "radio";

		//条件筛选处理
		list($join,$where) = goods_class::getSearchCondition($search);

		$goodsHandle = new IQuery('goods as go');
		$goodsHandle->order  = "go.id desc";
		$goodsHandle->fields = "distinct go.id as goods_id,go.name,go.img,go.store_nums,go.goods_no,go.sell_price,go.spec_array";
		$goodsHandle->limit  = 20;
		$goodsHandle->where  = $where;
		$goodsHandle->join   = $join;
		$data = $goodsHandle->find();

		//包含货品信息
		if($is_products && $data)
		{
			$goodsIdArray = array();
			foreach($data as $key => $val)
			{
				//有规格有货品
				if($val['spec_array'])
				{
					$goodsIdArray[$key] = $val['goods_id'];
					unset($data[$key]);
				}
			}

			if($goodsIdArray)
			{
				$productsDB        = new IQuery('products as pro');
				$productsDB->join  = "left join goods as go on go.id = pro.goods_id";
				$productsDB->where = "pro.goods_id in (".join(',',$goodsIdArray).")";
				$productsDB->fields="pro.goods_id,go.name,go.img,pro.id as product_id,pro.products_no as goods_no,pro.spec_array,pro.sell_price,pro.store_nums";
				$productDate       = $productsDB->find();
				$data              = array_merge($data,$productDate);
			}
		}

		$this->goodsData = $data;
		$this->type      = $type;
		$this->redirect('search_result');
	}
	/**
	 * @brief 单品手续费添加、修改
	 */
	public function goods_rate_edit()
	{
	    $id = IFilter::act(IReq::get('id'),'int');
	    if($id)
	    {
	        $goodsRateObj = new IModel('goods_rate');
	        $where        = 'goods_id = '.$id;
	        $goodsRateRow = $goodsRateObj->getObj($where);
	        if(!$goodsRateRow)
	        {
	            die("要查看的数据信息不存在");
	        }
	        //商品信息
	        $goodsObj = new IModel('goods');
	        $goodsRow = $goodsObj->getObj('id = '.$goodsRateRow['goods_id']);
	        $result = array(
	            'isError' => false,
	            'data'    => $goodsRow,
	        );
	        $goodsRateRow['goodsRow'] = JSON::encode($result);
	        $this->goodsRateRow = $goodsRateRow;
	    }
	    $this->redirect('goods_rate_edit');
	}
	/**
	 * 保存单品手续费
	 */
	public function goods_rate_save()
	{
	    $goods_id = IFilter::act(IReq::get('goods_id'),'int');
	    $goods_rate = IFilter::act(IReq::get('goods_rate'),'float');

	    $dataArray = array(
	        'goods_id' => $goods_id,
	        'goods_rate' => $goods_rate,
	        'goodsRow' => null,
	    );

	    if ($goods_id)
	    {
	        //商品信息
	        $goodsObj = new IModel('goods');
	        $goodsRow = $goodsObj->getObj('id = '.$goods_id);
	        $result = array(
	            'isError' => false,
	            'data'    => $goodsRow,
	        );
	        $dataArray['goodsRow'] = JSON::encode($result);
	    }
	    else
	    {
	        $this->goodsRateRow = $dataArray;
	        $this->redirect('goods_rate_edit', false);
	        Util::showMessage('请选择商品');
	    }

	    if (0 > $goods_rate || 100 < $goods_rate)
	    {
	        $this->goodsRateRow = $dataArray;
	        $this->redirect('goods_rate_edit', false);
	        Util::showMessage('单品手续费请填写0~100的数字');
	    }

	    $goodsRateObj = new IModel('goods_rate');
	    $goodsRateArray = array(
	        'goods_id' => $goods_id,
	        'goods_rate' => $goods_rate,
	    );
	    $goodsRateObj->setData($goodsRateArray);
	    $goodsRateObj->replace();

		//记录日志
		$logObj = new log('db');
		$logData = ["管理员:".$this->admin['admin_name'],"修改单品手续费","商品ID：".$goods_id."，名称：".$goodsRow['name']."，费率：".$goods_rate."%"];
		$logObj->write('operation',$logData);

	    $this->redirect('goods_rate_list');
	}
	/**
	 * 删除单品手续费
	 */
	public function goods_rate_del()
	{
	    $ids = IFilter::act(IReq::get('check'),'int');
	    $ids = is_array($ids) ? $ids : array($ids);
	    if ($ids)
	    {
	        $ids = implode(',', $ids);
	        $goodsRateObj = new IModel('goods_rate');
	        $where        = 'goods_id in(' .$ids. ')';
	        $goodsRateObj->del($where);
	    }

		//记录日志
		$logObj = new log('db');
		$logData = ["管理员:".$this->admin['admin_name'],"删除单品手续费","商品ID：".$ids];
		$logObj->write('operation',$logData);

	    $this->redirect('goods_rate_list');
	}
	/**
	 * @brief 分类手续费添加、修改
	 */
	public function category_rate_edit()
	{
	    $id = IFilter::act(IReq::get('id'),'int');
	    if($id)
	    {
	        $categoryRateObj = new IModel('category_rate');
	        $where        = 'category_id = '.$id;
	        $categoryRateRow = $categoryRateObj->getObj($where);
	        if(!$categoryRateRow)
	        {
	            die("要查看的数据信息不存在");
	        }
	        $this->categoryRateRow = $categoryRateRow;
	    }
	    $this->redirect('category_rate_edit');
	}
	/**
	 * 保存分类手续费
	 */
	public function category_rate_save()
	{
	    $category_id = IFilter::act(IReq::get('category_id'),'int');
	    $category_rate = IFilter::act(IReq::get('category_rate'),'float');

	    $dataArray = array(
	        'category_id' => $category_id,
	        'category_rate' => $category_rate,
	    );
	    // 分类信息
	    $categoryObj = new IModel('category');
	    $categoryRow = $categoryObj->getObj('id = '.$category_id);
	    if (!$categoryRow)
	    {
	        $this->categoryRateRow = $dataArray;
	        $this->redirect('category_rate_edit', false);
	        Util::showMessage('请选择分类');
	    }

	    if (0 > $category_rate || 100 < $category_rate)
	    {
	        $this->categoryRateRow = $dataArray;
	        $this->redirect('category_rate_edit', false);
	        Util::showMessage('分类手续费请填写0~100的数字');
	    }

	    $categoryRateObj = new IModel('category_rate');
	    $categoryRateObj->setData($dataArray);
	    $categoryRateObj->replace();

		//记录日志
		$logObj = new log('db');
		$logData = ["管理员:".$this->admin['admin_name'],"修改分类手续费","分类ID：".$category_id."，费率：".$category_rate."%"];
		$logObj->write('operation',$logData);

	    $this->redirect('category_rate_list');
	}
	/**
	 * 删除分类手续费
	 */
	public function category_rate_del()
	{
	    $ids = IFilter::act(IReq::get('check'),'int');
	    $ids = is_array($ids) ? $ids : array($ids);
	    if ($ids)
	    {
	        $ids = implode(',', $ids);
	        $categoryRateObj = new IModel('category_rate');
	        $where           = 'category_id in(' .$ids. ')';
	        $categoryRateObj->del($where);

			//记录日志
			$logObj = new log('db');
			$logData = ["管理员:".$this->admin['admin_name'],"删除分类手续费","分类ID：".$ids];
			$logObj->write('operation',$logData);
	    }
	    $this->redirect('category_rate_list');
	}

    //日期预订设置
	public function preorder_setting()
	{
		$id    = IFilter::act(IReq::get('id'),'int');
		$type  = IFilter::act(IReq::get('type'));
		$seller_id = IFilter::act(IReq::get('seller_id'),'int');

		if(!$id)
		{
			exit('请选择您要操作的商品');
		}

    	$dataRow = $type == 'goods' ? Api::run('getGoodsInfo',array("id" => $id)) : Api::run('getProductInfo',array("id" => $id));
        if(!$dataRow || ($seller_id && $dataRow['seller_id'] != $seller_id))
        {
            exit('商品信息不存在');
        }

        $whereCond = 'goods_id = '.$dataRow['goods_id'].' and product_id = '.$dataRow['product_id'];
        $whereCond.= $seller_id ? ' and seller_id = '.$seller_id : '';

        //价格浮动
		$discountDB = new IModel('goods_extend_preorder_discount');
		$discountData = $discountDB->query($whereCond);

		//库存锁定
		$disnumDB = new IModel('goods_extend_preorder_disnums');
        $disnumsData = $disnumDB->query($whereCond);

		$data = [
		    'id'       => $id,
		    'seller_id'=> $seller_id,
		    'goodsRow' => $dataRow,
		    'type'     => $type,
		    'discount' => $discountData,
		    'disnums'  => $disnumsData,
		];

		$this->layout = '';
		$this->setRenderData($data);
		$this->redirect('preorder_setting');
	}

	//保存预订日期设置
	public function preorder_setting_save()
	{
	    $id        = IFilter::act(IReq::get('id'),'int');
	    $seller_id = IFilter::act(IReq::get('seller_id'),'int');
	    $type      = IFilter::act(IReq::get('type'));
	    $colName   = $type == 'goods' ? 'goods_id' : 'product_id';

        $product_id = 0;
        $goods_id   = $id;
	    if($type == 'product')
	    {
	        $proDB = new IModel('products');
	        $proRow= $proDB->getObj($id);

	        $product_id = $id;
	        $goods_id   = $proRow['goods_id'];
	    }

	    //价格浮动
        $discountDateStart = IFilter::act(IReq::get('discountDateStart'),'date');
        $discountDateEnd   = IFilter::act(IReq::get('discountDateEnd'),'date');
        $discount          = IFilter::act(IReq::get('discount'),'float');

        $discountDB = new IModel('goods_extend_preorder_discount');
        $whereCond  = $colName.'='.$id;
        $whereCond .= $seller_id ? ' and seller_id = '.$seller_id : '';
        $discountDB->del($whereCond);

        if($discountDateStart && count($discountDateStart) == count($discountDateEnd) && count($discountDateEnd)  == count($discount))
        {
            foreach($discount as $key => $item)
            {
                $discountDB->setData([
                    'goods_id'  => $goods_id,
                    'product_id'=> $product_id,
                    'start_date'=> $discountDateStart[$key],
                    'end_date'  => $discountDateEnd[$key],
                    'discount'  => $discount[$key],
                    'seller_id' => $seller_id,
                ]);
                $discountDB->add();
            }
        }

	    //库存占用
        $disnumsDateStart  = IFilter::act(IReq::get('disnumsDateStart'),'date');
        $disnumsDateEnd    = IFilter::act(IReq::get('disnumsDateEnd'),'date');
        $disnums           = IFilter::act(IReq::get('disnums'),'int');

        $disnumDB = new IModel('goods_extend_preorder_disnums');
        $whereCond  = $colName.'='.$id;
        $whereCond .= $seller_id ? ' and seller_id = '.$seller_id : '';
        $disnumDB->del($whereCond);

        if($disnumsDateStart && count($disnumsDateStart) == count($disnumsDateEnd) && count($disnumsDateEnd)  == count($disnums))
        {
            foreach($disnums as $key => $item)
            {
                $disnumDB->setData([
                    'goods_id'  => $goods_id,
                    'product_id'=> $product_id,
                    'start_date'=> $disnumsDateStart[$key],
                    'end_date'  => $disnumsDateEnd[$key],
                    'disnums'   => $disnums[$key],
                    'seller_id' => $seller_id,
                ]);
                $disnumDB->add();
            }
        }

        die('<script type="text/javascript">parent.artDialogCallback();</script>');
	}
}
