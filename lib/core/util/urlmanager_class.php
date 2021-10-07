<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file urlmanager_class.php
 * @brief URL处理类
 * @author walu
 * @date 2011-07-18
 * @version 0.7
 * @note
 */

/**
 * @class IUrl
 * @brief URL处理类
 * @note
 */
class IUrl
{
	const UrlNative		= 1; //原生态的Url形式,指从index.php  例如：index.php?controller=blog&action=read&id=100
	const UrlPathinfo	= 2; //路径形式的Url,                 例如：/blog/read/id/100
	const UrlDiy		= 3; //经过urlRoute后的Url            例如: /blog-100.html
	const UrlPathSham   = 4; //带有index.php的pathinfo格式    例如：index.php/blog/red/id/100

	const UrlCtrlName	= 'controller';
	const UrlActionName	= 'action';

	const Anchor = "/#&"; //urlArray中表示锚点的索引

	const QuestionMarkKey = "?";// /site/abc/?callback=/site/login callback=/site/login部分在UrlArray里的key

	private static $urlRoute = array(); //路由规则的缓存

	//获取协议类型
	public static function scheme()
	{
		if(isset($_SERVER['HTTP_X_CLIENT_SCHEME']))
		{
			return $_SERVER['HTTP_X_CLIENT_SCHEME'];
		}
		return isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
	}

	/**
	 * @brief 获取当前Controller、action信息
	 * @param string $key controller或者action
	 * @return string
	 */
	public static function getInfo($key)
	{
		$arr = array(
			'controller'=> self::UrlCtrlName,
			'action'    => self::UrlActionName,
		);

		$result = isset($arr[$key]) ? IFilter::act( IReq::get($arr[$key]) , "%\w+%" ) : '';
		return is_array($result) ? current($result) : $result;
	}

	/**
	 * @brief 将Url从IWeb支持的一种Url格式转成另一个格式。
	 * @param string $url 想转换的url
	 * @param int $from IUrl::UrlNative或者.....
	 * @param int $to IUrl::UrlPathinfo或者.....
	 * @return string 如果转换失败则返回false
	 */
	public static function convertUrl($url,$from,$to)
	{
		if($from == $to)
		{
			return $url;
		}

		$urlArray = "";
		$fun_re = false;
		switch($from)
		{
			case self::UrlNative :
				$urlTmp = parse_url($url);
				$urlArray = self::queryStringToArray($urlTmp);
				$urlArray = array_filter($urlArray,function($param){return $param !=="";});
				break;
			case self::UrlPathinfo :
				$urlArray = self::pathinfoToArray($url);
				break;
			case self::UrlDiy :
				$urlArray = self::diyToArray($url);
				break;
			default:
				return $fun_re;
				break;
		}

		switch($to)
		{
			case self::UrlNative :
				$fun_re = self::urlArrayToNative($urlArray);
				break;
			case self::UrlPathinfo :
				$fun_re = self::urlArrayToPathinfo($urlArray);
				break;
			case self::UrlDiy:
				$fun_re = self::urlArrayToDiy($urlArray);
				break;
		}
		return $fun_re;
	}

	/**
	 * @brief 将controller=blog&action=read&id=100类的query转成数组的形式
	 * @param string $url
	 * @return array
	 */
	public static function queryStringToArray($url)
	{
		if(!is_array($url))
		{
			$url = parse_url($url);
		}
		$query = isset($url['query'])?explode("&",$url['query']):array();
		$re = array();
		foreach($query as $value)
		{
			$tmp = explode("=",$value);
			if( count($tmp) == 2 )
			{
				$re[$tmp[0]] = $tmp[1];
			}
		}
		$re = self::sortUrlArray($re);
		isset($url['fragment']) && ($re[self::Anchor] = $url['fragment'] );
		return $re;
	}

