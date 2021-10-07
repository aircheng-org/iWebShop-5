<?php
/**
 * @class log
 * @brief 日志记录类
 */
class Log
{
	private $logInfo = array(
		'operation' => array('table' => 'log_operation','cols' => array('author','action','content')),
	);

	//获取日志对象
	public function __construct($logType = 'db')
	{

	}

	/**
	 * @brief 写入日志
	 * @param string $type 日志类型
	 * @param array  $logs 日志内容数据
	 */
	public function write($type,$logs = array())
	{
		$logInfo = $this->logInfo;
		if(!isset($logInfo[$type]))
		{
			return false;
		}

		//组合日志数据
		$tableName = $logInfo[$type]['table'];
		$content = array(
			'datetime' => ITime::getDateTime(),
		);

		foreach($logInfo[$type]['cols'] as $key => $val)
		{
			$content[$val] = isset($logs[$val]) ? $logs[$val] : isset($logs[$key]) ? $logs[$key] : '';
		}

		$logObj = new IModel($tableName);
		$logObj->setData($content);
		return $logObj->add();
	}
}