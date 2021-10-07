<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file controller_class.php
 * @brief 控制器类,控制action动作,渲染页面
 * @author chendeshan
 * @date 2010-12-16
 * @update 2016/4/13 18:29:42
 * @version 4.4
 */

/**
 * @class IController
 * @brief 控制器
 */
class IController extends IControllerBase
{
	public $extend = '.html';                          //模板扩展名
	public $theme;                                     //主题方案名称
	public $skin;                                      //皮肤方案名称
	public $layout;                                    //布局方案名称
	public $defaultActions = array();                  //action对应关系,array(actionID => 对象引用)
	public $error          = array();                  //错误信息内容

	protected $app;                                    //隶属于APP的对象
	protected $ctrlId;                                 //控制器ID标识符
	protected $defaultLayoutPath = 'layouts';          //默认布局目录

	private $action;                                   //当前action对象
	private $defaultAction       = 'index';            //默认执行的action动作
	private $renderData          = array();            //渲染的数据

	/**
	 * @brief 构造函数
	 * @param string $app    上一级APP对象
	 * @param string $ctrlId 控制器ID标识符
	 */
	public function __construct($app,$controllerId)
	{
		$this->app    = $app;
		$this->ctrlId = $controllerId;
	}

	/**
	 * @brief 生成验证码
	 * @return image图像
	 */
	public function getCaptcha()
	{
		//清空布局
		$this->layout = '';

		//配置参数
		$width      = IReq::get('_w') ? IReq::get('_w') : 130;
		$height     = IReq::get('_h') ? IReq::get('_h') : 45;
		$wordLength = IReq::get('_l') ? IReq::get('_l') : 5;
		$fontSize   = IReq::get('_s') ? IReq::get('_s') : 25;

		if(max($width,$height,$wordLength,$fontSize) > 300)
		{
		    die('max size error');
		}

		//创建验证码
		$ValidateObj = new Captcha();
		$ValidateObj->width  = $width;
		$ValidateObj->height = $height;
		$ValidateObj->maxWordLength = $wordLength;
		$ValidateObj->minWordLength = $wordLength;
		$ValidateObj->fontSize      = $fontSize;
		$ValidateObj->CreateImage($text);

		//设置验证码
		ISafe::set('captcha',$text);
	}

	/**
	 * @brief 获取当前控制器的id标识符
	 * @return 控制器的id标识符
	 */
	public function getId()
	{
		return $this->ctrlId;
	}

	/**
	 * @brief 初始化controller对象
	 */
	public function init()
	{
	}

	/**
	 * @brief 获取当前action对象
	 * @return object 返回当前action对象
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @brief 执行action方法
	 * @param string $actionId 动作actionID
	 */
	public function run($actionId = '')
	{
		//开启缓冲区
		ob_start();

		header("Content-Type: text/html; charset=".$this->app->charset);

		//创建action对象
		IInterceptor::trigger("onBeforeCreateAction",$this,$actionId);
		//初始化控制器
		$this->init();
		$actionObj = $this->createAction($actionId);
		IInterceptor::trigger("onCreateAction",$this,$actionObj);
		$actionObj->run();
		IInterceptor::trigger("onFinishAction",$this,$actionObj);

		//处理缓冲区
		ob_end_flush();
	}

	/**
	 * @brief 创建action动作
	 * @param string $actionId 动作actionID
	 * @return object 返回action动作对象
	 */
	public function createAction($actionId = '')
	{
		//获取action的标识符
		$actionId = $actionId ? $actionId : $this->defaultAction;

		//创建action对象流程
		//1,控制器内部动作
		if(method_exists($this,$actionId) || is_callable($this->$actionId) || (isset($this->defaultActions[$actionId]) && is_object($this->defaultActions[$actionId])))
		{
			$this->action = new IInlineAction($this,$actionId);
		}
		//2,视图动作
		else
		{
			$this->action = new IViewAction($this,$actionId);
		}
		return $this->action;
	}

