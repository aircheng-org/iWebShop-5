<?php
/**
 * @copyright Copyright(c) 2010 aircheng.com
 * @file tag_class.php
 * @brief 标签解析类文件
 * @author webning
 * @date 2010-12-17
 * @version 0.6
 */
 /**
  * @brief ITag 系统标签处理文件
  * @class ITag
  */
class ITag
{
	private static $queryExcept = array('name','id','items','item','key');

    /**
     * @brief  解析给定的字符串
     * @param string $str 要解析的字符串
     * @return String 解析处理的字符串
     */
	public function resolve($str)
	{
		return preg_replace_callback('/{(\/?)(\$|url|webroot|theme|skin|echo|query|widget|foreach|set|include|require|if|elseif|else|while|for|js)\s*(:?)([^}]*)}/i', array($this,'translate'), $str);
	}
    /**
     * @brief 处理设定的每一个标签
     * @param array $matches
     * @return String php代码
     */
	public function translate($matches)
	{
		if($matches[1]!=='/')
		{
			switch($matches[2].$matches[3])
			{
				case '$':
                {
                    $str = trim($matches[4]);
                    $first = $str[0];
					if($first != '.' && $first != '(')
					{
						if( strpos($str,'(') !== false || (strpos($str,'[') === false && strpos($str,'->') !== false) )
						{
							return '<?php echo $'.$str.';?>';
						}
						else
						{
							return '<?php echo isset($'.$str.')?$'.$str.':"";?>';
						}
					}
                    else return $matches[0];
                }
				case 'echo:': return '<?php echo '.rtrim($matches[4],';/').';?>';
                case 'js:': return IJSPackage::load($matches[4]);
				case 'url:':
				{
					$matches[4] = $this->varReplace($matches[4]);
					return '<?php echo IUrl::creatUrl("'.$matches[4].'");?>';
				}
                case 'webroot:':
                {
                	$matches[4] = $this->varReplace($matches[4]);
                	return '<?php echo IUrl::creatUrl("")."'.$matches[4].'";?>';
                }
                case 'theme:': return '<?php echo $this->getWebViewPath()."'.$matches[4].'";?>';
                case 'skin:': return '<?php echo $this->getWebSkinPath()."'.$matches[4].'";?>';
				case 'if:': return '<?php if('.$matches[4].'){?>';
				case 'elseif:': return '<?php }elseif('.$matches[4].'){?>';
				case 'else:': return '<?php }else{'.$matches[4].'?>';
				case 'set:':
                {
                    return '<?php '.$matches[4].'?>';
                }
				case 'while:': return '<?php while('.$matches[4].'){?>';
				case 'foreach:':
				{
					$attr = $this->getAttrs($matches[4]);
					if(!isset($attr['items'])) $attr['items'] = '$items';
					else $attr['items'] = '$items='.$attr['items'];
					if(!isset($attr['key'])) $attr['key'] = '$key';
					else $attr['key'] = $attr['key'];
					if(!isset($attr['item'])) $attr['item'] = '$item';
					else $attr['item'] = $attr['item'];

					return '<?php foreach('.$attr['items'].' as '.$attr['key'].' => '.$attr['item'].'){?>';
				}
				case 'for:':
				{
					$attr = $this->getAttrs($matches[4]);
					if(!isset($attr['item'])) $attr['item'] = '$i';
					else $attr['item'] = $attr['item'];
					if(!isset($attr['from'])) $attr['from'] = 0;

                    if(!isset($attr['upto']) && !isset($attr['downto'])) $attr['upto'] = 10;
                    if(isset($attr['upto']))
                    {
                        $op = '<=';
                        $end = $attr['upto'];
                        if($attr['upto']<$attr['from']) $attr['upto'] = $attr['from'];
                        if(!isset($attr['step'])) $attr['step'] = 1;
                    }
                    else
                    {
                        $op = '>=';
                        $end = $attr['downto'];
                        if($attr['downto']>$attr['from'])$attr['downto'] = $attr['from'];
                        if(!isset($attr['step'])) $attr['step'] = -1;
                    }
					return '<?php for('.$attr['item'].' = '.$attr['from'].' ; '.$attr['item'].$op.$end.' ; '.$attr['item'].' = '.$attr['item'].'+'.$attr['step'].'){?>';
				}
				case 'query:':
				{
					$endchart=substr(trim($matches[4]),-1);
					$attrs = $this->getAttrs(rtrim($matches[4],'/'));
                    if(!isset($attrs['id']))
                    {
                    	$id = '$query';
                    }
                    else
                    {
                    	$id = $attrs['id'];
                    }

                    if(!isset($attrs['items']))
                    {
                    	$items = '$items';
                    }
                    else
                    {
                    	$items = $attrs['items'];
                    }
					$tem = "$id".' = new IQuery("'.$attrs['name'].'");';
					//实现属性中符号表达式的问题
					$old_char=array(' eq ',' l ',' g ',' le ',' ge ', ' neq ');
					$new_char=array(' = ' ,' < ',' > ',' <= ',' >= ', ' !=  ');
					foreach($attrs as $k => $v)
					{
						if(!in_array($k,self::$queryExcept))
						{
							$v    = preg_replace('%(\$\w+\->\w+\[[\'|\"]\w+[\'|\"]\])%','{$1}',$v);//对变量处理增加花括号
							$tem .= "{$id}->".$k.' = "'.str_replace($old_char,$new_char,$v).'";';
						}
					}
					$tem .= $items.' = '.$id.'->find();';
					if(!isset($attrs['key']))
					{
						$attrs['key'] = '$key';
					}
					else
					{
						$attrs['key'] = $attrs['key'];
					}
					if(!isset($attrs['item']))
					{
						$attrs['item'] = '$item';
					}
					else
					{
						$attrs['item'] = $attrs['item'];
					}
					if($endchart=='/')
					{
						return '<?php '.$tem.'?>';
					}
					else
					{
						return '<?php '.$tem.' foreach('.$items.' as '.$attrs['key'].' => '.$attrs['item'].'){?>';
					}
				}
				case 'require:':
				case 'include:':
				{
					$fileName = trim($matches[4],"/ ");
					return '<?php require(ITag::createRuntime("'.$fileName.'"));?>';
				}
				default:
				{
					 return $matches[0];
				}
			}
		}
		else
		{
			return '<?php }?>';
		}
	}
    /**
     * @brief 分析标签属性
     * @param string $str
     * @return array以数组的形式返回属性值
     */
	public function getAttrs($str)
	{
		$str = strtr($str,array("=>" => "^^"));
		preg_match_all('/\w+\s*=(?:[^=]+?)(?=(\S+\s*=)|$)/i', trim($str), $attrs);
		$attr = array();
		foreach($attrs[0] as $value)
		{
			$tem = explode('=',$value);
			$attr[trim($tem[0])] = trim(strtr($tem[1],array("^^" => "=>")));
		}
		return $attr;
	}

