/**
 * @brief 查询过滤高亮处理
 * @note 模板里面的id分别为 品牌：filter-brand*, 属性：filter-attr*, 价格：filter-price*, 排序：filter-order*, 升降：filter-by*
 * @param object config配置对象:lightClassName,descClassName,ascClassName
 */
function searchFilter(config)
{
    _self = this;
    var lightClassName = config && config.lightClassName ? config.lightClassName : 'current';
    var descClassName  = config && config.descClassName  ? config.descClassName  : 'desc';
    var ascClassName   = config && config.ascClassName   ? config.ascClassName   : 'asc';

    //利用js解析url查询字符串
    var searchObj = {};
    var attrObj   = {};
    var searchArray = decodeURIComponent(window.location.search.substring(1)).split("&");
    for(var searchStr in searchArray)
    {
        if(searchArray[searchStr].indexOf('=') !== -1)
        {
            var tempObj = searchArray[searchStr].split('=');

            if(tempObj[1])
            {
                if(tempObj[0].indexOf('attr') !== -1)
                {
                    var attrId = tempObj[0].match(/\d+/);
                    attrObj[attrId] = tempObj[1];
                }
                searchObj[tempObj[0]] = tempObj[1];
            }
        }
    }

	//品牌模块高亮和预填充
	if(searchObj.brand)
	{
	    $('[id ^= "filter-brand"]').removeClass(lightClassName);
	    $('#filter-brand'+searchObj.brand).addClass(lightClassName);
	}

	//属性模块高亮和预填充
	for(var attrId in attrObj)
	{
	    $('[id ^= "filter-attr'+attrId+'"]').removeClass(lightClassName);
	    $('#filter-attr'+attrId+attrObj[attrId]).addClass(lightClassName);
	}

	//价格模块高亮和预填充
	if(searchObj.min_price && searchObj.max_price)
	{
	    var priceId = searchObj.min_price+"-"+searchObj.max_price;
	    $('[id ^= "filter-price"]').removeClass(lightClassName);
	    $('#filter-price'+priceId).addClass(lightClassName);

    	$('input[name="min_price"]').val(parseFloat(searchObj.min_price));
    	$('input[name="max_price"]').val(parseFloat(searchObj.max_price));
	}

	//排序字段
	if(searchObj.order)
	{
	    $('#filter-order'+searchObj.order).addClass(lightClassName);
	    if(searchObj.by == 'desc')
	    {
	        $('#filter-by'+searchObj.order).removeClass(ascClassName);
	        $('#filter-by'+searchObj.order).addClass(descClassName);
	    }
	    else
	    {
	        $('#filter-by'+searchObj.order).removeClass(descClassName);
	        $('#filter-by'+searchObj.order).addClass(ascClassName);
	    }
	}

    //跳转URL检索地址
	this.link = function(param)
	{
	    for(var i in param)
	    {
	        if(i == 'order')
	        {
	            searchObj['by'] = searchObj[i] == param[i] && searchObj['by'] == 'asc' ? 'desc' : 'asc';
	        }
	        searchObj[i] = param[i];
	    }

	    if(window.location.search)
	    {
	        window.location.href = window.location.href.replace(window.location.search,"?"+jQuery.param(searchObj));
	    }
	    else
	    {
	        window.location.href = window.location.href+"?"+jQuery.param(searchObj);
	    }
	}
}