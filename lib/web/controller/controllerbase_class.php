<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file controllerbase_class.php
 * @brief 控制器基础类
 * @author chendeshan
 * @date 2010-12-3
 * @version 0.6
 */

/**
 * @class IControllerBase
 * @brief 控制器基础类
 */
class IControllerBase extends IObject
{
	//是否开启runtime编译缓存
	public $isRanderCache = true;

	/**
	 * @brief 渲染layout
	 * @param string $layoutFile 布局视图文件名
	 * @param string $viewContent 视图代码块
	 * @return string 编译合成后的完整视图
	 */
	public function renderLayout($layoutFile,$viewContent)
	{
		$layoutContent = file_get_contents($layoutFile);
		return str_replace('{viewcontent}',$viewContent,$layoutContent);
	}

	/**
	 * @brief 渲染处理
	 * @param string          $viewFile 要渲染的页面
	 * @param string or array $rdata 要渲染的数据
	 * @param boolean         $return 是否返回路径名称
	 * @param string          $runtimePath 运行目录（runtime）
	 */
	public function renderView($viewFile,$rdata=null,$return=false,$runtimePath = "")
	{
		//要渲染的视图
		$renderFile = $viewFile.$this->extend;

		//检查视图文件是否存在
		if(is_file($renderFile))
		{
			//控制器的视图(需要进行编译并且生成可以执行的php文件)
			if(stripos($renderFile,IWEB_PATH.'web/view/')===false)
			{
				//生成文件路径
				$runtimeFile = $runtimePath ? $runtimePath.".php" : str_replace($this->app->getBasePath(),$this->app->getRuntimePath(),$viewFile.".php");

				//layout文件
				$layoutFile = $this->getLayoutFile().$this->extend;
				$issetLayout= is_file($layoutFile);

				if($this->isRanderCache == false || !is_file($runtimeFile) || (filemtime($renderFile) > filemtime($runtimeFile)) || ($issetLayout && (filemtime($layoutFile) > filemtime($runtimeFile))))
				{
					//获取view内容
					$viewContent = file_get_contents($renderFile);

					//处理layout
					if($issetLayout && stripos($viewContent,"<html") === false)
					{
						$viewContent = $this->renderLayout($layoutFile,$viewContent);
					}

					//标签编译
					$inputContent = $this->tagResolve($viewContent);

					//创建文件
					$fileObj  = new IFile($runtimeFile,'w+');
					$fileObj->write($inputContent);
					$fileObj->save();
					unset($fileObj);
				}
			}
			else
			{
				$runtimeFile = $renderFile;
			}
			return $return ? $runtimeFile : $this->requireFile($runtimeFile,$rdata);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 引入编译后的视图文件
	 * @param string $__runtimeFile 视图文件名
	 * @param mixed  $rdata         渲染的数据
	 * @return string 编译后的视图数据
	 */
	public function requireFile($__runtimeFile,$rdata)
	{
		//渲染的数据
		if(is_array($rdata))
			extract($rdata,EXTR_OVERWRITE);
		else
			$data=$rdata;

		unset($rdata);

		//渲染控制器数据
		$__controllerRenderData = $this->getRenderData();
		extract($__controllerRenderData,EXTR_OVERWRITE);
		unset($__controllerRenderData);

		//渲染app数据
		$__moduleRenderData = $this->app->getRenderData();
		extract($__moduleRenderData,EXTR_OVERWRITE);
		unset($__moduleRenderData);

		require($__runtimeFile);
	}

	/**
	 * @brief 编译标签
	 * @param string $content 要编译的标签
	 * @return string 编译后的标签
	 */
	public function tagResolve($content)
	{
		$tagObj = new ITag();
		return $tagObj->resolve($content);
	}
}