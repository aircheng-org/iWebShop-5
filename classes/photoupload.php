<?php
/**
 * @copyright (c) 2018 aircheng.com
 * @file photoupload.php
 * @brief 图片上传类
 * @author chendeshan
 * @date 2018-03-14
 * @version 5.1
 */

/**
 * @class PhotoUpload
 * @brief 图片上传类
 */
class PhotoUpload
{
	private $dir         = 'upload'; //图片存储的目录名称
	private $iterance    = true;     //防止图片重复提交开关
	private $thumbWidth  = array();  //缩略图宽度
	private $thumbHeight = array();  //缩略图高度
	private $thumbKey    = array();  //缩略图返回键名
	private $size        = 10000;    //允许最大上传KB数，兼容核心类IUpload的构造函数
	private $type        = array('jpg','gif','png','bmp');  //允许上传的类型，兼容核心类IUpload的构造函数

	/**
	 * @brief 构造函数
	 * @param string $dir 上传目录
	 */
	public function __construct($dir = '')
	{
		//设置默认路径地址
		if($dir == '')
		{
			$dir = $this->hashDir();
		}
		$this->setDir($dir);
	}

	/**
	 * @brief 获取图片散列目录
	 * @return string
	 */
	public function hashDir()
	{
		$dir  = isset(IWeb::$app->config['upload']) ? IWeb::$app->config['upload'] : $this->dir;
		$dir .= '/'.date('Ymd');
		return $dir;
	}

	/**
	 * @brief 设置上传的目录
	 * @param string $dir
	 */
	public function setDir($dir)
	{
		$this->dir = $dir;
	}

	/**
	 * @brief 获取上传的目录
	 * @return string
	 */
	public function getDir()
	{
		return $this->dir;
	}

	/**
	 * @brief 防止图片重复提交
	 * @param bool $bool true:开启;false:关闭
	 */
	public function setIterance($bool)
	{
		$this->iterance = $bool;
	}

	/**
	 * @brief 获取图片重复提交机制
	 * @return boolean
	 */
	public function getIterance()
	{
		return $this->iterance;
	}

	/**
	 * @brief 设置缩略图宽度
	 * @param integer $width 缩略图宽度
	 */
	public function setThumbWidth($width)
	{
		$this->thumbWidth[]  = intval($width);
	}

	/**
	 * @brief 获取缩略图宽度
	 * @return array
	 */
	public function getThumbWidth()
	{
		return $this->thumbWidth;
	}

	/**
	 * @brief 设置缩略图高度
	 * @param integer $height 缩略图高度
	 */
	public function setThumbHeight($height)
	{
		$this->thumbHeight[] = intval($height);
	}

	/**
	 * @brief 获取缩略图高度
	 * @return array
	 */
	public function getThumbHeight()
	{
		return $this->thumbHeight;
	}

	/**
	 * @brief 获取缩略图返回值键名
	 * @return array
	 */
	public function getThumbKey()
	{
		return $this->thumbKey;
	}

	/**
	 * @brief 设置缩略图返回值键名
	 * @param string $key 缩略图返回值键名
	 */
	public function setThumbKey($key)
	{
		$thumbKey = $this->getThumbKey();
		if (in_array($key, $thumbKey))
		{
			$thumbCount = count($thumbKey) + 1;
			$key = $key . $thumbCount;
		}
		$this->thumbKey[] = $key;
	}

	/**
	 * @brief 删除缩略图返回值键名
	 * @param string $key 缩略图返回值键名
	 */
	public function unsetThumbKey($key)
	{
		if (isset($this->thumbKey[$key]))
		{
			unset($this->thumbKey[$key]);
		}
	}

	/**
	 * @brief 设置缩略图宽度和高度，和返回值键名，匹配成对设置
	 * @param int    $width  生成缩略图宽度;
	 * @param int    $height 生成缩略图高度;
	 * @param string $key    返回缩略图键名;
	 */
	public function setThumb($width,$height,$key = 'thumb')
	{
		$this->setThumbWidth($width);
		$this->setThumbHeight($height);
		$this->setThumbKey($key);
	}

	/**
	 * @brief 防止图片重复提交，根据图库MD5对比，当图片存在时则直接返回图片和缩略图（如果设置缩略图）
	 * @param string $file 文件路径地址
	 * @param object $photoObj 图库对象
	 * @return null|array
	 */
	private function checkIterance($file, $photoObj)
	{
		$iterance = $this->getIterance();
		//如果关闭了图片重复提交机制
		if (false == $iterance)
		{
			return null;
		}

		$fileMD5  = null;    //上传图片的md5值(默认)
		$photoRow = array(); //图库里照片信息(默认)
		$result   = array(); //结果

		if(is_file($file))
		{
			//生成文件md5码
			$fileMD5 = md5_file($file);
		}

		if(!is_null($fileMD5))
		{
    		//根据md5值取得图像数据
    		$where = "id = '".$fileMD5."'";
    		$photoRow = $photoObj->getObj($where);
		}

		//设置了缩略图
		if(isset($photoRow['img']))
		{
			if(is_file($photoRow['img']))
			{
				$result['img'] = $photoRow['img'];
				$result['flag']= 1;

				//检查缩略图是否存在
				$thumb_width_array  = $this->getThumbWidth();
				$thumb_height_array = $this->getThumbHeight();
				$thumb_key_array    = $this->getThumbKey();

				if($thumb_width_array && $thumb_height_array && $thumb_key_array)
				{
					foreach($thumb_width_array as $thumbWidth_Key => $thumbWidth_Val)
					{
						//获取此宽度和高度应有的缩略图名
				        $fileExt       = IFile::getFileSuffix($photoRow['img']);
				        $thumbFileName = str_replace('.'.$fileExt,'_'.$thumb_width_array[$thumbWidth_Key].'_'.$thumb_height_array[$thumbWidth_Key].'.'.$fileExt,$photoRow['img']);

						if(is_file($thumbFileName))
						{
							$result['thumb'][$thumb_key_array[$thumbWidth_Key]] = $thumbFileName;
							$this->unsetThumbKey($thumbWidth_Key);
						}
					}

					//重新生成系统中不存在的此宽高的缩略图
					foreach($thumb_key_array as $tk_key => $tk_val)
					{
						$thumbExtName = '_'.$thumb_width_array[$tk_key].'_'.$thumb_height_array[$tk_key];
						$thumbName    = $this->thumb($photoRow['img'],$thumb_width_array[$tk_key],$thumb_height_array[$tk_key],$thumbExtName);
						$result['thumb'][$thumb_key_array[$tk_key]] = $thumbName;
					}
				}
				return $result;
			}
			else
			{
				$photoObj->del('id = "'.$photoRow['id'].'"');
				return null;
			}
		}
		else
		{
			return null;
		}
	}

