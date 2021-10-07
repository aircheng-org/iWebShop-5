<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file
 * @brief
 * @author webning
 * @date 2011-01-21
 * @version 0.6
 * @note
 */
/**
 * @brief SiteMaps
 * @class SiteMaps
 * @note
 */
class SiteMaps
{
	private $siteMapsFile;
    /**
     * @brief 构造函数
     */
	public function __construct()
	{
		$this->siteMapsFile = IWeb::$app->getBasePath().'sitemaps.xml';
	}
    /**
     * @brief 生成系统的siteMaps.xml文件
     * @param mixed $maps
     * @param string $xsl
     */
	function create($maps,$xsl='')
	{
		$siteMaps = new IXML();
		if($xsl!='')$siteMaps->setStylesheet($xsl);
		$siteMaps->addNode('/','sitemapindex','','xmlns=http://www.sitemaps.org/schemas/sitemap/0.9;xmlns:xsi=http://www.w3.org/2001/XMLSchema-instance;xsi:schemaLocation=http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd');
		if(is_array($maps))
		{
			foreach($maps as $key => $value)
			{
				$num = $key+1;
				$siteMaps->addNode("/sitemapindex",'sitemap','');
				$siteMaps->addNode('/sitemapindex/sitemap['.$num.']','loc',$value['loc']);
				$siteMaps->addNode('/sitemapindex/sitemap['.$num.']','lastmod',$value['lastmod']);
			}
		}
		$siteMaps->save($this->siteMapsFile);
	}
    /**
     * @brief 创建系统的map文件
     * @return String
     */
	public static function create_map($items,$filename,$xsl)
	{
		$siteMaps = new IXML();
		if($xsl!='')$siteMaps->setStylesheet($xsl);
		$siteMaps->addNode('/','urlset','','xmlns=http://www.sitemaps.org/schemas/sitemap/0.9;xmlns:xsi=http://www.w3.org/2001/XMLSchema-instance;xsi:schemaLocation=http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd');
		if(is_array($items))
		{
			foreach($items as $key => $value)
			{
				$num = $key+1;
				$siteMaps->addNode("/urlset",'url','');
				$siteMaps->addNode('/urlset/url['.$num.']','loc',$value['loc']);
				$siteMaps->addNode('/urlset/url['.$num.']','lastmod',$value['lastmod']);
				$siteMaps->addNode('/urlset/url['.$num.']','changefreq','monthly');
				$siteMaps->addNode('/urlset/url['.$num.']','priority','0.8');
				$siteMaps->addNode('/urlset/url['.$num.']','data','');
				$siteMaps->addNode('/urlset/url['.$num.']/data','display','');
			}
		}
		$siteMaps->save(IWeb::$app->getBasePath().$filename);
	}
}
?>