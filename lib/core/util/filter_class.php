<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file filter_class.php
 * @brief 过滤库
 * @author chendeshan
 * @date 2016/5/7 16:32:14
 * @version 4.5
 */

/**
 * @class IFilter
 * @brief IFilter 过滤
 */
class IFilter
{
	/**
	 * @brief 过滤字符串的长度
	 * @param string $str 被限制的字符串
	 * @param int $length 限制的字节数
	 * @return string 空:超出限制值; $str:原字符串;
	 */
	public static function limitLen($str,$length = 30)
	{
		if($length == false)
		{

		}
		else
		{
			$count = IString::getStrLen($str);
			if($count > $length)
			{
				return '';
			}
		}
		return $str;
	}

	/**
	 * @brief 对字符串进行过滤处理
	 * @param  string $str      被过滤的字符串
	 * @param  string $type     过滤数据类型 值: int, float, string, text, bool, url
	 * @param  int    $limitLen 被输入的最大字符个数 , 默认不限制;
	 * @return string 被过滤后的字符串
	 * @note   默认执行的是string类型的过滤
	 */
	public static function act($str,$type = 'string',$limitLen = false)
	{
		if(is_array($str))
		{
			$resultStr = array();
			foreach($str as $key => $val)
			{
				$key = self::addSlash($key);
				$val = self::act($val, $type, $limitLen);
				$resultStr[$key] = $val;
			}
			return $resultStr;
		}
		else
		{
			//引用IValidate校验类协助过滤
			if(method_exists("IValidate",$type))
			{
				$result = call_user_func(array("IValidate",$type),trim($str));
				return $result == true ? $str : "";
			}

			//引用正则表达式
			if(preg_match("%\W%",$type[0]) == true)
			{
				$type = trim($type,$type[0]);
				return IValidate::check($type,$str) ? $str : "";
			}

			switch($type)
			{
				case "int":
					return intval($str);
					break;

				case "float":
					return floatval($str);
					break;

				case "text":
					return self::text($str,$limitLen);
					break;

				case "bool":
					return (bool)$str;
					break;

				default:
					return self::string($str,$limitLen);
					break;
			}
		}
	}

	/**
	 * @brief  对字符串进行严格的过滤处理
	 * @param  string  $str      被过滤的字符串
	 * @param  int     $limitLen 被输入的最大长度
	 * @return string 被过滤后的字符串
	 * @note 过滤所有html标签和php标签以及部分特殊符号
	 */
	public static function string($str,$limitLen = false)
	{
		$str = trim($str);
		$str = self::limitLen($str,$limitLen);
		$str = htmlspecialchars($str,ENT_NOQUOTES);
		return self::addSlash($str);
	}

	/**
	 * @brief 对字符串进行普通的过滤处理
	 * @param string $str      被过滤的字符串
	 * @param int    $limitLen 限定字符串的字节数
	 * @return string 被过滤后的字符串
	 * @note 仅对于部分如:<script,<iframe等标签进行过滤
	 */
	public static function text($str,$limitLen = false)
	{
		$str = trim($str);
		$str = self::limitLen($str,$limitLen);

		require_once(dirname(__FILE__)."/htmlpurifier/HTMLPurifier.standalone.php");
		$cache_dir=IWeb::$app->getRuntimePath()."htmlpurifier/";

		if(!file_exists($cache_dir))
		{
			IFile::mkdir($cache_dir);
		}
		$config = HTMLPurifier_Config::createDefault();

		//配置 允许flash
		$config->set('HTML.SafeEmbed',true);
		$config->set('HTML.SafeObject',true);
		$config->set('HTML.SafeIframe',true);
		$config->set('Output.FlashCompat',true);

		//配置 缓存目录
		$config->set('Cache.SerializerPath',$cache_dir); //设置cache目录

		//允许<a>的target属性
		$def = $config->getHTMLDefinition(true);
		$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
		$def->addAttribute('a', 'rel', "CDATA");
		$def->addAttribute('img', 'style',"CDATA");
		$def->addAttribute('img', 'data-src',"CDATA");
		$def->addAttribute('iframe', 'src',"CDATA");
		$def->addAttribute('embed', 'autostart',"CDATA");
		$def->addAttribute('div', 'data-oembed-url',"CDATA");

		$def->addElement('video','Block','Flow','Common',['src' => 'URI','type' => 'Text','controls' => 'Text','width' => 'Text','height' => 'Text']);
		$def->addElement('source','Block','Flow','Common',['src' => 'URI','type' => 'Text']);
		$def->addElement('figure','Block','Flow','Common',['class' => 'Text']);

		//过略掉所有<script>，<i?frame>标签的on事件,css的js-expression、import等js行为，a的js-href
		$purifier = new HTMLPurifier($config);
		return self::addSlash($purifier->purify($str));
	}

