<?php
namespace rule;

class RuleFaq
{

	public static function index()
	{
		$rule = array(
				'get'=> array('limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'type'=> array(\core\RuleBase::$checkNum,'faq类型错误',0,20,false),
						'keyWord'=> array(\core\RuleBase::$checkStr,'关键词搜索错误',0,false,false)));
		return $rule;
	}
}