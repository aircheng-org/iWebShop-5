<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file service.php
 * @brief 外部调用服务类，可以给APP应用提供JSON数据结构
 * @author nswe
 * @date 2018/11/12 23:17:36
 * @version 5.3
 * @note (1)所有的请求都需要MD5签名验证，通过priKey方法获取密钥，防止数据篡改，保证完全完整性。
         (2)用户API接口必须要有<userToken>用户身份令牌。

   @warning config.php中的encryptKey密钥必须保密，不得明文显示。
            强烈建议调用API接口要用HTTPS加密，防止内容和令牌被劫持。
 */
class service extends pluginBase
{
	//数据
	public $data = '';

	//用户身份令牌时效性
	private $userTokenLife = 72000;

	//插件注册
	public function reg()
	{
		plugin::reg("onBeforeCreateAction@service",function()
		{
    		//日志记录
    		$this->log(["开始请求" => IClient::getIp(),"请求数据" => $_REQUEST,"请求地址" => IUrl::getUrl()]);

		    $action = IFilter::act(IReq::get('action'),"/\w+/");
		    if($action)
		    {
		        //动态绑定action接口
            	self::controller()->$action = function() use($action)
    		    {
    		        $this->_run($action);
    		        $this->show();
            	};
		    }
		    else
		    {
		        $this->setError("service action is null");
		        $this->show();
		    }
		});
	}

	public static function name()
	{
		return "API数据接口";
	}

	public static function description()
	{
		return "允许外部调用API接口，获取JSON信息";
	}

    /**
     * @brief 接口总入口拦截
     * @param string $action 动作名称
     */
	private function _run($action)
	{
	    //获取令牌
	    if($action == 'userToken')
	    {
	        $this->userToken();
	    }
	    //其余接口
	    else
	    {
    		$mustData  = array_merge($_POST,$_GET);
    		$sign      = isset($mustData['sign']) ? $mustData['sign'] : ""; //签名数据
    		$sendParam = isset($mustData['param'])? $mustData['param']: ""; //扩展参数(只用于系统已经存在的接口使用)

    		unset($mustData['controller']);
    		unset($mustData['action']);
    		unset($mustData['sign']);

    		//1,检测数据完整性
    		if(in_array('',$mustData) || !$sign)
    		{
    			$this->setError("API接口缺少必要参数:".join(",",array_keys($mustData)));
    		}
    		//2,随机数不够标准
    		else if(strlen($mustData['rand']) <= 5)
    		{
    			$this->setError("随机数字必须大于5位");
    		}
    		//3,校验时间是否超时
    		else if($mustData['time']+30 <= time())
    		{
    			$this->setError("有效时间已过");
    		}
    		//4,md5加密对比sign是否合法
    		else if($this->sign($mustData) != $sign)
    		{
    			$this->setError("sign非法");
    		}
    		//5,userToken是否合法
    		else if(isset($mustData['userToken']))
    		{
                $cacheObj      = new ICache();
                $userTokenData = $cacheObj->get('userToken'.$mustData['userToken']);
                if($userTokenData)
                {
        		    $userRow = ICrypt::decode($userTokenData,$this->getPriKey());
        		    if($userRow && $userRow = JSON::decode($userRow))
        		    {
                        if(isset($userRow['expire']) && $userRow['expire'])
                        {
                            $nowDate = ITime::getDateTime();
            		        $diffSec = ITime::getDiffSec($nowDate,$userRow['expire']);
            		        if($diffSec >= $this->userTokenLife)
            		        {
            		            $cacheObj->del('userToken'.$mustData['userToken']);
            		            $this->setError("userToken已经过期，请重新登录获取");
            		        }
            		        else
            		        {
            		            //刷新生效时间，续订
            		            $this->createUserToken($userRow,$mustData['userToken']);
            		            $this->controller()->user = $userRow;
            		        }
                        }
                        else
                        {
                            $this->setError("userToken时间不存在，请重新登录获取");
                        }
        		    }
        		    else
        		    {
        		        $this->setError("userToken解码出错");
        		    }
                }
                else
                {
                    $this->setError("userToken不存在");
                }
    		}

            //有报错信息则直接退出
    		if($this->getError())
    		{
    		    return;
    		}

    		try
    		{
    			$apiParam = array($action);

    			//对参数的处理
    			if($sendParam)
    			{
    				if(is_array($sendParam))
    				{
    					foreach($sendParam as $k => $v)
    					{
    						$apiParam[] = !$k || is_numeric($k) ? $v : array("#".$k."#",$v);
    					}
    				}
    				else if(is_string($sendParam))
    				{
    					$apiParam[] = $sendParam;
    				}
    			}
    			Api::$type = 'out';//设置API类的使用方式远程调用
    			$resource  = call_user_func_array(array("Api","run"),$apiParam);
    			if($resource instanceof IQuery)
    			{
    				$this->data = $resource->find();
    			}
    			else
    			{
    				$this->data = $resource;
    			}
    		}
    		catch(Exception $e)
    		{
    			$this->setError($e->getMessage());
    		}
	    }
	}