	/**
	 * @brief 将/blog/read/id/100形式的url转成数组的形式
	 * @param string $url
	 * @return array
	 */
	public static function pathinfoToArray($url)
	{
		//blog/read/id/100
		//blog/read/id/100?comment=true#abcde
		$data = array();
		preg_match("!^(.*?)?(\\?[^#]*?)?(#.*)?$!",$url,$data);
		$re = array();
		if( isset($data[1]) && trim($data[1],"/ ") )
		{
			$string = explode("/", trim($data[1],"/ "));

			//前两个是ctrl和action，后面的是参数名和值
			$re[self::UrlCtrlName]   = array_shift($string);
			$re[self::UrlActionName] = array_shift($string);

			//剩余参数自动按对拆分
			$otherArray = array_chunk($string,2);
			foreach($otherArray as $value)
			{
				//key和value匹配正确
				if(count($value) == 2)
				{
					//url存在数组格式类型
					if(strpos($value[0],"[") !== false)
					{
						$urlArray = explode("[",$value[0]);
						$re[$urlArray[0]][trim($urlArray[1],"[]")] = $value[1];
					}
					else
					{
						$re[$value[0]] = $value[1];
					}
				}
			}
		}
		if( isset($data[2]) || isset($data[3]) )
		{
			$re[ self::QuestionMarkKey ] = ltrim($data[2],"?");
		}

		if(isset($data[3]))
		{
			$re[ self::Anchor ] = ltrim($data[3],"#");
		}

		$re = self::sortUrlArray($re);
		return $re;

	}

	/**
	 * @brief 将用户请求的url进行路由转换，得到urlArray
	 * @param string  $url
	 * @return array
	 */
	public static function diyToArray($url)
	{
		return self::decodeRouteUrl($url);
	}

	/**
	 * @brief 对Url数组里的数据进行排序
	 * ctrl和action最靠前，其余的按key排序
	 * @param array $re
	 * @access private
	 */
	private static function sortUrlArray($re)
	{
		$fun_re=array();
		isset( $re[self::UrlCtrlName] ) && ($fun_re[self::UrlCtrlName]=$re[self::UrlCtrlName]);
		isset( $re[self::UrlActionName] ) && ($fun_re[self::UrlActionName]=$re[self::UrlActionName]);
		unset($re[self::UrlCtrlName],$re[self::UrlActionName]);
		ksort($re);
		$fun_re = array_merge($fun_re,$re);
		return $fun_re;
	}

	/**
	 * @brief 将urlArray用pathinfo的形式表示出来
	 * @access private
	 */
	private static function urlArrayToPathinfo($arr)
	{
		$result = array();//最终的结果数组
		$questionMark = array();//查询字符串

		isset($arr[self::UrlCtrlName])     && $arr[self::UrlCtrlName]     ? $result[]       = $arr[self::UrlCtrlName]     : '';
		isset($arr[self::UrlActionName])   && $arr[self::UrlActionName]   ? $result[]       = $arr[self::UrlActionName]   : '';
		isset($arr[self::QuestionMarkKey]) && $arr[self::QuestionMarkKey] ? $questionMark[] = $arr[self::QuestionMarkKey] : '';

		$fragment = isset($arr[self::Anchor]) ? $arr[self::Anchor] : "";

		unset($arr[self::UrlCtrlName],$arr[self::UrlActionName],$arr[self::Anchor]);
		foreach($arr as $key => $value)
		{
			//参数值就有分隔符号
			if(stripos($value,"/") !== false)
			{
				$questionMark[] = $key."=".$value;
			}
			else
			{
				$result[] = $key;
				$result[] = $value;
			}
		}

		$pathUrl = "/".join("/",$result);
		$pathUrl.= $questionMark ? "?".join('&',$questionMark) : '';
		$pathUrl.= $fragment     ? "#".$fragment               : '';
		return $pathUrl;
	}

	/**
	 * @brief 将urlArray用原生url形式表现出来
	 * @access private
	 */
	private static function urlArrayToNative($arr)
	{
		$re = "/";
		$re .= self::getIndexFile();
		$fragment = isset($arr[self::Anchor]) ? $arr[self::Anchor] : "";

		$questionMark = isset($arr[self::QuestionMarkKey]) ? $arr[self::QuestionMarkKey] : "";

		unset($arr[self::Anchor] , $arr[self::QuestionMarkKey]  );
		if(count($arr))
		{
			$tmp = array();
			foreach($arr as $key => $value)
			{
				if(is_array($value))
				{
					foreach($value as $k => $v)
					{
						$tmp[] ="{$key}[{$k}]={$v}";
					}
				}
				else
				{
					$tmp[] ="{$key}={$value}";
				}
			}
			$tmp = join("&",$tmp);
			$re .= "?{$tmp}";
		}
		if( count($arr) && $questionMark!="" )
		{
			$re .= "&".$questionMark;
		}
		elseif($questionMark!="")
		{
			$re .= "?".$questionMark;
		}

		if($fragment != "")
		{
			$re .= "#{$fragment}";
		}
		return $re;
	}

