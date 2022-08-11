<?php
//语言包
class lang
{
	private static $langDir = 'language';
	private static $defaultLang = 'en';

	//语言
	public static function get($lang)
	{
		$data = include(IWeb::$app->getBasePath()."/".self::$langDir."/".self::$defaultLang."/lang.php");
		return isset($data[$lang]) ? $data[$lang] : $lang;
	}
}