	/**
	 * @brief 输出结果
	 * @param $result array('data' => '结果数据','status' => '执行状态success or fail','error' => '错误内容') 数据结果
	 */
	private function show()
	{
		$result = array('data' => $this->data,'status' => 'fail','error' => $this->getError());

		//正常
		if(!$result['error'])
		{
			$result['status'] = 'success';
		}

		//日志记录
		$this->log(["返回值" => $result]);

		echo $this->encode($result);
	}

	/**
	 * @brief 转换数据结构
	 * @param $data array  待转换的数据
	 * @param $type string 数据类型json
	 * @return string 数据结果
	 */
	private function encode($data,$type = 'json')
	{
		switch($type)
		{
			case "json":
			{
				$data = JSON::encode($data);
			}
			break;
		}
		return $data;
	}

	/**
	 * @brief 加密算法
	 * @param array $param 加密的数据
	 * @return 签名数据
	 */
	private function sign($param)
	{
		$key = $this->getPriKey();//通讯密钥
		ksort($param);
		reset($param);
		return md5(http_build_query($param).$key);
	}

	/**
	 * @brief 获取通讯密钥
	 * @return string 密钥
	 * @note 默认密钥在config/config.php中的encryptKey字段
	 */
	private function getPriKey()
	{
		return IWeb::$app->config['encryptKey'];
	}

	/**
	 * @brief 获取userToken令牌接口
	 * @return array 令牌数据 array(userToken => 用户身份令牌, expire => 过期时间)
	 */
    private function userToken()
    {
        $loginInfo = IReq::get('loginInfo');
        $password  = IReq::get('password');

        $userRow  = plugin::trigger("isValidUser",array($loginInfo,md5($password)));
        if($userRow)
        {
            $userToken = $this->createUserToken($userRow);
            $this->data = array("userToken" => $userToken,'expire' => $this->userTokenLife);
        }
        else
        {
            $this->setError("账号或密码错误");
        }
    }

	/**
	 * @brief 创建令牌数据，也可以复写存在令牌
	 * @param array $userRow 用户信息
	 * @param string $userToken 要更新的令牌,如果为空则生成新的
	 * @return string 令牌
	 */
	private function createUserToken($userRow,$userToken = '')
	{
        //记录用户权限令牌时间
        $userRow['expire'] = ITime::getDateTime();

        //生成加密令牌数据和MD5令牌名称
        $userTokenValue = ICrypt::encode(JSON::encode($userRow), $this->getPriKey());
        $userToken      = $userToken ? $userToken : md5($userRow['id'].$this->getPriKey());

        //把令牌记录到cache里面
        $cacheObj = new ICache();
        $cacheObj->set("userToken".$userToken,$userTokenValue);

        return $userToken;
	}

    //记录日志信息
	private function log($content)
	{
	    $logObj = new IFileLog('service/'.date('Y-m-d').'.log');
	    $logObj->write($content);
	}
}