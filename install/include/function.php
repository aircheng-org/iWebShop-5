<?php
//测试链接数据库
function check_mysql()
{
	$is_connect = false;
	$db_host = url_get('db_address');
	$db_user = url_get('db_user');
	$db_pwd  = url_get('db_pwd');

	if($db_host && class_exists('mysqli'))
	{
		$hostArray  = explode(":",$db_host);
		$hostPort   = isset($hostArray[1]) ? $hostArray[1] : ini_get("mysqli.default_port");
		$mysql_link = new mysqli($hostArray[0],$db_user,$db_pwd,NULL,$hostPort);
		if($mysql_link && $mysql_link->connect_error == NULL)
		{
			//检查max_allowed_packet必须大于1MB
			$sqlData = $mysql_link->query("select @@max_allowed_packet")->fetch_array(MYSQLI_ASSOC);
			$sqlData = current($sqlData);
			if($sqlData >> 10 < 1000)
			{
				die("您的MYSQL配置中的 'max_allowed_packet' 过小，请手动修改必须大于1MB");
			}

			die('success');
		}
		die($mysql_link->connect_error);
	}
	die('数据库连接失败');
}

//解析备份文件中的SQL
function parseSQL($fileName,$mysql_link)
{
	global $db_pre;

	//执行sql query次数的计数器 默认值
	$queryTimes = 0;

	//与前端交互的频率(数值与频率成反比,0表示关闭交互)
	$waitTimes  = 5;

	$percent   = 0;
	$fhandle   = fopen($fileName,'r');
	$firstLine = fgets($fhandle);
	rewind($fhandle);

	//跨过BOM头信息
	$charset[1] = substr($firstLine,0,1);
	$charset[2] = substr($firstLine,1,1);
	$charset[3] = substr($firstLine,2,1);
	if(ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191)
	{
		fseek($fhandle,3);
	}

	//计算安装进度
	$totalSize  = filesize($fileName);
	while(!feof($fhandle))
	{
		$lstr = fgets($fhandle);     //获取指针所在的一行数据

		//判断当前行存在字符
		if(isset($lstr[0]) && $lstr[0]!='#')
		{
			$prefix = substr($lstr,0,2);  //截取前2字符判断SQL类型
			switch($prefix)
			{
				case '--' :
				case '//' :
				{
					continue;
				}

				case '/*':
				{
					if(substr($lstr,-5) == "*/;\r\n" || substr($lstr,-4) == "*/\r\n")
						continue;
					else
					{
						skipComment($fhandle);
						continue;
					}
				}

				default :
				{
					$sqlArray[] = trim($lstr);
					if(substr(trim($lstr),-1) == ";")
					{
						$rcount   = 1;
						$sqlStr   = str_ireplace("{pre}",$db_pre,join($sqlArray),$rcount); //更换表前缀
						$sqlArray = array();
						$mysql_link->query($sqlStr);

						$queryTimes++;
						if($waitTimes > 0 && ($queryTimes/$waitTimes == 1))
						{
							$queryTimes = 0;

							//计算安装进度百分比
							$percent    = ftell($fhandle)/($totalSize+1);
							sqlCallBack($sqlStr,$mysql_link->error,$percent);
							set_time_limit(1000);
						}
					}
				}
			}
		}
	}
}

//略过注释
function skipComment($fhandle)
{
	$lstr = fgets($fhandle,4096);
	if(substr($lstr,-5) == "*/;\r\n" || substr($lstr,-4) == "*/\r\n")
		return true;
	else
		skipComment($fhandle);
}

//sql回调函数
function sqlCallBack($sql,$error,$percent)
{
	//创建表
	if(preg_match('/create\s+table\s+(\S+)/i',$sql,$match))
	{
		$tableName = isset($match[1]) ? $match[1] : '';
		$message   = '创建表'.$tableName;
	}
	//插入数据
	else if(preg_match('/insert\s+into/i',$sql))
	{
		$message   = '插入数据';
	}
	//其余操作
	else
	{
		$message   = '执行SQL';
	}

	//判断sql执行结果
	if($error)
	{
		$isError  = true;
		$message .= ' 失败! '.$sql.'<br />'.$error;
	}
	else
	{
		$isError  = false;
		$message .= '...';
	}

	$return_info = array(
		'isError' => $isError,
		'message' => $message,
		'percent' => $percent
	);

	showProgress($return_info);
	usleep(5000);
}

