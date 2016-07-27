/*! Copyright 2015 Ename Inc. All Rights Reserved. */
function Advertisement(option) {
	this.url = 'http://fenxiaos.com/Advert/getAdInfo'; // 要修改这个url地址
	var xmlhttp;
	/**
	 * ajax请求
	 */
	function ajax(url) {
		try {
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");// IE高版本创建XMLHTTP
		} catch (E) {
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");// IE低版本创建XMLHTTP
			} catch (E) {
				xmlhttp = new XMLHttpRequest();// 兼容非IE浏览器，直接创建XMLHTTP对象
			}
		}
		if ("withCredentials" in xmlhttp) {
			beforeSend();
			xmlhttp.open("GET", url, true);
			xmlhttp.send();
			xmlhttp.onreadystatechange = response;
		} else if (typeof XDomainRequest != 'undefined') {
			xmlhttp = new XDomainRequest();
			beforeSend();
			xmlhttp.onload = ieResponse;
			xmlhttp.onerror = ieError;
			xmlhttp.open("GET", url, true);
			xmlhttp.send();
		} else {//Ie11沙都没有
			beforeSend();
			xmlhttp.open("GET", url, true);
			xmlhttp.send();
			xmlhttp.onreadystatechange = response;
		}
	}
	/**
	 * 响应
	 */
	function response() {
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.status == 200) {
				var text = JSON.parse(xmlhttp.responseText);
				if (text) {
					html = creatHtml(text);
					exportHtml(html, text.PlatformType);
				}
			} else {
				exportError();
			}
		}
	}
	function ieResponse() {
		var text = eval("(" + xmlhttp.responseText + ")");
		if (text) {
			html = creatHtml(text);
			exportHtml(html, text.PlatformType);
		}
	}
	function ieError() {
		exportError();
	}
	function exportError() {
		var html = '<div style="border:1px solid red; width:'
				+ option.ename_ad_width + 'px; height:'
				+ option.ename_ad_height + 'px;"><span>内容获取失败</span></div>';
		exportHtml(html);
	}
	function exportHtml(html, type) {
		var obj = getClass("div", "Ename_" + option.ename_ad_solt);
		for ( var i = 0; i < obj.length; i++) {
			obj[i].innerHTML = html;
		}
		if (parseInt(type) == 2) {
			obj=document.getElementById('ename-domain');
			if(obj){
			total_domain_item = document.getElementById('ename-domain').childNodes.length;
					domain_item_width = getClass("div", "domain-item")[0].clientWidth,
					document.getElementById('ename-domain').style.width = (total_domain_item* domain_item_width + domain_item_width)+ 'px';
			}
		}
	}
	function beforeSend() {
		/*
		 * document.write('<div id=Ename_' + option.ename_ad_solt + '
		 * style=width:' + option.ename_ad_width + ';height:' +
		 * option.ename_ad_height + '></div>');
		 */
		document.write('<div class=Ename_' + option.ename_ad_solt + '></div>');
	}
	function creatHtml(text) {
		loadCss(text.html.html.css);
		if (text) {
			var html = text.html.html.head;
			switch (parseInt(text.agenttype)) {
			case 1:
				for ( var i = 0; i < text.data.data.length; i++) {
					var tmp = text.html.html.content.replace('{Name}',
							text.data.data[i].DomainName);
					tmp = tmp.replace('{SimpleDec}',text.data.data[i].SimpleDec);
					tmp = tmp.replace('{Price}',parseFloat(text.data.data[i].Price).toFixed(2));
					tmp = tmp.replace('{FinishTime}',text.data.data[i].FinishTime);
					if (parseInt(text.PlatformType) == 2) {
						if (i == 0) {
							tmp = tmp.replace('{First}', ' '+'domain-item-first');
						} else {
							tmp = tmp.replace('{First}', '');
						}
					}
					html += tmp.replace('{Url}', text.data.url[i]);
				}
				break;
			case 2:
				for ( var i = 0; i < text.data.data.length; i++) {
					var tmp = text.html.html.content.replace('{Name}',
							text.data.data[i].Name);
					tmp = tmp.replace('{Recommands}',text.data.data[i].Recommands);
					if (parseInt(text.PlatformType) == 2) {
						if (i == 0) {
							tmp = tmp.replace('{First}', ' '+'domain-item-first');
						} else {
							tmp = tmp.replace('{First}', '');
						}
					}
					html += tmp.replace('{Url}', text.data.url[i]);
				}
				break;
			}
			html += text.html.html.end;
			return html;
		}
		return '';
	}
	function loadCss(cssString) {
		var style = document.createElement("style");
		style.setAttribute("type", "text/css");

		if (style.styleSheet) {// IE
			style.styleSheet.cssText = cssString;
		} else {// w3c
			var cssText = document.createTextNode(cssString);
			style.appendChild(cssText);
		}

		var heads = document.getElementsByTagName("head");
		if (heads.length)
			heads[0].appendChild(style);
		else
			document.documentElement.appendChild(style);

	}
	// 执行广告
	ajax(this.url + "?posId=" + option.ename_ad_solt);

}
new Advertisement(adInfo);
var total_domain_item = '';
var domain_item_width = '';
var cur_left_domain_item_index = 1;
var cur_top_domain_item_index = 1;
var domain_show_every_time = '';
function left() {
	total_domain_item = document.getElementById('ename-domain').childNodes.length;

	domain_item_width = getClass("div", "domain-item")[0].clientWidth,
			domain_show_every_time = parseInt(getClass("div",
					"domain-items-wrapper")[0].clientWidth
					/ domain_item_width);
	if (cur_left_domain_item_index === 1)
		return;
	cur_left_domain_item_index -= 1;
	changeClass((cur_left_domain_item_index - 1), total_domain_item);
	var margin_left = (cur_left_domain_item_index - 1) * domain_item_width;
	document.getElementById('ename-domain').style.marginLeft = '-'
			+ margin_left + 'px';
};

