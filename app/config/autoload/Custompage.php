<?php
return array (
		'cpagesize' => 20,
		'thisbaseurl'=>'http://fenxiao.ename.com.cn',
		'errow_link_url' => 'http://escrow.ename.com/escrow/escr/{%domain%}/1',
		'whois_link_url'=>'http://whois.ename.net/{%domain%}',
		'page_domain_transinfo' => array('show'=>array(1, '显示'), 'hide'=>array(2, '不显示')),
		'page_domain_errowinfo' => array('show'=>array(1, '显示'), 'hide'=>array(2, '不显示')),
		'brokertel'=> '4000-4000-44',
		'page_systemplate_enameid' => 1000,
		'CUSTOMPAGE_DATA_PATH'=>'/var/www/data',
		'page_template_code'=>array(
    	'header' =>'
						<!DOCTYPE html>
						<html lang="zh">
						<head>
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
						<meta name="renderer" content="webkit">
						<link rel="stylesheet" type="text/css" href="{%css%}" />
						<script type="text/javascript" src="/js/jquery-1.11.3.min.js"></script>
						<title>{%title%}</title>
						<meta name="keywords" content="{%keywords%}" >
						<meta name="description" content="{%information%}" >
						</head>
						<body>',
    	'footer'=>'</body></html>'),
		'page_template_status' => array('normal'=>array(1, '正常'), 'waitaudit'=>array(2, '待审核'),'edit'=>array(3,'修改中'), 'auditfalse'=>array(4, '审核未通过'), 'del'=>array(5, '删除')),
		'page_template_style' => array('system'=>array(1, '系统风格'), 'diy'=>array(2, '自定义')),
		'page_domain_message'=>array(
										    'unValidPostData'=>'非法操作',
										    'delInTenMin'=>'在取消一个域名的展示页后的10分钟内无法再将其设为展示页',
										    'alreadyInPageDomain'=>"您已经添加到展示页！",
										    'unValidTemplate'=>"模板不存在",
										    'notUserDomain'=>"域名不属于您",
										    'cnameNotEffectInEname'=>"是我司域名，请等待解析生效",
										    'cnameNotEffectNotInEname'=>"是其它注册商域名，请到相关注册商检查是否设置了CNAME解析，若设置了请等待解析生效",
										    'setCnameError'=>"设置CNAME记录失败，请到展示页列表点击重试或联系客服",
										    'forbidAddPageDomain'=>"禁止添加展示页",
										    'addPageDomainSuccess'=>"添加展示页成功",
										    'addPageDomainFalse'=>"添加展示页失败",
										    'createPageDomainFalse'=>"生成展示页失败，请到展示页列表点击重试或联系客服",
										    'waitAudit'=>"请等待审核",
											 'batchset'=>'批量提交成功，请等待解析生效，设置解析后超过24小时未生效，请联系客服处理。',
										    'cnnic'=>'CNNIC正在进行CN域名、中文域名系统维护工作，暂停相关服务'),

		'interfaceKey' => array('getEbuyEndLogic'=>'Auction@ename#$511',
				'getEbuyInfoLogic'=>'AuctionPrice#@ename#$511',
				'getDomainForBBSLogic'=>'AuctionPrice#@ename#$511',
				'getShopForBBSLogic'=>'AuctionPrice#@ename#$511',
				'getShopRecommendDomain'=>'AuctionPrice#@ename#$511',
				'getShopName'=>'AuctionPrice#@ename#$511',
				'getSellerGoodRatePerfect'=>'Shop@ename#$getPerfectSeller',
				'getTransRushRegDomainLogic'=>'Auction@ename#$rushReg',
				'getEbuyDomainLogic'=>'Auction@ename#$getebuydomain',
				'getTopicDomainLogic'=>'Auction@ename#$gettopicdomain',
				'getTopDomainLogic'=>'Auction@ename#$gettopdomain',
				'getRecommendExpiredDomainLogic'=>'Auction@ename#$getrecommendexpireddomain',
				'getBookAuctionDomainLogic'=>'Auction@ename#$getbookauctiondomain',
				'getOnSaleDomainCntLogic'=>'Auction@ename#$getonsaledomaincnt',
				'getBookDomainTransOrderIdLogic'=>'Auction@ename#$getbookdomaintransorderid',
				'custompageFileLogic'=>'Auction@ename#custompagefile',
				'getExpiredEnDomainLogic'=>'Auction@ename#getexipiredendomain'
		),
		'page_domain_status'=>array(
    'success'=>array(1, '生效'),
    'page'=>array(2, '待生成展示页'),
    'cname'=>array(3, '等待解析'),
    'hold'=>array(4, '等待审核'),
    'del'=>array(5, '删除'),
    'auditfalse'=>array(6, '审核未通过')
),
		'page_cname_server' => 'cs.ename.net',
		'page_edit_sid' => "#EnamePageDomainEdit!",
		'page_add_sid' => "#EnamePageDomainAdd!",
		'page_domain_reg'=>array('inename'=>array(1, '易名'), 'notinename'=>array(2, '其他注册商')),
		'page_domain_holdstatus'=>array(
    'normal'=>array(1, '正常'),
    'clienthold'=>array(2, 'clienthold'), 	//我司
    'serverhold'=>array(3, 'serverhold'), 	//我司
    'hold'=>array(4, '-'),		//非我司锁定
    'unkown'=>array(5, '-')		//我司未获取到domainStatus
),
		'page_add_sid' => "#EnamePageDomainAdd!",
		'page_edit_sid' => "#EnamePageDomainEdit!",
		'page_stat_type' => array(
				1=>array("<script src=\"http://js.users.51.la/%1\$d.js\" language=\"JavaScript\"></script>", "51LA"),
				2=>array(
						array(1=>"<script src=\"http://v1.cnzz.com/stat.php?id=%1\$d&web_id=%1\$d&show=pic\" language=\"JavaScript\" charset=\"gb2312\"></script>",
								2=>"<script src=\"http://s22.cnzz.com/z_stat.php?id=%1\$d&web_id=%1\$d\" language=\"JavaScript\"></script>", //新cnzz统计代码
						), "CNZZ"),
		),
		'page_ad_type' => array(
				1=>array('<script src="http://pagead2.googlesyndication.com/pagead/show_ads.js" language="JavaScript"></script>', 'Google'),
				2=>array('<script src="http://cpro.baidustatic.com/cpro/ui/c.js" language="JavaScript"></script>', 'Baidu')
		),
		'page_ad' => array(
				1=>array("<script type=\"text/javascript\"> google_ad_client=\"%1\$s\"; google_ad_slot=\"%2\$s\"; google_ad_width=%3\$d; google_ad_height=%4\$d; </script>", "google"),
				2=>array("<script type=\"text/javascript\"> var cpro_id = \"u%1\$d\"; </script>", "baidu")
		),
		'page_domains_maxnum'=>50,
		'avatar_upload_path'=>'avatar/',
		'card_limit_num'=>10,//名片限制数量,
		'defalut_html_whois'=>'<a href="http://baidu.com" class="inner1"><i></i><span>whois查询</span></a>',
		'defalut_html_domain'=>'<div class="inner2"><span>{%domain%}</span></div>',
		'defalut_html_domaindesc'=>'<div class="inner3">简介: {%domaindesc%}</div>',
		'defalut_html_errow'=>'<div class="inner1"><div>如果您对该域名感兴趣，请点击<a href="http://baidu.com">委托买卖</a>提供您的报价</div><div>If you would like to purchase this domain name,please <a href="http://baidu.com">click here</a> to make an offer.</div></div>',
		'defalut_html_trans'=>' <p class="dm_time" ><span>拍卖方式：竞价</span><span>当前价格：10000</span><span>剩余时间：3天6时30分20秒</span><a href="#" class="btn detail">查看详情</a></p>',
		'defalut_css_whois'=>'.inner1 {padding-top: 10px;padding-bottom: 10px;color: white;font-family: "Microsoft YaHei";font-size: 16px;font-weight: 400;line-height: 45px;text-decoration: none;} .inner1 i {display: inline-block;vertical-align: middle;width: 22px;height: 22px;background-image: url(/upload/templateimages/system1/search-icon.png);}',
		'defalut_css_domain'=>' .inner2 {color: white;font-family: Impact;font-size: 50px;font-weight: 400;line-height: 45px;}',
		'defalut_css_domaindesc'=>'.inner3 {margin-top: 15px;color: white;font-family: "Microsoft YaHei";font-size: 20px;font-weight: 700;line-height: 45px;}',
		'defalut_css_errow'=>'.inner1 {padding-top: 10px;padding-bottom: 10px;color: white;font-family: "Microsoft YaHei";font-size: 16px;font-weight: 400;line-height: 45px;text-decoration: none;}'		,
		'defalut_css_trans'=>'.dm_time{border:1px solid #cfcfcf;background:#fafafa;border-radius:10px;line-height:44px;padding-left:20px;position:relative;top:10px;left:10px;width:730px;margin-bottom:20px;overflow:hidden;*zoom:1;}.dm_time span{padding-right:30px;display:inline-block;*display:inline;*zoom:1;}'
);