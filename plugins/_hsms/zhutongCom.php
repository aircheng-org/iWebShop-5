<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file zhutongCom.php
 * @brief 短信发送接口
 * @author nswe
 * @date 2019/6/17 16:19:35
 * @version 5.6
 * @note
    中国 台湾	Taiwan	886
    东帝汶民主共和国	DEMOCRATIC REPUBLIC OF TIMORLESTE	670
    中非共和国	Central African Republic	236
    丹麦	Denmark	45
    乌克兰	Ukraine	380
    乌兹别克斯坦	Uzbekistan	998
    乌干达	Uganda	256
    乌拉圭	Uruguay	598
    乍得	Chad	235
    也门	Yemen	967
    亚美尼亚	Armenia	374
    以色列	Israel	972
    伊拉克	Iraq	964
    伊朗	Iran	98
    伯利兹	Belize	501
    佛得角	Cape Verde	238
    俄罗斯	Russia	7
    保加利亚	Bulgaria	359
    克罗地亚	Croatia	385
    关岛	Guam	1671
    冈比亚	The Gambia	220
    冰岛	Iceland	354
    几内亚	Guinea	224
    几内亚比绍	Guinea-Bissau	245
    列支敦士登	Liechtenstein	423
    刚果共和国	The Republic of Congo 	242
    刚果民主共和国	Democratic Republic of the Congo	243
    利比亚	Libya	218
    利比里亚	Liberia	231
    加拿大	Canada	1
    加纳 	Ghana	233
    加蓬	Gabon	241
    匈牙利	Hungary	36
    南非	South Africa	27
    博茨瓦纳	Botswana	267
    卡塔尔	Qatar 	974
    卢旺达 	Rwanda	250
    卢森堡	Luxembourg	352
    印尼	Indonesia	62
    印度	India	91,918,919
    危地马拉	Guatemala	502
    厄瓜多尔	Ecuador	593
    厄立特里亚	Eritrea	291
    叙利亚	Syria	963
    古巴	Cuba	53
    吉尔吉斯斯坦	Kyrgyzstan	996
    吉布提	Djibouti	253
    哥伦比亚	Colombia	57
    哥斯达黎加	Costa Rica	506
    喀麦隆	Cameroon	237
    图瓦卢	Tuvalu	688
    土库曼斯坦	Turkmenistan 	993
    土耳其	Turkey	90
    圣卢西亚	Saint Lucia	1758
    圣基茨和尼维斯	Saint Kitts and Nevis	1869
    圣多美和普林西比	Sao Tome and Principe	239
    圣文森特和格林纳丁斯	Saint Vincent and the Grenadines	1784
    圣皮埃尔和密克隆群岛	Saint Pierre and Miquelon	508
    圣赫勒拿岛	Saint Helena	290
    圣马力诺	San Marino	378
    圭亚那	Guyana	592
    坦桑尼亚	Tanzania	255
    埃及	Egypt	20
    埃塞俄比亚	Ethiopia	251
    基里巴斯	Kiribati	686
    塔吉克斯坦	Tajikistan	992
    塞内加尔	Senegal	221
    塞尔维亚	Serbia and Montenegro	381
    塞拉利昂	Sierra Leone	232
    塞浦路斯	Cyprus	357
    塞舌尔	Seychelles	248
    墨西哥	Mexico	52
    多哥	Togo	228
    多米尼克	Dominica	1767
    奥地利	Austria	43
    委内瑞拉	Venezuela	58
    孟加拉	Bangladesh	880
    安哥拉	Angola	244
    安圭拉岛	Anguilla	1264
    安道尔	Andorra	376
    密克罗尼西亚	Federated States of Micronesia	691
    尼加拉瓜	Nicaragua	505
    尼日利亚	Nigeria	234
    尼日尔	Niger	227
    尼泊尔	Nepal  	977
    巴勒斯坦	Palestine	970
    巴哈马	The Bahamas	1242
    巴基斯坦	Pakistan	92
    巴巴多斯	Barbados	1246
    巴布亚新几内亚	Papua New Guinea	675
    巴拉圭	Paraguay	595
    巴拿马	Panama	507
    巴林	Bahrain	973
    巴西	Brazil	55
    布基纳法索	Burkina Faso	226
    布隆迪	Burundi	257
    希腊	Greece	30
    帕劳	Palau	680
    库克群岛	Cook Islands	682
    开曼群岛	Cayman Islands	1345
    德国	Germany	49
    意大利	Italy	39
    所罗门群岛	Solomon Islands	677
    托克劳	Tokelau	690
    拉脱维亚	Latvia	371
    挪威	Norway	47
    捷克共和国	Czech Republic	420
    摩尔多瓦	Moldova	373
    摩洛哥	Morocco	212
    摩纳哥	Monaco	377
    文莱	Brunei Darussalam	673
    斐济	Fiji	679
    斯威士兰王国	The Kingdom of Swaziland	268
    斯洛伐克	Slovakia	421
    斯洛文尼亚	Slovenia	386
    斯里兰卡	Sri Lanka	94
    新加坡	Singapore 	65
    新喀里多尼亚	New Caledonia	687
    新西兰	New Zealand	64
    日本	Japan	81
    智利	Chile	56
    朝鲜	Korea, North	850
    柬埔寨 	Cambodia	855
    格林纳达	Grenada	1473
    格陵兰	Greenland	299
    格鲁吉亚	Georgia	995
    比利时	Belgium	32
    毛里塔尼亚	Mauritania	222
    毛里求斯	Mauritius	230
    汤加	Tonga	676
    沙特阿拉伯	Saudi Arabia	966
    法国	France	33
    法属圭亚那	French Guiana	594
    法属波利尼西亚	French Polynesia	689
    法属西印度群岛	french west indies	596
    法罗群岛	Faroe Islands	298
    波兰	Poland	48
    波多黎各	The Commonwealth of Puerto Rico	17,871,939
    波黑	Bosnia and Herzegovina 	387
    泰国	Thailand	66
    津巴布韦	Zimbabwe	263
    洪都拉斯	Honduras	504
    海地	Haiti	509
    澳大利亚	Australia	61
    澳门	Macao	853
    爱尔兰	Ireland	353
    爱沙尼亚	Estonia	372
    牙买加 	Jamaica	1876
    特克斯和凯科斯群岛	Turks and Caicos Islands	1649
    特立尼达和多巴哥	Trinidad and Tobago	1868
    玻利维亚	Bolivia	591
    瑙鲁	Nauru	674
    瑞典	Sweden	46
    瑞士	Switzerland	41
    瓜德罗普	Guadeloupe	590
    瓦利斯和富图纳群岛	Wallis et Futuna	681
    瓦努阿图	Vanuatu	678
    留尼汪 	Reunion	262
    白俄罗斯	Belarus	375
    百慕大	Bermuda	1441
    直布罗陀	Gibraltar	350
    福克兰群岛	Falkland	500
    科威特	Kuwait	965
    科摩罗和马约特	Comoros	269
    科特迪瓦	Cote d’Ivoire	225
    秘鲁	Peru	51
    突尼斯	Tunisia	216
    立陶宛	Lithuania	370
    索马里	Somalia	252
    约旦	Jordan	962
    纳米比亚	Namibia	264
    纽埃岛	Island of Niue	683
    缅甸  	Burma	95
    罗马尼亚	Romania	40
    美国	United States of America	1
    美属维京群岛	Virgin Islands	1340
    美属萨摩亚	American Samoa	1684
    老挝	Laos	856
    肯尼亚	Kenya	254
    芬兰	Finland	358
    苏丹	Sudan	249
    苏里南	Suriname	597
    英国	United Kingdom	44
    英属维京群岛	British Virgin Islands	1284
    荷兰	Netherlands	31
    荷属安的列斯	Netherlands Antilles	599
    莫桑比克	Mozambique	258
    莱索托	Lesotho	266
    菲律宾	Philippines	63
    萨尔瓦多	El Salvador	503
    萨摩亚	Samoa	685
    葡萄牙	Portugal	351
    蒙古	Mongolia	976
    西班牙	Spain	34
    贝宁	Benin	229
    赞比亚	Zambia	260
    赤道几内亚	Equatorial Guinea	240
    越南	Vietnam	84
    阿塞拜疆	Azerbaijan	994
    阿富汗	Afghanistan	93
    阿尔及利亚	Algeria	213
    阿尔巴尼亚	Albania	355
    阿拉伯联合酋长国	United Arab Emirates	971
    阿曼	Oman	968
    阿根廷	Argentina	54
    阿鲁巴	Aruba	297
    韩国	Korea, South)	82
    香港  	Hong Kong (SAR)	852
    马其顿	Macedonia	389
    马尔代夫  	Maldives  	960
    马拉维	Malawi	265
    马来西亚	Malaysia	60
    马绍尔群岛	Marshall Islands	692
    马耳他	Malta	356
    马达加斯加	Madagascar	261
    马里	Mali	223
    黎巴嫩	Lebanon	961
    黑山共和国	The Republic of Montenegro	382
 */

 /**
 * @class zhutongCom
 * @brief 短信发送接口 短信后台地址 http://intl.zthysms.com
 */
