<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file keywords.php
 * @brief 词库管理类
 * @author chendeshan
 * @date 2013/5/9 13:17:33
 * @version 0.6
 */
class keywords
{
	/**
	 * 向词库中添加词
	 * @param $word  string 多个词以','分隔
	 * @param $hot   int    0:否;1:是
	 * @param $order int    排序
	 */
	public static function add($word, $hot = 0, $order = 99)
	{
		$hot   = IFilter::act($hot,'int');
		$order = IFilter::act($order,'int');

		if($word)
		{
			$keywordObj  = new IModel('keyword');
			$wordArray   = explode(',',$word);
			$wordArray   = IFilter::act($wordArray);
			$wordArray   = array_unique($wordArray);

			foreach($wordArray as $word)
			{
				if(IString::getStrLen($word) >= 15)
				{
					continue;
				}
				$is_exists = $keywordObj->getObj('word = "'.$word.'"');
				if(empty($is_exists))
				{
					$dataArray = array(
						'hot'        => $hot,
						'word'       => $word,
						'order'      => $order,
					);
					$keywordObj->setData($dataArray);
					$keywordObj->add();
				}
				else
				{
					$dataArray = array(
						'hot'        => $hot,
						'order'      => $order,
					);
					$keywordObj->setData($dataArray);
					$keywordObj->update("word = '".$word."'");
				}
			}
			return array('flag'=>true);
		}
		return array('flag'=>false,'data'=>'请填写关键词');
	}

	/**
	 * @brief 计算关键词所关联的商品数量
	 * @param string $word   词语
	 * return int    $result 商品数量
	*/
	public static function count($word)
	{
		$word     = IFilter::act($word);
		$goodsObj = new IModel('goods');
		$countNum = $goodsObj->getObj('name like "%'.$word.'%" or FIND_IN_SET("'.$word.'", search_words) AND is_del=0 ','count(*) as num');
		return isset($countNum['num']) ? $countNum['num'] : 0;
	}
}