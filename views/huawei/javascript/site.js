//商品移除购物车
function removeCart(goods_id,type)
{
	var goods_id = parseInt(goods_id);
	$.getJSON(creatUrl("simple/removeCart"),{goods_id:goods_id,type:type},function(content){
		if(content.isError == false)
		{
			$('[name="mycart_count"]').html(content.data['count']);
			$('[name="mycart_sum"]').html(content.data['sum']);
		}
		else
		{
			alert(content.message);
		}
	});
}

//添加收藏夹
function favorite_add_ajax(goods_id,obj)
{
	$.getJSON(creatUrl("simple/favorite_add"),{"goods_id":goods_id,"random":Math.random()},function(content){
		tips(content.message);
	});
}

//购物车展示
function showCart()
{
	$.getJSON(creatUrl("simple/showCart"),{sign:Math.random()},function(content)
	{
		var cartTemplate = template.render('cartTemplete',{'goodsData':content.data,'goodsCount':content.count,'goodsSum':content.sum});
		$('#div_mycart').html(cartTemplate);
		$('#div_mycart').show();
	});
}


//dom载入成功后开始操作
jQuery(function()
{
	//购物车数量加载
	if($('[name="mycart_count"]').length > 0)
	{
		$.getJSON(creatUrl("simple/showCart"),{sign:Math.random()},function(content)
		{
			$('[name="mycart_count"]').html(content.count);
		});

		//购物车div层显示和隐藏切换
		var mycartLateCall = new lateCall(200,function(){showCart();});
		$('[name="mycart"]').hover(
			function(){
				mycartLateCall.start();
			},
			function(){
				mycartLateCall.stop();
				$('#div_mycart').hide('slow');
			}
		);
	}
});

//[ajax]加入购物车
function joinCart_ajax(id,type)
{
	$.getJSON(creatUrl("simple/joinCart"),{"goods_id":id,"type":type,"random":Math.random()},function(content){
		if(content.isError == false)
		{
			var count = parseInt($('[name="mycart_count"]').html()) + 1;
			$('[name="mycart_count"]').html(count);
			tips(content.message);
		}
		else
		{
			alert(content.message);
		}
	});
}

//列表页加入购物车统一接口
function joinCart_list(id)
{
	$.getJSON(creatUrl("/simple/getProducts"),{"id":id},function(content)
	{
		if(!content || content.length == 0)
		{
			joinCart_ajax(id,'goods');
		}
		else
		{
			artDialog.open(creatUrl("/block/products_list/goods_id/"+id+"/type/radio"),{
				id:'selectProduct',
				title:'选择货品到购物车',
				okVal:'加入购物车',
				ok:function(iframeWin, topWin)
				{
					var productObj = $(iframeWin.document).find('input[name="id[]"]:checked');

					//执行处理回调
					joinCart_ajax(productObj.val(),'product');
					return true;
				}
			})
		}
	});
}