<?php
/**
 * @copyright (c) 2017 aircheng.com
 * @file sellerDecorate.php
 * @brief 商家店铺装饰类
 * @date 2017/1/5 10:41:41
 * @version 4.7

 * @update 新增导航，广告
 * @date 2019/7/20 8:40:18
 * @author killme

 * @update 优化商品列表seller_goods_list
 * @date 2020/5/15 18:51:32
 * @author nswe
 */
class sellerDecorate extends pluginBase
{
	//商户布局方案名称
	public $layout = "default";

	//商户主题方案名称
	public $theme = "";

	public $actions = ['site' => ['home','seller_goods_list']];

	private $_seller_id = "";

	public static function name()
	{
		return "商家店铺装饰";
	}

	public static function description()
	{
		return "装饰各个商家店铺，让店铺有独立的主题模板风格，商家模板放到: /plugins/sellerDecorate/sellerTheme 里面";
	}

	public static function install()
	{
		$decorateDB = new IModel('seller_decorate');
		if($decorateDB->exists())
		{
			return true;
		}
		$data = array(
			"comment" => self::name(),
			"column"  => array(
				"seller_id" => array("type" => "int(11) unsigned","comment" => "商家ID"),
				"theme" => array("type" => "varchar(255)","comment" => "模板主题名称"),
			),
			"index" => array("primary" => "seller_id"),
		);
		$decorateDB->setData($data);
		return $decorateDB->createTable();
	}

	public static function uninstall()
	{
		$decorateDB = new IModel('seller_decorate');
		return $decorateDB->dropTable();
	}

