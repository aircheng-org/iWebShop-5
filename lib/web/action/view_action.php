<?php
/**
 * @copyright (c) 2009-2011 aircheng.com
 * @file view_action.php
 * @brief 视图动作
 * @author Ben
 * @date 2010-12-16
 * @version 0.6
 */

/**
 * @class IViewAction
 * @brief 视图动作
 */
class IViewAction extends IAction
{
	//完整的视图路径地址(无扩展名)
	public $view;

	/**
	 * @brief 执行视图渲染
	 * @param string $view    渲染的视图物理完整路径
	 * @param mixed  $data    渲染的数据
	 * @param string $runtime 渲染的视图运行时物理完整路径
	 * @return 视图
	 */
	public function run($view = '',$data = null,$runtime = '')
	{
		$controller = $this->getController();
		IInterceptor::trigger("onCreateView",$controller,$this);

		$this->view = $view ? $view : $controller->getViewFile($this->getId());
		if(is_file($this->view.$controller->extend))
		{
			$controller->render($this->view,$data,false,$runtime);
		}
		else
		{
			$path = $this->view.$controller->extend;
			$path = IException::pathFilter($path);
			IError::show(404,"not found this view page({$path})");
		}
		IInterceptor::trigger("onFinishView",$controller,$this);
	}
}