	/**
	 * @brief 获取路由缓存
	 * @return array
	 */
	private static function getRouteCache()
	{
		//配置文件中不存在路由规则
		if(self::$urlRoute === false)
		{
			return null;
		}

		//存在路由的缓存信息
		if(self::$urlRoute)
		{
			return self::$urlRoute;
		}

		//第一次初始化
		$routeList = isset(IWeb::$app->config['urlRoute']) ? IWeb::$app->config['urlRoute'] : array();
		if(empty($routeList))
		{
			self::$urlRoute = false;
			return null;
		}

		$cacheRoute = array();
		foreach($routeList as $key => $val)
		{
			if(is_array($val))
			{
				continue;
			}

			$tempArray = explode('/',trim($val,'/'),3);
			if($tempArray < 2)
			{
				continue;
			}

			//进行路由规则的级别划分,$level越低表示匹配优先
			$level = 3;
			if    ( ($tempArray[0] != '<'.self::UrlCtrlName.'>') && ($tempArray[1] != '<'.self::UrlActionName.'>') ) $level = 0;
			elseif( ($tempArray[0] == '<'.self::UrlCtrlName.'>') && ($tempArray[1] != '<'.self::UrlActionName.'>') ) $level = 1;
			elseif( ($tempArray[0] != '<'.self::UrlCtrlName.'>') && ($tempArray[1] == '<'.self::UrlActionName.'>') ) $level = 2;

			$cacheRoute[$level][$key] = $val;
		}

		if(empty($cacheRoute))
		{
			self::$urlRoute = false;
			return null;
		}

		ksort($cacheRoute);
		self::$urlRoute = $cacheRoute;
		return self::$urlRoute;
	}

	/**
	 * @brief 将urlArray转成路由后的url
	 * @access private
	 */
	private static function urlArrayToDiy($arr)
	{
		if(!isset( $arr[self::UrlCtrlName] ) || !isset($arr[self::UrlActionName]) || !($routeList = self::getRouteCache()) )
		{
			return false;
		}

		foreach($routeList as $level => $regArray)
		{
			foreach($regArray as $regPattern => $value)
			{
				$urlArray = explode('/',trim($value,'/'),3);

				if($level == 0 && ($arr[self::UrlCtrlName].'/'.$arr[self::UrlActionName] != $urlArray[0].'/'.$urlArray[1]) )
				{
					continue;
				}
				else if($level == 1 && ($arr[self::UrlActionName] != $urlArray[1]) )
				{
					continue;
				}
				else if($level == 2 && ($arr[self::UrlCtrlName] != $urlArray[0]) )
				{
					continue;
				}

				$url = self::parseRegPattern($arr,array($regPattern => $value));

				if($url)
				{
					return $url;
				}
			}
		}
		return false;
	}

	/**
	 * @brief 根据规则生成URL
	 * @param $urlArray array url信息数组
	 * @param $regPattern array 路由规则
	 * @return string or false
	 */
	private static function parseRegPattern($urlArray,$regArray)
	{
		$regPattern = key($regArray);
		$value      = current($regArray);

		//存在自定义正则式
		if(preg_match_all("%<\w+?:.*?>%",$regPattern,$customRegMatch))
		{
			$regInfo = array();
			foreach($customRegMatch[0] as $val)
			{
				$val     = trim($val,'<>');
				$regTemp = explode(':',$val,2);
				$regInfo[$regTemp[0]] = $regTemp[1];
			}

			//匹配表达式参数
			$replaceArray = array();
			foreach($regInfo as $key => $val)
			{
				if(strpos($val,'%') !== false)
				{
					$val = str_replace('%','\%',$val);
				}

				if(isset($urlArray[$key]) && preg_match("%$val%",$urlArray[$key]))
				{
					$replaceArray[] = $urlArray[$key];
					unset($urlArray[$key]);
				}
				else
				{
					return false;
				}
			}

			$url = str_replace($customRegMatch[0],$replaceArray,$regPattern);
		}
		else
		{
			$url = $regPattern;
		}

		//处理多余参数
		$paramArray      = self::pathinfoToArray($value);

		$questionMarkKey = isset($urlArray[self::QuestionMarkKey]) ? $urlArray[self::QuestionMarkKey] : '';
		$anchor          = isset($urlArray[self::Anchor])          ? $urlArray[self::Anchor]          : '';
		unset($urlArray[self::UrlCtrlName],$urlArray[self::UrlActionName],$urlArray[self::Anchor],$urlArray[self::QuestionMarkKey]);
		foreach($urlArray as $key => $rs)
		{
			if(!isset($paramArray[$key]))
			{
				$questionMarkKey .= '&'.$key.'='.$rs;
			}
		}
		$url .= ($questionMarkKey) ? '?'.trim($questionMarkKey,'&') : '';
		$url .= ($anchor)          ? '#'.$anchor                    : '';

		return $url;
	}

