<?php
namespace rule;

class RuleStatic
{

	public static function dofarmStatic()
	{
		$rule = array(
				'get'=> array(
						'starttime'=> array(\core\RuleBase::$checkStr,'开始时间错误',1,10,false),
				'endtime'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,10,false),
				'dgroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',1,1000,false),
				'ttype'=> array(\core\RuleBase::$checkNum,'交易类型错误',1,10,false)));
		return $rule;
	}
	public static function farmerStatic()
	{
		$rule = array(
				'get'=> array(
						'starttime'=> array(\core\RuleBase::$checkStr,'开始时间错误',1,10,false),
						'endtime'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,10,false),
						'dgroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',1,1000,false),
						'ttype'=> array(\core\RuleBase::$checkNum,'交易类型错误',1,10,false)));
		return $rule;
	}
	public static function exportFarmer()
	{
		$rule = array(
				'get'=> array(
						'starttime'=> array(\core\RuleBase::$checkStr,'开始时间错误',1,10,false),
						'endtime'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,10,false),
						'dgroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',1,1000,false),
						'ttype'=> array(\core\RuleBase::$checkNum,'交易类型错误',1,10,false)));
		return $rule;
	}
	public static function doGuestStatic()
	{
		$rule = array(
				'get'=> array(
						'starttime'=> array(\core\RuleBase::$checkStr,'开始时间错误',1,10,false),
						'endtime'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,10,false),
						'ptype'=> array(\core\RuleBase::$checkNum,'推广渠道错误',1,10,false),
						'stype'=> array(\core\RuleBase::$checkNum,'推广形式错误',1,10,false)));
		return $rule;
	}
	public static function guestStatic()
	{
		$rule = array(
				'get'=> array(
						'starttime'=> array(\core\RuleBase::$checkStr,'开始时间错误',1,10,false),
						'endtime'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,10,false),
						'ptype'=> array(\core\RuleBase::$checkNum,'推广渠道错误',1,10,false),
						'stype'=> array(\core\RuleBase::$checkNum,'推广形式错误',1,10,false)));
		return $rule;
	}
	public static function exportGuest()
	{
		$rule = array(
				'get'=> array(
						'starttime'=> array(\core\RuleBase::$checkStr,'开始时间错误',1,10,false),
						'endtime'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,10,false),
						'ptype'=> array(\core\RuleBase::$checkNum,'推广渠道错误',1,10,false),
						'stype'=> array(\core\RuleBase::$checkNum,'推广形式错误',1,10,false)));
		return $rule;
	}
}
?>