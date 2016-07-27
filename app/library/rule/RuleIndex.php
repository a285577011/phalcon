<?php
namespace rule;

class RuleIndex
{

	public static function index()
	{
		$rule = array(
				'post'=> array('myname'=> array(\core\RuleBase::$checkStr,'please enter your myname 5-20',5,20,false),
						'user_email'=> array(\core\RuleBase::$checkEmail,'please email',3,70,false),
						'name'=> array(\core\RuleBase::$checkStrArr,'please enter your name 5-20',5,20,false)),
				'url'=> array('year'=> array(\core\RuleBase::$checkNum,'year error',1000,2099,false),
						'month'=> array(\core\RuleBase::$checkNum,'month error',1,12,false),
						'three'=> array(\core\RuleBase::$checkNum,'three error',1,3,false)),
				'get'=> array('a'=> array(\core\RuleBase::$checkStr,'please enter a str',3,70,false)));
		return $rule;
	}
}
?>