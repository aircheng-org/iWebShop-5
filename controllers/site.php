<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file site.php
 * @brief 前台最主要控制器
 * @author nswe
 * @date 2011-03-22
 * @version 0.6
 */
/**
 * @brief Site
 * @class Site
 */
class Site extends IController
{
    public $layout='site';

	function init()
	{

	}

	function index()
	{
		$this->index_slide = Api::run('getBannerList');
		$this->redirect('index');
	}

	//[首页]商品搜索
	function search_list()
	{
		$this->word = IFilter::act(IReq::get('word'),'text');
		$cat_id     = IFilter::act(IReq::get('cat'),'int');

		if(preg_match("|^[\w\x7f\s*-\xff*]+$|",$this->word))
		{
			//搜索关键字
			$tb_sear     = new IModel('search');
			$search_info = $tb_sear->getObj('keyword = "'.$this->word.'"','id');

			//如果是第一页，相应关键词的被搜索数量才加1
			if($search_info && intval(IReq::get('page')) < 2 )
			{
				//禁止刷新+1
				$allow_sep = "30";
				$flag = false;
				$time = ICookie::get('step');
				if($time)
				{
					if (time() - $time > $allow_sep)
					{
						ICookie::set('step',time());
						$flag = true;
					}
				}
				else
				{
					ICookie::set('step',time());
					$flag = true;
				}
				if($flag)
				{
					$tb_sear->setData(array('num'=>'num + 1'));
					$tb_sear->update('id='.$search_info['id'],'num');
				}
			}
			elseif( !$search_info )
			{
				//如果数据库中没有这个词的信息，则新添
				$tb_sear->setData(array('keyword'=>$this->word,'num'=>1));
				$tb_sear->add();
			}
		}
		else
		{
			IError::show(403,ILang::get('请输入正确的查询关键词'));
		}
		$this->cat_id = $cat_id;
		$this->redirect('search_list');
	}

	//[site,ucenter头部分]自动完成
	function autoComplete()
	{
		$word = IFilter::act(IReq::get('word'));
		$isError = true;
		$data    = array();

		if($word != '' && $word != '%' && $word != '_')
		{
			$wordObj  = new IModel('keyword');
			$wordList = $wordObj->query('word like "'.$word.'%" and word != "'.$word.'"','word, goods_nums','',10);

			if($wordList)
			{
				$isError = false;
				$data = $wordList;
			}
		}

		//json数据
		$result = array(
			'isError' => $isError,
			'data'    => $data,
		);

		echo JSON::encode($result);
	}

	//[首页]邮箱订阅
	function email_registry()
	{
		$email  = IFilter::act(IReq::get('email'));
		$result = array('isError' => true);

		if(!IValidate::email($email))
		{
			$result['message'] = ILang::get('请填写正确的email地址');
		}
		else
		{
			$emailRegObj = new IModel('email_registry');
			$emailRow    = $emailRegObj->getObj('email = "'.$email.'"');

			if($emailRow)
			{
				$result['message'] = ILang::get('此email已经订阅过了');
			}
			else
			{
				$dataArray = array(
					'email' => $email,
				);
				$emailRegObj->setData($dataArray);
				$status = $emailRegObj->add();
				if($status == true)
				{
					$result = array(
						'isError' => false,
						'message' => ILang::get('订阅成功'),
					);
				}
				else
				{
					$result['message'] = ILang::get('订阅失败');
				}
			}
		}
		echo JSON::encode($result);
	}

