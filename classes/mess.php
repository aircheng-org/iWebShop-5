<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file mess.php
 * @brief 会员消息的管理
 * @author chendeshan
 * @date 2013/11/13 11:44:08
 * @version 0.6
 *
 * @update 增加读取消息方法
 * @version 5.2
 * @date 2018/7/12 18:21:44
 */
 /**
  * example:
  * $message = new Mess($data['user_id']);
  * $message->writeMessage('0',1);
 */
class Mess
{
	//用户信息数据库实例
	private $memberDB = null;

	//用户id
	private $user_id = '';

	//用户消息ID
	private $messageIds = '';

	/**
	 * @brief 构造函数 用户id
	 * @param string $user_id 用户id
	 */
	function __construct($user_id)
	{
		$this->user_id  = $user_id;
		$this->memberDB = new IModel('member');
		$memberRow      = $this->memberDB->getObj('user_id = '.$user_id);

		//过滤消息内容，修正member表的【message_ids】字段
		if(isset($memberRow['message_ids']) && $memberRow['message_ids'])
		{
			$messObj   = new IModel('message');
			$messArray = explode(',',$memberRow['message_ids']);
			foreach($messArray as $key => $messId)
			{
				$mid = abs($messId);
				if(!$messObj->getObj($mid))
				{
					$memberRow['message_ids'] = str_replace(",".$messId.",",",",",".trim($memberRow['message_ids'],",").",");
				}
			}
			$this->memberDB->setData(array('message_ids' => $memberRow['message_ids']));
			$this->memberDB->update("user_id = ".$user_id);
		}
		$this->messageIds = $memberRow['message_ids'];
	}

	/**
	 * @brief 存储消息串
	 * @return boolean
	 */
	private function save()
	{
		$this->memberDB->setData(array('message_ids' => $this->messageIds));
		return $this->memberDB->update('user_id='.$this->user_id);
	}

	/**
	 * @brief 将messageid写入member表中
	 * @param $message_id int 消息的id
	 * @param $read int 0:未读(追加到用户id串后面)，1:已读(把用户id串增加‘-’负号)
	 * @return int or boolean
	 */
	public function writeMessage($message_id,$read = 0)
	{
		if($read == 1)
		{
			$tempIds = ','.trim($this->messageIds,',').',';
			if(strpos($tempIds,','.$message_id.',') === false)
			{
				return false;
			}
			$tempIds = str_replace(','.$message_id.',',',-'.$message_id.',',$tempIds);
			$this->messageIds = trim($tempIds,',').',';
		}
		else
		{
			$this->messageIds .= $message_id.',';
		}

		return $this->save();
	}
	/**
	 * @brief 获得member表中的messageid,去掉 '-' 且没有最后的 ',' 的message的id
	 * @return $message String 返回站内所有消息id的字符串
	 */
	public function getAllMsgIds()
	{
		return str_replace('-','',trim($this->messageIds,','));
	}
	/**
	 * @brief 判断messageid是否已经读过
	 * @param $mess_id int message的id
	 * @return $is_blog boolean 返回true为已读，false为未读
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
	 * @brief 删除member表中的message_ids的数据
	 * @param $message_id string 要删除的消息ID值
	 * @return string message_ids结果字符串
	 */
	public function delMessage($message_id)
	{
		$tempIds = str_replace(','.$message_id.',','',','.trim($this->messageIds,',').',');
		$tempIds = str_replace(',-'.$message_id.',','',','.trim($tempIds,',').',');
		$tempIds = trim($tempIds,',').',';
		$this->messageIds = $tempIds;
		$this->save();
	}

	/**
	 * 直接发站内信到用户
	 * 这个地方直接调用了Mysql的操作类
	 * @param $info array 查询user条件
	 * @param $content 信件内容 array('title' => '标题','content' => '内容')
	 */
	public static function sendToUser($info,$content)
	{
		set_time_limit(0);
        $info = $info ? array_filter($info) : null;
		//有检索条件
		if($info)
		{
    		$searchRes = Util::searchUser($info);
            if($searchRes)
            {
                $userIds = $searchRes['id'];
                $revInfo = $searchRes['note'];
            }
            else
            {
                return "用户不存在";
            }
		}
		//全部用户
		else
		{
		    $revInfo = "【全部用户】";
		}

		//插入$content
		$data = array(
			'title'   => $content['title'],
			'content' => $content['content'],
			'time'    => ITime::getDateTime(),
			'rev_info'=> $revInfo,
		);

		$msgDB = new IModel("message");
		$msgDB->setData($data);
		$id = $msgDB->add();

		$db = IDBFactory::getDB();
		$tableName = IWeb::$app->config['DB']['tablePre']."member";
		if(isset($userIds) && $userIds)
		{
			$sql = "UPDATE `{$tableName}` SET message_ids = CONCAT( IFNULL(message_ids,'') ,'{$id},') WHERE user_id in ({$userIds})";
		}
		else
		{
			$sql = "UPDATE `{$tableName}` SET message_ids = CONCAT( IFNULL(message_ids,'') ,'{$id},')";
		}
		return $db->query($sql) ? true : "更新数据失败";
	}

	/**
	 * @brief 获取未读的短消息
	 * @return int 消息数量
	 */
	public function needReadNum()
	{
		$tempIds = ','.trim($this->messageIds,',').',';
		preg_match_all('|,\d+|',$tempIds,$result);
		return count(current($result));
	}

    //读取消息内容
	public function readMessage($id)
	{
	    $allMessage = $this->getAllMsgIds();
	    if(stripos(",".$allMessage.",",",".$id.",") === false)
	    {
	        return null;
	    }

	    $messObj = new IModel('message');
	    return $messObj->getObj($id);
	}
}