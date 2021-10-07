<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file image_class.php
 * @brief 图片处理类库
 * @author chendeshan
 * @date 2011-03-18
 * @version 0.6
 */

/**
 * @class IImage
 * @brief IImage 图片处理类
 */
class IImage
{
	/**
	 * @brief 生成缩略图
	 * @param string  $fileName 原图路径
	 * @param int     $width    缩略图的宽度
	 * @param int     $height   缩略图的高度
	 * @param string  $extName  缩略图文件名附加值
	 * @param string  $saveDir  缩略图存储目录
	 * @param string $type 生成模式：pad补白;adapt适应裁剪;
	 * @return string 缩略图文件名
	 */
	public static function thumb($fileName, $width = 200, $height = 200 ,$extName = '_thumb' ,$saveDir = '',$type = 'adapt')
	{
		$GD = new GD($fileName);

		if($GD)
		{
			switch($type)
			{
				case "pad":
				{
					$GD->resize($width,$height);
					$GD->pad($width,$height);
				}
				break;

				default:
				{
					$GD->adaptiveResize($width,$height);
				}
			}

			//存储缩略图
			if($saveDir && IFile::mkdir($saveDir))
			{
		        //生成缩略图文件名
		        $thumbBaseName = $extName.basename($fileName);
		        $thumbFileName = $saveDir.basename($thumbBaseName);

				$GD->save($thumbFileName);
				return $thumbFileName;
			}
			//直接输出浏览器
			else
			{
				return $GD->show();
			}
		}
		return null;
	}
}