<?php
namespace rule;

class RulerSeller
{

	/**
	 * 淘域名搜索
	 * 
	 * @return multitype:multitype:multitype:string number boolean NULL  multitype:string number boolean
	 */
	public static function search()
	{
		$rule = array(
				'get'=> array(
						'domainname'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'domaintld'=> array(\core\RuleBase::$checkNum,'域名分类后缀错误',0,100,false),
						'finishtime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'pricestart'=> array(\core\RuleBase::$checkNum,'起始价格错误',1,false,false),
						'priceend'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'domaingroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transtype'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false)));
		return $rule;
	}

	/**
	 * 已设置列表
	 * 
	 * @return multitype:multitype:multitype:string number boolean NULL  multitype:string number boolean
	 */
	public static function agented()
	{
		$rule = array(
				'get'=> array(
						'domainname'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'domaintld'=> array(\core\RuleBase::$checkNum,'域名分类后缀错误',0,100,false),
						'finishtime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'pricestart'=> array(\core\RuleBase::$checkNum,'起始价格错误',1,false,false),
						'priceend'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'domaingroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transtype'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'percentstart'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'percentend'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false)));
		return $rule;
	}

	/**
	 * 已售出列表
	 * 
	 * @return multitype:multitype:multitype:string number boolean NULL  multitype:string number boolean
	 */
	public static function sold()
	{
		$rule = array(
				'get'=> array(
						'domainname'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'domaintld'=> array(\core\RuleBase::$checkNum,'域名分类后缀错误',0,100,false),
						'finishtime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'priceend'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'domaingroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transtype'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'percentstart'=> array(\core\RuleBase::$checkNum,'起始佣金错误',1,false,false),
						'percentend'=> array(\core\RuleBase::$checkNum,'结束佣金错误',1,false,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false)));
		return $rule;
	}

	/**
	 * 未设置列表
	 * 
	 * @return multitype:multitype:multitype:string number boolean NULL  multitype:string number boolean
	 */
	public static function unseted()
	{
		$rule = array(
				'get'=> array(
						'domainname'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'domaintld'=> array(\core\RuleBase::$checkNum,'域名分类后缀错误',0,100,false),
						'finishtime'=> array(\core\RuleBase::$checkNum,'结束时间错误',0,20,false),
						'pricestart'=> array(\core\RuleBase::$checkNum,'起始价格错误',1,false,false),
						'priceend'=> array(\core\RuleBase::$checkNum,'结束价格错误',1,false,false),
						'domaingroup'=> array(\core\RuleBase::$checkNum,'域名分组错误',0,100,false),
						'transtype'=> array(\core\RuleBase::$checkNum,'交易类型错误',0,10,false),
						'limit_start'=> array(\core\RuleBase::$checkNum,'当前分页数错误',0,false,false),
						'sort'=> array(\core\RuleBase::$checkStr,'排序错误',1,false,false)));
		return $rule;
	}

	/**
	 * 设置分销
	 * 
	 * @return multitype:multitype:multitype:string number boolean
	 */
	public static function agent()
	{
		$rule = array(
				'post'=> array(
						'param'=> array(\core\RuleBase::$checkStr,'please enter your DomainName 0-71',0,71,false),
						'percent'=> array(\core\RuleBase::$checkNum,'佣金比例错误',0,100,false),
						'agreement'=>array(\core\RuleBase::$checkNum,'服务协议错误',1,1,false)));
		return $rule;
	}

	/**
	 * 店铺设置分销
	 * 
	 * @return multitype:multitype:multitype:string number boolean NULL  multitype:string boolean number  multitype:string number boolean
	 */
	public static function shopAgent()
	{
		$rule = array(
				'post'=> array('endDate'=> array(\core\RuleBase::$checkDate,'请输入类似1970-12-10',false,false,false),
						'percent'=> array(\core\RuleBase::$checkNum,'佣金比例错误',0,100,false),
						'id'=> array(\core\RuleBase::$checkNum,'店铺ID错误',0,false,false),
						'agreement'=>array(\core\RuleBase::$checkNum,'服务协议错误',1,1,false)));
		return $rule;
	}
	
	/**
	 * 删除店铺分销
	 * 
	 * @return multitype:multitype:multitype:string number boolean
	 */
	public static function deleteShop()
	{
		$rule = array(
				'post'=> array('agentId'=> array(\core\RuleBase::$checkNum,'店铺ID错误',0,false,false)));
		return $rule;
	}

	/**
	 * 删除域名分销
	 * 
	 * @return multitype:multitype:multitype:string number boolean
	 */
	public static function delete()
	{
		$rule = array(
				'post'=> array('domainAgentId'=> array(\core\RuleBase::$checkNum,'域名推广ID错误',0,false,false)));
		return $rule;
	}
	
	/**
	 * 修改域名分销
	 * 
	 * @return multitype:multitype:multitype:string number boolean NULL  multitype:string number boolean
	 */
	public static function edit()
	{
		$rule = array(
				'post'=> array('param'=> array(\core\RuleBase::$checkNum,'域名推广ID错误',0,false,false),
						'percent'=> array(\core\RuleBase::$checkNum,'佣金比例错误',0,100,false)));
		return $rule;
	}
	
	public static function update()
	{
		$rule = array(
				'post'=> array('id'=> array(\core\RuleBase::$checkNum,'域名推广ID错误',0,false,false)));
		return $rule;
	}
	
	public static function editShop()
	{
		$rule = array(
			'post'=>array('id'=> array(\core\RuleBase::$checkNum,'店铺推广ID错误',0,false,false),
			'percent'=> array(\core\RuleBase::$checkNum,'佣金比例错误',0,100,false),
			'endDate'=> array(\core\RuleBase::$checkDate,'请输入类似1970-12-10',false,false,false))
		);
		return $rule;
	}
	
	public static function check()
	{
		$rule = array(
				'post'=>array('id'=> array(\core\RuleBase::$checkNum,'店铺推广ID错误',0,false,false))
		);
		return $rule;	
		
	}
}