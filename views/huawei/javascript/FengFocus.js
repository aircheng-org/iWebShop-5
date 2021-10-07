/*******************************************************************
 * @authors FengCms
 * @web     http://www.fengcms.com
 * @email   web@fengcms.com
 * @date    2015年9月6日
 * @version FengFocus 2.0
 * @copy    Copyright © 2013-2018 Powered by DiFang Web Studio
 *******************************************************************/
(function($) {
	$.fn.FengFocus = function(F) {
		F = $.extend({
			defaultIndex: 0,				// 默认显示第几个，第一个为 0
			trigger: "click",				// 数字交互方式，click 为 点击切换，mouseover 为鼠标碰到就切换
			showtime: 5000,					// 默认自动切换时间，单位为毫秒
			showWay: "slow",				// 焦点图切换方式，slow 为渐隐渐现 down 为上下切换
			butt:true,						// 默认是否开启滚动控制按钮 false 为关闭
			thumb:false						// 控制区是数字还是缩略图， true 为说略图 false 为数字
		}, F);

		// 将插件赋值设置为变量（没必要这么做，但是我习惯这样）

		var defaultIndex = F.defaultIndex,
			showWay = F.showWay,
			trigger = F.trigger,
			showtime = F.showtime;

		// 将插件DOM设置为变量，便于全局调用。

		var Obj = $(this);

		// 找到DOM中的UL并设置CLASS，便于唯一查询

		Obj.children('ul').addClass('FocusPic');
		var Ul = Obj.children('.FocusPic'),
		 	Li = Ul.children('li'),
			LiSize = Li.size();

		// 是否插入左右控制按钮
		F.butt ? Obj.append('<div class="FocusLeft"></div><div class="FocusRight"></div>') : null;

		// 在元素中插入小数控制ul
		Obj.append('<ul class="FocusNum"></ul>');
		var Num = Obj.children('ul.FocusNum');

		// 对默认DOM结构进行补全，并进行初始化

		Li.each(function(){
			var T = $(this),
				I = T.index(),
				Img = T.find('img'),
				Title = Img.prop('alt'),
				Info = Img.data('info'),
				Isrc = Img.attr('src');
			// 将图片添加为背景图,用于百分百的宽屏效果
			T.attr('style', Isrc ? 'background-image: url('+ Isrc +')' : "");
			// 将上一版本中的统一加上标题简介修改为 有就加，没有就不加
			Info ? T.append('<p>'+Info+'</p>') : null;
			Title ? T.append('<strong>'+Title+'</strong>') : null;

			// 显示默认焦点图，并隐藏其它图片
			I==defaultIndex ? T.show() : T.hide();

			// 如果开启缩略图则插入缩略图
			F.thumb ? Num.append('<li><img src='+Isrc+' /></li>') : Num.append('<li>'+(I+1)+'</li>');
		});

		// 对小数进行初始化，和设定切换
		var NumLi = Num.children('li');
		NumLi.each(function(){
			var T = $(this),
				I = T.index();
			I==defaultIndex ? T.addClass('on') : null;
			T.on(trigger,function() {
				T.addClass('on').siblings('li').removeClass('on');
				SandH(I);
			});
		});

		// 设定向左向右按钮
		Obj.children('.FocusLeft').click(function(){
			Go("L");
		});
		Obj.children('.FocusRight').click(function(){
			Go();
		});


		// 运动函数
		function Go(how){
			var OnI = Num.children('li.on').index();
			switch (how){
				case "L":
					var I = OnI - 1;
					I==-1 ? I = LiSize - 1 : null;
					break;
				default:
					var I = OnI + 1;
					I==LiSize ? I = 0 : null;
			}
			NumOn(I);
			SandH(I);
		}

		// 数字切换函数

		function NumOn(I) {
			NumLi.eq(I).addClass("on").siblings().removeClass("on");
		};

		// 图片切换函数

		function SandH(I) {
			switch (showWay) {
			case "down":
				Li.stop().eq(I).slideDown(500).siblings().slideUp(500);
				break;
			default:
				Li.eq(I).fadeIn(200).siblings().hide();
			}
		};

		// 定时器

		function actionDo(){
			return setInterval(function(){
				Go();
			},showtime);
		};

		// 当鼠标位于焦点图之上时的定时器的触发和关闭

		var StopAct = actionDo();
		Obj.hover(function() {
			clearInterval(StopAct);
		}, function() {
			StopAct = actionDo();
		});
	}
})(jQuery);