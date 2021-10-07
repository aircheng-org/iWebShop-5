<?php
/**
 * @copyright (c) 2015 aircheng.com
 * @file mysqli_class.php
 * @brief mysqli数据库应用
 * @author nswe
 * @date 2017/11/1 23:41:53
 * @version 5.3
 */
/**
 * @class IMysqli
 * @brief MYSQLI数据库应用
 */
class IMysqli extends IDB implements dbInter
{
	//数据库连接对象
	public $linkRes;

	/**
	 * @brief 数据库连接
	 * @param array $dbinfo 数据库的连接配制信息 [0]ip地址 [1]用户名 [2]密码 [3]数据库
	 * @return Boolean or resource 值: false:链接失败; resource类型:链接的资源句柄;
	 */
	public function connect($dbinfo)
	{
		$hostArray = explode(':',$dbinfo['host']);
		$hostPort  = isset($hostArray[1]) ? $hostArray[1] : ini_get("mysqli.default_port");

	  	$this->linkRes = new mysqli($hostArray[0],$dbinfo['user'],$dbinfo['passwd'],$dbinfo['name'],$hostPort);
	  	if($this->linkRes->connect_error)
	  	{
	  		throw new IException($this->linkRes->connect_error,1000);
	  		return false;
	  	}
	  	else
	  	{
		  	$DBCharset = isset(IWeb::$app->config['DB']['charset']) ? IWeb::$app->config['DB']['charset'] : 'utf8';
		  	$this->linkRes->set_charset($DBCharset);

			/*只有拥有MYSQL的SUPER权限才可以做临时环境的调整，否则无效*/
		  	//宽松的SQL执行
		  	$this->linkRes->query("SET SESSION sql_mode = '' ");

		  	//设置事务级别和日志方式
		  	$this->linkRes->query("SET SESSION binlog_format='MIXED' ");

		  	//设置时区东八区
		  	if(IWeb::$app->config['timezone'] == 'Asia/Shanghai')
		  	{
    		  	$this->linkRes->query("set global time_zone = '+8:00' ");
    		  	$this->linkRes->query("set time_zone = '+8:00' ");
		  	}
		  	return $this->linkRes;
	  	}
	}

	/**
	 * @brief 读取操作
	 * @param $sql  string   SQL语句
	 * @return array or Boolean 查询结果集
	 */
	public function read($sql)
	{
		$result   = array();
		$resource = $this->linkRes->query($sql);

		if($resource)
		{
			while($data = $resource->fetch_array(MYSQLI_ASSOC))
			{
				$result[] = $data;
			}
			$resource->free();
			return $result;
		}
		return false;
	}

	/**
	 * @brief 写入操作
	 * @param $sql string  SQL语句
	 * @return int or boolean 失败:false; 成功:影响的结果数量;
	 */
	public function write($sql)
	{
		$result = $this->linkRes->query($sql);

		if($result == true)
		{
			$sqlType = $this->getSqlType($sql);
			switch($sqlType)
			{
				case "insert":
				case "replace":
				{
					return $this->linkRes->insert_id;
				}
				break;

				case "update":
				{
					return $this->linkRes->affected_rows;
				}
				break;

				default:
				{
					return $result;
				}
			}
		}
		return false;
	}

	/**
	 * @brief 提交事务
	 * @return boolean
	 */
	public function goTransaction()
	{
		return $this->linkRes->commit();
	}

	/**
	 * @brief 回滚事务
	 * @return boolean
	 */
	public function backTransaction()
	{
		return $this->linkRes->rollback();
	}

	/**
	 * @brief 开启或者关闭事务自动提交
	 * @param $isOpen boolean true开启; false关闭
	 * @return boolean
	 */
	public function autoSubmit($isOpen)
	{
		return $this->linkRes->autocommit($isOpen);
	}

	/**
	 * @brief 关闭数据库
	 * @return boolean
	 */
	public function close()
	{
		return $this->linkRes->close();
	}
}