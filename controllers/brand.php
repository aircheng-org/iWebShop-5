<?php
/**
 * @class Brand
 * @brief 品牌模块
 * @note  后台
 */
class Brand extends IController implements adminAuthorization
{
	public $checkRight  = 'all';
    public $layout='admin';
	public $data = array();

	function init()
	{

	}

	/**
	 * @brief 品牌分类添加、修改
	 */
	function category_edit()
	{
		$category_id = (int)IReq::get('cid');
		//编辑品牌分类 读取品牌分类信息
		if($category_id)
		{
			$obj_brand_category = new IModel('brand_category');
			$category_info = $obj_brand_category->getObj('id='.$category_id);

			if($category_info)
			{
				$this->catRow = $category_info;
			}
			else
			{
				$this->redirect('category_list');
				Util::showMessage("没有找到相关品牌分类！");
				return;
			}
		}
		$this->redirect('category_edit');
	}

	/**
	 * @brief 保存品牌分类
	 */
	function category_save()
	{
		$id                = IFilter::act(IReq::get('id'),'int');
		$goods_category_id = IFilter::act(IReq::get('goods_category_id'),'int');
		$name              = IFilter::act(IReq::get('name'));

		$category_info = array(
			'name' => $name,
			'goods_category_id' => $goods_category_id
		);
		$tb_brand_category = new IModel('brand_category');
		$tb_brand_category->setData($category_info);

		//更新品牌分类
		if($id)
		{
			$where = "id=".$id;
			$tb_brand_category->update($where);
		}
		//添加品牌分类
		else
		{
			$tb_brand_category->add();
		}
		$this->redirect('category_list');
	}

	/**
	 * @brief 删除品牌分类
	 */
	function category_del()
	{
		$category_id = IFilter::act(IReq::get('cid'),'int');
		if($category_id)
		{
			$brand_category = new IModel('brand_category');
			$where = "id=".$category_id;
			if($brand_category->del($where))
			{
				$this->redirect('category_list');
			}
			else
			{
				$this->redirect('category_list');
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->redirect('category_list');
			$msg = "没有找到相关分类记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 修改品牌
	 */
	function brand_edit()
	{
		$brand_id  = IFilter::act(IReq::get('bid'),'int');
		$brandData = array();

		//编辑品牌 读取品牌信息
		if($brand_id)
		{
			$obj_brand = new IModel('brand');
			$brand_info = $obj_brand->getObj('id='.$brand_id);
			if($brand_info)
			{
				$brandData = $brand_info;
			}
			else
			{
				$this->redirect('brand_list');
				Util::showMessage("没有找到相关品牌");
				return;
			}
		}
		$this->setRenderData(array('brand' => $brandData));
		$this->redirect('brand_edit');
	}

	/**
	 * @brief 保存品牌
	 */
	function brand_save()
	{
		$brand_id = IFilter::act(IReq::get('id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$sort = IFilter::act(IReq::get('sort'),'int');
		$url = IFilter::act(IReq::get('url'));
		$category = IFilter::act(IReq::get('category_ids'),'int');
		$description = IFilter::act(IReq::get('description'));
		$callback = IReq::get('callback');
		$callback = strpos($callback,'brand_list') === false ? '' : $callback;

		$tb_brand = new IModel('brand');

		//判断品牌重复问题
		if($tb_brand->getObj('name = "'.$name.'"'))
		{
		    IError::show('品牌名称 '.$name.' 重复');
		}

		$brand = ['name'=>$name,'sort'=>$sort,'url'=>$url,'description' => $description];
		if($category && is_array($category))
		{
			$categorys = join(',',$category);
			$brand['category_ids'] = $categorys;
		}
		else
		{
			$brand['category_ids'] = '';
		}
		if(isset($_FILES['logo']['name']) && $_FILES['logo']['name']!='')
		{
		    $uploadDir = IWeb::$app->config['upload'].'/brand';
			$uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setIterance(false);
			$photoInfo = $uploadObj->run();
			if(isset($photoInfo['logo']['img']))
			{
				$brand['logo'] = $photoInfo['logo']['img'];
			}
		}
		$tb_brand->setData($brand);
		if($brand_id)
		{
			//保存修改分类信息
			$where = "id=".$brand_id;
			$tb_brand->update($where);
		}
		else
		{
			//添加新品牌
			$tb_brand->add();
		}

		$callback ? $this->redirect($callback) : $this->brand_list();
	}

	/**
	 * @brief 删除品牌
	 */
	function brand_del()
	{
		$brand_id = (int)IReq::get('bid');
		if($brand_id)
		{
			$tb_brand = new IModel('brand');
			$where = "id=".$brand_id;
			if($tb_brand->del($where))
			{
				$this->brand_list();
			}
			else
			{
				$this->brand_list();
				$msg = "没有找到相关分类记录！";
				Util::showMessage($msg);
			}
		}
		else
		{
			$this->brand_list();
			$msg = "没有找到相关品牌记录！";
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 品牌列表
	 */
	function brand_list()
	{
		$catAll = array();
		$brandCatData = Api::run('getBrandCategory');
		foreach($brandCatData as $item)
		{
			$catAll[$item['id']] = $item['name'];
		}
		$this->setRenderData(['catAll' => $catAll,'search' => IFilter::act(IReq::get('search'))]);
		$this->redirect('brand_list');
	}
}