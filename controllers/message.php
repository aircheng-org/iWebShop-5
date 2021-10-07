<?php
/**
 * @brief 消息模块
 * @class Message
 * @note  后台
 */
class Message extends IController implements adminAuthorization
{
	public $checkRight = 'all';
	public $layout     = 'admin';
	private $data      = array();

	function init()
	{

	}

	//删除电子邮箱订阅
	function registry_del()
	{
		$ids = IFilter::act(IReq::get('id'),'int');
		if(!$ids)
		{
			$this->redirect('registry_list',false);
			Util::showMessage('请选择要删除的邮箱');
			exit;
		}

		if(is_array($ids))
		{
			$ids = join(',',$ids);
		}

		$registryObj = new IModel('email_registry');
		$registryObj->del('id in ('.$ids.')');
		$this->redirect('registry_list');
	}

	/**
	 * @brief 删除登记的到货通知邮件
	 */
	function notify_del()
	{
		$notify_ids = IFilter::act(IReq::get('check'),'int');
		if($notify_ids)
		{
			$ids = join(',',$notify_ids);
			$tb_notify = new IModel('notify_registry');
			$where = "id in (".$ids.")";
			$tb_notify->del($where);
		}
		$this->redirect('notify_list');
	}

	/**
	 * @brief 发送到货通知邮件
	 */
	function notify_email_send()
	{
		$smtp  = new SendMail();
		$error = $smtp->getError();

		if($error)
		{
			$return = array(
				'isError' => true,
				'message' => $error,
			);
			echo JSON::encode($return);
			exit;
		}

		$notify_ids = IFilter::act(IReq::get('notifyid'));
		if($notify_ids && is_array($notify_ids))
		{
			$ids = join(',',$notify_ids);
			$query = new IQuery("notify_registry as notify");
			$query->join   = "right join goods as goods on notify.goods_id=goods.id ";
			$query->fields = "notify.*,goods.name as goods_name,goods.store_nums";
			$query->where  = "notify.id in(".$ids.")";
			$items = $query->find();

			//库存大于0，且处于未发送邮件状态的 发送通知
			$succeed = 0;
			$failed  = 0;
			$tb_notify_registry = new IModel('notify_registry');

			foreach($items as $value)
			{
				// 十进制转换为二进制
				$notify_status = decbin($value['notify_status']);
				// 是否已发送邮件
				if (1 == ($notify_status & 1)) {
					$failed++;
					continue;
				}

				$body   = mailTemplate::notify(array('{goodsName}' => $value['goods_name'],'{url}' => IUrl::getHost().IUrl::creatUrl('/site/products/id/'.$value['goods_id'])));
				$status = $smtp->send($value['email'],"到货通知",$body);

				if($status)
				{
					//发送成功
					$succeed++;
					$notify_status = $notify_status | 1;
					// 二进制转换为十进制
					$notify_status = bindec($notify_status);
					$data = array('notify_time' => ITime::getDateTime(),'notify_status' => $notify_status);
					$tb_notify_registry->setData($data);
					$tb_notify_registry->update('id='.$value['id']);
				}
				else
				{
					//发送失败
					$failed++;
				}
			}
		}

		$return = array(
			'isError' => false,
			'count'   => count($items),
			'succeed' => $succeed,
			'failed'  => $failed,
		);
		echo JSON::encode($return);
	}

	/**
	 * @brief 发送到货通知短信
	 */
	function notify_sms_send()
	{
		$notify_ids = IFilter::act(IReq::get('notifyid'));
		if($notify_ids && is_array($notify_ids))
		{
			$ids = join(',',$notify_ids);
			$query = new IQuery("notify_registry as notify");
			$query->join   = "right join goods as goods on notify.goods_id=goods.id ";
			$query->fields = "notify.*,goods.name as goods_name,goods.store_nums";
			$query->where  = "notify.id in(".$ids.")";
			$items = $query->find();

			//库存大于0，且处于未发送短信状态的 发送通知
			$succeed = 0;
			$failed  = 0;
			$tb_notify_registry = new IModel('notify_registry');

			foreach($items as $value)
			{
				// 十进制转换为二进制
				$notify_status = decbin($value['notify_status']);
				// 是否已发送短信
				if (10 == ($notify_status & 10)) {
					$failed++;
					continue;
				}

				$send_result = _hsms::notify($value['mobile'], array('{goodsName}' => $value['goods_name'],'{url}' => IUrl::getHost().IUrl::creatUrl('/site/products/id/'.$value['goods_id'])));
				if($send_result == 'success')
				{
					//发送成功
					$succeed++;
					$notify_status = $notify_status | 10;
					// 二进制转换为十进制
					$notify_status = bindec($notify_status);
					$data = array('notify_time' => ITime::getDateTime(),'notify_status' => $notify_status);
					$tb_notify_registry->setData($data);
					$tb_notify_registry->update('id='.$value['id']);
				}
				else
				{
					//发送失败
					$failed++;
				}
			}
		}

		$return = array(
			'isError' => false,
			'count'   => count($items),
			'succeed' => $succeed,
			'failed'  => $failed,
		);
		echo JSON::encode($return);
	}