	/**
	 * @brief 将请求的url通过路由规则解析成urlArray
	 * @param $url string 要解析的url地址
	 */
	private static function decodeRouteUrl($url)
	{
		$url       = trim($url,'/');
		$urlArray  = array();//url的数组形式
		$routeList = self::getRouteCache();
		if(!$routeList)
		{
			return $urlArray;
		}

		foreach($routeList as $level => $regArray)
		{
			foreach($regArray as $regPattern => $value)
			{
				//解析执行规则的url地址
				$exeUrlArray = explode('/',$value);

				//判断当前url是否符合某条路由规则,并且提取url参数
				$regPatternReplace = preg_replace("%<\w+?:(.*?)>%","($1)",$regPattern);
				if(strpos($regPatternReplace,'%') !== false)
				{
					$regPatternReplace = str_replace('%','\%',$regPatternReplace);
				}

				if(preg_match("%$regPatternReplace%",$url,$matchValue))
				{
					//是否完全匹配整个完整url
					$matchAll = array_shift($matchValue);
					if($matchAll != $url)
					{
						continue;
					}

					//如果url存在动态参数，则获取到$urlArray
					if($matchValue)
					{
						preg_match_all("%<\w+?:.*?>%",$regPattern,$matchReg);
						foreach($matchReg[0] as $key => $val)
						{
							$val                     = trim($val,'<>');
							$tempArray               = explode(':',$val,2);
							$urlArray[$tempArray[0]] = isset($matchValue[$key]) ? $matchValue[$key] : '';
						}

						//检测controller和action的有效性
						if( (isset($urlArray[ self::UrlCtrlName ]) && !preg_match("%^\w+$%",$urlArray[ self::UrlCtrlName ]) ) || (isset($urlArray[ self::UrlActionName ]) && !preg_match("%^\w+$%",$urlArray[ self::UrlActionName ]) ) )
						{
							$urlArray  = array();
							continue;
						}

						//对执行规则中的模糊变量进行赋值
						foreach($exeUrlArray as $key => $val)
						{
							$paramName = trim($val,'<>');
							if( ($val != $paramName) && isset($urlArray[$paramName]) )
							{
								$exeUrlArray[$key] = $urlArray[$paramName];
							}
						}
					}

					//分配执行规则中指定的参数
					$paramArray = self::pathinfoToArray(join('/',$exeUrlArray));
					$urlArray   = array_merge($urlArray,$paramArray);
					return $urlArray;
				}
			}
		}
		return $urlArray;
	}

	/**
	 * @brief  接收基准格式的URL，将其转换为Config中设置的模式
	 * @param  String $url      传入的url
	 * @return String $finalUrl url地址
	 */
	public static function creatUrl($url='')
	{
		$baseDir = self::getScriptDir();
		$baseUrl = self::getPhpSelf();
		if(!$url)
		{
			return $baseDir;
		}

		//根路径
		if($url == "/")
		{
			return $baseDir.$baseUrl;
		}

		//外部连接情况
		if(preg_match("!^[a-z]+://!i",$url) || strpos($url,'javascript:') === 0)
		{
			return $url;
		}

		//文件资源路径
		$fileExt = preg_match("%\.(png|jpg|gif)$%i",$url);
		if(strpos($url,'pic/thumb/') === false && $fileExt)
		{
			return $baseDir.$url;
		}

		//缩略图路径情况,把路径参数中的 "/" 替换为 "-"
		if(strpos($url,'pic/thumb/') !== false)
		{
			$url = preg_replace_callback("!(?<=/img/)(.*)!",function($matches){
				return IFile::dirExplodeEncode($matches[1]);
			},$url);
		}

		//获取config里面的url配置
		$rewriteRule = isset(IWeb::$app->config['rewriteRule'])?IWeb::$app->config['rewriteRule']:'url';
		switch($rewriteRule)
		{
			case "pathinfo":
			{
				$tempUrl = self::convertUrl($url,self::UrlPathinfo,self::UrlDiy);
				if($tempUrl !== false)
				{
					$url = $tempUrl;
				}
			}
			break;

			case "UrlPathSham":
			{
				$url = "/".self::getIndexFile().$url;
			}
			break;

			default:
			{
				$url = self::convertUrl($url,self::UrlPathinfo,self::UrlNative);
			}
		}
		return $baseDir.trim($url,"/");
	}

