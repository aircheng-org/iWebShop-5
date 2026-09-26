<?php
/**
 * @brief 升级更新控制器
 */
class Update extends IController
{
	/**
	 * @brief 升级更新
	 */
	public function index()
	{
		set_time_limit(0);

		$sql = array(
			"alter table `{pre}bill` add column `received_amount` decimal(15,2) NOT NULL default '0.00' COMMENT '已收到金额';",
			"alter table `{pre}bill` add column `status` tinyint(1) NOT NULL default '0' COMMENT '状态:0待处理,1进行中,2已完成,-1未完成';",
		);

		foreach($sql as $key => $val)
		{
		    IDBFactory::getDB()->query( $this->_c($val) );
		}

        //清空runtime缓存
		$runtimePath = IWeb::$app->getBasePath().'runtime';
		$result      = IFile::clearDir($runtimePath);
		die("升级成功!! V5.16版本");
	}

	public function _c($sql)
	{
		return str_replace('{pre}',IWeb::$app->config['DB']['tablePre'],$sql);
	}
}