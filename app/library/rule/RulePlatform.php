<?php
namespace rule;

class RulePlatform
{

	public static function addSite()
	{
		$rule = array(
				'post'=> array('siteName'=> array(\core\RuleBase::$checkStr,'网站名词长度错误',0,200),
						'site'=> array('Url','网站URL地址格式错误',0,false),
						'siteType'=> array(\core\RuleBase::$checkNum,'网站类型错误',1,25),
						'decr'=> array(\core\RuleBase::$checkStr,'网站描述错误',1,false,false)));
		return $rule;
	}

	public static function siteList()
	{
		$rule = array(
				'get'=> array('siteName'=> array(\core\RuleBase::$checkStr,'平台名字错误',0,200,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'分页量错误',0,false,false)));
		return $rule;
	}

	public static function updateIndex()
	{
		$rule = array('get'=> array('PlatformId'=> array(\core\RuleBase::$checkNum,'平台ID错误',1,false)));
		return $rule;
	}

	public static function updateSiteInfo()
	{
		$rule = array(
				'post'=> array('PlatformId'=> array(\core\RuleBase::$checkNum,'平台ID错误',1,false),
						'siteName'=> array(\core\RuleBase::$checkStr,'网站名词长度错误',0,200),
						'site'=> array('Url','网站URL地址格式错误',0,false),
						'siteType'=> array(\core\RuleBase::$checkNum,'网站类型错误',1,25),
						'decr'=> array(\core\RuleBase::$checkStr,'网站描述错误',1,false,false)));
		
		return $rule;
	}

	public static function addOther()
	{
		$rule = array(
				'post'=> array('Name'=> array(\core\RuleBase::$checkStr,'网站名词长度错误',0,200),
						'decr'=> array(\core\RuleBase::$checkStr,'网站描述错误',1,false,false)));
		return $rule;
	}

	public static function otherList()
	{
		$rule = array(
				'get'=> array('siteName'=> array(\core\RuleBase::$checkStr,'平台名字错误',0,200,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'分页量错误',0,false,false)));
		
		return $rule;
	}

	public static function updateOtherInfo()
	{
		$rule = array(
				'post'=> array('Name'=> array(\core\RuleBase::$checkStr,'网站名词长度错误',1,200),
						'decr'=> array(\core\RuleBase::$checkStr,'网站描述错误',1,false,false),
						'PlatformId'=> array(\core\RuleBase::$checkNum,'平台ID错误',1,false)));
		
		return $rule;
	}

	public static function getSiteByType()
	{
		$rule = array('post'=> array('PlatformType'=> array(\core\RuleBase::$checkNum,'平台类型错误',1,3)));
		
		return $rule;
	}

	public static function checkName()
	{
		$rule = array('post'=> array('Name'=> array(\core\RuleBase::$checkStr,'平台名字错误',0,false)),
				array('type'=> array(\core\RuleBase::$checkNum,'平台名字错误',1,3)));
		
		return $rule;
	}
}
?>