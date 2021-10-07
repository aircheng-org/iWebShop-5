<?php
/**
 * @copyright (c) 2015 aircheng.com
 * @file db_class.php
 * @brief 数据库抽象父类
 * @author chendeshan
 * @date 2017/11/1 23:15:06
 * @version 5.0
 */

/**
* @class IDB
* @brief 数据库底层抽象类
*/
abstract class IDB
{
	//数据库写操作连接资源
	private static $wLink = NULL;

	//数据库读操作连接资源
	private static $rLink = NULL;

	//缓存类库实例
	private static $cacheTarget = NULL;

	//原生态SQL语句
	private $sql;

	//是否自动提交,默认自动提交
	private $isAutocommit = true;

	//记录执行日志 0:关闭; 1:开启;
	public $log;

	//缓存类型
	public $cache;

	//是否输出SQL
	public $debug;

	/**
	 * @brief 初始化基本配置
	 */
	public function init()
	{
		$this->cache = "";
		$this->log   = isset(IWeb::$app->config['sqlLog'])   ? IWeb::$app->config['sqlLog']   : 0;
		$this->debug = isset(IWeb::$app->config['sqlDebug']) ? IWeb::$app->config['sqlDebug'] : 0;
	}

	/**
	 * @brief 获取读数据库连接对象
	 */
	private function getReadLink()
	{
		if(self::$rLink == null)
		{
			//多数据库支持并且读写分离
	        if(isset(IWeb::$app->config['DB']['read']))
	        {
	        	$ip = IClient::getIP();
	            self::$rLink = $this->connect(IHash::hash(IWeb::$app->config['DB']['read'],$ip));
	        }
	        //单数据库
	        else
	        {
	        	self::$rLink = $this->connect(IWeb::$app->config['DB']);
	        }
		}
        return self::$rLink;
	}

	/**
	 * @brief 获取写数据库连接对象
	 */
	private function getWriteLink()
	{
		if(self::$wLink == null)
		{
			//多数据库支持并且读写分离
	        if(isset(IWeb::$app->config['DB']['write']))
	        {
	        	self::$wLink = $this->connect(IWeb::$app->config['DB']['write']);
	        }
	        //单数据库
	        else
	        {
	        	self::$wLink = $this->connect(IWeb::$app->config['DB']);
	        }

			//设置是否自动提交
			if($this->isAutocommit == false)
			{
				$this->linkRes = self::$wLink;
				$this->autoSubmit(false);
			}
		}
        return self::$wLink;
	}

	/**
	* @brief 获取SQL语句的类型,类型：select,update,insert,delete
	* @param string $sql 执行的SQL语句
	* @return string SQL类型
	*/
	protected function getSqlType($sql)
	{
		$strArray = explode(' ',trim($sql),2);
		return strtolower($strArray[0]);
	}

	/**
	 * @brief 核心执行SQL
	 * @param string $sql 要执行的SQL语句
	 * @return mixed 执行的结果
	 */
    public function query($sql)
    {
    	$this->sql = $sql;

    	if($this->debug == 1)
    	{
			$this->debug();
    	}

    	if($this->log == 1)
    	{
			$this->log();
    	}

		//取得SQL类型
        $sqlType = $this->getSqlType($sql);

		//读方式
		$readyConf = array('select','show','describe');
        if(in_array($sqlType,$readyConf))
        {
            //如果启用了缓存机制优先读取缓存数据
            if($this->cache)
            {
            	self::$cacheTarget = $this->getCacheTarget($this->cache);
            	$cacheKey          = md5($sql);
            	$result            = self::$cacheTarget->get($cacheKey);
            	if($result)
            	{
            		return $result;
            	}
            }

			$this->linkRes = $this->getReadLink();
            $result = $this->read($sql);
            if($result === false)
            {
				throw new IException("{$sql}\n -- ".self::$rLink->error,1000);
				return false;
            }

            //如果启用了缓存机制则保存结果数据
            isset($cacheKey) ? self::$cacheTarget->set($cacheKey,$result) : "";
            return $result;
        }
        //写方式
        else
        {
			$this->linkRes = $this->getWriteLink();
            $result = $this->write($sql);
            if($result === false)
            {
				throw new IException("{$sql}\n -- ".self::$wLink->error,1000);
				return false;
            }

            return $result;
        }
    }