//安装mysql数据库
function install_sql()
{
	global $db_pre;

	//安装配置信息
	$db_address   = url_get('db_address');
	$db_user      = url_get('db_user');
	$db_pwd       = url_get('db_pwd');
	$db_name      = url_get('db_name');
	$db_pre       = url_get('db_pre');
	$admin_user   = url_get('admin_user');
	$admin_pwd    = url_get('admin_pwd');
	$install_type = url_get('install_type');

	//链接mysql数据库
	$hostArray  = explode(":",$db_address);
	$hostPort   = isset($hostArray[1]) ? $hostArray[1] : ini_get("mysqli.default_port");
	$mysql_link = new mysqli($hostArray[0],$db_user,$db_pwd,NULL,$hostPort);

	if($mysql_link->connect_errno)
	{
		showProgress(array('isError' => true,'message' => 'mysql链接失败'.$mysql_link->connect_errno));
	}

	//检测SQL安装文件
	$sql_file = ROOT_PATH.'./install/iwebshop.sql';
	if(!file_exists($sql_file))
	{
		showProgress(array('isError' => true,'message' => '安装的SQL文件'.basename($sql_file).'不存在'));
	}

	if($install_type == 'all')
	{
		$sqlTest_file = ROOT_PATH.'./install/install_test.sql';
		if(!file_exists($sqlTest_file))
		{
			showProgress(array('isError' => true,'message' => '测试数据的SQL文件'.basename($sqlTest_file).'不存在'));
		}
	}

	//执行SQL,创建数据库操作
  	$DBCharset = 'utf8';
  	$mysql_link->set_charset($DBCharset);
  	$mysql_link->query("SET SESSION sql_mode = '' ");

	if($mysql_link->select_db($db_name) == false)
	{
		$DATABASESQL = '';
		if(version_compare($mysql_link->server_version, '4.1.0', '>='))
		{
	    	$DATABASESQL = "DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		}

		if(!$mysql_link->query('CREATE DATABASE `'.$db_name.'` '.$DATABASESQL))
		{
			showProgress(array('isError' => true,'message' => '用户权限受限，创建'.$db_name.'数据库失败，请手动创建数据表'));
		}
		$mysql_link->select_db($db_name);
	}

	//安装SQL
	$mysql_link->query("SET FOREIGN_KEY_CHECKS = 0;");
	parseSQL($sql_file,$mysql_link);

	//采集并安装测试数据
	if($install_type == 'all')
	{
		parseSQL($sqlTest_file,$mysql_link);
	}
	$mysql_link->query("SET FOREIGN_KEY_CHECKS = 1;");

	//插入管理员数据
	$adminSql = 'insert into `'.$db_pre.'admin` (`admin_name`,`password`,`role_id`,`create_time`) values ("'.$admin_user.'","'.md5($admin_pwd).'",0,"'.date('Y-m-d H:i:s').'")';
	if(!$mysql_link->query($adminSql))
	{
		showProgress(array('isError' => true,'message' => '创建管理员失败'.$mysql_link->error,'percent' => 0.9));
	}

	//写入配置文件
	$configDefFile = ROOT_PATH.'./config/config_default.php';
	$configFile    = ROOT_PATH.'./config/config.php';
	$updateData    = array(
		'{TABLE_PREFIX}' => $db_pre,

		'{DB_R_ADDRESS}' => $db_address,
		'{DB_R_USER}'    => $db_user,
		'{DB_R_PWD}'     => $db_pwd,
		'{DB_R_NAME}'    => $db_name,

		'{DB_W_ADDRESS}' => $db_address,
		'{DB_W_USER}'    => $db_user,
		'{DB_W_PWD}'     => $db_pwd,
		'{DB_W_NAME}'    => $db_name,

		'{ENCRYPTKEY}'   => md5(rand(1000000000,9999999999)),
	);

	$is_success = create_config($configFile,$configDefFile,$updateData);
	if(!$is_success)
	{
		showProgress(array('isError' => true,'message' => '更新配置文件失败','percent' => 0.9));
	}

	//修改index.php首页
	$index_file = ROOT_PATH.'./index.php';
	$index_content = '<?php
$iweb = dirname(__FILE__)."/lib/iweb.php";
$config = dirname(__FILE__)."/config/config.php";
require($iweb);
IWeb::createWebApp($config)->run();
?>';

	$is_success = file_put_contents($index_file,$index_content);
	if(!$is_success)
	{
		showProgress(array('isError' => true,'message' => '生成index.php页面出错','percent' => 0.9));
	}

	//执行完毕
	showProgress(array('isError' => false,'message' => '安装完成','percent' => 1));
}

//输出json数据
function showProgress($return_info)
{
	echo '<script type="text/javascript">parent.update_progress('.JSON::encode($return_info).');</script>';
	flush();
	if($return_info['isError'] == true)
	{
		exit;
	}
}

//根据默认模板生成config文件
function create_config($config_file,$config_def_file,$updateData)
{
	$defaultData = file_get_contents($config_def_file);
	$configData  = str_replace(array_keys($updateData),array_values($updateData),$defaultData);
	return file_put_contents($config_file,$configData);
}

//查询解决方案
function configInfo($item)
{
	$data = array(
		'mysql'=> 'http://www.baidu.com/#wd=php%20mysql%E6%89%A9%E5%B1%95&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=4031&f=8&bs=php%20mysql%E7%BB%84%E4%BB%B6&rsv_sug3=16&rsv_sug4=653&rsv_sug1=22&rsv_sug2=0&rsv_sug=2',
		'gd'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20gd&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=1513&f=8&bs=php%20gd&rsv_sug3=23&rsv_sug4=914&rsv_sug1=34&rsv_sug2=0',
		'xml'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20xml&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=1262&f=8&bs=php%20%E5%BC%80%E5%90%AF%20gd&rsv_sug3=27&rsv_sug4=1014&rsv_sug1=36&rsv_sug2=0&rsv_sug=1',
		'session'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20session&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=7586&f=8&bs=php%20%E5%BC%80%E5%90%AF%20xml&rsv_sug3=34&rsv_sug4=1245&rsv_sug1=47&rsv_sug2=0',
		'iconv'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20iconv&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=878&f=8&bs=php%20%E5%BC%80%E5%90%AF%20session&rsv_sug3=36&rsv_sug4=1315&rsv_sug1=49&rsv_n=2&rsv_sug=1',
		'zip'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20zip&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=1823&f=8&bs=php%20%E5%BC%80%E5%90%AF%20iconv&rsv_sug3=43&rsv_sug4=1506&rsv_sug1=54&rsv_sug=2&rsv_sug2=0',
		'curl'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20curl&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=886&f=8&bs=php%20%E5%BC%80%E5%90%AF%20zip&rsv_sug3=45&rsv_sug4=1587&rsv_sug1=58&rsv_n=2',
		'OpenSSL'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20OpenSSL&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=909&f=8&bs=php%20%E5%BC%80%E5%90%AF%20curl&rsv_sug3=47&rsv_sug4=1667&rsv_sug1=61&rsv_n=2',
		'safe_mode'=> 'http://www.baidu.com/#wd=php%20safe_mode%20%E5%85%B3%E9%97%AD&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=885&f=8&bs=php%20safe_mode%20%E5%85%B3%E9%97%AD&rsv_sug=1&rsv_sug3=7&rsv_sug4=237&rsv_sug1=11&rsv_n=2',
		'allow_url_fopen'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20allow_url_fopen&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=1088&f=8&bs=php%20%E5%BC%80%E5%90%AF%20sockets&rsv_sug3=52&rsv_sug4=1844&rsv_sug1=65&rsv_n=2&rsv_sug=1',
		'memory_limit'=> 'http://www.baidu.com/#wd=php%20%E5%BC%80%E5%90%AF%20memory_limit&rsv_spt=1&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=2508&f=8&bs=php%20%E5%BC%80%E5%90%AF%20allow_url_fopen&rsv_sug3=54&rsv_sug4=1921&rsv_sug1=69&rsv_n=2&rsv_sug=1',
		'asp_tags'=> 'http://www.baidu.com/#wd=asp_tags%20%E5%85%B3%E9%97%AD&rsv_spt=3&rsv_bp=1&ie=utf-8&tn=baiduhome_pg&inputT=1244&f=8&bs=php%20asp_tags%20%E5%85%B3%E9%97%AD&rsv_sug3=69&rsv_sug4=2382&rsv_sug1=75&rsv_sug=1&rsv_sug2=0',
	);

	if(isset($data[$item]))
	{
		return "<a href='".$data[$item]."' target='_blank'>立即解决</a>";
	}
}