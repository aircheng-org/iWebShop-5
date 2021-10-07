<?php
/**
 * @copyright (c) 2016 aircheng.com
 * @file comment.php
 * @brief 商品评论API
 * @author nswe
 * @date 2016/6/13 23:53:59
 * @version 4.5
 */
class APIComment
{
	/**
	 * @brief 获取商品评论列表
	 * @param $goods_id int 商品id
	 * @param $point string 评分数支持多个分数逗号间隔，比如：1,2,3
	 * @return IQuery
	 */
	public function getListByGoods($goods_id,$point = "")
	{
		$goods_id = IFilter::act($goods_id,'int');
		$point    = IFilter::act($point,'int');
		$page  = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery("comment AS c");
		$query->fields = "c.*,u.username,u.head_ico";
		$query->join   = "left join user AS u ON c.user_id = u.id";
		$where         = "c.goods_id = {$goods_id} and c.status = 1";
		$where        .= $point ? " and c.point in (".$point.") " : "";
		$query->where  = $where;
		$query->order  = "c.id DESC";
		$query->page   = $page;
		return $query;
	}
}