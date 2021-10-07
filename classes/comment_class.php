<?php
/**
 * 与评论相关的
 *
 * @author walu
 * @packge iwebshop
 */

class Comment_Class
{
	/**
	 * 检测用户是否能够评论
	 *
	 * @param int $comment_id 评论id
	 * @param int $user_id 用户id
	 * @return array() array(成功or失败,数据)
	 */
	public static function can_comment($comment_id,$user_id)
	{
		$comment_id = intval($comment_id);
		$user_id = intval($user_id);

		$tb_comment = new IModel("comment");
		$comment = $tb_comment->getObj("id={$comment_id} AND user_id={$user_id}");
		if(!$comment)
		{
			return "没有这条数据";
		}

		if($comment['status'] != 0)
		{
			return "不能重复评论";
		}
		return $comment;
	}

	/**
	 * 获取某个商品的有关分数的评论数据,根据comment表里面的评价分数做分析
	 *
	 * 获取好评、中评、差评数量及平均分
	 * 返回的值里包含以下几个计算出来的索引
	 *	<ul>
	 *		<li>point_total，总分</li>
	 *		<li>comment_total，评论总数</li>
	 *		<li>average_point，平均分</li>
	 *	</ul>
	 *
	 * @param int $id 商品ID
	 * @return array()
	 */
	public static function get_comment_info($id)
	{
		$data  = array();
		$query = new IQuery("comment");
		$query->fields = "COUNT(*) AS num,point";
		$query->where  = "goods_id = {$id} AND status=1 ";
		$query->group  = "point";

		$config = array(0=>'none',1=>'bad',2=>'middle',3=>'middle',4=>'middle',5=>'good');

		$data['point_grade'] = array('none'=>0,'good'=>0,'middle'=>0,'bad'=>0);
		$data['point_total'] = 0;

		foreach( $query->find() AS $value )
		{
			if($value['point']>=0 && $value['point']<=5)
			{
				$data['point_total']+=$value['point']*$value['num'];
				$data['point_grade'][$config[$value['point']]] += $value['num'];
			}
		}
		$data['comment_total']=array_sum($data['point_grade']);
		$data['average_point']=0;
		if($data['point_total']>0)
		{
			$data['average_point'] = round($data['point_total'] / $data['comment_total']);
		}
		return $data;
	}

	/**
	 * @brief 获取评论商品
	 * @param int $commentId 评论ID
	 */
	public static function goodsInfo($commentId)
	{
	    $result    = [];
		$commentDB = new IModel('comment');
		$commentRow= $commentDB->getObj($commentId);
		if($commentRow)
		{
			$goodsDB = new IModel('goods');
			$goodsRow= $goodsDB->getObj($commentRow['goods_id']);
			if($goodsRow)
			{
				$result = array(
					'goods_id'   => $commentRow['goods_id'],
					'name'       => $goodsRow['name'],
					'img'        => $goodsRow['img'],
					'sell_price' => $goodsRow['sell_price']
				);
			}
		}
		return $result;
	}
}