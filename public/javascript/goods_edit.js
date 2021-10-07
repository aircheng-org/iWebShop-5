//初始化货品表格
function initProductTable()
{
	//默认产生一条商品标题空挡
	var goodsHeadHtml = template.render('goodsHeadTemplate',{'templateData':[]});
	$('#goodsBaseHead').html(goodsHeadHtml);

	//默认产生一条商品空挡
	var goodsRowHtml = template.render('goodsRowTemplate',{'templateData':[[]]});
	$('#goodsBaseBody').html(goodsRowHtml);
}

//选择规格值后重新生成货品
function selSpec(obj)
{
	var specIsHere    = getIsHereSpec();
	var specValueData = specIsHere.specValueData;
	var specData      = specIsHere.specData;

	//追加新建规格
	var jsonData = $(obj).find("option:selected").val();
	if(!jsonData)
	{
		return;
	}
	var json = $.parseJSON(jsonData);

	//判断规格数据是否重复
	if(specValueData[json.name])
	{
		for(var k in specValueData[json.name])
		{
			if(specValueData[json.name][k]['value'] == json.value)
			{
				alert('规格值重复');
				return;
			}
		}
	}
	else
	{
		specData[json.name]      = json;
		specValueData[json.name] = [];
	}
	specValueData[json.name].push({"image":json.image,"value":json.value});
	createProductList(specData,specValueData);
}

//根据规格ID通过ajax获取规格值
function selSpecVal(obj)
{
	var spec_id    = $(obj).val();
	var optionHtml = '<option value="">选择规格值</option>';
	$.getJSON(creatUrl("block/spec_value_list"),{"id":spec_id},function(json)
	{
		if(json.value)
		{
			var valObj = $.parseJSON(json.value);
			for(var i in valObj)
			{
				json.value = i;
				json.image = valObj[i];
				optionHtml+= "<option value='"+JSON.stringify(json)+"'>"+i+"</option>";
			}
			$('#specValSel').html(optionHtml);
		}
	});
	$('#specValSel').html(optionHtml);
}

//笛卡儿积组合
function descartes(list,specData)
{
	//parent上一级索引;count指针计数
	var point  = {};

	var result = [];
	var pIndex = null;
	var tempCount = 0;
	var temp   = [];

	//根据参数列生成指针对象
	for(var index in list)
	{
		if(typeof list[index] == 'object')
		{
			point[index] = {'parent':pIndex,'count':0}
			pIndex = index;
		}
	}

	//单维度数据结构直接返回
	if(pIndex == null)
	{
		return list;
	}

	//动态生成笛卡尔积
	while(true)
	{
		for(var index in list)
		{
			tempCount = point[index]['count'];
			var itemSpecData = list[index][tempCount];
			temp.push({"id":specData[index].id,"value":itemSpecData.value,"name":specData[index].name,"image":itemSpecData.image});
		}

		//压入结果数组
		result.push(temp);
		temp = [];

		//检查指针最大值问题
		while(true)
		{
			if(point[index]['count']+1 >= list[index].length)
			{
				point[index]['count'] = 0;
				pIndex = point[index]['parent'];
				if(pIndex == null)
				{
					return result;
				}

				//赋值parent进行再次检查
				index = pIndex;
			}
			else
			{
				point[index]['count']++;
				break;
			}
		}
	}
}

//获取已经存在的规格
function getIsHereSpec()
{
	//开始遍历规格
	var specValueData = {};
	var specData      = {};

	//规格已经存在的数据
	if($('input:hidden[name^="_spec_array"]').length > 0)
	{
		$('input:hidden[name^="_spec_array"]').each(function()
		{
			var json = $.parseJSON(this.value);
			if(!specValueData[json.name])
			{
				specData[json.name]      = json;
				specValueData[json.name] = [];
			}

			//去掉spec_array中的已经添加的重复值
			for(var i in specValueData[json.name])
			{
				for(var item in specValueData[json.name][i])
				{
					item = specValueData[json.name][i];
					if(item.value == json.value)
					{
						return;
					}
				}
			}
			specValueData[json.name].push({"image":json.image,"value":json.value});
		});
	}
	return {"specData":specData,"specValueData":specValueData};
}

