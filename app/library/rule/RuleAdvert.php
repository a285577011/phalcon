<?php
namespace rule;

class RuleAdvert
{

	public static function click()
	{
		$rule = array(
				'get'=> array(
						'c'=> array(\core\RuleBase::$checkStr,'参数错误',1,false,false)));
		return $rule;
	}

	public static function getAdInfo()
	{
		$rule = array(
				'get'=> array(
						'posId'=> array(\core\RuleBase::$checkNum,'ID错误',1,false)));
		return $rule;
	}

	public static function reviewAdInfo()
	{
		$rule = array(
				'post'=> array('AgentId'=> array(\core\RuleBase::$checkNumArr,'分销ID错误',1,false),
						'AgentType'=> array(\core\RuleBase::$checkNum,'平台ID错误',1,false),
						'StyleId'=> array(\core\RuleBase::$checkNum,'样式ID错误',1,false))
                      );
		return $rule;
	}
}
?>