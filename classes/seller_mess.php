<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file seller_mess.php
 * @brief 商户消息的管理
 * @author qfsoft
 * @date 2016/03/06 20:30:10
 * @version 4.4
 */
class seller_mess
{
	//商户信息数据库实例
	private $sellerDB = null;

	//商户id
	private $seller_id = '';

	//商户消息ID
	private $messageIds = '';

	/**
	 * @brief 构造函数 商户id
	 * @param string $seller_id 商户id
	 */
	function __construct($seller_id)
	{
		$this->seller_id  = $seller_id;
		$this->sellerDB = new IModel('seller');
		$sellerRow      = $this->sellerDB->getObj($seller_id);

		//过滤消息内容，修正member表的【message_ids】字段
		if(isset($sellerRow['message_ids']) && $sellerRow['message_ids'])
		{
			$messObj   = new IModel('seller_message');
			$messArray = explode(',',$sellerRow['message_ids']);
			foreach($messArray as $key => $messId)
			{
				$mid = abs($messId);
				if(!$messObj->getObj($mid))
				{
					$sellerRow['message_ids'] = str_replace(",".$messId.",",",",",".trim($sellerRow['message_ids'],",").",");
				}
			}
			$this->sellerDB->setData(array('message_ids' => $sellerRow['message_ids']));
			$this->sellerDB->update($seller_id);
		}

		$this->messageIds = $sellerRow['seller_message_ids'];
	}

	/**
	 * 直接发站内信到商户
	 * @param $info array 检索条件
	 * @param array $content 消息内容  array('title' => '标题','content' => '内容')
	 */
	public static function send($info, $content)
	{
		set_time_limit(0);
        $info = $info ? array_filter($info) : null;

		//有检索条件
		if($info)
		{
    		$searchRes = Util::searchSeller($info);
            if($searchRes)
            {
                $userIds = $searchRes['id'];
                $revInfo = $searchRes['note'];
            }
            else
            {
                return "商户不存在";
            }
		}
		//全部用户
		else
		{
		    $revInfo = "【全部用户】";
		}

		$data = array(
			'title'   => $content['title'],
			'content' => $content['content'],
			'time'    => ITime::getDateTime(),
			'rev_info'=> $revInfo,
		);

		$msgDB = new IModel("seller_message");
		$msgDB->setData($data);
		$id = $msgDB->add();

		$db = IDBFactory::getDB();
		$tableName = IWeb::$app->config['DB']['tablePre']."seller";
		if (isset($sellerIds) && $sellerIds)
		{
			$sql = "UPDATE `{$tableName}` SET seller_message_ids = CONCAT( IFNULL(seller_message_ids,'') ,'{$id},') WHERE id in ({$sellerIds})";
		}
		else
		{
			$sql = "UPDATE `{$tableName}` SET seller_message_ids = CONCAT( IFNULL(seller_message_ids,'') ,'{$id},')";
		}
		return $db->query($sql) ? true : "更新数据失败";
	}

	/**
	 * @brief 获得seller表中的seller_message_ids,去掉 '-' 和最后的 ','
	 * @return string 返回所有商户消息id的字符串
	 */
	public function getAllMsgIds()
	{
		return str_replace('-','',trim($this->messageIds,','));
	}

	/**
	 * @brief 判断seller_message_id是否已经读过
	 * @param int $messageId seller_message的id
	 * @return boolean 返回true为已读，false为未读
	 */
	public function is_read($messageId)
	{
		if(strpos(','.trim($this->messageIds,',').',',',-'.$messageId.',') === false)
		{
			return false;
		}
		return true;
	}

	/**
	 * @brief 将messageId写入seller表中
	 * @param int $messageId seller_message的id
	 * @param int $read 0:未读(追加到用户id串后面)，1:已读(把用户id串增加'-'负号)
	 * @return int or boolean
	 */
	public function writeMessage($messageId,$read = 0)
	{
		if($read == 1)
		{
			$tempIds = ','.trim($this->messageIds,',').',';
			if(strpos($tempIds,','.$messageId.',') === false)
			{
				return false;
			}
			$tempIds = str_replace(','.$messageId.',',',-'.$messageId.',',$tempIds);
			$this->messageIds = trim($tempIds,',').',';
		}
		else
		{
			$this->messageIds .= $messageId.',';
		}
		return $this->save();
	}

	/**
	 * @brief 存储消息串
	 * @return boolean
	 */
	private function save()
	{
		$this->sellerDB->setData(array('seller_message_ids' => $this->messageIds));
		return $this->sellerDB->update($this->seller_id);
	}

	/**
	 * @brief 删除seller表中的messageId数据
	 * @param $messageId string 要删除的消息ID值
	 * @return string message_ids结果字符串
	 */
	public function delMessage($messageId)
	{
		$tempIds = str_replace(','.$messageId.',','',','.trim($this->messageIds,',').',');
		$tempIds = str_replace(',-'.$messageId.',','',','.trim($tempIds,',').',');
		$tempIds = trim($tempIds,',').',';
		$this->messageIds = $tempIds;
		return $this->save();
	}

	/**
	 * @brief 获取未读的商户消息数
	 * @return int 消息数量
	 */
	public function needReadNum()
	{
		$tempIds = ','.trim($this->messageIds,',').',';
		preg_match_all('|,\d+|',$tempIds,$result);
		return count(current($result));
	}

	//读取消息
	public function read($id)
	{
		$wholeIds = $this->getAllMsgIds();
		if(strpos(",".$wholeIds.",",",".$id.",") === false)
		{
			return null;
		}
		$msgDB = new IModel("seller_message");
		return $msgDB->getObj($id);
	}
}