<?php
namespace rule;

class RuleAgentguests
{

	public static function domainAgent()
	{
		$rule = array(
				'get'=> array(
						 'DomainName'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'topic'=> array(\core\RuleBase::$checkNum,'专题错误',0,false,false),
						'FinishTime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'StartPrice'=> array(\core\RuleBase::$checkNum,'起始价格错误',1,false,false),
						'EndPrice'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'Group'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transType'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false)));
		return $rule;
	}

	public static function shopAgent()
	{
		$rule = array(
				'get'=> array(
						'ShopName'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-200',0,200,false),
						'startCredit'=> array(\core\RuleBase::$checkNum,'起始信用',1,false,false),
						'endCredit'=> array(\core\RuleBase::$checkNum,'结束信用',1,false,false),
						'startGoodRating'=> array(\core\RuleBase::$checkNum,'起始好评错误',1,false,false),
						'endGoodRating'=> array(\core\RuleBase::$checkNum,'结束好评错误',1,false,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false)));
		return $rule;
	}

	public static function autoAgentFirst()
	{
		$rule = array(
				'post'=> array('Tld'=> array(\core\RuleBase::$checkNumArr,'域名分类后缀错误',0,100,false),
						'FinishTime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'StartPrice'=> array(\core\RuleBase::$checkNum,'起始价格错误',1,false,false),
						'EndPrice'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'Group'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transType'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false)));
		return $rule;
	}

	public static function autoAgentSecond()
	{
		$rule = array(
				'post'=> array('Tld'=> array(\core\RuleBase::$checkStr,'域名分类后缀错误',0,false,false),
						'FinishTime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'StartPrice'=> array(\core\RuleBase::$checkNum,'起始价格错误',1,false,false),
						'EndPrice'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'Group'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transType'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'PlatformId'=> array(\core\RuleBase::$checkNum,'平台ID错误',0,false,false),
						'PlatformType'=> array(\core\RuleBase::$checkNum,'平台类型错误',1,3,false),
						'StyleId'=> array(\core\RuleBase::$checkNum,'样式ID错误',1,false,false),
						'Agreement'=> array(\core\RuleBase::$checkNum,'协议值错误',1,1)));
		
		return $rule;
	}

	public static function spreadAgent()
	{
		$rule = array(
				'post'=> array('PlatformId'=> array(\core\RuleBase::$checkNum,'平台ID错误',1,false,false),
						'PlatformType'=> array(\core\RuleBase::$checkNum,'平台类型错误',1,3,false),
						'StyleId'=> array(\core\RuleBase::$checkNum,'样式ID错误',1,false,false),
						'AgentId'=> array(\core\RuleBase::$checkNumArr,'分销ID错误',1,false,false),
						'AgentType'=> array(\core\RuleBase::$checkNum,'分销类型',1,3,false),
						'Agreement'=> array(\core\RuleBase::$checkNum,'协议值错误',1,1)),
				'get'=> array('PlatformId'=> array(\core\RuleBase::$checkNum,'平台ID错误',1,false,false),
						'PlatformType'=> array(\core\RuleBase::$checkNum,'平台类型错误',1,3,false),
						'StyleId'=> array(\core\RuleBase::$checkNum,'样式ID错误',1,false,false),
						'AgentId'=> array(\core\RuleBase::$checkNumArr,'分销ID错误',1,false,false),
						'AgentType'=> array(\core\RuleBase::$checkNum,'分销类型',1,3,false),
						'Agreement'=> array(\core\RuleBase::$checkNum,'协议值错误',1,1)));
		
		return $rule;
	}

	public static function platformStatistics()
	{
		$rule = array(
				'get'=> array('type'=> array(\core\RuleBase::$checkNum,'渠道类型错误',1,3),
						'startDate'=> array(\core\RuleBase::$checkStr,'起始时间错误',1,false,false),
						'endDate'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'name'=> array(\core\RuleBase::$checkStr,'名称错误',0,false,false)));
		return $rule;
	}

	public static function spreadDomain()
	{
		$rule = array(
				'get'=> array(
						'DomainName'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'topic'=> array(\core\RuleBase::$checkNum,'专题错误错误',1,false,false),
						'startDate'=> array(\core\RuleBase::$checkStr,'起始时间错误',1,false,false),
						'endDate'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'status'=> array(\core\RuleBase::$checkNum,'状态错误',- 1,1,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false),
						'PlatformType'=> array(\core\RuleBase::$checkNum,'渠道类型错误',1,3,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)));
		return $rule;
	}

	public static function spreadDetail()
	{
		$rule = array(
				'post'=> array('Name'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'startDate'=> array(\core\RuleBase::$checkStr,'起始时间错误',1,false,false),
						'endDate'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'status'=> array(\core\RuleBase::$checkNum,'状态错误',- 1,1,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false),
						'PlatformType'=> array(\core\RuleBase::$checkNum,'渠道类型错误',1,3,false),
						'agentType'=> array(\core\RuleBase::$checkNum,'分销类型错误',1,2),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)));
		return $rule;
	}

	public static function spreadShop()
	{
		$rule = array(
				'get'=> array('Name'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'StartCommission'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'startDate'=> array(\core\RuleBase::$checkStr,'起始时间错误',1,false,false),
						'endDate'=> array(\core\RuleBase::$checkStr,'结束时间错误',1,false,false),
						'EndCommission'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'status'=> array(\core\RuleBase::$checkNum,'状态错误',- 1,1,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false),
						'PlatformType'=> array(\core\RuleBase::$checkNum,'渠道类型错误',1,3,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false)));
		return $rule;
	}
}
?>