	//[列表页]商品
	function pro_list()
	{
		$this->catId = IFilter::act(IReq::get('cat'),'int');//分类id

		//查找分类信息
		$catObj       = new IModel('category');
		$this->catRow = $catObj->getObj('id = '.$this->catId);

		//获取子分类
		$this->childId = goods_class::catChild($this->catId);
		$this->redirect('pro_list');
	}
	//咨询
	function consult()
	{
		$this->goods_id = IFilter::act(IReq::get('id'),'int');
		if($this->goods_id == 0)
		{
			IError::show(403,ILang::get('缺少商品ID参数'));
		}

		$goodsObj   = new IModel('goods');
		$goodsRow   = $goodsObj->getObj('id = '.$this->goods_id);
		if(!$goodsRow)
		{
			IError::show(403,ILang::get('商品数据不存在'));
		}

		//获取次商品的评论数和平均分
		$goodsRow['apoint'] = $goodsRow['comments'] ? round($goodsRow['grade']/$goodsRow['comments']) : 0;

		$this->goodsRow = $goodsRow;
		$this->redirect('consult');
	}

	//咨询动作
	function consult_act()
	{
		$goods_id   = IFilter::act(IReq::get('goods_id','post'),'int');
		$captcha    = IFilter::act(IReq::get('captcha','post'));
		$question   = IFilter::act(IReq::get('question','post'));
		$_captcha   = ISafe::get('captcha');
		$message    = '';

    	if(!$captcha || !$_captcha || $captcha != $_captcha)
    	{
    		$message = ILang::get('验证码输入不正确');
    	}
    	else if(!$question)
    	{
    		$message = ILang::get('咨询内容不能为空');
    	}
    	else if(!$goods_id)
    	{
    		$message = ILang::get('商品ID不能为空');
    	}
    	else
    	{
    		$goodsObj = new IModel('goods');
    		$goodsRow = $goodsObj->getObj('id = '.$goods_id);
    		if(!$goodsRow)
    		{
    			$message = ILang::get('不存在此商品');
    		}
    	}

		//有错误情况
    	if($message)
    	{
    		IError::show(403,$message);
    	}
    	else
    	{
			$dataArray = array(
				'question' => $question,
				'goods_id' => $goods_id,
				'user_id'  => isset($this->user['user_id']) ? $this->user['user_id'] : 0,
				'time'     => ITime::getDateTime(),
			);
			$referObj = new IModel('refer');
			$referObj->setData($dataArray);
			$referObj->add();
			plugin::trigger('setCallback','/site/products/id/'.$goods_id);
			$this->redirect('/site/success');
    	}
	}

	//公告详情页面
	function notice_detail()
	{
		$this->notice_id = IFilter::act(IReq::get('id'),'int');
		if($this->notice_id == '')
		{
			IError::show(403,ILang::get('缺少公告ID参数'));
		}
		else
		{
			$noObj           = new IModel('announcement');
			$this->noticeRow = $noObj->getObj('id = '.$this->notice_id);
			if(empty($this->noticeRow))
			{
				IError::show(403,ILang::get('公告信息不存在'));
			}
			$this->redirect('notice_detail');
		}
	}

	//文章列表页面
	function article()
	{
		$catId  = IFilter::act(IReq::get('id'),'int');
		$catRow = Api::run('getArticleCategoryInfo',$catId);
		$queryArticle = $catRow ? Api::run('getArticleListByCatid',$catRow['id']) : Api::run('getArticleList');
		$this->setRenderData(array("catRow" => $catRow,'queryArticle' => $queryArticle));
		$this->redirect('article');
	}

	//文章详情页面
	function article_detail()
	{
		$this->article_id = IFilter::act(IReq::get('id'),'int');
		if($this->article_id == '')
		{
			IError::show(403,ILang::get('缺少咨询ID参数'));
		}
		else
		{
			$articleObj       = new IModel('article');
			$this->articleRow = $articleObj->getObj('id = '.$this->article_id);
			if(empty($this->articleRow))
			{
				IError::show(403,ILang::get('资讯文章不存在'));
				exit;
			}

			//关联商品
			$this->relationList = Api::run('getArticleGoods',array("#article_id#",$this->article_id));
			$this->redirect('article_detail');
		}
	}

	//商品展示
	function products()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');

