<?php
/**
 * @brief 动态生成缩略图类
 */
class Thumb
{
	//缩略图路径
	public static $thumbDir = "runtime/_thumb/";

	/**
	 * @brief 获取缩略图物理路径
	 */
	public static function getThumbDir()
	{
		return IWeb::$app->getBasePath().self::$thumbDir;
	}

	/**
	 * @brief 生成缩略图
	 * @param string $imgSrc 图片路径
	 * @param int $width 图片宽度
	 * @param int $height 图片高度
	 * @param string $type 生成模式：pad补白;adapt适应裁剪;
	 * @return string WEB图片路径名称
	 */
    public static function get($imgSrc,$width=100,$height=100,$type='adapt')
    {
    	//远程图片
		if(strpos($imgSrc,"http") === 0)
		{
			// 第三方缩略图地址
			$thumb_url = plugin::trigger("get_thumb", $imgSrc, $width, $height);
			if (false !== $thumb_url)
			{
				return $thumb_url;
			}

			$urlArray = parse_url($imgSrc);
			if(!isset($urlArray['path']))
			{
				return;
			}
			//根据URL生成要保存的唯一路径
			$extPad  = "";
			$fileExt = pathinfo($imgSrc,PATHINFO_EXTENSION);
			if($fileExt == "")
			{
				$extPad = ".jpg";
			}
			else if(!in_array(strtolower($fileExt),array("jpg","png","gif","tbi")))
			{
				return;
			}
			$dirname  = dirname($urlArray['path']);
			$downFile = self::getThumbDir().trim($dirname,"/")."/".basename($imgSrc).$extPad;

			//如果系统不存在此路径则直接下载
			if(!is_file($downFile))
			{
				$ch = curl_init($imgSrc);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				$fileRes = new IFile($downFile,"w+");
				$result  = $fileRes->write(curl_exec($ch));
				if(!$result)
				{
					throw new IException($downFile." download fail");
				}
			}
			$sourcePath = $downFile;
		}
		//本地图片
		else
		{
			$sourcePath = IWeb::$app->getBasePath().$imgSrc;
			if(is_file($sourcePath) == false)
			{
				return;
			}
			$dirname = dirname($imgSrc);
		}

		//缩略图文件名
		$preThumb      = "{$width}_{$height}_";
		$thumbFileName = $preThumb.basename($sourcePath);

		//缩略图目录
		$thumbDir    = self::getThumbDir().trim($dirname,"/")."/";
		$webThumbDir = self::$thumbDir.trim($dirname,"/")."/";
		if(is_file($thumbDir.$thumbFileName) == false)
		{
			IImage::thumb($sourcePath,$width,$height,$preThumb,$thumbDir,$type);
		}
		return $webThumbDir.$thumbFileName;
    }
}