<?php
/**
 * @copyright (c) 2016 aircheng.com
 * @file baiduShare.php
 * @brief 百度分享插件
 * @author nswe
 * @date 2016/6/26 18:08:50
 * @version 4.5
 */
class baiduShare extends pluginBase
{
	public static function name()
	{
		return "商品分享插件";
	}

	public static function description()
	{
		return "在商品详情页面中把商品信息分享到各大主流网站，分享功能必须要把网站部署到互联网上才可以正常使用";
	}

	public function reg()
	{
		plugin::reg("onFinishView@site@products",function(){$this->show();});
	}

	public function show()
	{
		$shareUrl = plugin::trigger('getCommissionUrl');
echo <<< OEF
<script>window._bd_share_config={"common":{"bdSnsKey":{},"bdText":"","bdUrl":"{$shareUrl}","bdMini":"2","bdMiniList":false,"bdPic":"","bdStyle":"0","bdSize":"16"},"slide":{"type":"slide","bdImg":"0","bdPos":"right","bdTop":"90"},"image":{"viewList":["qzone","tsina","tqq","renren","weixin"],"viewText":"分享到：","viewSize":"16"},"selectShare":{"bdContainerClass":null,"bdSelectMiniList":["qzone","tsina","tqq","renren","weixin"]}};with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src=_webRoot+'plugins/baiduShare/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];</script>
OEF;
	}
}