$(function() {
	$('.menu_list li').mouseover(function() {
		$(this).find("ul").show().prev("a").css("background", "#ff9400");
	}).mouseout(function() {
		$(this).find("ul").hide().prev("a").css("background", "");
	});

	$(".com_aside_dl dt").click(function() {
		$(this).next("dd").toggle().siblings("dd").hide();
	});
})
/**
 * 导航高亮
 * 
 * @param url
 * @param left_url
 */
function navLight(url, left_url)
{
	var top_url=arguments[2]?arguments[2]:'';
	url=url.toLowerCase();
	left_url=left_url.toLowerCase();
	top_url=top_url.toLowerCase();
	$('.clearfix').find('a[href="' + url + '"]').closest('li').addClass(
			'active');
	$(".com_aside_dl").find('a[href="' + left_url + '"]').parent().parent()
			.parent().show().siblings("dd").hide();
	$(".com_aside_dl").find('a[href="' + left_url + '"]').addClass('cur');
	$(".menu_list").find('a[href="' + top_url + '"]').closest('li').addClass(
			'menu_curren').siblings().removeClass('menu_curren');

};

// 全选或全不选
function allChecked(allCheckObj) {
	$('.select_domain').prop('checked', allCheckObj.checked);
	$('.select_domain').each( function() {
		if($(this).is(':disabled')) {
			$(this).prop('checked', false);
		}
	});
}

// 判断是否勾上全选的按钮
function isAllChecked() {
	$('.select_domain').each( function() {
		if(!$(this).is(':disabled')) {
			if(!$(this).is(':checked')) {
				$('.all_checked').prop('checked', false);
				return false;
			} else {
				$('.all_checked').prop('checked', true);
			}
		}
	});
}

function getDays(AddDayCount)
{
	var dd = new Date(); 
	dd.setDate(dd.getDate()+AddDayCount);//获取AddDayCount天后的日期 
	var y = dd.getFullYear(); 
	var m = (dd.getMonth()+1)<10?"0"+(dd.getMonth()+1):(dd.getMonth()+1);//获取当前月份的日期，不足10补0
	var d = dd.getDate()<10?"0"+dd.getDate():dd.getDate(); //获取当前几号，不足10补0
	return y+"-"+m+"-"+d; 
}

// 重置
function searchFormReset() {
    var tform = $('#search_form');
    tform.find(':input[type!="button"][type!="reset"][type!="submit"]').val(null);
    tform.find(':checkbox').attr('checked',false);
    $("#pricestart").val();
    $("#percentstart").val();
}

//新开窗口不被拦截
function openWin(url) {
	var a = document.createElement("a");
	a.setAttribute("href", url);
	a.setAttribute("target", "_blank");
	document.body.appendChild(a);
	a.click();
}
//获取url参数
function getUrlParam(name)
{
var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
var r = window.location.search.substr(1).match(reg);  //匹配目标参数
if (r!=null) return unescape(r[2]); return null; //返回参数值
} 