class zhutongCom extends hsmsBase
{
	private $submitUrl  = "http://intl.zthysms.com/intSendSms.do";
	private $countryCode= "61";//国家编号

	/**
	 * @brief 获取config用户配置
	 * @return array
	 */
	public function getConfig()
	{
		$siteConfigObj = new Config("site_config");

		return array(
			'username' => $siteConfigObj->sms_username,
			'userpwd'  => $siteConfigObj->sms_pwd,
		);
	}

    //处理手机号码
	private function filterMobile($mobile)
	{
	    if(stripos($mobile,'0') === 0)
	    {
	        $mobile = substr($mobile,1);
	    }
	    return $mobile;
	}

	/**
	 * @brief 发送短信
	 * @param string $mobile
	 * @param string $content
	 * @return
	 */
	public function send($mobile,$content)
	{
	    $mobile = $this->filterMobile($mobile);
		$config = self::getConfig();
		date_default_timezone_set('Asia/Shanghai');
		$datetime = date('YmdHis');

		$post_data = array(
			'username' => $config['username'],
			'tkey'     => $datetime,
			'password' => md5(md5($config['userpwd']).$datetime),
			'code'     => $this->countryCode,
			'content'  => $content,
			'mobile'   => $mobile,
		);

		$url    = $this->submitUrl;
		$string = '';
		foreach ($post_data as $k => $v)
		{
		   $string .="$k=".urlencode($v).'&';
		}

		$post_string = substr($string,0,-1);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
		$result = curl_exec($ch);
		if($result === false)
		{
            $error = curl_error($ch);
            curl_close($ch);
			return "CURL错误：".$error;
		}
		return $this->response($result);
	}

