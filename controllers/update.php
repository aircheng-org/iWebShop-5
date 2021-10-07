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
			"alter table `{pre}order_goods` add column `refunds_nums` smallint(5) NOT NULL default '0' COMMENT '退款数量';",
			"alter table `{pre}refundment_doc` add column `order_goods_nums` text COMMENT '退款数量集合';",
			"alter table `{pre}exchange_doc` add column `order_goods_nums` text COMMENT '退款数量集合';",
			"alter table `{pre}fix_doc` add column `order_goods_nums` text COMMENT '退款数量集合';",

			"alter table `{pre}seller` add column `x` decimal(16,11) default NULL COMMENT '商家坐标X';",
			"alter table `{pre}seller` add column `y` decimal(16,11) default NULL COMMENT '商家坐标Y';",

			"alter table `{pre}withdraw` add column `finish_time` datetime NULL COMMENT '完成时间';",
			"alter table `{pre}withdraw` add column `pay_no` varchar(50) NULL COMMENT '转账回执单号';",
			"alter table `{pre}withdraw` add column `way` varchar(50) NULL COMMENT '转账方式';",

			"ALTER TABLE `{pre}withdraw` DROP `is_del`;",
		);

		foreach($sql as $key => $val)
		{
		    IDBFactory::getDB()->query( $this->_c($val) );
		}

        //清空runtime缓存
		$runtimePath = IWeb::$app->getBasePath().'runtime';
		$result      = IFile::clearDir($runtimePath);
		die("升级成功!! V5.10版本");
	}

	public function _c($sql)
	{
		return str_replace('{pre}',IWeb::$app->config['DB']['tablePre'],$sql);
	}
}