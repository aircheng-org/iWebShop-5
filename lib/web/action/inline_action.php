<?php
/**
 * @copyright (c) 2016 aircheng.com
 * @file inline_action.php
 * @brief 控制器内部action
 * @author nswe
 * @date 2016/3/7 9:07:57
 * @version 4.4
 */

/**
 * @class IInlineAction
 * @brief 控制器内部action
 */
class IInlineAction extends IAction
{
	/**
	 * @brief 内部action动作执行方法
	 */
	public function run()
	{
		$controller = $this->getController();
		$methodName = $this->getId();

        //1,自定义defaultActions配置
		if(isset($controller->defaultActions[$methodName]) && is_object($controller->defaultActions[$methodName]))
		{
		    call_user_func(array($controller->defaultActions[$methodName],$methodName));
		}
		//2,匿名函数
		else if(is_callable($controller->$methodName))
		{
		    call_user_func($controller->$methodName);
		}
		//3,类方法
		else
		{
		    call_user_func(array($controller,$methodName));
		}
	}
}