		if(!$goods_id)
		{
			IError::show(403,ILang::get('传递的参数不正确'));
		}

		//使用商品id获得商品信息
		$tb_goods = new IModel('goods');
		$goods_info = $tb_goods->getObj('id='.$goods_id." AND is_del=0");
		if(!$goods_info)
		{
			IError::show(403,ILang::get('这件商品不存在'));
		}

		//品牌名称
		if($goods_info['brand_id'])
		{
			$tb_brand = new IModel('brand');
			$brand_info = $tb_brand->getObj('id='.$goods_info['brand_id']);
			if($brand_info)
			{
				$goods_info['brand'] = $brand_info['name'];
			}
		}

		//获取商品分类
		$categoryObj = new IModel('category_extend as ca,category as c');
		$categoryList= $categoryObj->query('ca.goods_id = '.$goods_id.' and ca.category_id = c.id','c.id,c.name','ca.id desc',1);
		$categoryRow = null;
		if($categoryList)
		{
			$categoryRow = current($categoryList);
		}
		$goods_info['category'] = $categoryRow ? $categoryRow['id'] : 0;

		//商品图片
		$tb_goods_photo = new IQuery('goods_photo_relation as g');
		$tb_goods_photo->fields = 'p.id AS photo_id,p.img ';
		$tb_goods_photo->join = 'left join goods_photo as p on p.id=g.photo_id ';
		$tb_goods_photo->where =' g.goods_id='.$goods_id;
		$tb_goods_photo->order =' g.id asc';
		$goods_info['photo'] = $tb_goods_photo->find();

		//商品是否参加营销活动
		if($goods_info['promo'] && $goods_info['active_id'])
		{
			$activeObj    = new Active($goods_info['promo'],$goods_info['active_id'],$this->user['user_id'],$goods_id);
			$activeResult = $activeObj->data();
			if(is_string($activeResult))
			{
				IError::show(403,$activeResult);
			}
			else
			{
				$goods_info[$goods_info['promo']] = $activeResult;
				$goods_info['activeTemplate']     = $activeObj->productTemplate();
			}
		}

		//获得扩展属性
		$tb_attribute_goods = new IQuery('goods_attribute as g');
		$tb_attribute_goods->join  = 'left join attribute as a on a.id=g.attribute_id ';
		$tb_attribute_goods->fields=' a.name,g.attribute_value ';
		$tb_attribute_goods->where = "goods_id='".$goods_id."' and attribute_id!=''";
		$goods_info['attribute'] = $tb_attribute_goods->find();

		//获得商品的价格区间
		$tb_product = new IModel('products');
		$product_info = $tb_product->getObj('goods_id='.$goods_id,'max(sell_price) as maxSellPrice ,max(market_price) as maxMarketPrice');
		if(isset($product_info['maxSellPrice']) && $goods_info['sell_price'] != $product_info['maxSellPrice'])
		{
			$goods_info['sell_price']   .= "-".$product_info['maxSellPrice'];
		}

		if(isset($product_info['maxMarketPrice']) && $goods_info['market_price'] != $product_info['maxMarketPrice'])
		{
			$goods_info['market_price'] .= "-".$product_info['maxMarketPrice'];
		}

		//获得会员价
		$countsumInstance = new countsum();
		$goods_info['group_price'] = $countsumInstance->groupPriceRange($goods_id);

		//获取商家信息
		if($goods_info['seller_id'])
		{
			$sellerDB = new IModel('seller');
			$sellerData = $sellerDB->getObj($goods_info['seller_id']);
			if(!$sellerData)
			{
			    IError::show(403,ILang::get('商家信息不存在'));
			}
			$goods_info['seller'] = $sellerData;
		}

		//增加浏览次数
		$visit    = ISafe::get('visit');
		$checkStr = "#".$goods_id."#";
		if($visit && strpos($visit,$checkStr) !== false)
		{
		}
		else
		{
			$tb_goods->setData(array('visit' => 'visit + 1'));
			$tb_goods->update('id = '.$goods_id,'visit');
			$visit = $visit === null ? $checkStr : $visit.$checkStr;
			ISafe::set('visit',$visit);
		}