	/**
	 * @brief 渲染
	 * @param string          $view   要渲染的视图文件
	 * @param string or array $data   要渲染的数据
	 * @param boolean         $return 是否直接返回模板视图
	 * @param string          $runtimePath 运行目录（runtime）
	 * @return 渲染出来的数据
	 */
	public function render($view,$data=null,$return=false,$runtimePath = "")
	{
		$output = $this->renderView($view,$data,$return,$runtimePath);
		if($output === false)
		{
			return false;
		}

		if($return)
		{
			return $output;
		}
		echo $output;
	}

	/**
	 * @brief 渲染出静态文字
	 * @param string $text 要渲染的静态数据
	 * @param bool $return 输出方式 值: true:返回; false:直接输出;
	 * @param bool $isLayout 是否包括布局文件 true:包括 false:不包括
	 * @return string 静态数据
	 */
	public function renderText($text,$return=false,$isLayout=false)
	{
	    if($isLayout)
	    {
    		//layout文件
    		$layoutFile = $this->getLayoutFile().$this->extend;
    		$issetLayout= is_file($layoutFile);

			//处理layout
			if($issetLayout && stripos($text,"<html") === false)
			{
				$text = $this->renderLayout($layoutFile,$text);
			}
	    }

		$text = $this->tagResolve($text);
		if($return)
		{
			return $text;
		}
		echo $text;
	}

	/**
	 * @brief 获取当前主题下的皮肤路径
	 * @return string 皮肤路径
	 */
	public function getSkinPath()
	{
		$skin = $this->getSkinDir();
		if($skin)
		{
			return $this->getViewPath().$this->app->defaultSkinDir.DIRECTORY_SEPARATOR.$skin.DIRECTORY_SEPARATOR;
		}
		return $this->getViewPath().$this->app->defaultSkinDir.DIRECTORY_SEPARATOR;
	}

	/**
	 * @brief 获取layout文件路径(无扩展名)
	 * @return string layout路径
	 */
	public function getLayoutFile()
	{
		if(!$this->layout)
		{
			return false;
		}
		//布局文件为自定义的路径格式
		else if(stripos($this->layout,"/") !== false)
		{
			return $this->layout;
		}
		return $this->getViewPath().$this->defaultLayoutPath.DIRECTORY_SEPARATOR.$this->layout;
	}

	/**
	 * @brief 获取当前主题下的模板路径
	 * @return string 模板路径
	 */
	public function getViewPath()
	{
		$theme = $this->getThemeDir();
		if($theme)
		{
			return $this->app->getViewPath().$theme.DIRECTORY_SEPARATOR;
		}
		return $this->app->getViewPath();
	}

	/**
	 * @brief 取得视图文件路径(无扩展名)
	 * @param string $viewName 视图文件名
	 * @return string 视图文件路径
	 */
	public function getViewFile($viewName)
	{
		return $this->getViewPath().strtolower($this->ctrlId).DIRECTORY_SEPARATOR.$viewName;
	}

    /**
     * @brief 获取当前控制器所属的theme方案
     *        在App的config中可以配置theme => array('客户端' => array("主题方案" => "皮肤方案"))
     * @return String theme方案名称
     */
	public function getThemeDir()
	{
		if(!$this->theme)
		{
			$client    = $this->app->clientType;
			$themeList = isset($this->app->config['theme']) ? $this->app->config['theme'] : null;
			if($themeList && isset($themeList[$client]) && is_array($themeList[$client]) && $themeList[$client])
			{
				foreach($themeList[$client] as $theme => $skin)
				{
					$tryPath = $this->app->getViewPath().$theme.DIRECTORY_SEPARATOR.strtolower($this->getId());
					if(is_dir($tryPath))
					{
						$this->theme = $theme;
						break;
					}
				}
			}
		}
		return $this->theme;
	}

