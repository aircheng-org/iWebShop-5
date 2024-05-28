<?php
/**
 * @copyright (c) 2017 aircheng.com
 * @file kuaidi100.php
 * @brief 快递100查询接口
 * @date 2023/10/23 23:02:08
 * @version 5.14
 */
class kuaidi100 implements freight_inter
{
	private $key      = '';//*ujjaTbtn9533**
	private $customer = '';//*2C378A4F0CEEE507856770BB872A939D**

	/**
	 * @brief 显示快递跟踪
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function line($ShipperCode,$LogisticCode)
	{
		if(!$this->key || !$this->customer)
		{
			return ['result' => 'fail','reason' => '没有申请快递100物流查询接口'];
		}

		$cacheObj = new ICache();
		$data = $cacheObj->get($LogisticCode);

		if(!$data)
		{
			$deliveryDB = new IModel('delivery_doc');
			$deliveryRow = $deliveryDB->getObj('delivery_code = "'.$LogisticCode.'"','time');
			$topData = ['time' => $deliveryRow['time'],'context' => '包裹正在等待揽收'];

			//参数设置
			$key      = $this->key;
			$customer = $this->customer;

			$param = [
				'com' => $ShipperCode,// 快递公司编码
				'num' => $LogisticCode, // 快递单号
				'phone' => '',        // 手机号
				'from' => '',         // 出发地城市
				'to' => '',           // 目的地城市
				'resultv2' => '1',    // 开启行政区域解析
				'show' => '0',        // 返回格式：0：json格式（默认），1：xml，2：html，3：text
				'order' => 'desc'     // 返回结果排序:desc降序（默认）,asc 升序
			];

			//请求参数
			$post_data = [];
			$post_data['customer'] = $customer;
			$post_data['param'] = json_encode($param, JSON_UNESCAPED_UNICODE);
			$sign = md5($post_data['param'].$key.$post_data['customer']);
			$post_data['sign'] = strtoupper($sign);

			$url = 'https://poll.kuaidi100.com/poll/query.do'; // 实时查询请求地址

			// 发送post请求
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$result = curl_exec($ch);
			// 第二个参数为true，表示格式化输出json
			$data = json_decode($result, true);

			if(isset($data['result']) && $data['result'] == false)
			{
				$data['data'] = [];
			}

			//记录缓存
			array_push($data['data'],$topData);
			if($data['state'] == 3)
			{
				$cacheObj->set($LogisticCode,$data);
			}
			else
			{
				$cacheObj->set($LogisticCode,$data,86400);
			}
		}

		if(isset($data['data']) && $data['data'])
		{
			$sendData = [];
			foreach($data['data'] as $item)
			{
				$sendData[] = ["time" => $item['time'],"station" => $item['context']];
			}
			return ['result' => 'success','data' => $sendData];
		}
	}

	/**
	 * @brief 订阅物流快递轨迹
	 * @param $ShipperCode  string 物流公司快递号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function subscribe($ShipperCode,$LogisticCode)
	{

	}

	/**
	 * @brief 订阅物流快递回调接口
	 * @param $callbackData mixed 物流回传信息
	 * @return mixed
	 */
	public function subCallback($callbackData)
	{

	}
}