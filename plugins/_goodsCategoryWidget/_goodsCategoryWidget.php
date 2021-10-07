<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file _goodsCategoryWidget.php
 * @brief 商品分类视图插件
 * @author nswe
 * @version 4.4
 * @date 2016/3/26 9:47:48
 */
class _goodsCategoryWidget extends pluginBase
{
	//是否加载过了JS
	private static $isJsLoad = false;

	public function reg()
	{
		plugin::reg("goodsCategoryWidget",$this,"showWidget");
		plugin::reg("onBeforeCreateAction@block@goods_category",function(){
			self::controller()->goods_category = function(){$this->view("goodsCategory");};
		});
	}

	/**
	 * @brief 显示插件内容
	 * @param array $param 配置array('seller_id' => '商家ID,如果0表示自营','name' => 控件name值,'value' => 分类ID字符串,'table' => '表名字','id' => '按钮的name','callback' => '选择分类数据的回调函数','afterCallback':'在原有的处理函数后回调')
	 */
	public function showWidget($param)
	{
		$where          = false;
		$param          = is_array($param)           ? $param              : array();
		$param['table'] = isset($param['table'])     ? $param['table']     : "category";

		if(isset($param['seller_id']))
		{
			$where = 'seller_id = '.$param['seller_id'];
		}

		//获取分类数据
		$categoryDB                  = new IModel($param['table']);
		$param['categoryData']       = $categoryDB->query($where,"*","sort asc");
		$param['categoryParentData'] = goods_class::categoryParentStruct($param['categoryData']);

		//默认商品分类数据
		if(isset($param['value']) && $param['value'])
		{
			$param['value'] = IFilter::act($param['value'],'int');
			$idString       = is_array($param['value']) ? join(",",$param['value']) : $param['value'];
			$cateData       = $categoryDB->query("id in (".$idString.")");
			if($cateData)
			{
				$param['default'] = $cateData;
			}
		}
		$param = JSON::encode($param);

		if(self::$isJsLoad == false)
		{
			self::$isJsLoad = true;
			$publicRoot   = IUrl::creatUrl("")."public/javascript/public.js";
			$widgetRoot   = IUrl::creatUrl("")."plugins/_goodsCategoryWidget/goodsCategoryWidget.js";
			$templateRoot = IJSPackage::load("artTemplate");
echo <<< OEF
<script src="{$publicRoot}"></script>
<script src="{$widgetRoot}"></script>
{$templateRoot}
OEF;
		}

echo <<< OEF
<script>
jQuery(function()
{
	_goodsCategoryWidget = new categoryWidget({$param});
});
</script>
OEF;
	}
}