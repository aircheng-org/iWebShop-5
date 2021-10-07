<?php
/**
 * @copyright Copyright(c) 2010 aircheng.com
 * @file xml_class.php
 * @brief 封装XML操作类
 * 主要实现XML快速操作
 * @author webning
 * @date 2010-12-03
 * @version 0.6
 */
/**
 * @brief IXML
 * @class IXML
 * @note
 */
class IXML
{
	private $dom;
    private $xmlfile;
	private $xpath;
    /**
     * @brief 构造函数
     * @param string $xmlfile xml文件名 如果文件名不填写[即为空]，则保存时必需填写文件名。
     * @param string $encoding 文件的编码格式，默认为UTF-8编码
     */
	public function __construct($xmlfile="", $encoding="UTF-8")
	{
		$this->dom=new DOMDocument('1.0');
		if(file_exists($xmlfile))$this->dom->load($xmlfile);
		$this->dom->formatOutput=true;
		$this->dom->encoding=$encoding;
        if($xmlfile!='' && strtolower(substr($xmlfile,-4))=='.xml') $this->xmlfile = $xmlfile;
		$this->xpath=new DOMXpath($this->dom);
	}
    /**
     * @brief 查询节点函数
     * @param string $query xpath查询字符串
     * @return mixed 查询成功返回节点,失败返回false;
     */
	public function query($query)
	{
		$element = $this->xpath->query($query);
		if($element->length>0)
		{
			return $element;
		}
		else
		{
			return false;
		}
	}
	public function setStylesheet($value)
	{
		$this->dom->appendChild($this->dom->createProcessingInstruction('xml-stylesheet', 'href="'.$value.'" type="text/xsl"'));
	}
	/**
     * @brief 添加节点
	 * @param string $query xpath查询字符串
	 * @param string $name 节点名称
	 * @param string $value 节点值
	 * @param string $attrs 属性值，可以设置多个属性，如:name=web;age=12;class=高二
	 * @return bool 成功返回true,失败返回false
	 */
	public function addNode($query, $name, $value="", $attrs="")
	{
		$target_node=$this->query($query);
		if(is_string($name))
		{
			if($target_node)
			{
				$value = str_replace(
					array('>','<','&'),
					array('&gt;','&lt;','&amp;'),
					$value
				);
				$node=$this->dom->createElement($name,$value);
				if($attrs!="")
				{
					$tems=explode(";",$attrs);
					if(count($tems)>0)
					{
						for($i=0;$i<count($tems);$i++)
						{
							$tem=explode("=",$tems[$i]);
							$node->setAttribute($tem[0],$tem[1]);
						}
					}
				}
				$target_node->item(0)->appendChild($node);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			$target_node->item(0)->appendChild($name);
		}
	}
	/**
     * @brief 给节点添加属性
	 * @param string $query xpath查询字符串
	 * @param string $name 属性名称
	 * @param string $value 属性值
	 * @return bool 成功返回true,失败返回false
	 */
	public function addAttr($query, $name, $value)
	{
		$node=$this->query($query);
		if($node)
		{
            for($i=0; $i<$node->length; $i++) $node->item($i)->setAttribute($name,$value);
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
     * @brief 删除节点
	 * @param string $query xpath查询字符串
	 * @return bool 成功返回true,失败返回false
	 */
	function delNode($query)
	{
		$node=$this->query($query);
		if($node)
		{
			$node->item(0)->parentNode->removeChild($node->item(0));
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
     * @brief 删除一个节点的属性
	 * @param string $query xpath查询字符串
	 * @param string $name 属性名称
	 * @return bool 成功返回true,失败返回false
	 */
	function delAttr($query, $name)
	{
		$node=$this->query($query);
		if($node)
		{
			$node->item(0)->removeAttribute($name);
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
     * @brief 修改节点属性
	 * @param string $query xpath查询字符串
	 * @param string $name 属性名称
	 * @param string $value 属性值
	 * @return bool 成功返回true,失败返回false
	 */
	public function updAttr($query, $name, $value)
	{
		$node=$this->query($query);
		if($node)
		{
			$node->item(0)->setAttribute($name,$value);
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
     * @brief 修改节点的值
	 * @param string $query xpath查询字符串
	 * @param string $value 属性值
	 * @return bool 成功返回true,失败返回false
	 */
	public function updNode($query, $value)
	{
		$node=$this->query($query);
		if($node)
		{
			$node->item(0)->nodeValue=$value;
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
     * @brief 保存XML文档
	 * @param string $file XML文件名，不填写则默认以创建此类文件时的文件名来存储。
	 * @return mixed 成功返回写入的字节， 失败返回false
	 */
	public function save($file='')
	{
        if($file=='' && $this->xmlfile!='')$file = $this->xmlfile;
		$this->dom->save($file);
	}
    /**
     * @brief 转换成XML字符串
     * @return String
     */
	public function  toXML()
	{
		return $this->dom->saveXML();
	}
}