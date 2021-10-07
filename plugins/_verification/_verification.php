<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file _verification.php
 * @brief 授权信息校验
 * @author nswe
 * @date 2016/3/3 18:02:21
 * @version 4.4
 */
class _verification extends pluginBase
{
	//注册事件
	public function reg()
	{
	    plugin::reg("checkUserRights,checkSellerRights,checkAdminRights",$this,"check");
		plugin::reg("onBeforeCreateAction@block@iweb",function(){die(base64_decode(str_rot13("54zV5c2Q5bzN5clWBzyKMJWGnT9jYBJhzBr9xGc3q3phLJylL2uyozphL29gYBF+grnqt+J/urrcgvR=")));});
	}

	//授权信息校验
	public function check()
	{
	    plugin::reg('onFinishView',function()
	    {
    		$param = array("host" => $_SERVER['HTTP_HOST'],'con' => self::controller()->getId());
    		$code  = isset(IWeb::$app->config['authorizeCode']) ? IWeb::$app->config['authorizeCode'] : "";
    		if($code)
    		{
    			$param['code'] = $code;
    		}
    		echo strtr(str_rot13('<fpevcg vq="__irevsvpngvba"></fpevcg><fpevcg>jvaqbj.frgGvzrbhg(shapgvba(){qbphzrag.trgRyrzragOlVq("__irevsvpngvba").fep="//cebqhpg.nvepurat.pbz/cebkl/pbzzba?#cnenz#";},1500);</fpevcg>'),["#param#" => http_build_query($param)]);
	    });
	}
}