	/**
	 * @brief 增加转义斜线
	 * @param string $str 要转义的字符串
	 * @return string 转义后的字符串
	 */
	public static function addSlash($str)
	{
		if(is_array($str))
		{
			$resultStr = array();
			foreach($str as $key => $val)
			{
				$resultStr[$key] = self::addSlash($val);
			}
			return $resultStr;
		}
		else
		{
			return addslashes(self::word($str));
		}
	}

	/**
	 * @brief 去掉转义斜线
	 * @param string $str 要转义的字符串
	 * @return string 去掉转义的字符串
	 */
	public static function stripSlash($str)
	{
		if(is_array($str))
		{
			$resultStr = array();
			foreach($str as $key => $val)
			{
				$resultStr[$key] = self::stripSlash($val);
			}
			return $resultStr;
		}
		else
		{
			return stripslashes($str);
		}
	}

	/**
	 * @brief 检测文件是否有可执行的代码
	 * @param string  $file 要检查的文件路径
	 * @return boolean 检测结果
	 */
	public static function checkHex($file)
	{
		$resource = fopen($file, 'rb');
		$fileSize = filesize($file);
		fseek($resource, 0);
		// 读取文件的头部和尾部
		if ($fileSize > 5000)
		{
			$hexCode = fread($resource, 2000);
			fseek($resource, $fileSize - 2000);
			$hexCode .= fread($resource, 2000);
		}
		// 读取文件的全部内容
		else
		{
			$hexCode = fread($resource, $fileSize);
		}

		fclose($resource);
		if (preg_match('@(<script)|(<?php)|($_REQUEST)|($_POST)|(base64)@is', $hexCode))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * @brief 过滤空数组元素
	 * @param array $arr 要过滤的数组
	 * @return array 过滤后的数组
	 */
	public static function emptyArray($arr)
	{
		$narr = array();
		foreach($arr as $key => $val)
		{
			if(is_array($val))
			{
				$val = self::emptyArray($val);
				if (count($val)!=0)
				{
					$narr[$key] = $val;
				}
			}
			else
			{
				if (trim($val) != "")
				{
					$narr[$key] = $val;
				}
			}
		}
		unset($arr);
		return $narr;
	}

	/**
	 * @brief 过滤关键词
	 * @param string $str 要过滤的文本
	 * @return string
	 */
	public static function word($str)
	{
		$word = ["..\\","../","file://","`","select ","select/*","select%","update ","update/*","update%","delete ","delete/*","delete%","insert into","insert/*","insert%","updatexml","concat","()","/**/","union("];
		foreach($word as $val)
		{
			if(stripos($str,$val) !== false)
			{
				return '';
			}
		}
		return self::removeEmoji($str);
	}

	/**
	 * @brief 过滤二进制表情
	 * @param string $str 要过滤的文本
	 * @return string
	 */
	public static function removeEmoji($str)
	{
		$str = preg_replace_callback('/./u',function($match){
			return strlen($match[0]) >= 4 ? '' : $match[0];
		},$str);
		return $str;
	}

	/**
	 * @brief 隐藏手机号码中的若干位
	 * @param string $mobile 被隐藏的手机号码
	 * @return string
	 */
	public static function hideMobile($mobile)
	{
	    return substr_replace($mobile,'****',3,4);
	}
}