<?php
namespace rule;

class RuleCustompage
{

	public static function addContact()
	{
		$rule = array(
				'post'=> array(
						'username'=> array(\core\RuleBase::$checkStr,'用户姓名错误',1,25,false),
						'cardname'=> array(\core\RuleBase::$checkStr,'名片名称长度错误',1,50,false),
						'phone'=> array(\core\RuleBase::$checkStr,'电话错误',1,20,false),
						'email'=> array(\core\RuleBase::$checkEmail,'邮箱错误',1,false,false),
						'qq'=> array(\core\RuleBase::$checkNum,'QQ错误',1,999999999999,false),
						'desc'=> array(\core\RuleBase::$checkStr,'描述错误',1,50,false),
						'avatar'=> array(\core\RuleBase::$checkStr,'头像错误',1,100,false)));
		return $rule;
	}
	public static function getContact()
	{
		$rule = array(
				'post'=> array(
						'cid'=> array(\core\RuleBase::$checkNum,'名片ID有误',1,false,false)));
		return $rule;
	}
	public static function editContact()
	{
		$rule = array(
				'post'=> array(
						'username'=> array(\core\RuleBase::$checkStr,'用户姓名错误',1,25,false),
						'cardname'=> array(\core\RuleBase::$checkStr,'名片名称长度错误',1,50,false),
						'phone'=> array(\core\RuleBase::$checkStr,'电话错误',1,20,false),
						'email'=> array(\core\RuleBase::$checkEmail,'邮箱错误',1,false,false),
						'qq'=> array(\core\RuleBase::$checkNum,'QQ错误',1,999999999999,false),
						'desc'=> array(\core\RuleBase::$checkStr,'描述错误',1,50,false),
						'cid'=> array(\core\RuleBase::$checkNum,'名片ID有误',1,false,false),
						'avatar'=> array(\core\RuleBase::$checkStr,'头像错误',1,100,false)));
		return $rule;
	}
	public static function delContact()
	{
		$rule = array(
				'get'=> array(
						'cid'=> array(\core\RuleBase::$checkNum,'名片ID有误',1,false,false)));
		return $rule;
	}
	public static function addSeo()
	{
		$rule = array(
				'post'=> array(
						'title'=> array(\core\RuleBase::$checkStr,'标题错误',1,255,false),
						'seoname'=> array(\core\RuleBase::$checkStr,'名称长度错误',1,50,false),
						'kword'=> array(\core\RuleBase::$checkStr,'关键词错误',1,250,false),
						'desc'=> array(\core\RuleBase::$checkStr,'描述错误',1,255,false)));
		return $rule;
	}
	public static function getSeo()
	{
		$rule = array(
				'post'=> array(
						'sid'=> array(\core\RuleBase::$checkNum,'名片ID有误',1,false,false)));
		return $rule;
	}
	public static function editSeo()
	{
		$rule = array(
				'post'=> array(
						'title'=> array(\core\RuleBase::$checkStr,'标题错误',1,255,false),
						'seoname'=> array(\core\RuleBase::$checkStr,'名称长度错误',1,50,false),
						'kword'=> array(\core\RuleBase::$checkStr,'关键词错误',1,250,false),
						'sid'=> array(\core\RuleBase::$checkNum,'名片ID有误',1,false,false),
						'desc'=> array(\core\RuleBase::$checkStr,'描述错误',1,255,false)));
		return $rule;
	}
	public static function delSeo()
	{
		$rule = array(
				'get'=> array(
						'sid'=> array(\core\RuleBase::$checkNum,'名片ID有误',1,false,false)));
		return $rule;
	}
	public static function doAddtemplate()
	{
		$rule = array(
				'post'=> array(
						'templateName'=> array(\core\RuleBase::$checkStr,'模板名称有误',1,100,false),
						'statType'=> array(\core\RuleBase::$checkNum,'统计类型',1,5,false),
						'statId'=> array(\core\RuleBase::$checkNum,'统计ID有误',1,false,false),
						'adType'=> array(\core\RuleBase::$checkNum,'广告类型有误',1,false,false),
						'adId'=> array(\core\RuleBase::$checkNum,'广告ID有误',1,false,false),
						'templateId'=> array(\core\RuleBase::$checkNum,'模板ID有误',1,false,false),
						'ucid'=> array(\core\RuleBase::$checkNum,'米掌柜名片ID有误',1,false,false),
						'seoid'=> array(\core\RuleBase::$checkNum,'SEOID有误',1,false,false),
						'type'=> array(\core\RuleBase::$checkNum,'模板类型有误',1,5,false),
						'pubid'=> array(\core\RuleBase::$checkStr,'adclient有误',1,100,false),
						'slotid'=> array(\core\RuleBase::$checkStr,'adslot有误',1,100,false),
						'adwidth'=> array(\core\RuleBase::$checkNum,'广告宽度有误',1,false,false),
						'enameType'=> array(\core\RuleBase::$checkNum,'推广位类型有误',1,false,false),
						'enameCode'=> array(\core\RuleBase::$checkStr,'推广位值有误',1,100,false),
						'adheight'=> array(\core\RuleBase::$checkNum,'广告高度有误',1,false,false),
						'htmlCode'=> array(\core\RuleBase::$checkStr,'htmlCode有误',1,false,false),
						'cssCode'=> array(\core\RuleBase::$checkStr,'cssCode有误',1,false,false)));
		return $rule;
	}
	public static function modelView()
	{
		$rule = array(
				'post'=> array(
						'templateName'=> array(\core\RuleBase::$checkStr,'模板名称有误',1,100,false),
						'statType'=> array(\core\RuleBase::$checkNum,'统计类型',1,5,false),
						'statId'=> array(\core\RuleBase::$checkNum,'统计ID有误',1,false,false),
				'adType'=> array(\core\RuleBase::$checkNum,'广告类型有误',1,false,false),
				'adId'=> array(\core\RuleBase::$checkNum,'广告ID有误',1,false,false),
				'templateid'=> array(\core\RuleBase::$checkNum,'模板ID有误',1,false,false),
				'dataid'=> array(\core\RuleBase::$checkNum,'模板ID有误',1,false,false),
				'Ucid'=> array(\core\RuleBase::$checkNum,'米掌柜名片ID有误',1,false,false),
				'Seoid'=> array(\core\RuleBase::$checkNum,'SEOID有误',1,false,false),
				'typeid'=> array(\core\RuleBase::$checkNum,'模板类型有误',1,5,false),
				'pubid'=> array(\core\RuleBase::$checkStr,'adclient有误',1,100,false),
				'slotid'=> array(\core\RuleBase::$checkStr,'adslot有误',1,100,false),
				'adwidth'=> array(\core\RuleBase::$checkNum,'广告宽度有误',1,false,false),
				'adheight'=> array(\core\RuleBase::$checkNum,'广告高度有误',1,false,false),
				'htmlCode'=> array(\core\RuleBase::$checkStr,'htmlCode有误',1,false,false),
				'cssCode'=> array(\core\RuleBase::$checkStr,'cssCode有误',1,false,false),
						'templateName'=> array(\core\RuleBase::$checkStr,'用户姓名错误',1,25,false),
						'cardname'=> array(\core\RuleBase::$checkStr,'名片名称',1,25,false),
						'phone'=> array(\core\RuleBase::$checkStr,'电话错误',1,20,false),
				'email'=> array(\core\RuleBase::$checkEmail,'邮箱错误',1,false,false),
				'qq'=> array(\core\RuleBase::$checkNum,'QQ错误',1,999999999999,false),
				'desc'=> array(\core\RuleBase::$checkStr,'描述错误',1,100,false),
				'avatar'=> array(\core\RuleBase::$checkStr,'头像错误',1,100,false)));
		return $rule;
	}
	public static function addPagedomain()
	{
		$rule = array(
				'post'=> array(
						'domainName'=> array(\core\RuleBase::$checkStr,'域名错误',1,false,false)));
		return $rule;
	}
	public static function index()
	{
		$rule = array(
				'get'=> array(
						'domainName'=> array(\core\RuleBase::$checkStr,'域名错误',1,false,false),
		'status'=> array(\core\RuleBase::$checkNum,'生效状态错误',0,6,false),
		'templateId'=> array(\core\RuleBase::$checkNum,'模板Id错误',0,false,false),
		'transInfo'=> array(\core\RuleBase::$checkNum,'交易信息状态错误',0,2,false),
		'errowInfo'=> array(\core\RuleBase::$checkNum,'经纪中介状态错误',0,2,false),
		'reger'=> array(\core\RuleBase::$checkNum,'注册商错误',0,2,false),
		'holdStatus'=> array(\core\RuleBase::$checkNum,'hold状态错误',0,3,false),
		'limit_start'=> array(\core\RuleBase::$checkNum,'页码错误',0,false,false)));
		return $rule;
	}
	public static function templatelist()
	{
		$rule = array(
				'get'=> array(
						'templateType'=> array(\core\RuleBase::$checkNum,'模板类型',1,false,false),
						'status'=> array(\core\RuleBase::$checkNum,'生效状态错误',0,6,false),
						'templateName'=> array(\core\RuleBase::$checkStr,'模板名称有误',0,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'页码错误',0,false,false)));
		return $rule;
	}
	public static function deltemplate()
	{
		$rule = array(
				'get'=> array(
						'id'=> array(\core\RuleBase::$checkNum,'模板ID错误',0,false,false)));
		return $rule;
	}
	public static function settemplate()
	{
		$rule = array(
				'get'=> array(
						'id'=> array(\core\RuleBase::$checkNum,'模板ID错误',0,false,false)));
		return $rule;
	}
	public static function addTemplate()
	{
		$rule = array(
				'get'=> array(
						'type'=> array(\core\RuleBase::$checkNum,'模板类型错误',1,false,false),
						'templateid'=> array(\core\RuleBase::$checkNum,'模板ID错误',0,false,false)));
		return $rule;
	}
	public static function preview()
	{
		$rule = array(
				'get'=> array(
						'id'=> array(\core\RuleBase::$checkNum,'ID错误',0,false,false)));
		return $rule;
	}
	public static function dosettemplate()
	{
		$rule = array(
				'post'=> array(
						'templateName'=> array(\core\RuleBase::$checkStr,'模板名称有误',1,100,false),
						'statType'=> array(\core\RuleBase::$checkNum,'统计类型',1,5,false),
						'statId'=> array(\core\RuleBase::$checkNum,'统计ID有误',1,false,false),
				'adType'=> array(\core\RuleBase::$checkNum,'广告类型有误',1,false,false),
				'adId'=> array(\core\RuleBase::$checkNum,'广告ID有误',1,false,false),
				'templateId'=> array(\core\RuleBase::$checkNum,'模板ID有误',1,false,false),
				'dataid'=> array(\core\RuleBase::$checkNum,'模板ID有误',1,false,false),
				'ucid'=> array(\core\RuleBase::$checkNum,'米掌柜名片ID有误',1,false,false),
				'seoid'=> array(\core\RuleBase::$checkNum,'SEOID有误',1,false,false),
				'type'=> array(\core\RuleBase::$checkNum,'模板类型有误',1,5,false),
				'pubid'=> array(\core\RuleBase::$checkStr,'adclient有误',1,100,false),
				'slotid'=> array(\core\RuleBase::$checkStr,'adslot有误',1,100,false),
				'adwidth'=> array(\core\RuleBase::$checkNum,'广告宽度有误',1,false,false),
				'enameType'=> array(\core\RuleBase::$checkNum,'推广位类型有误',1,5,false),
				'enameCode'=> array(\core\RuleBase::$checkStr,'推广位值有误',1,100,false),
				'adheight'=> array(\core\RuleBase::$checkNum,'广告高度有误',1,false,false),
				'htmlCode'=> array(\core\RuleBase::$checkStr,'htmlCode有误',1,false,false),
				'cssCode'=> array(\core\RuleBase::$checkStr,'cssCode有误',1,false,false)));
		return $rule;
	}
	public static function pageview()
	{
		$rule = array(
				'get'=> array(
						'templateid'=> array(\core\RuleBase::$checkNum,'模板ID错误',0,false,false)));
		return $rule;
	}
	public static function doretry()
	{
		$rule = array(
				'get'=> array(
						'id'=> array(\core\RuleBase::$checkNum,'展示页ID错误',0,false,false)));
		return $rule;
	}
	public static function setPageDomain()
	{
		$rule = array(
				'get'=> array(
						'id'=> array(\core\RuleBase::$checkNumArr,'展示页ID错误',0,false,false)));
		return $rule;
	}
	public static function delPageDomain()
	{
		$rule = array(
				'get'=> array(
						'id'=> array(\core\RuleBase::$checkNumArr,'展示页ID错误',0,false,false)));
		return $rule;
	}
	public static function autoAgentForUser()
	{
		$rule = array(
				'get'=> array(
						'codeid' => array(\core\RuleBase::$checkNum,'推广ID错误',0,false,false),
						'templateid'=> array(\core\RuleBase::$checkNum,'模板ID错误',0,false,false)));
		return $rule;
	}
	public static function getTemplateBySid()
	{
		$rule = array(
				'get'=> array(
						'styleid'=> array(\core\RuleBase::$checkNum,'模板ID错误',0,false,false)));
		return $rule;
	}
	public static function dosetpagedomain()
	{
		$rule = array(
				'post'=> array(
						'domainName'=> array(\core\RuleBase::$checkStrArr,'域名格式错误',0,72,false)));
		return $rule;
	}
	public static function doaddpagedomain()
	{
		$rule = array(
				'post'=> array(
						'domainName'=> array(\core\RuleBase::$checkStrArr,'域名格式错误',0,72,false)));
		return $rule;
	}
}
?>