	//开启或者关闭自动提交
	public function autocommit($isOpen)
	{
		$this->isAutocommit = $isOpen;
		if(self::$wLink)
		{
			$this->linkRes = self::$wLink;
			$this->autoSubmit($isOpen);
		}
	}

	//提交事务
	public function commit()
	{
		if(self::$wLink)
		{
			$this->linkRes = self::$wLink;
			$this->goTransaction();
		}
	}

	//回滚事务
	public function rollback()
	{
		if(self::$wLink)
		{
			$this->linkRes = self::$wLink;
			$this->backTransaction();
		}
	}

	//析构函数
    public function __destruct()
    {
    	if(self::$wLink)
    	{
			$this->linkRes = self::$wLink;
			$this->close();
    		self::$wLink = null;
    	}

    	if(self::$rLink)
    	{
			$this->linkRes = self::$rLink;
			$this->close();
    		self::$rLink = null;
    	}
    }

	//获取原生态SQL语句
    public function getSql()
    {
		return $this->sql;
    }

	//保存日志
    private function log()
    {
		//SQL语句
		list($usec, $sec) = explode(" ", microtime());
		$time   = ITime::getDateTime()." ".$usec;
		$string = $this->getSql();

		//获取引用堆栈
		$traceData      = array();
		$traceDataArray = debug_backtrace(false);
		array_shift($traceDataArray);
		array_shift($traceDataArray);
		$traceData = IException::formatTrace($traceDataArray);
		$logArray  = array(
			"SQL: ".$string,
			"TIME: ".$time,
			join("\n",$traceData)
		);
		$logString = join("\n",$logArray);
		$logString = "<SQL_BLOCK>\n".$logString."\n</SQL_BLOCK>";

		//创建文件记录日志
		$logInstance = new IFileLog("sql/".date("y-m-d").".log");
		return $logInstance->write($logString);
    }

	//打印调试SQL
    private function debug()
    {
		echo $this->getSql();
    }

	/**
	 * @brief 获取缓存实例对象
	 * @param $type string 缓存类型file,memcache
	 & @return cache 缓存对象
	 */
    private function getCacheTarget($type)
    {
    	if(self::$cacheTarget && self::$cacheTarget->getCacheType() == $type)
    	{
			return self::$cacheTarget;
    	}
    	return new ICache($type);
    }
}

/**
* @class dbInter
* @brief 数据库接口规范
*/
interface dbInter
{
	/**
	 * @brief 数据库连接
	 * @param array $dbinfo 数据库的连接配制信息 [0]ip地址 [1]用户名 [2]密码 [3]数据库
	 * @return Boolean or resource 值: false:链接失败; resource类型:链接的资源句柄;
	 */
	public function connect($dbinfo);

	/**
	 * @brief 获取数据库内容
	 * @param $sql     string   SQL语句
	 * @param $type    string   返回数据的键类型
	 * @return array or Boolean 查询结果集
	 */
    public function read($sql);

	/**
	 * @brief 写入操作
	 * @param $sql     string   SQL语句
	 * @return int or boolean 失败:false; 成功:影响的结果数量;
	 */
    public function write($sql);

	/**
	 * @brief 开启或者关闭事务自动提交
	 * @param $isOpen boolean true开启; false关闭
	 * @return boolean
	 */
    public function autoSubmit($isOpen);

	/**
	 * @brief 提交事务
	 * @return boolean
	 */
    public function goTransaction();

	/**
	 * @brief 回滚事务
	 * @return boolean
	 */
    public function backTransaction();

	/**
	 * @brief 关闭数据库
	 * @return boolean
	 */
    public function close();
}