	/**
	 * @brief 获取网站根路径
	 * @return String $baseUrl  网站根路径
	 */
	public static function getHost()
	{
		if(isset($_SERVER['HTTP_X_CLIENT_SCHEME']))
		{
			$scheme = $_SERVER['HTTP_X_CLIENT_SCHEME'];
		}
		else if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
		{
			$scheme = "HTTPS";
		}
		else if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
		{
		    $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
		}
		else if(isset($_SERVER['REQUEST_SCHEME']))
		{
			$scheme = $_SERVER['REQUEST_SCHEME'];
		}
		else if(isset($_SERVER['SERVER_PROTOCOL']))
		{
			$schemeArray = explode("/",$_SERVER['SERVER_PROTOCOL']);
			$scheme      = trim($schemeArray[0]);
		}
		$scheme  = $scheme ? $scheme : "http";
		$host	 = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
		$baseUrl = strtolower($scheme).'://'.$host;
		return $baseUrl;
	}
	/**
	 * @brief 获取当前执行文件名
	 * @return String 文件名
	 */
	public static function getPhpSelf()
	{
		$re = explode("/",$_SERVER['SCRIPT_NAME']);
		return end($re);
	}
	/**
	 * @brief 返回入口文件URl地址
	 * @return string 返回入口文件URl地址
	 */
	public static function getEntryUrl()
	{
		return self::getHost().$_SERVER['SCRIPT_NAME'];
	}

	/**
	 * @brief 获取入口文件名
	 */
	public static function getIndexFile()
	{
		return isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : 'index.php';
	}

	/**
	 * @brief 返回该主机名下的前一页地址
	 * @param $isPathInfo boolean 是否为pathinfo格式
	 * @return string 返回页面的前一页地址
	 */
	public static function getRefRoute($isPathInfo = true)
	{
		$host = self::getHost();
		$dir  = self::getScriptDir();
		if(isset($_SERVER['HTTP_REFERER']) && ($host & $_SERVER['HTTP_REFERER']) == $host)
		{
			if($isPathInfo == true)
			{
				$urlType = stripos($_SERVER['HTTP_REFERER'],"index.php?") === false ? self::UrlPathinfo : self::UrlNative;
				$url     = self::convertUrl(urldecode($_SERVER['HTTP_REFERER']),$urlType,self::UrlPathinfo);

				//过滤主机,目录,入口文件
				$filterArray = array($host,self::getIndexFile());
				$dir         == '/' ? "" : array_push($filterArray,$dir);
				$url         = str_replace($filterArray,'',$url);
				return '/'.trim($url,'/\\');
			}
			return $_SERVER['HTTP_REFERER'];
		}
		return '';
	}
	/**
	 * @brief  获取当前脚本所在文件夹
	 * @return 脚本所在文件夹
	 */
	public static function getScriptDir()
	{
		$_SERVER['SCRIPT_NAME'] = stristr($_SERVER['SCRIPT_NAME'],'index.php',true).'index.php';
		$return = strtr(dirname($_SERVER['SCRIPT_NAME']),"\\","/");
		return $return == '/' ? '/' : $return.'/';
	}

	/**
	 * @brief 获取当前绝对URL地址
	 * @return String 当前绝对URL地址
	 */
	public static function getUrl()
	{
		return self::getHost().self::getUri();
	}

	/**
	 * @brief 获取当前相对URI地址
	 * @return String 获取当前相对URI地址
	 */
	public static function getUri()
	{
		$obj = IServerVars::factory($_SERVER['SERVER_SOFTWARE']);
		return $obj->requestUri();
	}

