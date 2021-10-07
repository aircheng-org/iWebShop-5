<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file errors.php
 * @brief 错误处理类
 * @author chendeshan
 * @date 2018/5/6 9:19:24
 * @version 5.1

 * @update 控制器增加布局layout属性
 * @date 2018/6/23 13:46:03
 * @version 5.2
 */
class Errors extends IController
{
	public $layout = 'site';

	//根据场景跳转不同的错误界面
	private function sence()
	{
		//网站后台
		if(IWeb::$app->getController()->admin)
		{
			return '/errors_admin/error';
		}
		//商家后台
		else if(IWeb::$app->getController()->seller)
		{
			return '/errors_seller/error';
		}
		//默认前台
		return '/errors/error';
	}

	//404报错
	public function error404($data)
	{
		$data = "访问的资源地址不存在";
		$this->redirect($this->sence().'/message/'.$data);
	}

	//403报错
	public function error403($data)
	{
		$data = IFilter::act($data);
		$this->redirect($this->sence().'/message/'.$data);
	}

	//503报错
	public function error503($data)
	{
		$data = IFilter::act($data);
		$this->redirect($this->sence().'/message/'.$data);
	}
}