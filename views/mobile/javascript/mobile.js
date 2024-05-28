$(function(){
	$('.header_search').on('click', function(){
		$('.viewport').animate({scrollTop:0},200);
	})
	// 设置当前页面标题以及返回路径 开始
	var pageInfo = $("#pageInfo"),
		pageInfoTitle = pageInfo.data('title');
	if (pageInfoTitle) {
		$("#page_title").html(pageInfoTitle);
	};
	if (window.location.href.indexOf("costpoint") !== -1) {
		$(".header_so_btn").hide();
	}
	//
	var $vB = $("#viewport_bottom");
	var $lB = $("#layout_bottom");
	if ($vB) {
		var str = $vB.html();
		$lB.html(str);
		$vB.html('');
	}
});

// 跳转函数
function gourl(url){
	window.location.href = url;
}
// 获取url参数函数
function getUrlParam(name){
		var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
		var r = window.location.search.substr(1).match(reg);
		if (r!=null) return unescape(r[2]); return null;
}
// 隐藏底部导航
function hideNav(){
	$(".footer_nav").hide()
}
