/**
 * @brief 分类插件类
 * @param json param 完整分类数据
 */
function categoryWidget(param)
{
	param._self = this;
	if(param['id'])
	{
		param.id    = param['id'];
		param.idArea= param['id'];
	}
	else
	{
		param.id    = "_goodsCategoryButton";
		param.idArea= "__categoryBox";
	}

	//完整分类数据
	art.dialog.data(param.id+'categoryWhole',param.categoryData);
	art.dialog.data(param.id+'categoryParentData',param.categoryParentData);

	//生成分类按钮数据
	this.createGoodsCategory = function(categoryObj)
	{
		if(!categoryObj)
		{
			return;
		}

		buttonTemplate  = '<ctrlArea style="margin-right:5px;">';
		buttonTemplate += '<input type="hidden" value="<%=templateData.id%>" name="'+param.name+'" />';
		buttonTemplate += '<button class="btn btn-default" type="button" id="_catButton<%=templateData.id%>" onclick="return confirm(\'确定删除此分类？\',function(){$(\'#_catButton<%=templateData.id%>\').parent().remove();})">';
		buttonTemplate += '<span><%=templateData.name%></span></button>';
		buttonTemplate += '</ctrlArea>'

		var dataArea = $('#'+param.idArea);
		dataArea.empty();
		for(var item in categoryObj)
		{
			item = categoryObj[item];
			var goodsCategoryHtml = template.compile(buttonTemplate)({'templateData':item});
			dataArea.append(goodsCategoryHtml);
		}
	}

	//弹出分类界面进行选择
	this.selectGoodsCategory = function()
	{
		//根据表单里面的name值生成分类ID数据
		var categoryName = param.name;
		var result = [];
		$('[name="'+categoryName+'"]').each(function()
		{
			result.push(this.value);
		});
		art.dialog.data(param.id+'categoryValue',result);

		//URL地址
		if(param.type && param.type == 'checkbox')
		{
			var urlValue = creatUrl('block/goods_category/type/checkbox/id/'+param.id);
		}
		else
		{
			var urlValue = creatUrl('block/goods_category/type/radio/id/'+param.id);
		}

		art.dialog.open(urlValue,{
			title:'选择商品分类',
			okVal:'确定',
			ok:function(iframeWin, topWin)
			{
				var categoryObject = [];
				var categoryWhole  = art.dialog.data(param.id+'categoryWhole');
				var categoryValue  = art.dialog.data(param.id+'categoryValue');
				for(var item in categoryWhole)
				{
					item = categoryWhole[item];
					if(jQuery.inArray(item['id'],categoryValue) != -1)
					{
						categoryObject.push(item);
					}
				}

				//是否自定义函数
				if(param.callback)
				{
					window[param.callback](categoryObject);
				}
				else
				{
					param._self.createGoodsCategory(categoryObject);
					if(param.afterCallback)
					{
						window[param.afterCallback](categoryObject);
					}
				}
			},
			cancel:function()
			{
				return true;
			}
		})
	}

	//如果存在默认数据就生成
	if(param.default)
	{
		this.createGoodsCategory(param.default);
	}

	//绑定UI按钮入口
	$(document).on("click","[name='"+param.id+"']",this.selectGoodsCategory);
}