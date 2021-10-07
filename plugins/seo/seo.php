<?php
/**
 * @copyright (c) 2011 aircheng
 * @file seo.php
 * @brief seo处理
 * @author nswe
 * @date 2016/7/12 9:15:21
 * @version 4.5
 */
class seo extends pluginBase
{
    public static $data = ["name" => "","title" => "","keywords" => "","description" => "","img" => ""];

	//插件注册
	public function reg()
	{
		//后台管理SEO
		plugin::reg("onSystemMenuCreate",function(){
			$link = "/plugins/seo_list";
			$link = "javascript:void(art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'seo'}));";
			Menu::$menu["插件"]["插件管理"][$link] = $this->name();
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_list",function(){
			self::controller()->seo_list = function(){$this->seo_list();};
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_edit",function(){
			self::controller()->seo_edit = function(){$this->seo_edit();};
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_update",function(){
			self::controller()->seo_update = function(){$this->seo_update();};
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_del",function(){
			self::controller()->seo_del = function(){$this->seo_del();};
		});

		//设置网页SEO信息
		plugin::reg("onFinishView",$this,"setSeo");
	}

	//seo变量信息
	public function seoVarInfo()
	{
		return array(
			"{name}" => "网页动态内容名称",
			"{title}" => "网页动态内容标题",
			"{keywords}" => "网页动态内容关键词",
			"{description}" => "网页动态内容描述",
			"{web_name}" => "网站首页名称",
			"{web_title}" => "网站首页标题",
			"{web_keywords}" => "网站首页关键词",
			"{web_description}" => "网站首页描述",
		);
	}

	//编辑SEO页面
	public function seo_edit()
	{
		$id     = IFilter::act(IReq::get('id'),'int');
		$seoRow = array();
		if($id)
		{
			$seoDB = new IModel('seo');
			$seoRow= $seoDB->getObj('id = '.$id);
		}
		$this->view('seo_edit',array('seoData' => $seoRow,'seoVar' => $this->seoVarInfo()));
	}

	//更新SEO信息
	public function seo_update()
	{
		$id          = IFilter::act(IReq::get('id'),'int');
		$name        = IFilter::act(IReq::get('name'));
		$pathinfo    = IFilter::act(IReq::get('pathinfo'));
		$title       = IFilter::act(IReq::get('title'));
		$keywords    = IFilter::act(IReq::get('keywords'));
		$description = IFilter::act(IReq::get('description'));

		$updateData  = array(
			'name'        => $name,
			'pathinfo'    => trim($pathinfo,"/"),
			'title'       => $title,
			'keywords'    => $keywords,
			'description' => $description,
		);

		$seoDB = new IModel('seo');
		$seoDB->setData($updateData);
		if($id)
		{
			$seoDB->update('id = '.$id);
		}
		else
		{
			$seoDB->add();
		}
		$this->view('seo_list');
	}

