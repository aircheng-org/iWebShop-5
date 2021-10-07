<?php
/**
 * @copyright Copyright(c) 2010 aircheng.com
 * @file application.php
 * @brief 应用的基本类文件，cli命令行或web方式都要集成此类
 * @author nswe
 * @date 2014/10/12 20:55:14
 * @version 4.7
 */

/**
 * @brief IApplication 创建应用的基本类
 * @class IApplication
 */
abstract class IApplication
{
	//应用的名称
    public $name = 'iWebShop';

    //应用的编码
    public $charset = 'UTF-8';

    //应用的语言
    public $language = 'zh_sc';

	//默认语言包目录
    public $defaultLanguageDir = "language";

    //程序运行的实际路径
	public $runtimePath;

    //应用的config信息
    public $config;

    //应用位置实际路径
    protected $basePath;

	//默认控制器名称
	protected $defaultController = 'site';

    //渲染时的数据
    private $renderData = array();

    /**
     * @brief 构造函数
     * @param array or string $config 配置数组或者配置文件名称
     */
    public function __construct($config)
    {
		if(version_compare(PHP_VERSION, '5.4.1', '<'))
		{
			die('Your host needs to use PHP 5.4 or higher to run this version of iWebShop!');
		}

    	IWeb::setApplication($this);

    	if(is_string($config) && is_file($config))
    	{
    		$config = require($config);
    	}

        $this->config = is_array($config) ? $config : array();

		//配置文件中设置实际路径，如果是cli命令行方式则此参数必须传
		if(isset($this->config['basePath']) && $this->config['basePath'])
		{
			$this->basePath = $this->config['basePath'];
		}

		date_default_timezone_set(isset($config['timezone']) ? $config['timezone'] : 'Asia/Shanghai');
		IWeb::setClasses(isset($config['classes']) ? $config['classes'] : 'classes.*');
		$this->charset  = isset($config['charset']) ? $config['charset'] : $this->charset;
		$this->language = isset($config['lang']) ? $config['lang'] : $this->language;
		$this->setDebugMode($config['debug']);
		$this->defaultLanguageDir = isset($config['langPath']) ? $config['langPath'] : $this->defaultLanguageDir;

		//开始向拦截器里注册类
		if( isset($config['interceptor']) && is_array($config['interceptor']) )
		{
			IInterceptor::reg($config['interceptor']);
			register_shutdown_function(array('IInterceptor',"shutDown"));
		}
    }

    //执行请求
    abstract public function execRequest();
    /**
     * @brief 应用运行的方法
     * @return Void
     */
    public function run()
    {
		IInterceptor::trigger("onCreateApp");
        $this->execRequest();
		IInterceptor::trigger("onFinishApp");
    }

    /**
     * @brief 设置调试模式
     * @param $flag 0:关闭; 1:部分; 2:全部
     */
    private function setDebugMode($flag)
    {
    	switch($flag)
    	{
    		//部分
    		case 1:
    		{
    			ini_set("display_errors","on");
    			IException::setDebugMode(true);
    			error_reporting(E_ERROR | E_PARSE);
    			set_error_handler("IException::phpError",E_ERROR | E_PARSE | E_WARNING);
    		}
    		break;

			//全部
    		case 2:
    		{
    			ini_set("display_errors","on");
    			IException::setDebugMode(true);
    			error_reporting(E_ALL | E_STRICT);
    			set_error_handler("IException::phpError" ,E_ALL | E_STRICT );
    		}
    		break;

			//关闭
    		default:
    		{
    			ini_set("display_errors","off");
    			IException::setDebugMode(false);
    			error_reporting(0);
    			set_error_handler("IException::phpError" ,E_ALL | E_STRICT );
    		}
    	}
		set_exception_handler("IException::phpException");
    }

    /**
     * @brief 取得应用的路径
     * @return String 路径地址
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
    /**
     * @brief 得到当前的运行实际路径
     * @return String 实际路径
     */
    public function getRuntimePath()
    {
        if($this->runtimePath == null)
        {
            $this->runtimePath = $this->getBasePath().'runtime'.DIRECTORY_SEPARATOR;
        }
        return $this->runtimePath;
	}

    /**
     * @brief 得到当前的语言包实际路径
     * @return String 实际路径
     */
	public function getLanguagePath()
	{
        return $this->getBasePath().$this->defaultLanguageDir.DIRECTORY_SEPARATOR;
	}

    /**
     * @brief 设置渲染数据
     * @param array $data 数组的形式存储，渲染后键值将作为变量名。
     */
    public function setRenderData($data)
    {
        if(is_array($data))
        {
            $this->renderData = array_merge($this->renderData,$data);
        }
    }
    /**
     * @brief 取得应用级的渲染数据
     * @return array
     */
    public function getRenderData()
    {
        return $this->renderData;
    }
}