<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file kdniao.php
 * @brief 快递鸟的快递查询接口
 * @date 2017/10/25 20:34:45
 * @version 5.0
 */

/**
 * @class kdniao
 * @brief 物流查询类
 */
class kdniao implements freight_inter
{
	private $appid     = '';
	private $appkey    = '';

	//订阅物流公司
	private static $subCode = array('HTKY');

	/**
	 * @brief 查询物流快递轨迹(实时+订阅)
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function line($ShipperCode,$LogisticCode)
	{
		//1,先查询delivery_trace表数据
		$traceData = order_class::readTrace($LogisticCode);
		if($traceData && isset($traceData['content']) && $traceData['content'])
		{
			$data = array('Traces' => JSON::decode($traceData['content']));
			return $this->response($data);
		}

		//2,调用订阅查询接口
		if(in_array($ShipperCode,self::$subCode))
		{
			try
			{
				$result = $this->subscribe($ShipperCode,$LogisticCode);
				$data   = array('Reason' => '暂无物流信息，请稍后再试...');
				return $this->response($data);
			}
			catch(Exception $e)
			{
				$data = array('Reason' => $e->getMessage());
				return $this->response($data);
			}
		}
		//3,调用实时查询接口
		else
		{
			return $this->realTime($ShipperCode,$LogisticCode);
		}
	}

	/**
	 * @brief 订阅物流快递轨迹
	 * @param $ShipperCode  string 物流公司编号
	 * @param $LogisticCode string 快递单号
	 */
	public function subscribe($ShipperCode,$LogisticCode)
	{
		$params = array(
			'ShipperCode' => $ShipperCode,
			'LogisticCode'=> $LogisticCode,
		);

		$sendData = JSON::encode($params);
		$curlData = array(
			'RequestData' => $sendData,
			'EBusinessID' => $this->appid,
			'RequestType' => '1008',
			'DataType'    => 2,
			'DataSign'    => base64_encode(md5($sendData.$this->appkey)),
		);
		$result     = $this->curlSend("http://api.kdniao.cc/api/dist",$curlData);
		$resultJson = JSON::decode($result);

		if(!isset($resultJson['Success']) || $resultJson['Success'] == false)
		{
			return "订阅失败：".var_export($result,true);
		}
		return true;
	}

	/**
	 * @brief 物流订阅推送接口
	 * @param $callbackData mixed 物流回传信息
	 */
	public function subCallback($callbackData)
	{
		if(isset($callbackData['RequestData']) && $callbackData['RequestData'])
		{
			$RequestData = JSON::decode($callbackData['RequestData']);
			if(isset($RequestData['Data']) && $RequestData['Data'])
			{
				foreach($RequestData['Data'] as $k => $v)
				{
					if(isset($v['LogisticCode']) && isset($v['Traces']))
					{
						$delivery_code = $v['LogisticCode'];
						$content       = $v['Traces'];
						$result        = order_class::saveTrace($delivery_code,JSON::encode($content));
						if($result === false)
						{
							throw new IException("物流信息写入【delivery_trace】表，发生错误".var_export($callbackData,true));
							return false;
						}
					}
				}
			}
		}
		else
		{
			throw new IException("物流推送信息错误：".var_export($callbackData,true));
			return false;
		}
	}

	/**
	 * @brief 即时查询物流快递轨迹
	 * @param $ShipperCode string 物流公司代号
	 * @param $LogisticCode string 物流单号
	 * @return array 通用的结果集 array('result' => 'success或者fail','data' => array( array('time' => '时间','station' => '地点'),......),'reason' => '失败原因')
	 */
	private function realTime($ShipperCode,$LogisticCode)
	{
		$params = array(
			'ShipperCode' => $ShipperCode,
			'LogisticCode'=> $LogisticCode,
		);

		$sendData = JSON::encode($params);
		$curlData = array(
			'RequestData' => $sendData,
			'EBusinessID' => $this->appid,
			'RequestType' => '1002',
			'DataType'    => 2,
			'DataSign'    => base64_encode(md5($sendData.$this->appkey)),
		);
		$result     = $this->curlSend("http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx",$curlData);
		$resultJson = JSON::decode($result);

		if(!$resultJson)
		{
			die(var_export($result));
		}
		return $this->response($resultJson);
	}

	/**
	 * @brief 物流轨迹统一数据格式
	 * @param $result 结果处理
	 * @return array 通用的结果集 array('result' => 'success或者fail','data' => array( array('time' => '时间','station' => '地点'),......),'reason' => '失败原因')
	 */
	private function response($result)
	{
		$status = "fail";
		$data   = array();
		$message= "";

		if(isset($result['Traces']) && $result['Traces'])
		{
			foreach($result['Traces'] as $key => $val)
			{
				$data[$key]['time']   = $val['AcceptTime'];
				$data[$key]['station']= $val['AcceptStation'];
			}
			$status = "success";
		}

		if(isset($result['Message']))
		{
			$message = $result['Message'];
		}
		else if(isset($result['Reason']))
		{
			$message = $result['Reason'];
		}
		return array('result' => $status,'data' => $data,'reason' => $message ? $message : "此单号无跟踪记录");
	}

	/**
	 * @brief CURL模拟提交数据
	 * @param $url string 提交的url
	 * @param $data array 要发送的数据
	 * @return mixed 返回的数据
	 */
	private function curlSend($url,$data)
	{
		$data = $this->encodeData($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		return curl_exec($ch);
	}

	//进行数据的string字符串编码
	private function encodeData($datas)
	{
	    $temps = array();
	    foreach ($datas as $key => $value) {
	        $temps[] = sprintf('%s=%s', $key, $value);
	    }
	    $post_data = join('&', $temps);
	    return $post_data;
	}
}