	/**
	 * @brief 获取url参数
	 * @param String url 需要分析的url，默认为当前url
	 */
	public static function beginUrl($url='')
	{
		//四种
		//native： /index.php?controller=blog&action=read&id=100
		//pathinfo:/blog/read/id/100
		//native-pathinfo:/index.php/blog/read/id/100
		//diy:/blog-100.html
		$obj = IServerVars::factory($_SERVER['SERVER_SOFTWARE']);
		$url  = $url ? $url : $obj->realUri();

		//某些服务器会返回带?query=value查询字符串参数的url,我们需要处理并且过滤掉
		$beforeUrl = strstr($url,"?",true);
		if($beforeUrl)
		{
			//把url中的query字符参数通过IReq接口设置到$_GET全局数组里面
			$urlArray = parse_url($url);
			if(isset($urlArray['query']) && $urlArray['query'])
			{
				parse_str($urlArray['query'],$getParam);
				if($getParam)
				{
					foreach($getParam as $key => $value)
					{
						IReq::set($key,$value);
					}
				}
			}
			//去掉?param=value的前置URL
			$url = $beforeUrl;
		}
		preg_match('/\.php(.*)/',$url,$phpurl);
		if(!isset($phpurl[1]) || !$phpurl[1])
		{
			return;
		}
		$url = $phpurl[1];

		//伪静态软路由
		$urlArray = array();
		$rewriteRule = isset(IWeb::$app->config['rewriteRule'])?IWeb::$app->config['rewriteRule']:'url';
		if($rewriteRule!='url')
		{
			$urlArray = self::decodeRouteUrl($url);
		}

		if($urlArray == array())
		{
			if( $url[0] == '?' )
			{
				$urlArray = $_GET;
			}
			else
			{
				$urlArray = self::pathinfoToArray($url);
			}
		}
		if( isset($urlArray[self::UrlCtrlName]) )
		{
			IReq::set(self::UrlCtrlName,$urlArray[self::UrlCtrlName]);
		}
		if( isset($urlArray[self::UrlActionName]) )
		{
			IReq::set(self::UrlActionName,$urlArray[self::UrlActionName]);
		}

		unset($urlArray[self::UrlActionName] , $urlArray[self::UrlCtrlName] , $urlArray[self::Anchor] );
		foreach($urlArray as $key => $value)
		{
			IReq::set($key,$value);
		}
	}
	/**
	 * @brief  获取拼接两个地址
	 * @param  String $path_a
	 * @param  String $path_b
	 * @return string 处理后的URL地址
	 */
	public static function getRelative($path_a,$path_b)
	{
		$path_a = strtolower(str_replace('\\','/',$path_a));
		$path_b = strtolower(str_replace('\\','/',$path_b));
		$arr_a = explode("/" , $path_a) ;
		$arr_b = explode("/" , $path_b) ;
		$i = 0 ;
		while (true)
		{
			if($arr_a[$i] == $arr_b[$i]) $i++ ;
			else break ;
		}
		$len_b = count($arr_b) ;
		$len_a = count($arr_a) ;
		if(!$arr_b[$len_b-1])$len_b = $len_b - 1;
		if(!$len_a[$len_a-1])$len_a = $len_a - 1;
		$len = ($len_b>$len_a)?$len_b:$len_a ;
		$str_a = '' ;
		$str_b = '' ;
		for ($j = $i ;$j<$len ;$j++)
		{
			if(isset($arr_a[$j]))
			{
				$str_a .= $arr_a[$j].'/' ;
			}
			if(isset($arr_b[$j])) $str_b .= "../" ;
		}
		return $str_b . $str_a ;
	}
}

//$_SERVER里与url有关的两个量的兼容处理方案
//这是个不成熟的解决方案，可能仅适用于本框架
interface IIServerVars
{
	/**
	 * @brief 获取当前浏览器地址栏中的相对URI地址
	 */
	public function requestUri();

	/**
	 * @brief 服务器执行的物理URI路径,包括入口index.php
	 */
	public function realUri();
}

/**
 * @brief URL处理工厂类
 */
class IServerVars implements IIServerVars
{
	public static function factory($server_type)
	{
		$obj = null;
		$type = array(
			'apache'=> 'IServerVars_Apache',
			'iis'	=> 'IServerVars_IIS' ,
			'nginx' => 'IServerVars_Nginx'
		);

		foreach($type as $key => $value)
		{
			if(stripos($server_type,$key) !== false )
			{
				$obj = new $value($server_type);
				break;
			}
		}

		if($obj === null)
		{
			return new $type['apache']($server_type);
		}
		return $obj;
	}