    /**
     * @brief 变量替换操作
     * @param string $str
     * @return string
     */
	public function varReplace($str)
	{
		return preg_replace(array("#(\\$.*?(?=$|\/))#","#(\\$\w+)\[(\w+)\]#"),array("\".$1.\"","$1['$2']"),$str);
	}

	/**
	 * @brief 根据模板文件生成可以执行的PHP脚本
	 * @param string $fileParam 文件参数
	 * @param boolean $isLayout 模板是否带有布局
	 */
	public static function createRuntime($fileParam,$isLayout = false)
	{
		$pathInfo = explode("/",$fileParam);
		switch(count($pathInfo))
		{
			case 1:
			{
				$ctrlId   = IWeb::$app->getController()->getId();
				$ctrlObj  = IWeb::$app->getController();
				$actionId = $pathInfo[0];
			}
			break;

			case 2:
			{
				$ctrlId   = $pathInfo[0];
				$ctrlObj  = IWeb::$app->createController($ctrlId);
				$actionId = $pathInfo[1];
			}
			break;

			default:
			{
				throw new IException("模板标签 【include】或【require】 参数路径不规范");
			}
		}

		themeroute::onCreateController($ctrlObj);

		if($isLayout == false)
		{
			$ctrlObj->layout = "";
		}

		$viewFile  = $ctrlObj->getViewFile($actionId);
		if(is_file($viewFile.$ctrlObj->extend))
		{
			return $ctrlObj->render($viewFile,null,true);
		}
		else
		{
			throw new IException("模板标签 【include】或【require】 路径 {$fileParam} 不存在");
		}
	}
}