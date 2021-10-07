/*******************************************************************
 * @authors FengCms 
 * @web     http://www.fengcms.com
 * @email   web@fengcms.com
 * @date    2014年12月4日 19:42:34
 * @version FengTab Beta 1.0
 * @copy    Copyright © 2013-2018 Powered by DiFang Web Studio  
 *******************************************************************/

(function($) {
	$.fn.FengTab = function(config){
		// 默认参数
		config = $.extend({
			titCell			: ".tab",
			mainCell		: ".con",
			defaultIndex	: 0,
			trigger			: "mouseover",
			titOnClassName	: "on",
			showtime		: 200
		}, config);
		// 全局变量
		var obj	= $(this),
			tabLi	= obj.find(config.titCell).children(),
			conDiv	= obj.find(config.mainCell).children();
		// 显示内容部分处理
		conDiv.each(function(){
			var t		= $(this),
				index	= t.index();
			index==config.defaultIndex ? t.show() : t.hide()
		});
		// 选项卡控制部分处理以及选项卡切换
		tabLi.each(function(){
			var li = $(this),
				index = li.index();
			
			if(index==config.defaultIndex){
				li.addClass(config.titOnClassName);
			};
			li.on(config.trigger, function(){
				li.addClass(config.titOnClassName).siblings().removeClass(config.titOnClassName);
				conDiv.eq(index).show().siblings().hide();
			});
		});
	};
})(jQuery);