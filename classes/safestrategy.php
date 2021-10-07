<?php
/**
 * check website safe strategy
 * @date 2013/9/8 17:00:46
 * @author nswe
 */
class safeStrategy
{
	private $safeInfo = array();

	/**
	 * constructor
	 */
	public function __construct()
	{
	}

	/**
	 * start check website safe options and return a array
	 * @return array
	 */
	public function check()
	{
		$this->cInstall();
		return $this->safeInfo;
	}

	/**
	 * check the install dir whether exists
	 * @return boolean
	 */
	private function cInstall()
	{
		$appBasePath = IWeb::$app->getBasePath();
		$installPath = $appBasePath . 'install';

		if(file_exists($installPath))
		{
			$this->safeInfo[] = array('content' => '您的安装目录(install目录)没有删除，为了商店安全，请尽快删除或者重新命名');
		}
	}
}