/**
 * @brief 根据规格数据生成货品序列
 * @param object specData规格数据对象,比如：{规格名1:{id:1,value:"",name:"规格名1"},规格名2:{id:2,value:"",name:"规格名2"}}
 * @param object specValueData 规格值对象集合,比如：{规格名1:[{image:"图片1",value:"规格值1"},{image:"图片2",value:"规格值2"},{image:"图片3",value:"规格值3"}],规格名2:[{image:"图片5",value:"规格值5"},{image:"图片6",value:"规格值6"}]}
 */
function createProductList(specData,specValueData)
{
	//生成货品的笛卡尔积
	var specMaxData = descartes(specValueData,specData);

	//生成最终的货品数据
	var productList = [];
	for(var i = 0;i < specMaxData.length;i++)
	{
		//从表单中获取默认商品数据
		var productJson = {};

		//直接匹配当前页面存在的规格JSON数据,以tr为每行，里面包含某些规格数据
		var specContent = ['has(input[type="hidden"])'];
		for(var j in specMaxData[i])
		{
            specContent.push( 'has([value *= \'"value":"'+specMaxData[i][j]['value']+'","name":"'+specMaxData[i][j]['name']+'"\'])' );
		}

        //进行循环缩减查询范围以匹配默认数据
		while(true)
		{
		    var inputDataJQ = $('#goodsBaseBody tr:'+specContent.join(":")).find('input[type="text"]');
    		if(inputDataJQ.length == 0 && specContent.length > 0)
    		{
    		    specContent.pop();
    		}
    		else
    		{
    		    break;
    		}
		}

		inputDataJQ.each(function(){
			productJson[this.name.replace(/^_(\w+)\[\d+\]/g,"$1")] = this.value;
		});

		var productItem = {};
		for(var index in productJson)
		{
			//自动组建货品号
			if(index == 'goods_no')
			{
				//值为空时设置默认货号
				if(productJson[index] == '')
				{
					productJson[index] = defaultProductNo;
				}

				if(productJson[index].match(/(?:\-\d*)$/) == null)
				{
					//正常货号生成
					productItem['goods_no'] = productJson[index]+'-'+(i+1);
				}
				else
				{
					//货号已经存在则替换
					productItem['goods_no'] = productJson[index].replace(/(?:\-\d*)$/,'-'+(i+1));
				}
			}
			else
			{
				productItem[index] = productJson[index];
			}
		}
		productItem['spec_array'] = specMaxData[i];
		productList.push(productItem);
	}

	//创建规格标题
	var goodsHeadHtml = template.render('goodsHeadTemplate',{'templateData':specData});
	$('#goodsBaseHead').html(goodsHeadHtml);

	//创建货品数据表格
	var goodsRowHtml = template.render('goodsRowTemplate',{'templateData':productList});
	$('#goodsBaseBody').html(goodsRowHtml);

	if($('#goodsBaseBody tr').length == 0)
	{
		initProductTable();
	}
}

/**
 * 设置商品默认图片
 */
function defaultImage(_self)
{
	$('#thumbnails img[name="picThumb"]').removeClass('current');
	$(_self).addClass('current');
}

//删除规格
function delSpec(specId)
{
	$('input:hidden[name^="_spec_array"]').each(function()
	{
		var json = $.parseJSON(this.value);
		if(json.id == specId)
		{
			$(this).remove();
		}
	});

	//当前已经存在的规格数据
	var specIsHere = getIsHereSpec();
	createProductList(specIsHere.specData,specIsHere.specValueData);
}

//jquery图片上传
$('[name="_goodsFile"]').fileupload({
    dataType: 'json',
    done: function (e, data)
    {
    	if(data.result && data.result.flag == 1)
    	{
    	    //{'flag','img','list','show'}
    	    var picJson = data.result;
        	var picHtml = template.render('picTemplate',{'picRoot':picJson.img});
        	$('#thumbnails').append(picHtml);

        	//默认设置第一个为默认图片
        	if($('#thumbnails img[name="picThumb"][class="current"]').length == 0)
        	{
        		$('#thumbnails img[name="picThumb"]:first').addClass('current');
        	}
    	}
    	else
    	{
    		alert(data.result.error);
    		$('#uploadPercent').text(data.result.error);
    	}
    	$('[type="submit"]').attr('disabled',false);
    },
    progressall: function (e, data)
    {
        var progress = parseInt(data.loaded / data.total * 100);
        $('#uploadPercent').text("加载完成："+progress+"%");
    },
    start: function(e)
    {
        $('[type="submit"]').attr('disabled',true);
    }
});