	/**
	 * @brief 解析结果
	 * @param $result 发送结果
	 * @return string success or fail
	 */
	public function response($result)
	{
		if(strpos($result,'1,') === 0)
		{
			return 'success';
		}
		else
		{
			return $this->getMessage($result);
		}
	}

	/**
	 * @brief 获取参数
	 */
	public function getParam()
	{
		return array(
			"username" => "用户名",
			"userpwd"  => "密码",
			"usersign" => "短信签名",
		);
	}

	//返回消息提示
	public function getMessage($code)
	{
		$messageArray = array(
			-1 =>"用户名或者密码不正确或用户禁用",
			2  =>"余额不够或扣费错误",
			3  =>"扣费失败异常（请联系客服）",
			6  =>"有效号码为空",
			7  =>"短信内容为空",
			8  =>"无签名，必须，格式：【签名】",
			9  =>"没有Url提交权限",
			10 =>"发送号码过多,最多支持200个号码",
			11 =>"产品ID异常或产品禁用",
			12 =>"参数异常",
			13 =>"30分种重复提交",
			14 =>"用户名或密码不正确，产品余额为0，禁止提交，联系客服",
			15 =>"Ip验证失败",
			19 =>"短信内容过长，最多支持500个",
			20 =>"定时时间不正确：格式：20130202120212(14位数字)",
		);
		return isset($messageArray[$code]) ? $messageArray[$code] : $code;
	}
}