	//删除SEO信息
	public function seo_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$seoDB = new IModel('seo');
			$seoDB->del('id = '.$id);
		}
		$this->view('seo_list');
	}

	//SEO列表信息
	public function seo_list()
	{
		$this->view('seo_list');
	}

	public static function name()
	{
		return "SEO优化插件";
	}

	public static function description()
	{
		return "设置各个网页SEO信息，方便搜索引擎收录提升网站排名";
	}

	public static function install()
	{
		$seoDB = new IModel('seo');
		if($seoDB->exists())
		{
			return true;
		}
		$data = array(
			"comment" => self::name(),
			"column"  => array(
				"id"         => array("type" => "int(11) unsigned",'auto_increment' => 1),
				"name"       => array("type" => "varchar(255)","comment" => "伪静态名称"),
				"pathinfo"   => array("type" => "varchar(255)","comment" => "URL伪静态格式(控制器/动作)"),
				"title"      => array("type" => "varchar(255)","comment" => "SEO信息title"),
				"keywords"   => array("type" => "text","comment" => "SEO信息keywords"),
				"description"=> array("type" => "text","comment" => "SEO信息description"),
			),
			"index" => array("primary" => "id","key" => "pathinfo"),
		);
		$seoDB->setData($data);
		$seoDB->createTable();

		//插入标准seo数据
		$defaultData = array(
			array("name" => "首页",    "pathinfo" => "site/index",         "title" => "{web_title}","keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "购物车",  "pathinfo" => "simple/cart",        "title" => "购物车",     "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "用户登录","pathinfo" => "simple/login",       "title" => "用户登录",   "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "用户注册","pathinfo" => "simple/reg",         "title" => "用户注册",   "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "商家注册","pathinfo" => "simple/seller",      "title" => "商家入驻",   "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "商家列表","pathinfo" => "site/seller",        "title" => "商家列表",   "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "商家主页","pathinfo" => "site/home",          "title" => "{name}",     "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "商品列表","pathinfo" => "site/pro_list",      "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "商品图库","pathinfo" => "site/pic_show",      "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "商品资料","pathinfo" => "site/pro_detail",    "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "商品详情","pathinfo" => "site/products",      "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "商品检索","pathinfo" => "site/search_list",   "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "品牌列表","pathinfo" => "site/brand",         "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "品牌主页","pathinfo" => "site/brand_zone",    "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "公告列表","pathinfo" => "site/notice",        "title" => "商城公告",   "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "公告详情","pathinfo" => "site/notice_detail", "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "文章列表","pathinfo" => "site/article",       "title" => "最新资讯",   "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "文章详情","pathinfo" => "site/article_detail","title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "帮助列表","pathinfo" => "site/help_list",     "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "帮助详情","pathinfo" => "site/help",          "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "团购列表","pathinfo" => "site/groupon",       "title" => "{name}",     "keywords" => "{keywords}"    ,"description" => "{description}"),
			array("name" => "特价列表","pathinfo" => "site/sale",          "title" => "特价商品",   "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "商品标签","pathinfo" => "site/tags",          "title" => "商品标签",   "keywords" => "{web_keywords}","description" => "{web_description}"),
			array("name" => "专题活动","pathinfo" => "site/topic",         "title" => "{name}",     "keywords" => "{web_keywords}","description" => "{web_description}"),
		);
		foreach($defaultData as $val)
		{
			$seoDB->setData($val);
			$seoDB->add();
		}
		return true;

	}

	public static function uninstall()
	{
		$seoDB = new IModel('seo');
		return $seoDB->dropTable();
	}

	public static function configName()
	{
		return array(
			"webTitlePosition" => array("name" => "添加网站名称到网页标题","type" => "select","value" => array("后缀" => "behind","前缀" => "front","否" => "none")),
			"defaultSEO" => array("name" => "未设置网页的SEO规则","type" => "select","value" => array("采用默认首页SEO信息" => "defaultWeb","不显示" => "none")),
		);
	}

	//运行SEO网页渲染
	public function setSeo()
	{
		//获取插件配置
		$defConfig= $this->config();

		//获取当前pathinfo信息
		$pathinfo = $this->controller()->getId().'/'.$this->action()->getId();

		//查询SEO信息
		$seoDB    = new IModel('seo');
		$seoConfig= $seoDB->getObj('pathinfo = "'.$pathinfo.'"');
		if($seoConfig)
		{
			unset($seoConfig['id'],$seoConfig['name'],$seoConfig['pathinfo']);

			//网站名称缀处理
			switch($defConfig['webTitlePosition'])
			{
				case "front":
				{
					$seoConfig['title'] = $this->controller()->_siteConfig->name.' - '.$seoConfig['title'];
				}
				break;

				case "behind":
				{
					$seoConfig['title'] = $seoConfig['title'].' - '.$this->controller()->_siteConfig->name;
				}
				break;
			}
		}
		else if($defConfig['defaultSEO'] == 'defaultWeb')
		{
			$seoConfig = array(
				'title'       => $this->controller()->_siteConfig->index_seo_title,
				'keywords'    => $this->controller()->_siteConfig->index_seo_keywords,
				'description' => $this->controller()->_siteConfig->index_seo_description,
			);
		}

		//设置网页SEO信息
		if(isset($seoConfig) && $seoConfig)
		{
			$seoConfig = $this->replaceSEOVar($pathinfo,$seoConfig);
			$this->set($seoConfig);
		}
	}

	/**
	 * 在view里为iwebshop页面调整title、keywords、description
	 * @param array $config array('title'=>'','keywords'=>'','description'=>'')
	 */
	public static function set($config)
	{
		$html = ob_get_contents();
		preg_match("!<head>(.*?)</head>!ius",$html,$m);

		//如果页面本来就没有head头，则直接返回
		if(!isset($m[0]) || $m[0]=="")
		{
			return;
		}

		ob_clean();//清空之前的所有输出内容,把seo数据重新整合后再输出
		$head = $m[1];
		if(isset($config['title']))
		{
			$title = "<title>{$config['title']}</title>";
			if(preg_match('!<title>.*?</title>!',$head))
			{
				$head = preg_replace("!<title>.*?</title>!ui",$title,$head,1);
			}
			else
			{
				$head .= "\n".$title;
			}
		}

		if(isset($config['keywords']))
		{
			$keywords = "<meta name='keywords' content='{$config['keywords']}'>";
			if(preg_match("!<meta\s.*?name=['\"]keywords!ui",$head))
			{
				$head = preg_replace("!<meta\s.*?name=['\"]keywords.*?/?>!ui",$keywords,$head,1);
			}
			else
			{
				$head .= "\n".$keywords;
			}
		}

		if(isset($config['description']))
		{
			$description = "<meta name='description' content='{$config['description']}'>";
			if(preg_match("!<meta\s.*?name=['\"]description!ui",$head))
			{
				$head = preg_replace("!<meta\s.*?name=['\"]description.*?/?>!ui",$description,$head,1);
			}
			else
			{
				$head .= "\n".$description;
			}
		}
		$head = "<head>{$head}</head>";
		$html = preg_replace("!<head>(.*?)</head>!ius",$head,$html,1);
		echo $html;
	}

	/**
	 * 对部分特殊ACTION进行变量预处理
	 * @param string $pathinfo 伪静态地址
	 * @param array $content SEO信息 array(title => "标题",keywords => "关键字",description => "描述")
	 */
	public function replaceSEOVar($pathinfo,$content)
	{
		//根据网页具体内容替换变量
		$name        = "";
		$title       = "";
		$keywords    = "";
		$description = "";
		$img         = $this->controller()->_siteConfig->logo;

		switch($pathinfo)
		{
			case "site/products":
			case "site/pic_show":
			case "site/pro_detail":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('goods');
				$contentRow = $contentDB->getObj('id = '.$id,"name,keywords,description,img");
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['name'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['description'];
					$img         = $contentRow['img'];
				}
			}
			break;
			case "site/article_detail":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('article');
				$contentRow = $contentDB->getObj('id = '.$id,"title,keywords,description");
				if($contentRow)
				{
					$name        = $contentRow['title'];
					$title       = $contentRow['title'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['description'];
				}
			}
			break;
			case "site/article":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('article_category');
				$contentRow = $contentDB->getObj('id = '.$id);
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['title'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['description'];
				}
			}
			break;
			case "site/notice_detail":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('announcement');
				$contentRow = $contentDB->getObj('id = '.$id,"title,keywords,description");
				if($contentRow)
				{
					$name        = $contentRow['title'];
					$title       = $contentRow['title'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['description'];
				}
			}
			break;
			case "site/help_list":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("help_category");
				$contentRow = $contentDB->getObj("id = ".$id,'name');
				if($contentRow)
				{
					$name = $contentRow['name'];
				}
			}
			break;
			case "site/help":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("help");
				$contentRow = $contentDB->getObj("id = ".$id,'name');
				if($contentRow)
				{
					$name = $contentRow['name'];
				}
			}
			break;
			case "site/home":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("seller");
				$contentRow = $contentDB->getObj("id = ".$id,'true_name');
				if($contentRow)
				{
					$name = $contentRow['true_name'];
				}
			}
			break;
			case "site/pro_list":
			{
				$id         = IFilter::act(IReq::get("cat"),'int');
				$contentDB  = new IModel("category");
				$contentRow = $contentDB->getObj("id = ".$id,'name,keywords,descript,title');
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['title'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['descript'];
				}
			}
			break;
			case "site/search_list":
			{
				$name = $this->controller()->word;
			}
			break;
			case "site/groupon":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("regiment");
				$contentRow = $contentDB->getObj("id = ".$id,'title,img');
				$name       = "团购活动";
				$title      = "团购活动";
				if($contentRow)
				{
					$name  = $contentRow['title'];
					$title = $contentRow['title'];
					$img   = $contentRow['img'];
				}
			}
			break;
			case "site/brand_zone":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("brand");
				$contentRow = $contentDB->getObj("id = ".$id,'name,description,logo');
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['name'];
					$description = $contentRow['description'];
					$img         = $contentRow['logo'];
				}
			}
			break;
			case "site/topic":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("topic");
				$contentRow = $contentDB->getObj("id = ".$id,'name');
				if($contentRow)
				{
					$name = $contentRow['name'];
				}
			}
			break;
		}

		//标签代表的数据内容
		$replaceArray = array_merge(
			array(
				"{web_name}"        => $this->controller()->_siteConfig->name,
				"{web_title}"       => $this->controller()->_siteConfig->index_seo_title,
				"{web_keywords}"    => $this->controller()->_siteConfig->index_seo_keywords,
				"{web_description}" => $this->controller()->_siteConfig->index_seo_description,
			),
			array(
				"{name}"            => $name,
				"{title}"           => $title,
				"{keywords}"        => $keywords,
				"{description}"     => $description,
			)
		);

        //设置控制器全局
		self::$data = ["name" => $name,"title" => $title,"keywords" => $keywords,"description" => $description,"img" => $img];

		//对标签数据进行逐个替换
		array_walk($content,function(&$value,$key,$replaceArray){$value = strtr($value,$replaceArray);},$replaceArray);

        //补充空白的数据
		foreach(self::$data as $key => $val)
		{
		    if(!$val && $content && isset($content[$key]) && $content[$key])
		    {
		        self::$data[$key] = $content[$key];
		    }
		}
		return $content;
	}
}