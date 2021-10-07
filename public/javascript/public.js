//创建URL地址 by air_cheng
function creatUrl(param)
{
	var urlArray   = [];
	var _tempArray = param.split("/");
	for(var index in _tempArray)
	{
		if(_tempArray[index])
		{
			urlArray.push(_tempArray[index]);
		}
	}

	if(urlArray.length >= 2)
	{
		var iwebshopUrl = _webUrl.replace("_controller_",urlArray[0]).replace("_action_",urlArray[1]);

		//存在URL参数情况
		if(urlArray.length >= 3)
		{
			//卸载原数组中已经拼接的数据
			urlArray.splice(0,2);

			//伪静态格式
			if(iwebshopUrl.indexOf("?") == -1)
			{
				var param1 = [];
				var param2 = "";
				for(var t in urlArray)
				{
					if(urlArray[t].indexOf('&') == -1 || urlArray[t].indexOf('=') == -1)
					{
						param1.push(urlArray[t]);
					}
					else
					{
						param2 += urlArray[t];
					}
				}
				iwebshopUrl = iwebshopUrl.replace("_paramKey_/_paramVal_",param1.join("/"));
				iwebshopUrl+= param2 ? "?"+param2 : "";
			}
			//非伪静态格式
			else
			{
				var _paramVal_ = "";
				for(var i in urlArray)
				{
					if(i%2 == 0)
					{
						_paramVal_ += urlArray[i];
					}
					else
					{
						_paramVal_ += "="+urlArray[i];

						//网址末尾不需要加&连接符号
						if(i != urlArray.length-1)
						{
							_paramVal_ += "&";
						}
					}
				}
				iwebshopUrl = iwebshopUrl.replace("_paramKey_=_paramVal_",_paramVal_);
			}
		}
		return iwebshopUrl;
	}
	return '';
}

//切换验证码
function changeCaptcha()
{
	$('#captchaImg').prop('src',creatUrl("site/getCaptcha/random/"+Math.random()));
}

//资源web路径
function webroot(path)
{
	if(!path || typeof(path) != 'string')
	{
		return;
	}
	return path.indexOf('http') == 0 ? path : _webRoot+path;
}

/**
 * @brief 商品筛选
 * @param object {"type":radio,none,checkbox,"callback":回调函数,"submit":提交函数,"seller_id":商户ID,"is_products":0,1是否包括货品数据,"mode":检索模式simple,normal}
 */
function searchGoods(config)
{
	var data         = config.data        ? config.data        : "";
	var mode         = config.mode        ? config.mode        : "simple";
	var is_products  = config.is_products ? config.is_products : 0;
	var listType     = config.type        ? config.type        : 'radio';
	var seller_id    = config.seller_id   ? config.seller_id   : 0;
	var conditionUrl = creatUrl('/goods/search/type/'+listType+'/seller_id/'+seller_id+'/is_products/'+is_products+'/mode/'+mode+'/'+data);

	var step = 0;
	var artConfig =
	{
		"id":"searchGoods",
		"title":"商品检索",
		"okVal":"执行",
		"button":
		[{
			"name":"后退",
			"callback":function(iframeWin,topWin)
			{
				if(step > 0)
				{
					iframeWin.window.history.go(-1);
					this.size(1,1);
					step--;
				}
				return false;
			}
		}],
		"ok":function(iframeWin,topWin)
		{
			//自定义提交函数
			if(config.submit)
			{
				config.submit(iframeWin);
				return;
			}

			if(step == 0)
			{
				iframeWin.document.forms[0].submit();
				step++;
				return false;
			}
			else if(step == 1)
			{
				var goodsList = $(iframeWin.document).find('input[name="id[]"]:checked');

				//添加选中的商品
				if(goodsList.length == 0)
				{
					alert('请选择要添加的商品');
					return false;
				}
				//执行处理回调
				config.callback(goodsList);
				return true;
			}
		}
	};

	//如果有提交函数则直接去掉回退按钮无用处
	if(config.submit)
	{
		artConfig.button = null;
	}

	art.dialog.open(conditionUrl,artConfig);
}

//全选
function selectAll(nameVal)
{
	if($("input[type='checkbox'][name^='"+nameVal+"']:not(:checked)").length > 0)
	{
		$("input[type='checkbox'][name^='"+nameVal+"']").prop('checked',true);
	}
	else
	{
		$("input[type='checkbox'][name^='"+nameVal+"']").prop('checked',false);
	}
}
/**
 * @brief 获取控件元素值的数组形式
 * @param string nameVal 控件元素的name值
 * @param string sort    控件元素的类型值:checkbox,radio,text,textarea,select
 * @return array
 */