function right() {
	total_domain_item = document.getElementById('ename-domain').childNodes.length;
	domain_item_width = getClass("div", "domain-item")[0].clientWidth,
			domain_show_every_time = parseInt(getClass("div",
					"domain-items-wrapper")[0].clientWidth
					/ domain_item_width);
	if ((cur_left_domain_item_index + domain_show_every_time - 1) >= total_domain_item
			|| total_domain_item <= domain_show_every_time)
		return;
	cur_left_domain_item_index += 1;
	changeClass((cur_left_domain_item_index - 1), total_domain_item);

	var margin_left = (cur_left_domain_item_index - 1) * domain_item_width;
	document.getElementById('ename-domain').style.marginLeft = '-'
			+ margin_left + 'px';
}
function left_two() {
	total_domain_item = document.getElementById('ename-domain_two').childNodes.length;

	domain_item_width = getClass("div", "domain-item")[0].clientWidth,
			domain_show_every_time = parseInt(getClass("div",
					"domain-items-wrapper_two")[0].clientWidth
					/ domain_item_width);
	if (cur_top_domain_item_index === 1)
		return;
	cur_left_domain_item_index -= 2;
	cur_top_domain_item_index -=1;
	changeClassTwo((cur_left_domain_item_index - 1), total_domain_item);
	var margin_left = (cur_top_domain_item_index - 1) * (getClass("div", "domain-item")[0].clientHeight+1);
	document.getElementById('ename-domain_two').style.marginTop = '-'
			+ margin_left + 'px';
};

function right_two() {
	total_domain_item = document.getElementById('ename-domain_two').childNodes.length;
	domain_item_width = getClass("div", "domain-item")[0].clientWidth,
			domain_show_every_time = parseInt(getClass("div",
					"domain-items-wrapper_two")[0].clientWidth
					/ domain_item_width)*2;
	if ((cur_left_domain_item_index + domain_show_every_time-1) >= total_domain_item
			|| total_domain_item <= domain_show_every_time)
		return;
	cur_left_domain_item_index += 2;
	cur_top_domain_item_index+=1;
	changeClassTwo((cur_left_domain_item_index - 1), total_domain_item);

	var margin_left = (cur_top_domain_item_index - 1) * (getClass("div", "domain-item")[0].clientHeight+1);
	document.getElementById('ename-domain_two').style.marginTop = '-'
			+ margin_left + 'px';
}
function changeClass(cur_index, total_domain_item) {
	for ( var i = 0; i < total_domain_item; i++) {
		if (i == cur_index) {
			addClass(document.getElementById('ename-domain').childNodes[i], 'domain-item-first')
		} else {
			removeClass(document.getElementById('ename-domain').childNodes[i], 'domain-item-first')
		}
	}
}
function changeClassTwo(cur_index, total_domain_item) {
	for ( var i = 0; i < total_domain_item; i++) {
		if (i == cur_index) {
			addClass(document.getElementById('ename-domain_two').childNodes[i], 'domain-item-first')
		} else {
			removeClass(document.getElementById('ename-domain_two').childNodes[i], 'domain-item-first')
		}
	}
}
function getClass(a, e) {
	if (document.getElementsByClassName)
		return document.getElementsByClassName(e);
	a = document.getElementsByTagName(a);
	for ( var g = [], f = 0; f < a.length; f++)
		a[f].className == e && (g[g.length] = a[f]);
	return g
}
function hasClass(obj, cls) {
	return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
}

function addClass(obj, cls) {
	if (!this.hasClass(obj, cls))
		obj.className += " " + cls;
}

function removeClass(obj, cls) {
	if (hasClass(obj, cls)) {
		var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
		obj.className = obj.className.replace(reg, ' ');
	}
}