<?php
/**
 * @copyright (c) 2011 [group]
 * @file pointlog.php
 * @brief 积分日志记录处理类
 * @author chendeshan
 * @date 2011-6-15 14:58:39
 * @version 0.6
 */
class Point
{
	//错误信息
	private $error  = '';

	/**
	 * @brief 积分操作的构造函数
	 * @param array $config => array('user_id' => 用户ID , 'point' => 积分增减(正，负区分) , 'log' => 日志记录内容)
	 */
	public function update($config)
	{
		if(!isset($config['user_id']) || intval($config['user_id']) <= 0)
		{
			$this->error = '用户ID不能为空';
		}
		else if(!isset($config['point']) || intval($config['point']) == 0)
		{
			$this->error = '积分格式不正确';
		}
		else if(!isset($config['log']))
		{
			$this->error = '积分日志内容不正确';
		}
		else
		{
			$is_success = $this->editPoint($config['user_id'],$config['point']);
			if($is_success)
			{
				if(!$this->writeLog($config))
				{
					$this->error = '记录日志失败';
				}
			}
			else
			{
				$this->error = '积分更新失败';
			}
		}

		return $this->error == '' ? true:false;
	}

	//返回错误信息
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @brief 日志记录
	 * @param array $config => array('user_id' => 用户ID , 'point' => 积分增减(正，负区分) , 'log' => 日志记录内容)
	 */
	private function writeLog($config)
	{
		//修改pointLog表
		$poinLogObj    = new IModel('point_log');
		$pointLogArray = array(
			'user_id' => $config['user_id'],
			'datetime'=> ITime::getDateTime(),
			'value'   => $config['point'],
			'intro'   => $config['log'],
		);
		$poinLogObj->setData($pointLogArray);
		return $poinLogObj->add();
	}

	/**
	 * @brief 积分更新
	 * @param int $user_id 用户ID
	 * @param int $point   积分数(正，负)
	 */
	private function editPoint($user_id,$point)
	{
		$memberObj   = new IModel('member');
		$memberArray = array('point' => 'point + '.$point);
		$memberObj->setData($memberArray);
		return $memberObj->update('user_id = '.$user_id,'point');
	}

}