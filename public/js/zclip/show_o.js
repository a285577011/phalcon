function Advertisement(a) {
	document.write('<div class=Ename_'+ a.ename_ad_solt+'></div>');
	var b = "?posId=" + a.ename_ad_solt;
	document.write("<iframe width="+ a.ename_ad_width+ "px height="+ a.ename_ad_height+ "px class=Ename_"+ a.ename_ad_solt+ ' frameborder="0" allowfullscreen="true" scrolling="no" allowtransparency="true" hspace="0" vspace="0" marginheight="0" marginwidth="0"></iframe>');
	a = getClass("iframe", "Ename_" + a.ename_ad_solt);
	for ( var c = 0; c < a.length; c++)
		a[c].src = "about:blank";
}
function getClass(tagName, className) {  //第一个参数 表示是className是所属那个dom标签下,这样为了提高检索效率
    //如果是火狐择调用火狐的getElementsByClassName 内置函数
    if (document.getElementsByClassName) {
        return document.getElementsByClassName(className)
    }
    else {
        var nodes = document.getElementsByTagName(tagName),
        ret = []
        for (i = 0; i < nodes.length; i++) {
            if (hasClass(nodes[i], className)) { ret.push(nodes[i]) }
        }
        return ret;
    }
}
function hasClass(obj, cls) {
	return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
}
new Advertisement(adInfo);