/**
 * 会员价格
 * @param obj 按钮所处对象
 */
function memberPrice(obj,seller_id)
{
	var sellerId  = seller_id ? seller_id : 0;
	var sellPrice = $(obj).siblings('input[name^="_sell_price"]')[0].value;
	if($.isNumeric(sellPrice) == false)
	{
		alert('请先设置商品的价格再设置会员价格');
		return;
	}

	var groupPriceValue = $(obj).siblings('input[name^="_groupPrice"]');

	//用户组的价格
	art.dialog.data('groupPrice',groupPriceValue.val());

	//开启新页面
	var tempUrl = creatUrl("goods/member_price/sell_price/@sell_price@/seller_id/@seller_id@");
	tempUrl = tempUrl.replace('@sell_price@',sellPrice).replace('@seller_id@',sellerId);
	art.dialog.open(tempUrl,{
		id:'memberPriceWindow',
	    title: '会员价格',
	    ok:function(iframeWin, topWin)
	    {
	    	var formObject = iframeWin.document.forms['groupPriceForm'];
	    	var groupPriceObject = {};
	    	$(formObject).find('input[name^="groupPrice"]').each(function(){
	    		if(this.value != '')
	    		{
	    			//去掉前缀获取group的ID
		    		var groupId = this.name.replace('groupPrice','');

		    		//拼接json串
		    		groupPriceObject[groupId] = this.value;
	    		}
	    	});

	    	//更新会员价格值
    		var temp = [];
    		for(var gid in groupPriceObject)
    		{
    			temp.push('"'+gid+'":"'+groupPriceObject[gid]+'"');
    		}
    		groupPriceValue.val('{'+temp.join(',')+'}');
    		return true;
		}
	});
}

//快速录入规格
function speedSpec()
{
    var specItem = "<div class='form-group'><input type='text' class='form-control' placeholder='请输入规格名称' name='speedSpecName' /><textarea class='form-control' placeholder='请输入规格值，多个规格以回车分隔' rows='4' name='speedSpecValue' /></textarea></div>";
    var specHtml = "<form id='speedBox' style='width:350px;height:380px;overflow:auto'></form>";
    art.dialog(
    {
        "title":"根据规格批量生成货品",
        "init":function(){$('#speedBox').append(specItem);},
        "content":specHtml,
        "ok":function()
        {
            var specNameData  = {};
            var specValueData = {};
            $('[name="speedSpecValue"]').each(function(indexId)
            {
                var specArray = $(this).val();
                if(specArray)
                {
                    var specName = $('[name="speedSpecName"]:eq('+indexId+')').val();
                    specNameData[specName]  = {"id":String(Math.floor(Math.random()*(50000-5000+1)+5000)),"name":specName,"value":""};
                    specValueData[specName] = [];

                    var tmpData  = specArray.split("\n");
                    var testItems= [];//用于过滤重复数据
                    for(var i in tmpData)
                    {
                        if(tmpData[i] && $.inArray(tmpData[i],testItems) == -1)
                        {
                            testItems.push(tmpData[i]);
                            specValueData[specName].push({"image":"","value":tmpData[i]});
                        }
                    }
                }
            });
            createProductList(specNameData,specValueData);

            //手动触发同步修改选项
            $('#synCheckBox').prop('checked',true);
            synData();
            return true;
        },
        "okVal":"开始生成",
        "button":[{"name":"增加规格","callback":function()
            {
                $('#speedBox').append(specItem);
                return false;
            }}
        ],
    });
}

//货品数据自动同步
function synData()
{
    var isOpen = $('#synCheckBox').prop('checked');
    var synName = ["_goods_no","_store_nums","_market_price","_sell_price","_cost_price","_weight"];
    if(isOpen == true)
    {
        for(var indexVal in synName)
        {
            $('input[name^="'+synName[indexVal]+'"]:eq(0)').on('keyup',function()
            {
                var nameVal = $(this).attr('name').replace("[0]","");
                $('input[name^="'+nameVal+'"]:gt(0)').val($(this).val());
            });
        }
    }
    else
    {
        for(var indexVal in synName)
        {
            $('input[name^="'+synName[indexVal]+'"]:eq(0)').off();
        }
    }
}