	public function reg()
	{
        parent::reg();

		//后台管理部分
		plugin::reg("onSystemMenuCreate",function(){
			$link = "/plugins/decorate_list";
			$link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'decorate'});";
			Menu::$menu["插件"]["插件管理"][$link] = $this->name();
		});

		//装饰列表视图
		plugin::reg("onBeforeCreateAction@plugins@decorate_list",function(){
			self::controller()->decorate_list = function(){$this->view('decorate_list',array('themeData' => $this->getSellerTheme()));};
		});

		//设置商户主题
		plugin::reg("onBeforeCreateAction@plugins@decorate_setting",function(){
			self::controller()->decorate_setting = function()
			{
				$seller_id = IFilter::act(IReq::get('seller_id'),'int');
				$theme     = IFilter::act(IReq::get('theme'));
				$this->updateTheme($seller_id,$theme);
			};
		});

		//商户管理部分
		$configData = $this->config();
		if(isset($configData['sellerCostom']) && $configData['sellerCostom'] == 'yes')
		{
			//商家装饰管理
			plugin::reg("onSellerMenuCreate",function(){
				$link = "/seller/decorate_edit";
				$link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'".$this->name()."',width:'100%',height:'100%',id:'decorate'});";
				menuSeller::$menu["配置模块"][$link] = $this->name();
			});

			//商户装饰修改页面
			plugin::reg("onBeforeCreateAction@seller@decorate_edit",function(){
				self::controller()->decorate_edit = function(){
					$seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
					$sellerDeDB  = new IModel('seller_decorate');
					$sellerDeRow = $sellerDeDB->getObj('seller_id = '.$seller_id);
					$this->view('decorate_edit',array('themeData' => $this->getSellerTheme(),'sellerDeRow' => $sellerDeRow));
				};
			});

			//商户设置主题
			plugin::reg("onBeforeCreateAction@seller@decorate_setting",function(){
				self::controller()->decorate_setting = function()
				{
					$seller_id = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
					$theme     = IFilter::act(IReq::get('theme'));
					$this->updateTheme($seller_id,$theme);
				};
			});
		}

        //商家装饰导航管理
        plugin::reg("onSellerMenuCreate",function(){
            $link = "/seller/conf_guide";
            $link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'商家店铺导航',width:'100%',height:'100%',id:'guide'});";
            menuSeller::$menu["配置模块"][$link] = '店铺导航';

            $link = "/seller/ad_position_list";
            $link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'商家店铺广告位',width:'100%',height:'100%',id:'ad_position_list'});";
            menuSeller::$menu["配置模块"][$link] = '店铺广告位';

            $link = "/seller/ad_list";
            $link = "javascript:art.dialog.open('".IUrl::creatUrl($link)."',{title:'商家店铺广告',width:'100%',height:'100%',id:'ad_list'});";
            menuSeller::$menu["配置模块"][$link] = '店铺广告';

			$link = "/seller/conf_banner";
			menuSeller::$menu["配置模块"][$link] = '主页幻灯图';
        });

        //商户装饰导航修改
        plugin::reg("onBeforeCreateAction@seller@conf_guide",function(){
            self::controller()->conf_guide = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $this->view('conf_guide',array('seller_id' => $seller_id));
            };
        });

        //商户装饰导航更新
        plugin::reg("onBeforeCreateAction@seller@guide_update",function(){
            self::controller()->guide_update = function()
            {
                $seller_id = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $guideName = IFilter::act(IReq::get('guide_name'));
                $guideLink = IFilter::act(IReq::get('guide_link'));
                $data      = array();
                $guideObj = new IModel('guide');
                if($guideName)
                {
                    foreach($guideName as $key => $val)
                    {
                        $data[$key]['name']       = $guideName[$key];
                        $data[$key]['link']       = $guideLink[$key];
                        $data[$key]['seller_id']  = $seller_id;
                    }
                }

                //清空导航栏
                $guideObj->del('seller_id = '.$seller_id);

                if($data)
                {
                    //插入数据
                    foreach($data as $dataArray)
                    {
                        $guideObj->setData($dataArray);
                        $guideObj->add();
                    }
                }
                die("保存成功<script>parent.tips('保存成功');parent.art.dialog.list['guide'].close();</script>");
            };
        });

        //商户广告位列表
        plugin::reg("onBeforeCreateAction@seller@ad_position_list",function(){
            self::controller()->ad_position_list = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $this->view('ad_position_list',array('seller_id' => $seller_id));
            };
        });

        //商户广告位 添加修改
        plugin::reg("onBeforeCreateAction@seller@ad_position_edit",function(){
            self::controller()->ad_position_edit = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $id   = IFilter::act( IReq::get('id'),'int' );
                $name = IFilter::act( IReq::get('name'),'text' );

                $obj = new IModel('ad_position');
                if($name)
                {
                    $positionRow = $obj->getObj('name="'.$name.'" and seller_id = '.$seller_id);
                }
                else if($id)
                {
                    $positionRow = $obj->getObj('id = '.$id .' and seller_id = '.$seller_id);
                }
                $positionRow = isset($positionRow) && $positionRow ? $positionRow : array('name' => $name);
                $this->view('ad_position_edit',array('positionRow' => $positionRow));
            };
        });

        //商户广告位添加和修改动作
        plugin::reg("onBeforeCreateAction@seller@ad_position_edit_act",function(){
            self::controller()->ad_position_edit_act = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $id = IFilter::act( IReq::get('id') );
                $obj = new IModel('ad_position');
                $dataArray = array(
                    'name'         => IFilter::act( IReq::get('name','post') ,'string' ),
                    'fashion'      => IFilter::act( IReq::get('fashion','post'),'int' ),
                    'status'       => IFilter::act( IReq::get('status','post'),'int' ),
                    'seller_id'   => $seller_id
                );
                $obj->setData($dataArray);

                if($id)
                {
                    $where = 'id = '.$id.' and seller_id = '.$seller_id;
                    $result = $obj->update($where);
                }
                else
                {
                    $result = $obj->add();
                }
                $this->view('ad_position_list');
            };
        });

        //商户广告位删除
        plugin::reg("onBeforeCreateAction@seller@ad_position_del",function(){
            self::controller()->ad_position_del = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $id          = IFilter::act( IReq::get('id') , 'int' );
                if($id)
                {
                    $obj = new IModel('ad_position');
                    if(is_array($id) && isset($id[0]) && $id[0]!='')
                    {
                        $id_str = join(',',$id);
                        $where  = ' id in ('.$id_str.') and seller_id = '.$seller_id;
                    }
                    else
                    {
                        $where = 'id = '.$id .' and seller_id = '.$seller_id;
                    }
                    $obj->del($where);
                }
                $this->view('ad_position_list');
            };
        });

        //商户广告列表
        plugin::reg("onBeforeCreateAction@seller@ad_list",function(){
            self::controller()->ad_list = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $this->view('ad_list',array('seller_id' => $seller_id));
            };
        });

        //商户广告 添加修改
        plugin::reg("onBeforeCreateAction@seller@ad_edit",function(){
            self::controller()->ad_edit = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $id          = IFilter::act(IReq::get('id'),'int');
                $positionId  = IFilter::act(IReq::get('pid'),'int');
                $adRow = array('position_id' => $positionId);
                if($id)
                {
                    $obj   = new IModel('ad_manage');
                    $adRow = $obj->getObj('id = '.$id.' and seller_id = '.$seller_id);
                }
                $this->view('ad_edit',array('adRow'=>$adRow,'seller_id'=>$seller_id));
            };
        });

        //商户广告添加和修改动作
        plugin::reg("onBeforeCreateAction@seller@ad_edit_act",function(){
            self::controller()->ad_edit_act = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $id      = IFilter::act(IReq::get('id'),'int');
                $type    = IFilter::act(IReq::get('type'),'int');
                $content = IFilter::act(IReq::get('content'));//禁止脚本 过滤来源
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
                        IError::show(403,"上传失败,错误信息：".$result['error']);
                    }
                }

                $adObj = new IModel('ad_manage');
                $dataArray = array(
                    'content'      => IFilter::addSlash($content),
                    'name'         => IFilter::act(IReq::get('name')),
                    'position_id'  => IFilter::act(IReq::get('position_id')),
                    'type'         => $type,
                    'link'         => IFilter::addSlash(IReq::get('link')),
                    'start_time'   => IFilter::act(IReq::get('start_time')),
                    'end_time'     => IFilter::act(IReq::get('end_time')),
                    'description'  => IFilter::act(IReq::get('description'),'text'),
                    'order'        => IFilter::act(IReq::get('order'),'int'),
                    'goods_cat_id' => IFilter::act(IReq::get('goods_cat_id'),'int'),
                    'seller_id'    => $seller_id,
                );

                $adObj->setData($dataArray);
                if($id)
                {
                    $where = 'id = '.$id.' and seller_id = '.$seller_id;
                    $adObj->update($where);
                }
                else
                {
                    $adObj->add();
                }
                $this->view('ad_list');
            };
        });

        //商户广告删除
        plugin::reg("onBeforeCreateAction@seller@ad_del",function(){
            self::controller()->ad_del = function(){
                $seller_id   = self::controller()->seller ? self::controller()->seller['seller_id'] : 0;
                $id = IFilter::act( IReq::get('id') , 'int' );
                if($id)
                {
                    $obj = new IModel('ad_manage');
                    if(is_array($id) && isset($id[0]) && $id[0]!='')
                    {
                        $id_str = join(',',$id);
                        $where  = ' id in ('.$id_str.') and seller_id = '.$seller_id;
                    }
                    else
                    {
                        $where = 'id = '.$id.' and seller_id = '.$seller_id;
                    }
                    $obj->del($where);
                }
                $this->view('ad_list');
            };
        });
	}

	public static function configName()
	{
		return array(
			"sellerCostom" => array("name" => "允许商家自选","type" => "radio","value" => array("允许" => "yes","禁止" => "no"),"info" => "提示：允许:商家可自己从后台选择模板; 禁止:只能管理员设置商家模板"),
		);
	}

	public static function explain()
	{
		return "把商户模板以目录形式放到 \plugins\sellerDecorate\sellerTheme 里面，即可在配置模板页面中出现选择项";
	}

	//更新商户模板信息
	public function updateTheme($seller_id,$theme)
	{
		if($seller_id)
		{
			$sellerDeDB= new IModel('seller_decorate');
			$sellerDeDB->setData(array('seller_id' => $seller_id,'theme' => $theme));
			return $sellerDeDB->replace();
		}
		return false;
	}

	//获取商家模板方案名称
	public function getSellerTheme()
	{
		$result   = [];
		$planPath = $this->path().'sellerTheme';
		if(is_dir($planPath))
		{
			$dirRes = opendir($planPath);

			//遍历目录读取配置文件
			while(false !== ($dir = readdir($dirRes)))
			{
				if($dir[0] == ".")
				{
					continue;
				}
                $fileName     = $planPath.'/'.$dir.'/config.php';
                $tempData     = file_exists($fileName) ? include($fileName) : [];
				$result[$dir] = $tempData;
			}
		}
		return $result;
	}

	//获取layout布局文件路径
	public function getLayout()
	{
		return parent::path().'layouts/'.$this->layout;
	}

	//获取商户主题
	public function getTheme($seller_id = "")
	{
		$sellerDeDB = new IModel('seller_decorate');
		$sellerDeRow= $sellerDeDB->getObj('seller_id = '.$seller_id);
		if($sellerDeRow && $sellerDeRow['theme'])
		{
			return $sellerDeRow['theme'];
		}
		return '';
	}

	/**
	 * @brief 插件物理目录
	 * @param string 插件路径地址
	 */
	public function path()
	{
		return $this->theme ? parent::path()."sellerTheme/".$this->theme."/" : parent::path();
	}

	/**
	 * @brief 插件WEB目录
	 * @param string 插件路径地址
	 */
	public function webPath()
	{
		return $this->theme ? parent::webPath()."sellerTheme/".$this->theme."/" : parent::webPath();
	}


    //绑定site/home
	private function home()
	{
	    $this->_seller_id = IFilter::act(IReq::get('id'),'int');
	    return [];
	}

    //绑定site/seller_goods_list
	private function seller_goods_list()
	{
	    $cat_id    = IFilter::act(IReq::get('id'),'int');//店内分类检索
	    $word      = IFilter::act(IReq::get('word'),'text');//店内商品名称检索
	    $seller_id = IFilter::act(IReq::get('seller_id'));//店内商品名称检索

	    if($cat_id)
	    {
            $catObj = new IModel('category_seller');
            $catRow = $catObj->getObj($cat_id);
            if(!$catRow)
            {
                IError::show(403,'商户分类信息不存在');
            }
            $this->_seller_id = $catRow['seller_id'];
            $search = ['category_extend_seller' => $cat_id];
	    }

	    if($word)
	    {
	        $this->_seller_id = $seller_id;
	        $search = ['seller_id' => $seller_id,'search' => $word];
	    }

        $searchObj = search_goods::find($search,20);
        $resultData = $searchObj->find();
	    return ['searchObj' => $searchObj,'resultData' => $resultData];
	}

    /**
	 * @brief 拦截actions属性命名的方法,在各个方法中务必要实现$this->_seller_id的获取
	 * @param string $methodName 方法名称
	 * @param string $arguments 参数
     */
	public function __call($methodName, $arguments)
	{
	    switch($methodName)
	    {
	        case "home":
	        {
	            $renderData = $this->home();
	        }
	        break;

	        case "seller_goods_list":
	        {
	            $renderData = $this->seller_goods_list();
	        }
	        break;

	        default:
	        {
	            die($methodName.'页面不存在');
	        }
	    }

	    if(!$this->_seller_id)
	    {
	        IError::show('未获取到商家信息');
	    }

	    $sellerRow = Api::run('getSellerInfo',$this->_seller_id);
	    if(!$sellerRow)
	    {
	        IError::show(403,'商户信息不存在');
	    }

        $this->theme = $this->getTheme($this->_seller_id);

        //有装饰方案
        if($this->theme)
        {
    	    $renderData['seller_id'] = $this->_seller_id;
    	    $renderData['sellerRow'] = $sellerRow;

            $this->controller()->layout = $this->getLayout();
            $this->controller()->setRenderData($renderData);
            $this->redirect($methodName);
        }
        //无装饰方案启用默认
        else
        {
            IWeb::$app->getController()->$methodName();
        }
	}
}