	public function requestUri()
	{}

	public function realUri()
	{}
}

/**
 * APACHE服务器
 */
class IServerVars_Apache implements IIServerVars
{
	public function __construct($server_type)
	{}

	public function requestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function realUri()
	{
		$result = $_SERVER['SCRIPT_NAME'];
		if(isset($_SERVER['REDIRECT_PATH_INFO']))
		{
			if(strpos($_SERVER['REDIRECT_PATH_INFO'],$result) === false)
			{
				$result .= $_SERVER['REDIRECT_PATH_INFO'];
			}
			else
			{
				$result = $_SERVER['REDIRECT_PATH_INFO'];
			}
		}
		else if(isset($_SERVER['REDIRECT_REDIRECT_PATH_INFO']))
		{
			if(strpos($_SERVER['REDIRECT_REDIRECT_PATH_INFO'],$result) === false)
			{
				$result .= $_SERVER['REDIRECT_REDIRECT_PATH_INFO'];
			}
			else
			{
				$result = $_SERVER['REDIRECT_REDIRECT_PATH_INFO'];
			}
		}
		else if(isset($_SERVER['ORIG_PATH_INFO']))
		{
			if(strpos($_SERVER['ORIG_PATH_INFO'],$result) === false)
			{
				$result .= $_SERVER['ORIG_PATH_INFO'];
			}
			else
			{
				$result = $_SERVER['ORIG_PATH_INFO'];
			}
		}
		else if(isset($_SERVER['PATH_INFO']))
		{
			if(strpos($_SERVER['PATH_INFO'],$result) === false)
			{
				$result .= $_SERVER['PATH_INFO'];
			}
			else
			{
				$result = $_SERVER['PATH_INFO'];
			}
		}
		else if(isset($_SERVER['REQUEST_URI']))
		{
			if(strpos($_SERVER['REQUEST_URI'],$result) === false)
			{
				//一级目录
				if(strlen(dirname($result)) == 1)
				{
					$result .= $_SERVER['REQUEST_URI'];
				}
				//多级目录
				else
				{
					$result .= trim(str_replace(dirname($result),"",$_SERVER['REQUEST_URI']),"/\\");
				}
			}
			else
			{
				$result = $_SERVER['REQUEST_URI'];
			}
		}
		return $result;
	}
}

/**
 * IIS服务器
 */
class IServerVars_IIS implements IIServerVars
{
	public function __construct($server_type){}

	public function requestUri()
	{
		$result = "";
		if(isset($_SERVER['HTTP_X_REWRITE_URL']))
		{
			$result = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		else if(isset($_SERVER['HTTP_X_ORIGINAL_URL']))
		{
			$result = $_SERVER['HTTP_X_ORIGINAL_URL'];
		}
		else if(isset($_SERVER['REQUEST_URI']))
		{
			$result = $_SERVER['REQUEST_URI'];
		}
		return $result;
	}

	public function realUri()
	{
		$result  = $_SERVER['SCRIPT_NAME'];
		$trimDir = dirname($_SERVER['SCRIPT_NAME']);
		$trimDir = $trimDir == "/" ? "" : $trimDir;

		if(isset($_SERVER['HTTP_X_REWRITE_URL']))
		{
			$result .= strtr($_SERVER['HTTP_X_REWRITE_URL'],array($trimDir => "",$result => ""));
		}
		else if(isset($_SERVER['HTTP_X_ORIGINAL_URL']))
		{
			$result .= strtr($_SERVER['HTTP_X_ORIGINAL_URL'],array($trimDir => "",$result => ""));
		}
		return $result;
	}
}

/**
 * NGINX服务器
 */
class IServerVars_Nginx implements IIServerVars
{
	public function __construct($server_type){}

	public function requestUri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function realUri()
	{
		$result = "";
		if(isset($_SERVER['DOCUMENT_URI']) )
		{
			$result = $_SERVER['DOCUMENT_URI'];
		}
		elseif( isset($_SERVER['REQUEST_URI']) )
		{
			$result = $_SERVER['SCRIPT_NAME'].$_SERVER['REQUEST_URI'];
		}
		return $result;
	}
}