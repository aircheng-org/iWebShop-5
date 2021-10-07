<?php
/**
 * @brief 工具模块
 * @class Tools
 * @note  后台
 */
class Tools extends IController implements adminAuthorization
{
	public $layout='admin';
	public $checkRight = array('check' => 'all','uncheck' => array('seo_sitemaps'));

	function init()
	{

	}

	public function seo_sitemaps()
	{
		$siteMaps =  new SiteMaps();
		$url = IUrl::getHost().IUrl::creatUrl("");
		$date = ITime::getNow('Y-m-d');
		$maps = array(
			array('loc'=>$url.'sitemap_goods.xml','lastmod'=>$date),
			array('loc'=>$url.'sitemap_article.xml','lastmod'=>$date)
		);
		$siteMaps->create($maps,$url.'sitemaps.xsl');
		$this->seo_items('goods');
		$this->seo_items('article');
		$this->redirect('seo_sitemaps');
	}
	public function seo_items($item)
	{
		$weburl = IUrl::getHost().IUrl::creatUrl("");
		switch($item)
		{
			case 'goods':
			{
				$url = '/site/products/id/';
				$query        = new IQuery('goods');
				$query->fields= "concat('$url',id) as loc,DATE_FORMAT(create_time,'%Y-%m-%d') as lastmod";
				$query->where = "is_del = 0";
				$items = $query->find();

				//对url进行处理
				foreach($items as $key => $val)
				{
					$items[$key]['loc'] = IUrl::getHost().IUrl::creatUrl($val['loc']);
				}
				SiteMaps::create_map($items,'sitemap_goods.xml',$weburl.'sitemaps.xsl');
				break;
			}
			case 'article':
			{
				$url   = '/site/article_detail/id/';
				$query = new IQuery('article');
				$query->fields="concat('$url',id) as loc,DATE_FORMAT(create_time,'%Y-%m-%d') as lastmod";
				$items = $query->find();

				//对url进行处理
				foreach($items as $key => $val)
				{
					$items[$key]['loc'] = IUrl::getHost().IUrl::creatUrl($val['loc']);
				}
				SiteMaps::create_map($items,'sitemap_article.xml',$weburl.'sitemaps.xsl');
			}
		}
	}

	//上传sql附件进行还原
	function upload_sql()
	{
		$this->layout = '';
		$this->redirect('upload_sql');
	}

	//[备份还原]数据备份动作(ajax操作)
	function db_act_bak()
	{
		//要备份的数据表
		$tableName = IReq::get('name','post');
		$tableName = IFilter::act($tableName,"string");

		//分卷大小限制(KB)
		$partSize = 4000;

		if(is_array($tableName) && isset($tableName[0]) && $tableName[0]!='')
		{
			$backupObj = new DBBackup($tableName);
			$backupObj->setPartSize($partSize);   //设置分卷大小
			$backupObj->runBak();                 //开始执行
			$result = array(
				'isError' => false,
				'redirect'=> IUrl::creatUrl('/tools/db_res'),
			);
		}
		else
		{
			$result = array(
				'isError' => true,
				'message' => '请选择要备份的数据表',
			);
		}
		echo JSON::encode($result);
	}

	//[备份还原]下载数据库
	function download()
	{
		$file = IFilter::act(IReq::get('file'),'filename');
		$backupObj = new DBBackup;
		$backupObj->download($file);
	}

	//[备份还原]删除备份
	function backup_del()
	{
		$name = IFilter::act(IReq::get('name'),'filename');
		$name = is_array($name) ? $name : array($name);

		if($name)
		{
			$backupObj = new DBBackup($name);
			$backupObj->del();
			$this->redirect('db_res');
		}
		else
		{
			IError::show(403,'请选择要删除的备份文件');
		}
	}