	/**
	 * @brief 发送信件
	 */
	function registry_message_send()
	{
		set_time_limit(0);
		$ids     = IFilter::act(IReq::get('ids'),'int');
		$title   = IFilter::act(IReq::get('title'));
		$content = IReq::get("content");

		$smtp  = new SendMail();
		$error = $smtp->getError();

		$list = array();
		$tb   = new IModel("email_registry");

		$ids_sql = "1";
		if($ids)
		{
			$ids_sql = "id IN ({$ids})";
		}

		$start = 0;
		$query = new IQuery("email_registry");
		$query->fields = "email";
		$query->order  = "id DESC";
		$query->where  = $ids_sql;

		do
		{
			$query->limit = "{$start},50";
			$list = $query->find();
			if(!$list)
			{
				die('没有要发送的邮箱数据');
				break;
			}
			$start += 51;

			$to = array_pop($list);
			$to = $to['email'];
			$bcc = array();
			foreach($list as $value)
			{
				$bcc[] = $value['email'];
			}
			$bcc = join(";",$bcc);
			$result = $smtp->send($to,$title,$content,$bcc);
			if(!$result)
			{
				die('发送失败');
			}
		}
		while(count($list)>=50);
		echo "success";
	}

	/**
	 * @brief 营销短信列表
	 */
	function marketing_sms_list()
	{
		$tb_user_group = new IModel('user_group');
		$data_group = $tb_user_group->query();
		$data_group = is_array($data_group) ? $data_group : array();
		$group      = array();
		foreach($data_group as $value)
		{
			$group[$value['id']] = $value['group_name'];
		}
		$this->data['group'] = $group;

		$this->setRenderData($this->data);
		$this->redirect('marketing_sms_list');
	}

	/**
	 * @brief 发送营销短信
	 */
	function marketing_sms_send()
	{
		$this->layout = '';
		$this->redirect('marketing_sms_send');
	}

	/**
	 * @brief 发送短信
	 */
	function start_marketing_sms()
	{
		set_time_limit(0);
		$info    = IFilter::act(IReq::get('search'));
		$info    = $info ? array_filter($info) : null;
		$content = IFilter::act(IReq::get('content'));

		if(!$content)
		{
			die('<script type="text/javascript">parent.startMarketingSmsCallback("信息内容不存在");</script>');
		}

		$list = array();
		$offset = 0;
		// 单次发送数量
		$length = 50;
		$succeed = 0;

		$query = new IQuery("member");
		$query->fields = "mobile";
		$query->order  = "user_id DESC";

		//有检索条件
		if($info)
		{
    		$searchRes = Util::searchUser($info);
            if($searchRes)
            {
                $userIds = $searchRes['id'];
                $revInfo = $searchRes['note'];

    			$query->where = "user_id IN ({$userIds}) AND `mobile` IS NOT NULL AND `mobile`!='' ";
    			$list = $query->find();
            }
		}
		//全部用户
		else
		{
		    $revInfo = "【全部用户】";
			$query->where = " `mobile` IS NOT NULL AND `mobile`!='' ";
			$list = $query->find();
		}

		if($list)
		{
			$mobile_array = array();
			foreach ($list as $value)
			{
				if(false != IValidate::mobi($value['mobile']))
				{
					$mobile_array[] = $value['mobile'];
				}
			}
			unset($list);
			$mobile_count = count($mobile_array);
			if (0 < $mobile_count)
			{
				$send_num = ceil($mobile_count / $length);
				for ($i = 0; $i < $send_num; $i++)
				{
					$mobiles = array_slice($mobile_array, $offset, $length);
					$mobile_string = implode(",", $mobiles);
					$send_result = Hsms::send($mobile_string, $content, 0);
					if($send_result == 'success')
					{
						$succeed += count($mobiles);
					}
					$offset += $length;
				}
			}
		}
		else
		{
		    die('<script type="text/javascript">parent.startMarketingSmsCallback("用户信息不存在");</script>');
		}

		//获得marketing_sms的表对象
		$tb_marketing_sms = new IModel('marketing_sms');
		$tb_marketing_sms->setData(array(
			'content'=>$content,
			'send_nums' =>$succeed,
			'time'=> ITime::getDateTime(),
			'rev_info' => $revInfo,
		));
		$tb_marketing_sms->add();
		die('<script type="text/javascript">parent.startMarketingSmsCallback("success");</script>');
	}

	/**
	 * @brief 删除营销短信
	 */
	function marketing_sms_del()
	{
		$refer_ids = IFilter::act(IReq::get('check'),'int');
		$refer_ids = is_array($refer_ids) ? $refer_ids : array($refer_ids);
		if($refer_ids)
		{
			$ids = implode(',',$refer_ids);
			if($ids)
			{
				$tb_refer = new IModel('marketing_sms');
				$where = "id in (".$ids.")";
				$tb_refer->del($where);
			}
		}
		$this->marketing_sms_list();
	}

	//营销短信内容
	function marketing_sms_show()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$item = Api::run("getMarketingRowById",array('id'=>$id));
		if(!$item)
		{
			IError::show(403,'信息不存在');
		}
		$this->setRenderData(array('smsRow' => $item));
		$this->redirect('marketing_sms_show');
	}
}