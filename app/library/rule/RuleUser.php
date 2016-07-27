<?php
namespace rule;

class RuleUser
{

	public static function finance()
	{
		$rule = array('get'=> array('limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)));
		return $rule;
	}

	public static function orderDetail()
	{
		$rule = array(
				'get'=> array('OrderType'=> array(\core\RuleBase::$checkNum,'订单状态',0,3,false),
						'topic'=> array(\core\RuleBase::$checkNum,'专题错误',0,false,false),
						'startDate'=> array(\core\RuleBase::$checkStr,'起始时间错误',1,false,false),
						'endDate'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)));
		return $rule;
	}
	public function doTurnOut(){
		$rule = array(
				'post'=> array('price'=> array(\core\RuleBase::$checkNum,'转出金额错误',0.01,false)));
		return $rule;
	}
	public static function turnOut()
	{
		$rule = array(
				'get'=> array('startDate'=> array(\core\RuleBase::$checkStr,'起始时间错误',1,false,false),
						'endDate'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)));
		return $rule;
	}
	public static function changeIsAgree()
	{
		$rule = array(
				'post'=> array('status'=> array(\core\RuleBase::$checkNum,'状态错误',1,1)));
		return $rule;
	}
	public static function guideOne()
	{
		$rule = array(
				'get'=> array('status'=> array(\core\RuleBase::$checkStr,'类型错误',1,3,false)));
		return $rule;
	}
	
	public static function message()
	{
		$rule = array(
			'get' => array('status' => array(\core\RuleBase::$checkNum,'消息类型',3,5,false,
								'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)))
		);
		
		return $rule;
	}
	
	public static function editMsg()
	{
		$rule = array(
			'post' => array('messageId' => array(\core\RuleBase::$checkNumArr,'消息ID错误',0,false,false),
									'status'=>array(\core\RuleBase::$checkNum,'修改消息的类型错误',0,5,false))
		);
		return $rule;
	}
}
?>