		//数据处理用于显示
		$goods_info['weight'] = common::formatWeight($goods_info['weight']);

		$this->setRenderData($goods_info);
		$this->redirect('products');
	}
	//商品讨论更新
	function discussUpdate()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');
		$content  = IFilter::act(IReq::get('content'),'text');
		$captcha  = IReq::get('captcha');
		$_captcha = ISafe::get('captcha');
		$return   = array('isError' => true , 'message' => '');

		if(!$this->user['user_id'])
		{
			$return['message'] = ILang::get('请先登录系统');
		}
    	else if(!$captcha || !$_captcha || $captcha != $_captcha)
    	{
    		$return['message'] = ILang::get('验证码输入不正确');
    	}
    	else if(trim($content) == '')
    	{
    		$return['message'] = ILang::get('内容不能为空');
    	}
    	else
    	{
    		$return['isError'] = false;

			//插入讨论表
			$tb_discussion = new IModel('discussion');
			$dataArray     = array(
				'goods_id' => $goods_id,
				'user_id'  => $this->user['user_id'],
				'time'     => ITime::getDateTime(),
				'contents' => $content,
			);
			$tb_discussion->setData($dataArray);
			$tb_discussion->add();

			$return['time']     = $dataArray['time'];
			$return['contents'] = $content;
			$return['username'] = $this->user['username'];
    	}
    	echo JSON::encode($return);
	}

	//获取货品数据
	function getProduct()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$specJSON = IReq::get('specJSON');
		if(!$specJSON || !is_array($specJSON))
		{
			echo JSON::encode(array('flag' => 'fail','message' => ILang::get('规格值不符合标准')));
			exit;
		}

		//获取货品数据
		$tb_products = new IModel('products');
		$procducts_info = $tb_products->getObj("goods_id = ".$goods_id." and spec_array = '".IFilter::act(htmlspecialchars_decode(JSON::encode($specJSON)))."'");

		//匹配到货品数据
		if(!$procducts_info)
		{
			echo JSON::encode(array('flag' => 'fail','message' => ILang::get('没有找到相关货品')));
			exit;
		}

		//获得会员价
		$countsumInstance = new countsum();
		$group_price = $countsumInstance->getGroupPrice($procducts_info['id'],'product');

		//会员价格
		if($group_price !== null)
		{
			$procducts_info['group_price'] = $group_price;
		}

		//处理数据内容
		$procducts_info['weight'] = common::formatWeight($procducts_info['weight']);
		echo JSON::encode(array('flag' => 'success','data' => $procducts_info));
	}

	//顾客评论ajax获取
	function comment_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$commentDB = new IQuery('comment as c');
		$commentDB->join   = 'left join goods as go on c.goods_id = go.id left join user as u on u.id = c.user_id';
		$commentDB->fields = 'u.head_ico,u.username,c.*';
		$commentDB->where  = 'c.goods_id = '.$goods_id.' and c.status = 1';
		$commentDB->order  = 'c.id desc';
		$commentDB->page   = $page;
		$data     = $commentDB->find();

		//过滤用户手机号码
		foreach($data as $key => $val)
		{
		    if(IValidate::mobi($val['username']))
		    {
		        $val['username'] = IFilter::hideMobile($val['username']);
		        $data[$key] = $val;
		    }
		}
		$pageHtml = $commentDB->getPageBar("javascript:void(0);",'onclick="comment_ajax([page])"');
		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//购买记录ajax获取
	function history_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$orderGoodsDB = new IQuery('order_goods as og');
		$orderGoodsDB->join   = 'left join order as o on og.order_id = o.id left join user as u on o.user_id = u.id';
		$orderGoodsDB->fields = 'o.user_id,og.goods_price,og.goods_nums,o.create_time as completion_time,u.username';
		$orderGoodsDB->where  = 'og.goods_id = '.$goods_id.' and o.status = 5';
		$orderGoodsDB->order  = 'o.create_time desc';
		$orderGoodsDB->page   = $page;
		$data = $orderGoodsDB->find();

		//过滤用户手机号码
		foreach($data as $key => $val)
		{
		    if(IValidate::mobi($val['username']))
		    {
		        $val['username'] = IFilter::hideMobile($val['username']);
		        $data[$key] = $val;
		    }
		}
		$pageHtml = $orderGoodsDB->getPageBar("javascript:void(0);",'onclick="history_ajax([page])"');
		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//讨论数据ajax获取
	function discuss_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$discussDB = new IQuery('discussion as d');
		$discussDB->join = 'left join user as u on d.user_id = u.id';
		$discussDB->where = 'd.goods_id = '.$goods_id;
		$discussDB->order = 'd.id desc';
		$discussDB->fields = 'u.username,d.time,d.contents';
		$discussDB->page = $page;
		$data = $discussDB->find();

		//过滤用户手机号码
		foreach($data as $key => $val)
		{
		    if(IValidate::mobi($val['username']))
		    {
		        $val['username'] = IFilter::hideMobile($val['username']);
		        $data[$key] = $val;
		    }
		}
		$pageHtml = $discussDB->getPageBar("javascript:void(0);",'onclick="discuss_ajax([page])"');
		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//买前咨询数据ajax获取
	function refer_ajax()
	{
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$page     = IFilter::act(IReq::get('page'),'int') ? IReq::get('page') : 1;

		$referDB = new IQuery('refer as r');
		$referDB->join = 'left join user as u on r.user_id = u.id';
		$referDB->where = 'r.goods_id = '.$goods_id;
		$referDB->order = 'r.id desc';
		$referDB->fields = 'u.username,u.head_ico,r.time,r.question,r.reply_time,r.answer';
		$referDB->page = $page;

		$data = $referDB->find();

		//过滤用户手机号码
		foreach($data as $key => $val)
		{
		    if(IValidate::mobi($val['username']))
		    {
		        $val['username'] = IFilter::hideMobile($val['username']);
		        $data[$key] = $val;
		    }
		}
		$pageHtml = $referDB->getPageBar("javascript:void(0);",'onclick="refer_ajax([page])"');
		echo JSON::encode(array('data' => $data,'pageHtml' => $pageHtml));
	}

	//评论列表页
	function comments_list()
	{
		$id   = IFilter::act(IReq::get("id"),'int');
		$type = IFilter::act(IReq::get("type"));
		$data = array();

		//评分级别
		$type_config = array('bad'=>'1','middle'=>'2,3,4','good'=>'5');
		$point       = isset($type_config[$type]) ? $type_config[$type] : "";

		//查询评价数据
		$this->commentQuery = Api::run('getListByGoods',$id,$point);
		$this->commentCount = Comment_Class::get_comment_info($id);
		$this->goods        = Api::run('getGoodsInfo',array("#id#",$id));
		if(!$this->goods)
		{
			IError::show(ILang::get('商品信息不存在'));
		}
		$this->redirect('comments_list');
	}

	//提交评论页
	function comments()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if(!$id)
		{
			IError::show(403,ILang::get('传递的参数不完整'));
		}

		if(!isset($this->user['user_id']) || $this->user['user_id']==null )
		{
			IError::show(403,ILang::get('登录后才允许评论'));
		}

		$result = Comment_Class::can_comment($id,$this->user['user_id']);
		if(is_string($result))
		{
			IError::show(403,$result);
		}

		$this->comment      = $result;
		$this->commentCount = Comment_Class::get_comment_info($result['goods_id']);
		$this->goods        = Comment_Class::goodsInfo($id);
		if(!$this->goods)
		{
			IError::show(ILang::get('商品信息不存在'));
		}
		$this->redirect("comments");
	}

	/**
	 * @brief 进行商品评论 ajax操作
	 */
	public function comment_add()
	{
		$id      = IFilter::act(IReq::get('id'),'int');
		$content = IFilter::act(IReq::get("contents"));
		$img_list= IFilter::act(IReq::get("_imgList"));

		if(!$id || !$content)
		{
			IError::show(403,ILang::get('填写完整的评论内容'));
		}

		if(!isset($this->user['user_id']) || !$this->user['user_id'])
		{
			IError::show(403,ILang::get('未登录用户不能评论'));
		}

		$data = array(
			'point'        => IFilter::act(IReq::get('point'),'float'),
			'contents'     => $content,
			'status'       => 1,
			'img_list'     => '',
			'comment_time' => ITime::getNow("Y-m-d"),
		);

		if($data['point']==0)
		{
			IError::show(403,ILang::get('请选择分数'));
		}

        if(isset($img_list) && $img_list)
        {
            $img_list   = trim($img_list,',');
            $img_list   = explode(",",$img_list);
            if(count($img_list) > 5){
                IError::show(403,ILang::get('最多上传5张图片'));
            }
            $img_list   = array_filter($img_list);
            $img_list   = JSON::encode($img_list);
            $data['img_list'] = $img_list;
        }

		$result = Comment_Class::can_comment($id,$this->user['user_id']);
		if(is_string($result))
		{
			IError::show(403,$result);
		}

		$tb_comment = new IModel("comment");
		$tb_comment->setData($data);
		$re         = $tb_comment->update("id={$id}");

		if($re)
		{
			$commentRow = $tb_comment->getObj('id = '.$id);

			//同步更新goods表,comments,grade
			$goodsDB = new IModel('goods');
			$goodsDB->setData(array(
				'comments' => 'comments + 1',
				'grade'    => 'grade + '.$commentRow['point'],
			));
			$goodsDB->update('id = '.$commentRow['goods_id'],array('grade','comments'));

			//同步更新seller表,comments,grade
			$sellerDB = new IModel('seller');
			$sellerDB->setData(array(
				'comments' => 'comments + 1',
				'grade'    => 'grade + '.$commentRow['point'],
			));
			$sellerDB->update('id = '.$commentRow['seller_id'],array('grade','comments'));
			$this->redirect("/site/comments_list/id/".$commentRow['goods_id']);
		}
		else
		{
			IError::show(403,ILang::get('评论失败'));
		}
	}

	function pic_show()
	{
		$this->layout="";

		$id   = IFilter::act(IReq::get('id'),'int');
		$item = Api::run('getGoodsInfo',array('#id#',$id));
		if(!$item)
		{
			IError::show(403,ILang::get('商品信息不存在'));
		}
		$photo = Api::run('getGoodsPhotoRelationList',array('#id#',$id));
		$this->setRenderData(array("id" => $id,"item" => $item,"photo" => $photo));
		$this->redirect("pic_show");
	}

	function help()
	{
		$id       = IFilter::act(IReq::get("id"),'int');
		$tb_help  = new IModel("help");
		$help_row = $tb_help->getObj($id);
		if($help_row)
		{
		    $tb_help_cat = new IModel("help_category");
		    $cat_row     = $tb_help_cat->getObj("id={$help_row['cat_id']}");

		    $this->cat_row  = $cat_row;
		    $this->help_row = $help_row;
		}

		if(!isset($cat_row) || !$cat_row)
		{
			IError::show(403,ILang::get('帮助信息或者帮助分类不存在'));
		}

		$this->redirect("help");
	}

	function help_list()
	{
		$id          = IFilter::act(IReq::get("id"),'int');
		$tb_help_cat = new IModel("help_category");
		$cat_row     = $tb_help_cat->getObj("id={$id}");

		//帮助分类数据存在
		if($cat_row)
		{
			$this->helpQuery = Api::run('getHelpListByCatId',$id);
			$this->cat_row   = $cat_row;
		}
		else
		{
			$this->helpQuery = Api::run('getHelpList');
			$this->cat_row   = array('id' => 0,'name' => ILang::get('站点帮助'));
		}
		$this->redirect("help_list");
	}

	//团购页面
	function groupon()
	{
		$id = IFilter::act(IReq::get("id"),'int');

		//指定某个团购
		if($id)
		{
			$this->regiment_list = Api::run('getRegimentRowById',array('#id#',$id));
			$this->regiment_list = $this->regiment_list ? array($this->regiment_list) : array();
		}
		else
		{
			$this->regiment_list = Api::run('getRegimentList');
		}

		if(!$this->regiment_list)
		{
			IError::show(ILang::get('当前没有可以参加的团购活动'));
		}

		//往期团购
		$this->ever_list = Api::run('getEverRegimentList');
		$this->redirect("groupon");
	}

	//品牌列表页面
	function brand()
	{
		$id   = IFilter::act(IReq::get('id'),'int');
		$name = IFilter::act(IReq::get('name'));
		$this->setRenderData(array('id' => $id,'name' => $name));
		$this->redirect('brand');
	}

	//品牌专区页面
	function brand_zone()
	{
		$brandId  = IFilter::act(IReq::get('id'),'int');
		$brandRow = Api::run('getBrandInfo',$brandId);
		if(!$brandRow)
		{
			IError::show(403,ILang::get('品牌信息不存在'));
		}
		$this->setRenderData(array('brandId' => $brandId,'brandRow' => $brandRow));
		$this->redirect('brand_zone');
	}

	//商家主页
	function home()
	{
		$seller_id = IFilter::act(IReq::get('id'),'int');
		$cat_id    = IFilter::act(IReq::get('cat'),'int');
		$sellerRow = Api::run('getSellerInfo',$seller_id);
		if(!$sellerRow)
		{
			IError::show(403,ILang::get('商户信息不存在'));
		}
		$this->setRenderData(array('sellerRow' => $sellerRow,'seller_id' => $seller_id,'cat_id' => $cat_id));
		$this->redirect('home');
	}

	//拼团页面
	function assemble()
	{
		$id = IFilter::act(IReq::get("id"),'int');

		//指定某个拼团
		if($id)
		{
			$this->assemble_list = Api::run('getAssembleRowById',array('#id#',$id));
			$this->assemble_list = $this->assemble_list ? array($this->assemble_list) : array();
		}
		else
		{
			$this->assemble_list = Api::run('getAssembleList');
		}

		if(!$this->assemble_list)
		{
			IError::show(ILang::get('当前没有可以参加的拼团活动'));
		}
		$this->redirect("assemble");
	}

	//专题页面
	public function topic()
	{
	    $id   = IFilter::act(IReq::get('id'),'int');
		$word = IFilter::act(IReq::get('word'),'text');
	    $this->data = Api::run('getTopicRow',['#id#',$id]);
		$this->goodsData = [];
	    if($this->data)
	    {
			if($this->data['goods_ids'])
			{
				$where = 'id in ('.$this->data['goods_ids'].')';
				if($word)
				{
					$where .= ' and name like "%'.$word.'%" ';
				}
				$goodsDB = new IModel('goods');
				$this->goodsData = $goodsDB->query($where);
			}
			$this->redirect('topic');
//专题内搜索
echo <<< OEF
<script>
jQuery(function()
{
	$('[name="action"]').val('topic');
	$('[name="action"]').after('<input type="hidden" name="id" value="{$id}" />');
	$('[name="word"]').val('{$word}');
});
</script>
OEF;
			if($word && !$this->goodsData)
			{
				IError::show(ILang::get('当前专题无此商品'));
			}
	    }
	    else
	    {
	        IError::show(ILang::get('当前专题不存在'));
	    }
	}
}
