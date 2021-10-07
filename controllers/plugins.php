<?php
/**
 * @brief 插件模块
 * @class plugins
 * @note  后台
 */
class plugins extends IController implements adminAuthorization
{
	public $layout='admin';
	public $checkRight = array('check' => 'all');

	function init()
	{

	}

	//修改插件
	public function plugin_edit()
	{
		$className = IFilter::act(IReq::get('class_name'));
		if(!$className || !$pluginRow = plugin::getItems($className))
		{
			IError::show("插件不存在");
		}
		$this->pluginRow = $pluginRow;
		if($this->pluginRow['is_install'] == 0)
		{
			IError::show("插件必须还没有安装无法进行配置");
		}
		$this->redirect('plugin_edit');
	}

	//更新插件信息
	public function plugin_update()
	{
		$className    = IFilter::act(IReq::get('class_name'));
		$isOpen       = IFilter::act(IReq::get('is_open'));
		$config_param = array();

		$pluginRow = plugin::getItems($className);
		if(!$pluginRow)
		{
			IError::show("插件不存在");
		}

		if($pluginRow['is_install'] == 0)
		{
			IError::show("插件还没有安装无法进行配置");
		}

		if($_POST)
		{
			foreach($_POST as $key => $val)
			{
				if(array_key_exists($key,$pluginRow['config_name']))
				{
					$config_param[$key] = is_array($val) ? join(";",$val) : $val;
					$config_param[$key] = IFilter::act(trim($config_param[$key]));
				}
			}
		}

		Config::edit('config/plugin_config.php',[$className => ['is_open' => $isOpen,'config_param' => JSON::encode($config_param)]]);
		$this->redirect('plugin_list');
	}

	//删除插件
	public function plugin_del()
	{
		$className = IFilter::act(IReq::get('class_name'));
		$pluginRow = plugin::getItems($className);
		if(!$pluginRow)
		{
			IError::show("插件不存在");
		}

		if($pluginRow['is_install'] == 0)
		{
			IError::show("插件未安装到系统");
		}

		//运行插件uninstall卸载接口
		$uninstallResult = call_user_func(array($pluginRow['class_name'],"uninstall"));
		if($uninstallResult === true)
		{
			Config::edit('config/plugin_config.php',[$className],'del');
		}
		else
		{
			$message = is_string($uninstallResult) ? $uninstallResult : "卸载插件失败";
			IError::show($message);
		}
		$this->redirect('plugin_list');
	}

	//添加插件
	public function plugin_add()
	{
		$className = IFilter::act(IReq::get('class_name'));
		$pluginRow = plugin::getItems($className);
		if(!$pluginRow)
		{
			IError::show("插件不存在");
		}

		if($pluginRow['is_install'] == 1)
		{
			IError::show("插件已经安装到系统");
		}

		//运行插件install安装接口
		$installResult = call_user_func(array($pluginRow['class_name'],"install"));
		if($installResult === true)
		{
			Config::edit('config/plugin_config.php',[$className => ['is_open' => 0]]);
		}
		else
		{
			$message = is_string($installResult) ? $installResult : "安装插件失败";
			IError::show($message);
		}
		$this->redirect('plugin_list');
	}
}