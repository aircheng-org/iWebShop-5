//更新统计数据开关boolean by air_cheng
__openUpdateStatus = true;

//购物车数量改动计算
function cartCount(obj)
{
	if(__openUpdateStatus == false)
	{
		return false;
	}
	__openUpdateStatus = false;

	var countInput = $('#count_'+obj.goods_id+'_'+obj.product_id);
	var countInputVal = parseInt(countInput.val());

	//之前商品数量的基数，以这个为参考增减量
	var oldNum = countInput.data('oldNum') ? countInput.data('oldNum') : obj.count;

	//商品数量大于1件
	if(isNaN(countInputVal) || (countInputVal <= 0))
	{
	    __openUpdateStatus = true;
		alert('购买的数量必须大于1件');
		countInput.val(1);
		countInput.change();
	}
	//商品数量小于库存量
	else if(countInputVal > parseInt(obj.store_nums))
	{
	    __openUpdateStatus = true;
		alert('购买的数量不能大于此商品的库存量');
		countInput.val(parseInt(obj.store_nums));
		countInput.change();
	}
	else
	{
		var diff = parseInt(countInputVal) - parseInt(oldNum);
		if(diff == 0)
		{
			return;
		}
		var goods_id   = obj.product_id > 0 ? obj.product_id : obj.goods_id;
		var goods_type = obj.product_id > 0 ? "product"      : "goods";

		//更新购物车中此商品的数量
		$.getJSON(creatUrl("/simple/joinCart"),{"goods_id":goods_id,"type":goods_type,"goods_num":diff,"random":Math.random()},function(content){
			if(content.isError == true)
			{
				alert(content.message);
				countInput.val(1);
				countInput.data('oldNum',1);
				countInput.change();
			}
			else
			{
				countInput.data('oldNum',countInputVal);
				refreshCount();

				//更新小计的价格
				$('#sum_'+obj.goods_id+'_'+obj.product_id).html(((obj.sell_price - obj.reduce) * countInputVal).toFixed(2));
			}
		});
	}
}

//增加商品数量
function cart_increase(obj)
{
	//库存超量检查
	var countInput = $('#count_'+obj.goods_id+'_'+obj.product_id);
	if(parseInt(countInput.val()) + 1 > parseInt(obj.store_nums))
	{
		alert('购买的数量大于此商品的库存量');
	}
	else
	{
		if(__openUpdateStatus == false)
		{
			return false;
		}
		countInput.val(parseInt(countInput.val()) + 1);
		countInput.change();
	}
}

//减少商品数量
function cart_reduce(obj)
{
	//库存超量检查
	var countInput = $('#count_'+obj.goods_id+'_'+obj.product_id);
	if(parseInt(countInput.val()) - 1 <= 0)
	{
		alert('购买的数量必须大于1件');
	}
	else
	{
		if(__openUpdateStatus == false)
		{
			return false;
		}
		countInput.val(parseInt(countInput.val()) - 1);
		countInput.change();
	}
}

//移除购物车
function removeCartByJSON(obj)
{
	var goods_id   = obj.product_id > 0 ? obj.product_id : obj.goods_id;
	var goods_type = obj.product_id > 0 ? "product"      : "goods";
	$.getJSON(creatUrl("/simple/removeCart"),{"goods_id":goods_id,"type":goods_type,"random":Math.random()},function()
	{
		window.location.reload();
	});
}

//开始计算选中的商品数据
function exceptCartGoodsAjax()
{
	var data = [];
	//获取未选中的商品
	$('input[type="checkbox"][name^="selectCartGoods"]:not(:checked)').each(function()
	{
		data.push(this.value);
	});

	$.getJSON(creatUrl("/simple/exceptCartGoodsAjax"),{"data":data},function(content)
	{
		refreshCount();
	});
}

//刷新购物车统计数据
function refreshCount()
{
	$.getJSON(creatUrl("/simple/promotionRuleAjax"),{"random":Math.random()},function(content){
		$('#cart_prompt_box').empty();
		if(content.promotion.length > 0)
		{
			for(var i = 0;i < content.promotion.length; i++)
			{
				$('#cart_prompt_box').append( template.render('promotionTemplate',{"item":content.promotion[i]}) );
			}
			$('#cart_prompt').show();
		}
		else
		{
			$('#cart_prompt').hide();
		}

		/*开始更新数据*/
		$('#weight').html(content.weight);
		$('#origin_price').html(content.sum.toFixed(2));
		$('#discount_price').html(content.reduce.toFixed(2));
		$('#promotion_price').html(content.proReduce.toFixed(2));
		$('#sum_price').html(content.final_sum.toFixed(2));

		if(content.goodsList)
		{
			//更新多选按钮状态
			for(var col in content.goodsList)
			{
				var goods_id   = content.goodsList[col]['goods_id'];
				var product_id = content.goodsList[col]['product_id'];
				var valueVal   = goods_id+"_"+product_id;
				$('input[type="checkbox"][name^=selectCartGoods][value="'+valueVal+'"]').prop("checked",true);
			}
		}

		//全选按钮状态
		checkButtonStatus();

		__openUpdateStatus = true;
	});
}

//按钮选择状态
function checkButtonStatus()
{
	//总选择按钮
	if($('input[type="checkbox"][name^="selectCartGoods"]:not(:checked)').length > 0)
	{
		$("input[type='checkbox'][name='_selectCartGoods']").prop("checked",false);
	}
	else
	{
		$("input[type='checkbox'][name='_selectCartGoods']").prop("checked",true);
		$("input[type='checkbox'][name^='selectCartGoods']").prop("checked",true);
	}

	//商家分组按钮
	$('input[type="checkbox"][name^="selectCartGoods"][name$="[]"]:not(:checked)').each(function()
	{
		var sellerName = $(this).attr('name').replace("[]","");
		$("input[type='checkbox'][name='"+sellerName+"']").prop("checked",false);
	});
}

//加载完毕后运行JS
$(function()
{
	//单个或组商品选择按钮
	$('input[type="checkbox"][name*="selectCartGoods"]').change(function()
	{
		//获取计算信息
		exceptCartGoodsAjax();
	});
	//获取计算信息
	refreshCount();
})