	//数据库备份列表
	function db_bak()
	{
		$dbObj = IDBFactory::getDB();
		$tableInfo = $dbObj->query('show table status');
		$this->setRenderData(array('tableInfo' => $tableInfo));
		$this->redirect('db_bak');
	}

	//数据库还原
	function db_res()
	{
		$backupObj = new DBBackup;
		$resList = $backupObj->getList();
		$this->setRenderData(array('resList' => $resList));
		$this->redirect('db_res');
	}

	//[备份还原]导入数据(ajax)
	function res_act()
	{
		$name = IFilter::act(IReq::get('name'));
		if(is_array($name) && $name)
		{
			$backupObj = new DBBackup($name);
			$backupObj->runRes();
			$result = array(
				'isError' => false,
				'redirect'=> IUrl::creatUrl('/tools/db_bak'),
			);
		}
		else
		{
			$result = array(
				'isError' => true,
				'message' => '请选择要导入的SQL文件',
			);
		}
		echo JSON::encode($result);
	}

	//本地上传sql文件导入
	public function localUpload()
	{
		if(isset($_FILES['attach']['tmp_name']) && file_exists($_FILES['attach']['tmp_name']))
		{
			$fileName  = $_FILES['attach']['tmp_name'];
			$backupObj = new DBBackup();
			$backupObj->parseSQL($fileName);
			die('<script type="text/javascript">parent.uploadSuccess();</script>');
		}
		else
		{
		    $error = IUpload::errorMessage($_FILES['attach']['error']);
			die('<script type="text/javascript">parent.uploadFail("'.$error.'");</script>');
		}
	}

	//[备份还原]打包下载
	function download_pack()
	{
		$name = IFilter::act(IReq::get('name'));

		if($name)
		{
			$backupObj = new DBBackup($name);
			$fileName  = $backupObj->packDownload();
			if($fileName === false)
			{
				IError::show(403,'环境不支持zip扩展');
			}

			$db_fileName = $backupObj->download($fileName);
			if(is_file($db_fileName))
			{
				@unlink($db_fileName);
			}
		}
		else
		{
			IError::show(403,'请选择要打包的文件');
		}
	}

	//[文章]删除
	function article_del()
	{
		$id = IFilter::act( IReq::get('id') ,'int' );
		if($id)
		{
			$obj = new IModel('article');
			$relationObj = new IModel('relation');

			if(is_array($id) && isset($id[0]) && $id[0]!='')
			{
				$id_str = join(',',$id);
				$where1 = ' id in ('.$id_str.')';
				$where2 = ' article_id in ('.$id_str.')';
			}
			else
			{
				$where1 = 'id = '.$id;
				$where2 = 'article_id = '.$id;
			}
			$obj->del($where1);               //删除商品
			$relationObj->del($where2);       //删除关联商品表
			$this->redirect('article_list');
		}
		else
		{
			$this->redirect('article_list',false);
			Util::showMessage('请选择要删除的文章');
		}
	}

