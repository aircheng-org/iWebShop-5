<?php
/**
 * @copyright (c) 2017 aircheng.com
 * @file tracking.php
 * @brief trackingmore查询接口 参考：https://www.trackingmore.com/api-php-cn.html
 * @date 2019/6/6 12:43:55
 * @version 5.6
 * @author nswe
 */
class tracking implements freight_inter
{
	/**
	 * @brief 显示快递跟踪
	 * @param $ShipperCode  string 物流公司代号
	 * @param $LogisticCode string 快递单号
	 * @return mixed
	 */
	public function line($ShipperCode,$LogisticCode)
	{
        $track = new Trackingmore();
        $track->createTracking($ShipperCode,$LogisticCode);
        $data = $track->getSingleTrackingResult($ShipperCode,$LogisticCode);
        return $this->response($data);
	}

	/**
	 * @brief 物流轨迹统一数据格式
	 * @param $result 结果处理
	 * @return array 通用的结果集 array('result' => 'success或者fail','data' => array( array('time' => '时间','station' => '地点'),......),'reason' => '失败原因')
	 */
	private function response($result)
	{
		$status = "fail";
		$data   = [];
		$message= "";

		if(isset($result['data']) && isset($result['data']['origin_info']) && isset($result['data']['origin_info']['trackinfo']))
		{
		    $trace = $result['data']['origin_info']['trackinfo'];
			foreach($trace as $key => $val)
			{
				$data[$key]['time']   = $val['Date'];
				$data[$key]['station']= $val['StatusDescription'].'【'.$val['Details'].'】';
			}
			$status = "success";
		}

		if(isset($result['meta']) && isset($result['meta']['message']) && $result['meta']['message'])
		{
			$message = $result['meta']['message'];
		}
		return array('result' => $status,'data' => $data,'reason' => $message ? $message : "此单号无跟踪记录");
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

//公开调用类
class Trackingmore{

    const API_BASE_URL             = 'http://api.trackingmore.com/v2/';
    const ROUTE_CARRIERS           = 'carriers/';
	const ROUTE_CARRIERS_DETECT    = 'carriers/detect';
    const ROUTE_TRACKINGS          = 'trackings';
	const ROUTE_LIST_ALL_TRACKINGS = 'trackings/get';
	const ROUTE_CREATE_TRACKING    = 'trackings/post';
    const ROUTE_TRACKINGS_BATCH    = 'trackings/batch';
	const ROUTE_TRACKINGS_REALTIME = 'trackings/realtime';
	const ROUTE_TRACKINGS_RELETE   = 'trackings/delete';
	const ROUTE_TRACKINGS_UPDATE   = 'trackings/update';
	const ROUTE_TRACKINGS_GETUSEINFO = 'trackings/getuserinfo';
	const ROUTE_TRACKINGS_GETSTATUS = 'trackings/getstatusnumber';
	const ROUTE_TRACKINGS_NOTUPDATE = 'trackings/notupdate';
	const ROUTE_TRACKINGS_REMOTE   = 'trackings/remote';
	const ROUTE_TRACKINGS_COSTTIME   = 'trackings/costtime';
	const ROUTE_TRACKINGS_UPDATEMORE   = 'trackings/updatemore';
    protected $apiKey              = '5e81f38e-0ac7-4f51-945c-46889e3e222f';


    protected function _getApiData($route, $method = 'GET', $sendData = array()){
		$method     = strtoupper($method);
        $requestUrl = self::API_BASE_URL.$route;
        $curlObj    = curl_init();
        curl_setopt($curlObj, CURLOPT_URL,$requestUrl);
		if($method == 'GET'){
            curl_setopt($curlObj, CURLOPT_HTTPGET,true);
        }elseif($method == 'POST'){
            curl_setopt($curlObj, CURLOPT_POST, true);
        }elseif ($method == 'PUT'){
            curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, "PUT");
        }else{
			curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, $method);
		}

        curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlObj, CURLOPT_TIMEOUT, 90);

        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        $headers = array(
            'Trackingmore-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
        );
        if($sendData){
            $dataString = json_encode($sendData);
            curl_setopt($curlObj, CURLOPT_POSTFIELDS, $dataString);
            $headers[] = 'Content-Length: ' . strlen($dataString);
        }
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curlObj);
        curl_close($curlObj);
        unset($curlObj);
        return $response;
    }



    // List all carriers
    public function getCarrierList(){
        $returnData = array();
        $requestUrl = self::ROUTE_CARRIERS;
        $result = $this->_getApiData($requestUrl, 'GET');
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

	/*Detect a carrier by tracking code
	* @param string $trackingNumber  Tracking number
    * @return array
	*/
	public function detectCarrier($trackingNumber)
    {
        $returnData = array();
        $requestUrl = self::ROUTE_CARRIERS_DETECT;
		$sendData['tracking_number'] = $trackingNumber;
        $result = $this->_getApiData($requestUrl, 'POST',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
	* List all trackings
	* @access public
	* @param int $numbers Tracking numbers,eg:$numbers = LY044217709CN,UG561422482CN (optional)
	* @param int $orders Tracking order,eg:$orders = #123 (optional)
	* @param int $page  Page to display (optional)
	* @param int $limit Items per page (optional)
	* @param int $createdAtMin Start date and time of trackings created (optional)
	* @param int $createdAtMax End date and time of trackings created (optional)
	* @param int $update_time_min Start date and time of trackings updated (optional)
	* @param int $update_time_max End date and time of trackings updated (optional)
	* @param int $order_created_time_min Start date and time of order created (optional)
	* @param int $order_created_time_max End date and time of order created (optional)
	* @param int $lang Language,eg:$lang=cn(optional)
	* @return array
	*/
	public function getTrackingsList($numbers = "",$orders = "",$page = 1,$limit = 100,$createdAtMin = 0,$createdAtMax = 0,$update_time_min = 0,$update_time_max = 0,$order_created_time_min = 0,$order_created_time_max = 0,$lang = ""){
        $returnData = array();
		$sendData   = array();
        $requestUrl = self::ROUTE_LIST_ALL_TRACKINGS;
		$createdAtMax = !empty($createdAtMax)?$createdAtMax:time();
        $update_time_max = !empty($update_time_max)?$update_time_max:time();
        $order_created_time_max = !empty($order_created_time_max)?$order_created_time_max:time();
		$sendData['page']           = $page;
		$sendData['limit']          = $limit;
		$sendData['created_at_min'] = $createdAtMin;
		$sendData['created_at_max'] = $createdAtMax;
		$sendData['update_time_min'] = $update_time_min;
		$sendData['update_time_max'] = $update_time_max;
		$sendData['order_created_time_min'] = $order_created_time_min;
		$sendData['order_created_time_max'] = $order_created_time_max;
		$sendData['lang'] = $lang;
		$sendData['numbers'] = $numbers;
		$sendData['orders'] = $orders;
        $result = $this->_getApiData($requestUrl.'?'.http_build_query($sendData), 'GET', $sendData=array());
        if ($result) {
            $returnData = json_decode($result,1);
        }
        return $returnData;
    }

	/**
	* Create a tracking item
	* @access public
	* @param string $trackingNumber  Tracking number
	* @param string $carrierCode Carrier code
	* @param array $extraInfo (Title,Customer name,email,order ID,customer phone,order create time,destination code,tracking ship date,tracking postal code,language) (optional)
	* @return array
	*/
	public function createTracking($carrierCode,$trackingNumber,$extraInfo = array()){
        $returnData = array();
		$sendData   = array();
        $requestUrl = self::ROUTE_CREATE_TRACKING;

		$sendData['tracking_number']      = $trackingNumber;
		$sendData['carrier_code']         = $carrierCode;
		$sendData['title']                = !empty($extraInfo['title'])?$extraInfo['title']:null;
		$sendData['logistics_channel']    = !empty($extraInfo['logistics_channel'])?$extraInfo['logistics_channel']:null;
		$sendData['customer_name']        = !empty($extraInfo['customer_name'])?$extraInfo['customer_name']:null;
		$sendData['customer_email']       = !empty($extraInfo['customer_email'])?$extraInfo['customer_email']:null;
		$sendData['order_id']             = !empty($extraInfo['order_id'])?$extraInfo['order_id']:null;
		$sendData['customer_phone']       = !empty($extraInfo['customer_phone'])?$extraInfo['customer_phone']:null;
		$sendData['order_create_time']    = !empty($extraInfo['order_create_time'])?$extraInfo['order_create_time']:null;
		$sendData['destination_code']     = !empty($extraInfo['destination_code'])?$extraInfo['destination_code']:'';
		$sendData['tracking_ship_date']   = !empty($extraInfo['tracking_ship_date'])?$extraInfo['tracking_ship_date']:null;
		$sendData['tracking_postal_code'] = !empty($extraInfo['tracking_postal_code'])?$extraInfo['tracking_postal_code']:"";
		$sendData['lang']                 = !empty($extraInfo['lang'])?$extraInfo['lang']:"en";

        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

	/**
	* Create multiple trackings.
	* @access public
	* @param  array $multipleData (Multiple tracking number,carrier code,title,customer name,customer email,order id,destination code,customer phone,order create time,tracking ship date,tracking postal code,special number destination,language)
	* @return array
	*/
	public function createMultipleTracking($multipleData){
        $returnData = array();
		$sendData   = array();
        $requestUrl = self::ROUTE_TRACKINGS_BATCH;
		if(!empty($multipleData)){
			foreach($multipleData as $val){
				$items                         = array();
			    $items['tracking_number']      = !empty($val['tracking_number'])?$val['tracking_number']:null;
				$items['carrier_code']         = !empty($val['carrier_code'])?$val['carrier_code']:null;
				$items['title']                = !empty($val['title'])?$val['title']:null;
				$items['logistics_channel']    = !empty($val['logistics_channel'])?$val['logistics_channel']:null;
				$items['customer_name']        = !empty($val['customer_name'])?$val['customer_name']:null;
				$items['customer_email']       = !empty($val['customer_email'])?$val['customer_email']:null;
				$items['order_id']             = !empty($val['order_id'])?$val['order_id']:null;
				$items['destination_code']     = !empty($val['destination_code'])?$val['destination_code']:null;
				$items['customer_phone']       = !empty($val['customer_phone'])?$val['customer_phone']:null;
				$items['order_create_time']    = !empty($val['order_create_time'])?$val['order_create_time']:null;
				$items['tracking_ship_date']   = !empty($val['tracking_ship_date'])?$val['tracking_ship_date']:null;
				$items['tracking_postal_code'] = !empty($val['tracking_postal_code'])?$val['tracking_postal_code']:null;
				$items['specialNumberDestination'] = !empty($val['specialNumberDestination'])?$val['specialNumberDestination']:null;
				$items['lang']                 = !empty($val['lang'])?$val['lang']:'en';
                $sendData[]                    = $items;
			}
		}

        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }


	/**
	* Get tracking results of a single tracking
	* @access public
	* @param string $trackingNumber  Tracking number
	* @param string $carrierCode Carrier code
	* @param string $lang language
	* @return array
	*/
	public function getSingleTrackingResult($carrierCode,$trackingNumber,$lang=''){
        $returnData = array();
        $requestUrl = self::ROUTE_TRACKINGS.'/'.$carrierCode.'/'.$trackingNumber.'/'.$lang;
        $result = $this->_getApiData($requestUrl, 'GET');
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

	/**
	* Update Tracking item
	* @access public
	* @param string $trackingNumber  Tracking number
	* @param string $carrierCode Carrier code
	* @param array $extraInfo (Title,Customer name,email,order ID,customer phone,destination code,status) (optional)
	* @return array
	*/
	public function updateTrackingItem($carrierCode,$trackingNumber,$extraInfo){
        $returnData = array();
        $requestUrl = self::ROUTE_TRACKINGS.'/'.$carrierCode.'/'.$trackingNumber;
		$sendData['title']           = !empty($extraInfo['title'])?$extraInfo['title']:null;
		$sendData['logistics_channel'] = !empty($extraInfo['logistics_channel'])?$extraInfo['logistics_channel']:null;
		$sendData['customer_name']   = !empty($extraInfo['customer_name'])?$extraInfo['customer_name']:null;
		$sendData['customer_email']  = !empty($extraInfo['customer_email'])?$extraInfo['customer_email']:null;
		$sendData['customer_phone']  = !empty($extraInfo['customer_phone'])?$extraInfo['customer_phone']:null;
		$sendData['order_id']        = !empty($extraInfo['order_id'])?$extraInfo['order_id']:null;
		$sendData['destination_code']= !empty($extraInfo['destination_code'])?$extraInfo['destination_code']:null;
		$sendData['status']= !empty($extraInfo['status'])?$extraInfo['status']:null;
        $result = $this->_getApiData($requestUrl, 'PUT',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
	* Delete a tracking item
	* @access public
	* @param string $trackingNumber  Tracking number
	* @param string $carrierCode Carrier code
	* @return array
	*/
	public function deleteTrackingItem($carrierCode,$trackingNumber){
        $returnData = array();
        $requestUrl = self::ROUTE_TRACKINGS.'/'.$carrierCode.'/'.$trackingNumber;
        $result = $this->_getApiData($requestUrl, 'DELETE');
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

	/**
	* Get realtime tracking results of a single tracking
	* @access public
	* @param string $trackingNumber  Tracking number
	* @param string $carrierCode Carrier code
	* @param array  $extraInfo (Destination_code,Tracking_ship_date Customer_email,Tracking_postal_code,SpecialNumberDestination,order,lang) (optional)
	* @return array
	*/
	public function getRealtimeTrackingResults($carrierCode,$trackingNumber,$extraInfo=array()){
        $returnData = array();
        $requestUrl = self::ROUTE_TRACKINGS_REALTIME;
		$sendData['tracking_number'] = $trackingNumber;
		$sendData['carrier_code']    = $carrierCode;
		$sendData['destination_code']           = !empty($extraInfo['destination_code'])?$extraInfo['destination_code']:null;
		$sendData['tracking_ship_date']   = !empty($extraInfo['tracking_ship_date'])?$extraInfo['tracking_ship_date']:null;
		$sendData['order_create_time']  = !empty($extraInfo['order_create_time'])?$extraInfo['order_create_time']:null;
		$sendData['tracking_postal_code']        = !empty($extraInfo['tracking_postal_code'])?$extraInfo['tracking_postal_code']:null;
		$sendData['specialNumberDestination']        = !empty($extraInfo['specialNumberDestination'])?$extraInfo['specialNumberDestination']:null;
		$sendData['order']        = !empty($extraInfo['order'])?$extraInfo['order']:null;
		$sendData['lang']        = !empty($extraInfo['lang'])?$extraInfo['lang']:null;
        $result = $this->_getApiData($requestUrl, 'POST',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * delete multiple tracking
     * @access public
     * @param array  $multipleData (tracking number,carrier code)
     * @return array
     */
    public function deleteMultipleTracking($multipleData){
        $returnData = array();
        $sendData   = array();
        $requestUrl = self::ROUTE_TRACKINGS_RELETE;
        if(!empty($multipleData)){
            foreach ($multipleData as $val){
                $items                    = array();
                $items['tracking_number'] = !empty($val['tracking_number'])?$val['tracking_number']:null;
                $items['carrier_code']    = !empty($val['carrier_code'])?$val['carrier_code']:null;
                $sendData[]               = $items;
            }
        }
        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @param string $trackingNumber  Tracking number
     * @param string $carrierCode Carrier code
     * @param string $carrierCode Update carrier code
     * @return array
     */
    public function updateCarrierCode($tracking_number,$carrier_code,$update_carrier_code){
        $returnData = array();
        $sendData   = array();
        $requestUrl = self::ROUTE_TRACKINGS_UPDATE;
        $sendData["tracking_number"] = $tracking_number;
        $sendData["carrier_code"] = $carrier_code;
        $sendData["update_carrier_code"] = $update_carrier_code;
        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @return array
     */
    public function getUserInfoBalance(){
        $returnData = array();
        $requestUrl = self::ROUTE_TRACKINGS_GETUSEINFO;
        $result = $this->_getApiData($requestUrl, 'GET');
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @param int $created_at_min Start date and time of trackings created (optional)
     * @param int $created_at_max End date and time of trackings created (optional)
     * @param int $order_created_time_min Start date and time of order created (optional)
     * @param int $order_created_time_max End date and time of order created (optional)
     * @return array
     */
    public function getStatusNumberCount($created_at_min = 0,$created_at_max = 0,$order_created_time_min = 0,$order_created_time_max = 0){
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_TRACKINGS_GETSTATUS;
        $sendData["created_at_min"] = !empty($created_at_min)?$created_at_min:null;
        $sendData["created_at_max"] = !empty($created_at_max)?$created_at_max:time();
        $sendData["order_created_time_min"] = !empty($order_created_time_min)?$order_created_time_min:null;
        $sendData["order_created_time_max"] = !empty($order_created_time_max)?$order_created_time_max:time();
        $result = $this->_getApiData($requestUrl, 'GET',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @param array $multipleData (tracking number,carrier code)
     * @param string $carrierCode Carrier code
     * @return array
     */
    public function setNumberNotUpdate($multipleData){
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_TRACKINGS_NOTUPDATE;
        if(!empty($multipleData)){
            foreach ($multipleData as $val){
                $items                    = array();
                $items['tracking_number'] = !empty($val['tracking_number'])?$val['tracking_number']:null;
                $items['carrier_code']    = !empty($val['carrier_code'])?$val['carrier_code']:null;
                $sendData[]               = $items;
            }
        }
        $result = $this->_getApiData($requestUrl, 'POST',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @param array $multipleData (Country two code,Post code or city name,company),eg:array(0=>array("CN","518131","DHL"));
     * @return array
     */
    public function searchDeliveryIsRemote($multipleData){
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_TRACKINGS_REMOTE;
        if(!empty($multipleData)){
            foreach ($multipleData as $val){
                $items              = array();
                $items['country']   = !empty($val['country'])?$val['country']:null;
                $items['postcode']  = !empty($val['postcode'])?$val['postcode']:null;
                $items['company']  = !empty($val['company'])?$val['company']:null;
                $sendData[]         = $items;
            }
        }
        $result = $this->_getApiData($requestUrl, 'POST',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @param array $multipleData (Country two code,Post code or city name,company),eg:array(0=>array("CN","518131","DHL"));
     * @return array
     */
    public function getCarrierCostTime($multipleData){
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_TRACKINGS_COSTTIME;
        if(!empty($multipleData)){
            foreach ($multipleData as $val){
                $items              = array();
                $items['carrier_code']   = !empty($val['carrier_code'])?$val['carrier_code']:null;
                $items['original']  = !empty($val['original'])?$val['original']:null;
                $items['destination']  = !empty($val['destination'])?$val['destination']:null;
                $sendData[]         = $items;
            }
        }
        $result = $this->_getApiData($requestUrl, 'POST',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * update carrier code
     * @access public
     * @param array $multipleData (Country two code,Post code or city name,company),eg:array(0=>array("CN","518131","DHL"));
     * @return array
     */
    public function updateMultipleTrackItem($multipleData){
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_TRACKINGS_UPDATEMORE;
        if(!empty($multipleData)){
            foreach ($multipleData as $val){
                $items              = array();
                $items['tracking_number']   = !empty($val['tracking_number'])?$val['tracking_number']:null;
                $items['carrier_code']  = !empty($val['carrier_code'])?$val['carrier_code']:null;
                $items['title']  = !empty($val['title'])?$val['title']:null;
                $items['logistics_channel'] = !empty($val['logistics_channel'])?$val['logistics_channel']:null;
                $items['customer_name']  = !empty($val['customer_name'])?$val['customer_name']:null;
                $items['customer_email']  = !empty($val['customer_email'])?$val['customer_email']:null;
                $items['order_id']  = !empty($val['order_id'])?$val['order_id']:null;
                $items['destination_code']  = !empty($val['destination_code'])?$val['destination_code']:null;
                $items['status']  = !empty($val['status'])?$val['status']:null;
                $sendData[]         = $items;
            }
        }
        $result = $this->_getApiData($requestUrl, 'POST',$sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

}