function getArray(nameVal,sort)
{
	//要ajax的json数据
	var jsonData = new Array;

	switch(sort)
	{
		case "checkbox":
		$('input[type="checkbox"][name="'+nameVal+'"]:checked').each(
			function(i)
			{
				jsonData[i] = $(this).val();
			}
		);
		break;
	}
	return jsonData;
}
window.loadding = function(message){var message = message ? message : '正在执行，请稍后...';art.dialog({"id":"loadding","lock":true,"fixed":true,"drag":false}).content(message);}
window.unloadding = function(){art.dialog({"id":"loadding"}).close();}
window.tips = function(mess){art.dialog.tips(mess);}
window.alert = function(mess){art.dialog.alert(String(mess));}
window.confirm = function(mess,bnYes,bnNo)
{
	art.dialog.confirm(
		String(mess),
		function(){typeof bnYes == "function" ? bnYes() : bnYes && (bnYes.indexOf('/') == 0 || bnYes.indexOf('http') == 0) ? window.location.href=bnYes : eval(bnYes);},
		function(){typeof bnNo == "function" ? bnNo() : bnNo && (bnNo.indexOf('/') == 0 || bnNo.indexOf('http') == 0) ? window.location.href=bnNo : eval(bnNo);}
	);
}
/**
 * @brief 删除操作
 * @param object conf
	   msg :提示信息;
	   form:要提交的表单名称;
	   link:要跳转的链接地址;
 */
function delModel(conf)
{
	var ok = null;            //执行操作
	var msg= '确定要删除么？';//提示信息

	if(conf)
	{
		if(conf.form)
		{
			var ok = 'formSubmit("'+conf.form+'")';
			if(conf.link)
			{
				var ok = 'formSubmit("'+conf.form+'","'+conf.link+'")';
			}
		}
		else if(conf.link)
		{
			var ok = 'window.location.href="'+conf.link+'"';
		}

		if(conf.msg)
		{
			var msg = conf.msg;
		}

		if(conf.name && checkboxCheck(conf.name,"请选择要操作项") == false)
		{
			return '';
		}
	}
	if(ok==null && document.forms.length >= 1)
		var ok = 'document.forms[0].submit();';

	if(ok!=null)
	{
		window.confirm(msg,ok);
	}
	else
	{
		alert('删除操作缺少参数');
	}
}

//根据表单的name值提交
function formSubmit(formName,url)
{
	if(url)
	{
		$('form[name="'+formName+'"]').attr('action',url);
	}
	$('form[name="'+formName+'"]').submit();
}

//根据checkbox的name值检测checkbox是否选中
function checkboxCheck(boxName,errMsg)
{
	if($('input[name="'+boxName+'"]:checked').length < 1)
	{
		alert(errMsg);
		return false;
	}
	return true;
}

//倒计时
var countdown=function()
{
	var _self=this;
	this.handle={};
	this.parent={'second':'minute','minute':'hour','hour':""};
	this.add=function(id)
	{
		_self.handle.id=setInterval(function(){_self.work(id,'second');},1000);
	};
	this.work=function(id,type)
	{
		if(type=="")
		{
			return false;
		}

		var e = document.getElementById("cd_"+type+"_"+id);
		var value=parseInt(e.innerHTML);
		if( value == 0 && _self.work( id,_self.parent[type] )==false )
		{
			clearInterval(_self.handle.id);
			return false;
		}
		else
		{
			e.innerHTML = (value==0?59:(value-1));
			return true;
		}
	};
};

/*实现事件页面的连接*/
function event_link(url)
{
	window.location.href = url;
}

//延迟执行
function lateCall(t,func)
{
	var _self = this;
	this.handle = null;
	this.func = func;
	this.t=t;

	this.execute = function()
	{
		_self.func();
		_self.stop();
	}

	this.stop=function()
	{
		clearInterval(_self.handle);
	}

	this.start=function()
	{
		_self.handle = setInterval(_self.execute,_self.t);
	}
}

//数量减少1个
function numsReduce(id)
{
	let value = parseInt($("#"+id).val());
	value--;
	if(value <= 0)
	{
		value = 1;
	}
	$("#"+id).val(value);
}

//数量增加1个
function numsAdd(id)
{
	let max   = parseInt($("#"+id).attr('max'));
	let value = parseInt($("#"+id).val());
	value++;
	if(value >= max)
	{
		value = max;
	}
	$("#"+id).val(value);
}

//修改更新数量
function numUpdate(id)
{
	let value = parseInt($("#"+id).val());
	let max = parseInt($("#"+id).attr('max'));
	if(value <= 1)
	{
		value = 1;
	}
	else if(value >= max)
	{
		value = max;
	}
	$("#"+id).val(value);
}

/**
 * @brief 订单检索
 * @param object {"submit":提交函数,"seller_id":商户ID}
 */
function searchOrder(config)
{
	var data         = config.data        ? config.data        : "";
	var seller_id    = config.seller_id   ? config.seller_id   : 0;
	var conditionUrl = creatUrl('/order/search/seller_id/'+seller_id+'/'+data);

	var artConfig =
	{
		"id":"searchOrder",
		"title":"订单检索",
		"okVal":"执行",
		"ok":function(iframeWin,topWin)
		{
			//自定义提交函数
			if(config.submit)
			{
				config.submit(iframeWin);
				return;
			}
		}
	};
	art.dialog.open(conditionUrl,artConfig);
}

__iwebshopMobile = false;
if (/(iPhone|iPad|iPod|iOS|Android)/i.test(navigator.userAgent)) {
    __iwebshopMobile = true;
}