	//[文章]单页
	function article_edit()
	{
		$data = array();
		$id   = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			//获取文章信息
			$articleObj       = new IModel('article');
			$this->articleRow = $articleObj->getObj('id = '.$id);
			if(!$this->articleRow)
			{
				IError::show(403,"文章信息不存在");
			}
		}
		$this->redirect('article_edit');
	}

	//[文章]增加修改
	function article_edit_act()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		$articleObj = new IModel('article');
		$dataArray  = array(
			'title'       => IFilter::act(IReq::get('title','post')),
			'content'     => IFilter::act(IReq::get('content','post'),'text'),
			'category_id' => IFilter::act(IReq::get('category_id','post'),'int'),
			'create_time' => ITime::getDateTime(),
			'keywords'    => IFilter::act(IReq::get('keywords','post')),
			'description' => IFilter::act(IReq::get('description','post'),'text'),
			'visibility'   => IFilter::act(IReq::get('visibility','post'),'int'),
			'top'         => IFilter::act(IReq::get('top','post'),'int'),
			'sort'        => IFilter::act(IReq::get('sort','post'),'int'),
			'style'       => IFilter::act(IReq::get('style','post')),
			'color'       => IFilter::act(IReq::get('color','post')),
		);

		//检查catid是否为空
		if($dataArray['category_id'] == 0)
		{
			$this->articleRow = $dataArray;
			$this->redirect('article_edit',false);
			Util::showMessage('请选择分类');
		}
		$articleObj->setData($dataArray);

		if($id)
		{
			//开始更新操作
			$where = 'id = '.$id;
			$is_success = $articleObj->update($where);
		}
		else
		{
			$id = $articleObj->add();
			$is_success = $id ? true : false;
		}

		if($is_success)
		{
			$ralationObj = new IModel('relation');
			$ralationObj->del('article_id = '.$id);

			/*article关联商品操作*/
			//获取新 article关联goods ID
			$goodsId = IFilter::act(IReq::get('goods_id','post'));
			if($goodsId)
			{
				foreach($goodsId as $key => $val)
				{
					$reData = array(
						'goods_id'   => $val,
						'article_id' => $id,
					);
					$ralationObj->setData($reData);
					$ralationObj->add();
				}
			}
		}
		else
		{
			$this->articleRow = $dataArray;
			$this->redirect('article_edit',false);
			Util::showMessage('插入数据时发生错误');
		}

		$this->redirect('article_list');
	}

	//[公告]增加修改
	function notice_edit_act()
	{
		$id = intval(IReq::get('id','post'));

		$noticeObj = new IModel('announcement');
		$dataArray  = array(
			'title'       => IFilter::act(IReq::get('title','post')),
			'content'     => IFilter::act(IReq::get('content','post'),'text'),
			'keywords'    => IFilter::act(IReq::get('keywords','post'),'text'),
			'description' => IFilter::act(IReq::get('description','post'),'text'),
		);
		$dataArray['time'] = date("Y-m-d H:i:s");
		$noticeObj->setData($dataArray);

		if($id)
		{
			$is_success = $noticeObj->update( "id={$id}" );
		}
		else
		{
			$noticeObj->add();
		}
		$this->redirect('notice_list');
	}

	//[公告]删除
	function notice_del()
	{
		$id = IFilter::act( IReq::get('id') , 'int'  );
		if(!is_array($id))
		{
			$id = array($id);
		}
		$id = implode(",",$id);

		$noticeObj = new IModel('announcement');
		$noticeObj->del( "id IN ({$id})" );
		$this->redirect('notice_list');
	}

	//[公告]单页
	function notice_edit()
	{
		$id = IFilter::act( IReq::get('id') , 'int'  );
		if($id)
		{
			//获取文章信息
			$noticeObj       = new IModel('announcement');
			$this->noticeRow = $noticeObj->getObj('id = '.$id);
			if(!$this->noticeRow)
			{
				IError::show(403,"信息不存在");
			}
		}

		$this->redirect('notice_edit',false);
	}
	//[文章分类] 增加和修改动作
	function cat_edit_act()
	{
		$id        = IFilter::act( IReq::get('id','post') );
		$parent_id = IFilter::act( IReq::get('parent_id','post') ) ;

		$catObj    = new IModel('article_category');
		$DataArray = array(
			'parent_id' => $parent_id,
			'name'      => IFilter::act( IReq::get('name','post'),'string'),
			'issys'     => IFilter::act( IReq::get('issys','post') ),
			'sort'      => IFilter::act( IReq::get('sort','post') ),
			'title'     => IFilter::act( IReq::get('title','post') ),
			'keywords'  => IFilter::act( IReq::get('keywords','post') ),
			'description'=>IFilter::act( IReq::get('description','post') ),
		);

		/*开始--获取path信息*/
		//1,修改操作
		if($id)
		{
			$where  = 'id = '.$id;
			$catRow = $catObj->getObj($where);
			if($catRow['parent_id']==$parent_id)
			{
				$isMoveNode = false;
				$DataArray['path'] = $catRow['path'];
			}
			else
				$isMoveNode = true;

			$localId = $id;
		}
		//2,新增操作
		else
		{
			$max_id  = $catObj->getObj('','max(id) as max_id');
			$localId = $max_id['max_id'] ? $max_id['max_id']+1 : 1;
		}

		//如果不存在path数据时,计算path数据
		if(!isset($DataArray['path']))
		{
			//获取父节点的path路径
			if($parent_id==0)
				$DataArray['path'] = ','.$localId.',';
			else
			{
				$where     = 'id = '.$parent_id;
				$parentRow = $catObj->getObj($where);
				$DataArray['path'] = $parentRow['path'].$localId.',';
			}
		}
		/*结束--获取path信息*/
		//设置数据值
		$catObj->setData($DataArray);

		//1,修改操作
		if($id)
		{
			//节点移动
			if($isMoveNode == true)
			{
				if($parentRow['path']!=null && strpos($parentRow['path'],','.$id.',')!==false)
				{
					$this->catRow = array(
						'parent_id' => $DataArray['parent_id'],
						'name'      => $DataArray['name'],
						'issys'     => $DataArray['issys'],
						'sort'      => $DataArray['sort'],
						'id'        => $id,
						'title'     => $DataArray['title'],
 						'keywords'   =>$DataArray['keywords'],
						'description'=>$DataArray['description'],
					);
					$this->redirect('article_cat_edit',false);
					Util::showMessage('不能该节点移动到其子节点的位置上');
				}
				else
				{
					//其子节点批量移动
					$childObj = new IModel('article_category');
					$oldPath  = $catRow['path'];
					$newPath  = $DataArray['path'];

					$where = 'path like "'.$oldPath.'%"';
					$updateData = array(
						'path' => "replace(path,'".$oldPath."','".$newPath."')",
					);
					$childObj->setData($updateData);
					$childObj->update($where,array('path'));
				}
			}
			$where = 'id = '.$id;
			$catObj->update($where);
		}
		//2,新增操作
		else
			$catObj->add();

		$this->redirect('article_cat_list');
	}

	//[文章分类] 增加修改单页
	function cat_edit()
	{
		$data = array();
		$id = IFilter::act( IReq::get('id'),'int' );

		if($id)
		{
			$catObj = new IModel('article_category');
			$where  = 'id = '.$id;
			$data = $catObj->getObj($where);
			if(count($data)>0)
			{
				$this->catRow = $data;
				$this->redirect('article_cat_edit',false);
			}
		}
		if(count($data)==0)
		{
			$this->redirect('article_cat_list');
		}
	}

	//[文章分类] 删除
	function cat_del()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
		$catObj = new IModel('article_category');

		//是否执行删除检测值
		$isCheck=true;

		//检测是否有parent_id 为 $id
		$where   = 'parent_id = '.$id;
		$catData = $catObj->getObj($where);
		if($catData)
		{
			$isCheck=false;
			$message='此分类下还有子分类';
		}

		//检测是否有article的category_id 为 $id
		else
		{
			$articleObj = new IModel('article');
			$where = 'category_id = '.$id;
			$catData = $articleObj->getObj($where);

			if($catData)
			{
				$isCheck=false;
				$message='此分类下还有文章';
			}
		}

		if($isCheck == false)
		{
			$message = isset($message) ? $message : '删除失败';
			$this->redirect('article_cat_list',false);
			Util::showMessage($message);
		}
		else
		{
			$where  = 'id = '.$id;
			$result = $catObj->del($where);
			$this->redirect('article_cat_list');
		}
	}

	//[广告位] 删除
	function ad_position_del()
	{
		$id = IFilter::act( IReq::get('id') , 'int' );
		if($id)
		{
			$obj = new IModel('ad_position');
			if(is_array($id) && isset($id[0]) && $id[0]!='')
			{
				$id_str = join(',',$id);
				$where  = ' id in ('.$id_str.') and seller_id = 0';
			}
			else
			{
				$where = 'id = '.$id . ' and  seller_id = 0';
			}
			$obj->del($where);
			$this->redirect('ad_position_list');
		}
		else
		{
			$this->redirect('ad_position_list',false);
			Util::showMessage('请选择要删除的广告位');
		}
	}

	//[广告位] 添加修改 (单页)
	function ad_position_edit()
	{
		$id   = IFilter::act( IReq::get('id'),'int' );
		$name = IFilter::act( IReq::get('name'),'text' );

		$obj = new IModel('ad_position');
		if($name)
		{
			$positionRow = $obj->getObj('name="'.$name.'" and seller_id = 0');
		}
		else if($id)
		{
			$positionRow = $obj->getObj('id = '.$id.' and seller_id = 0');
		}
		$this->positionRow = isset($positionRow) && $positionRow ? $positionRow : array('name' => $name);
		$this->redirect('ad_position_edit',false);
	}

	//[广告位] 添加和修改动作
	function ad_position_edit_act()
	{
		$id = IFilter::act( IReq::get('id') );

		$obj = new IModel('ad_position');

		$dataArray = array(
			'name'         => IFilter::act( IReq::get('name','post') ,'string' ),
			'width'        => IFilter::act( IReq::get('width','post') ),
			'height'       => IFilter::act( IReq::get('height','post') ),
			'fashion'      => IFilter::act( IReq::get('fashion','post'),'int' ),
			'status'       => IFilter::act( IReq::get('status','post'),'int' )
		);
		$obj->setData($dataArray);

		if($id)
		{
			$where  = 'id = '.$id.' and seller_id = 0';
			$result = $obj->update($where);
		}
		else
			$result = $obj->add();

		$this->redirect('ad_position_list');
	}

	//[广告] 删除
	function ad_del()
	{
		$id = IFilter::act( IReq::get('id') , 'int' );
		if($id)
		{
			$obj = new IModel('ad_manage');
			if(is_array($id) && isset($id[0]) && $id[0]!='')
			{
				$id_str = join(',',$id);
				$where  = ' id in ('.$id_str.') and seller_id = 0';
			}
			else
			{
				$where = 'id = '.$id.' and seller_id = 0';
			}
			$obj->del($where);
			$this->redirect('ad_list');
		}
		else
		{
			$this->redirect('ad_list',false);
			Util::showMessage('请选择要删除的广告');
		}
	}

	//[广告] 添加修改 (单页)
	function ad_edit()
	{
		$id          = IFilter::act(IReq::get('id'),'int');
		$positionId  = IFilter::act(IReq::get('pid'),'int');
		$this->adRow = array('position_id' => $positionId);
		if($id)
		{
			$obj = new IModel('ad_manage');
			$this->adRow = $obj->getObj('id = '.$id.' and seller_id = 0');
		}
		$this->redirect('ad_edit');
	}

	//[广告] 添加和修改动作
	function ad_edit_act()
	{
		$id      = IFilter::act( IReq::get('id'),'int');
		$type    = IFilter::act(IReq::get('type'),'int');
		$content = IReq::get('content');
		$files   = $_FILES ? current($_FILES)  : "";

		//附件上传
		if(isset($files['name']) && $files['name']!='')
		{
			$upType    = $type == 1 ? array("gif","png","jpg") : array('flv','swf');
			$uploadDir = IWeb::$app->config['upload'].'/ad';
			$uploadObj = new PhotoUpload($uploadDir);
			$uploadObj->setType($upType);
			$photoInfo = $uploadObj->run();
			$result = $photoInfo ? current($photoInfo) : "";

			if($result && isset($result['flag']) && $result['flag'] == 1)
			{
				//最终附件路径
				$content = $result['img'];
			}
			else if(!$content)
			{
				IError::show(403,"上传失败,错误信息：".var_export($result,true));
			}
		}

		$adObj = new IModel('ad_manage');
		$dataArray = array(
			'content'     => IFilter::addSlash($content),
			'name'        => IFilter::act(IReq::get('name')),
			'position_id' => IFilter::act(IReq::get('position_id')),
			'type'        => $type,
			'link'        => IFilter::addSlash(IReq::get('link')),
			'start_time'  => IFilter::act(IReq::get('start_time')),
			'end_time'    => IFilter::act(IReq::get('end_time')),
			'description' => IFilter::act(IReq::get('description'),'text'),
			'order'       => IFilter::act(IReq::get('order'),'int'),
			'goods_cat_id'=> IFilter::act(IReq::get('goods_cat_id'),'int'),
		);

		$adObj->setData($dataArray);
		if($id)
		{
			$where = 'id = '.$id.' and seller_id = 0';
			$adObj->update($where);
		}
		else
		{
			$adObj->add();
		}
		$this->redirect("ad_list");
	}

	//帮助信息列表
	function help_list()
	{
		$this->query = Api::run('getHelpList');
		$this->redirect("help_list");
	}

	function help_edit()
	{
		$id = IFilter::act(IReq::get("id"),'int');
		if($id)
		{
			$helpRow = Api::run('getHelpInfo',array('id' => $id));
			if(!$helpRow)
			{
				IError::show(403,'没有这条记录');
			}
			$this->help_row = $helpRow;
		}
		$this->redirect("help_edit");
	}

	//帮助信息保存和更新
	function help_edit_act()
	{
		$id   = IFilter::act(IReq::get("id"),'int');
		$data = array(
			'cat_id'  => IFilter::act(IReq::get('cat_id'),'int'),
			'name'    => IFilter::act(IReq::get('name')),
			'sort'    => IFilter::act(IReq::get("sort"),'int'),
			'content' => IFilter::act(IReq::get("content"),'text'),
			'dateline'=> time(),
		);

		$helpDB = new IModel('help');
		$helpDB->setData($data);

		if($id)
		{
			$helpDB->update('id = '.$id);
		}
		else
		{
			$helpDB->add();
		}
		$this->redirect("help_list");
	}

	//帮助信息删除
	function help_del()
	{
		$id = IFilter::act(IReq::get("id"));
		$id = is_array($id) ? join(',',$id) : $id;
		if(!$id)
		{
			$this->redirect('help_list',false);
			util::showMessage("请选择要删除的信息");
			return;
		}
		$helpDB = new IModel('help');
		$helpDB->del('id in ('.$id.')');
		$this->redirect('help_list');
	}

	//帮助信息编辑
	function help_cat_edit()
	{
		$id = IFilter::act(IReq::get("id"),'int');
		if($id)
		{
			$catRow = Api::run('getHelpCategoryInfo',array('id' => $id));
			if(!$catRow)
			{
				IError::show(403,'信息不存在');
			}
			$this->cat_row = $catRow;
		}
		$this->redirect("help_cat_edit");
	}

	//帮助分类保存和更新
	function help_cat_edit_act()
	{
		$id   = IFilter::act(IReq::get("id","post"),'int');
		$data = array(
			"name"          => IFilter::act(IReq::get("name")),
			"position_left" => IFilter::act(IReq::get("position_left"),'int'),
			"position_foot" => IFilter::act(IReq::get("position_foot"),'int'),
			"sort"          => IFilter::act(IReq::get("sort"),'int'),
		);

		$helpCatDB = new IModel('help_category');
		$helpCatDB->setData($data);

		if($id)
		{
			$helpCatDB->update('id = '.$id);
		}
		else
		{
			$helpCatDB->add();
		}
		$this->redirect('help_cat_list');
	}

	//帮助分类删除
	function help_cat_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$id = is_array($id) ? join(',',$id) : $id;
		if(!$id)
		{
			$this->redirect('help_cat_list',false);
			util::showMessage("请选择要删除的信息");
			return;
		}
		$helpCatDB = new IModel('help_category');
		$helpCatDB->del('id in ('.$id.')');
		$this->redirect('help_cat_list');
	}

	//[关键词管理]添加
	function keyword_add()
	{
		$word  = IFilter::act(IReq::get('word'));
		$hot   = intval(IReq::get('hot'));
		$order = IReq::get('order') ? intval(IReq::get('order')) : 99;

		$re = keywords::add($word ,$hot,$order);

		if($re['flag']==true)
		{
			$this->redirect('keyword_list');
		}
		else
		{
			$this->redirect('keyword_edit');
			Util::showMessage($re['data']);
		}
	}

	//[关键词管理]删除
	function keyword_del()
	{
		$id = IFilter::act(IReq::get('id'));
		if($id)
		{
			$keywordObj = new IModel('keyword');
			if(is_array($id))
			{
				$ids = '"'.join('","',$id).'"';
				$keywordObj->del('word in ('.$ids.')');
			}
			else
			{
				$keywordObj->del('word = "'.$id.'"');
			}
		}
		else
		{
			$message = '请选择要删除的关键词';
		}

		if(isset($message))
		{
			$this->redirect('keyword_list',false);
			Util::showMessage($message);
		}
		else
		{
			$this->redirect('keyword_list');
		}
	}

	//[关键词管理]设置hot
	function keyword_hot()
	{
		$id  = IFilter::act(IReq::get('id'));

		$keywordObj = new IModel('keyword');
		$dataArray  = array('hot' => 'abs(hot - 1)');
		$keywordObj->setData($dataArray);
		$is_result  = $keywordObj->update('word = "'.$id.'"','hot');

		$keywordRow = $keywordObj->getObj('word = "'.$id.'"');
		if($is_result!==false)
		{
			echo JSON::encode(array('isError' => false,'hot' => $keywordRow['hot']));
		}
		else
		{
			echo JSON::encode(array('isError'=>true,'message'=>'设置失败'));
		}
	}

	//[关键词管理]统计商品数量
	function keyword_account()
	{
		$word = IFilter::act(IReq::get('id'));
		if(!$word)
		{
			$this->redirect('keyword_list',false);
			Util::showMessage('请选择要同步的关键词');
		}

		$keywordObj = new IModel('keyword');
		foreach($word as $key => $val)
		{
			//获取各个关键词的管理商品数量
			$resultCount = keywords::count($val);
			$dataArray = array(
				'goods_nums' => $resultCount,
			);
			$keywordObj->setData($dataArray);
			$keywordObj->update('word = "'.$val.'"');
		}
		$this->redirect('keyword_list');
	}
	//关键词排序
	function keyword_order()
	{
		$word  = IFilter::act(IReq::get('id'));
		$order = IReq::get('order') ? intval(IReq::get('order')) : 99;

		$keywordObj = new IModel('keyword');
		$dataArray = array('order' => $order);
		$keywordObj->setData($dataArray);
		$is_success = $keywordObj->update('word = "'.$word.'"');

		if($is_success === false)
		{
			$result = array(
				'isError' => true,
				'message' => '更新排序失败',
			);
		}
		else
		{
			$result = array(
				'isError' => false,
			);
		}
		echo JSON::encode($result);
	}

	/**
	 * 查询删除
	 */
	function search_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$id = is_array($id) ? join(",",$id) : $id;

		//生成search对象
	    $tb_search = new IModel('search');
	    if($id)
		{
			$tb_search->del("id in (".$id.")");
		}
		else
		{
			$this->redirect("search_list",false);
			Util::showMessage('请选择要删除的数据');
		}
		$this->redirect("search_list");
	}
}