<?php
//检查环境配置
class checkConfig
{
	//安装需求
	private $php_version     = '5.5.0';
	private $must_extension  = array('mysqli','gd','xml','session','iconv');
	private $recom_extension = array('zip','curl','OpenSSL','mbstring','sockets');
	private $writeable_dir   = array('config/config.php','config/site_config.php','config/plugin_config.php','install','backup','index.php','upload','runtime');
	private $readable_dir    = array('config/config_default.php','install/iwebshop.sql');
	private $php_ini         = array('safe_mode' => array('type' => '=','value' => false),'allow_url_fopen' => array('type' => '=','value' => '1'),'memory_limit' => array('type' => '>','value' => '12M'),'asp_tags' => array('type' => '=','value' => false));
	private $php_function    = array('unlink','mkdir','filemtime','fopen','fwrite','fclose','session_start','assert');

	//记录检查结果
	private static $npass_must_num  = 0;
	private static $npass_recom_num = 0;

	//构造函数
	public function __construct()
	{

	}

	//检查php函数
	public function c_functionExists()
	{
		$return = array();
		foreach($this->php_function as $key => $val)
		{
			$is_pass = function_exists($val);
			if(!$is_pass)
			{
				self::$npass_must_num++;
			}
			$return[$val] = $is_pass;
		}
		return $return;
	}

	//检查php版本
	public function c_phpVersion()
	{
		$is_pass = version_compare(phpversion(),$this->php_version);
		if($is_pass < 0)
		{
			self::$npass_must_num++;
		}
		return $is_pass < 0 ? false : true;
	}

	//获取程序所需的php版本号
	public function getPHPVersion()
	{
		return $this->php_version;
	}

	//检查目录权限
	public function c_writeableDir()
	{
		if(defined('ROOT_PATH') == false)
		{
			die('缺少ROOT_PATH常量,无法找到程序路径');
		}
		$return = array();
		foreach($this->writeable_dir as $key => $val)
		{
			$checkDir = is_dir($val) ? $val : ROOT_PATH.'./'.$val;
			$is_pass = is_writable($checkDir);
			if(!$is_pass)
			{
				self::$npass_must_num++;
			}

			//根目录
			if($val == '.')
			{
				$val = '根目录';
			}
			$return[$val] = $is_pass;
		}
		return $return;
	}

	//检查目录可读性
	public function c_readableDir()
	{
		if(defined('ROOT_PATH') == false)
		{
			die('缺少ROOT_PATH常量,无法找到程序路径');
		}
		$return = array();
		foreach($this->readable_dir as $key => $val)
		{
			$is_pass = is_readable(ROOT_PATH.'./'.$val);
			if(!$is_pass)
			{
				self::$npass_must_num++;
			}
			$return[$val] = $is_pass;
		}
		return $return;
	}

	//检查php_ini配置
	public function c_phpIni()
	{
		$return  = array();
		foreach($this->php_ini as $key => $val)
		{
			$localIni = @ini_get($key);

			if($localIni === false)
			{
				$return[$key] = true;
				continue;
			}

			if($val['type'] == '=' && $localIni == $val['value'])
			{
				$return[$key] = true;
				continue;
			}

			if($val['type'] == '>' && floatval($localIni) >= floatval($val['value']))
			{
				$return[$key] = true;
				continue;
			}

			self::$npass_must_num++;
			$return[$key] = false;
		}
		return $return;
	}

	//检查必备php扩展
	public function c_must_extension()
	{
		$return = array();
		foreach($this->must_extension as $key => $val)
		{
			$is_pass = extension_loaded($val);

			if($is_pass)
			{
				switch($val)
				{
					//考虑GD库的FreeType字体库是否存在
					case "gd":
					{
						$is_pass = $this->checkGD();
					}
					break;
				}
			}

			$return[$val] = $is_pass;

			if($is_pass == false)
			{
				self::$npass_must_num++;
			}
		}
		return $return;
	}

	//检查建议php扩展
	public function c_recom_extension()
	{
		$return = array();
		foreach($this->recom_extension as $key => $val)
		{
			$is_pass = extension_loaded($val);
			$return[$val] = $is_pass;

			if($is_pass == false)
			{
				self::$npass_recom_num++;
			}
		}
		return $return;
	}

	//获取检测数据
	public function getNpassMustNum()
	{
		return self::$npass_must_num;
	}

	//检查GD库是否完整
	private function checkGD()
	{
		$gdInfo = gd_info();
		if(isset($gdInfo['FreeType Support']) && $gdInfo['FreeType Support'] == '1')
		{
			return true;
		}
		return false;
	}
}