    /**
     * @brief 获取当前控制器所属的skin方案
     *        在App的config中可以配置theme => array('客户端' => array("主题方案" => "皮肤方案"))
     * @return String skin方案名称
     */
	public function getSkinDir()
	{
		if(!$this->skin)
		{
			$theme = $this->getThemeDir();
			if($theme)
			{
				$client    = $this->app->clientType;
				$themeList = isset($this->app->config['theme']) ? $this->app->config['theme'] : null;
				$this->skin = $themeList[$client][$theme];
			}
		}
		return $this->skin;
	}

	/**
	 * @brief 获取WEB模板路径
	 * @return string 返回WEB路径格式
	 */
	public function getWebViewPath()
	{
		return $this->app->getWebViewPath().$this->getThemeDir()."/";
	}

	/**
	 * @brief 获取WEB皮肤路径
	 * @return string 返回WEB路径格式
	 */
	public function getWebSkinPath()
	{
		return $this->getWebViewPath().$this->app->defaultSkinDir."/".$this->getSkinDir()."/";
	}

	/**
	 * @brief 获取要渲染的数据
	 * @return array 渲染的数据
	 */
	public function getRenderData()
	{
		return $this->renderData;
	}

	/**
	 * @brief 设置要渲染的数据
	 * @param array $data 渲染的数据数组
	 */
	public function setRenderData($data)
	{
		if(is_array($data))
			$this->renderData = array_merge($this->renderData,$data);
	}

	/**
	 * @brief 视图重定位
	 * @param string $next     下一步要执行的动作或者路径名,注:当首字符为'/'时，则支持跨控制器操作
	 * @param bool   $location 是否重定位 true:是 false:否
	 */
	public function redirect($nextUrl, $location = true, $data = null)
	{
	    $resultUrl = IInterceptor::trigger('onControllerRedirect',$nextUrl);
	    $nextUrl   = $resultUrl ? $resultUrl : $nextUrl;

		//绝对地址直接跳转
		if(strpos($nextUrl,'http') === 0)
		{
			header('location: '.$nextUrl);
		}
		//伪静态路径
		else
		{
			//获取当前的action动作
			$actionId = IReq::get('action');
			if($actionId === null)
			{
				$actionId = $this->defaultAction;
			}

			//分析$nextAction 支持跨控制器跳转
			$nextUrl = strtr($nextUrl,'\\','/');

			//不跨越控制器redirect
			if($nextUrl[0] != '/')
			{
				//重定跳转定向
				if($actionId!=$nextUrl && $location == true)
				{
					$locationUrl = IUrl::creatUrl('/'.$this->ctrlId.'/'.$nextUrl);
					header('location: '.$locationUrl);
				}
				//非重定向,直接引入本控制器内的视图模板
				else
				{
					$this->action = new IViewAction($this,$nextUrl);
					$this->action->run();
				}
			}
			//跨越控制器redirect
			else
			{
				$urlArray   = explode('/',$nextUrl,4);
				$ctrlId     = isset($urlArray[1]) ? $urlArray[1] : '';
				$nextAction = isset($urlArray[2]) ? $urlArray[2] : '';

				//url参数
				if(isset($urlArray[3]))
				{
					$nextAction .= '/'.$urlArray[3];
				}
				$locationUrl = IUrl::creatUrl('/'.$ctrlId.'/'.$nextAction);
				header('location: '.$locationUrl);
			}
		}
	}

	/**
	 * @brief 设置错误信息
	 * @param string $errorMsg 错误信息内容
	 * @param string $errorNo  错误信息编号
	 */
	public function setError($errorMsg,$errorNo = "")
	{
		if($errorNo)
		{
			$this->error[$errorNo] = $errorMsg;
		}
		else
		{
			$this->error[] = $errorMsg;
		}
	}

	/**
	 * @brief 获取单条错误信息
	 * @return string 错误信息内容
	 */
	public function getError()
	{
		return $this->error ? current($this->error) : "";
	}

	/**
	 * @brief 获取全部错误信息
	 * @return array 全部错误信息内容
	 */
	public function getAllError()
	{
		return $this->error;
	}
}