	/**
	 * @brief 图片信息入库
	 * @param array $insertData 要插入数据
	 * @param object $photoObj 图库对象
	 */
	private function insert($insertData,$photoObj)
	{
		$iterance = $this->getIterance();
		if($iterance == true && !$photoObj->getObj('id = "'.$insertData['id'].'"'))
		{
			$photoObj->setData($insertData);
			$photoObj->replace();
		}
	}

	/**
	 * @brief 生成$fileName文件的缩略图,位置与$fileName相同
	 * @param string  $fileName 要生成缩略图的目标文件
	 * @param int     $width    缩略图宽度
	 * @param int     $height   缩略图高度
	 * @param string  $extName  缩略图文件名附加值
	 * @param string  $saveDir  缩略图存储目录
	 * @return string
	 */
	public static function thumb($fileName,$width,$height,$extName = '_thumb',$saveDir = '')
	{
		return Thumb::get($fileName,$width,$height);
	}

	/**
	 * @brief 执行图片上传
	 * @param boolean $isForge 是否伪造数据提交
	 * @return array key:控件名; val:图片路径名;
	 */
	public function run($isForge = false)
	{
		//创建图片模型对象
		$photoObj = new IModel('goods_photo');

		//已经存在的图片文件数据
		$photoArray = array();

		//过滤图库中已经存在的图片
		foreach($_FILES as $key => $val)
		{
			//上传的所有临时文件
			$tmpFile = isset($_FILES[$key]['tmp_name']) ? $_FILES[$key]['tmp_name'] : null;

			//没有找到匹配的控件
			if($tmpFile == null)
				continue;

			if(is_array($tmpFile))
			{
				foreach($tmpFile as $tmpKey => $tmpVal)
				{
					$result = $this->checkIterance($tmpVal,$photoObj);
					if($result)
					{
						$photoArray[$key][$tmpKey] = $result;
						unset($_FILES[$key]['name'][$tmpKey]);
						unset($_FILES[$key]['tmp_name'][$tmpKey]);
					}
				}
			}
			else
			{
				$result = $this->checkIterance($tmpFile,$photoObj);
				if($result)
				{
					$photoArray[$key] = $result;
					unset($_FILES[$key]);
				}
			}
		}

		//图片上传
		$size = $this->getSize();
		$type = $this->getType();
		$dir  = $this->getDir();
		$thumb_width  = $this->getThumbWidth();
		$thumb_height = $this->getThumbHeight();
		$thumb_key    = $this->getThumbKey();

		$upObj = new IUpload($size, $type);
		$upObj->setIsForge($isForge);
		$upObj->setDir($dir);
		$upState = $upObj->execute();

		//检查上传状态
		foreach($upState as $field => $rs)
		{
			$isArray = is_array($_FILES[$field]['name']) ? true : false;
			foreach($rs as $innerKey => $val)
			{
				if($val['flag']==1)
				{
					//上传成功后图片信息
					$fileName = $val['fileSrc'];
					$fileMD5  = md5_file($fileName);

					$rs[$innerKey]['img'] = $fileName;

					$insertData = array(
						'id'  => $fileMD5,
						'img' => $fileName
					);

					//将图片信息入库
					$this->insert($insertData,$photoObj);

					if($thumb_width && $thumb_height && $thumb_key)
					{
						//重新生成系统中不存在的此宽高的缩略图
						foreach($thumb_key as $tk_key => $tk_val)
						{
							$thumbExtName = '_'.$thumb_width[$tk_key].'_'.$thumb_height[$tk_key];
							$thumbName    = $this->thumb($fileName,$thumb_width[$tk_key],$thumb_height[$tk_key],$thumbExtName);
							$rs[$innerKey]['thumb'][$thumb_key[$tk_key]] = $thumbName;
						}
					}
				}
				$photoArray[$field] = $isArray == true ? $rs : $rs[$innerKey];
			}
		}
		return $photoArray;
	}

	/**
	 * @brief 设置允许最大上传KB数，兼容核心类IUpload的构造函数
	 * @param integer $size 最大上传KB数
	 */
	public function setSize($size)
	{
		$this->size = $size;
	}

	/**
	 * @brief 获取允许最大上传KB数
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @brief 设置允许上传的类型，兼容核心类IUpload的构造函数
	 * @param array $type 上传类型
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @brief 获取允许上传的类型
	 */
	public function getType()
	{
		return $this->type;
	}
}