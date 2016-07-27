<?php
namespace lib\agent;
use logic\ad\Advert;
use core\ModelBase;

class AgentGuestsLib
{

	/**
	 * 域名搜索处理
	 *
	 * @param unknown $domainName
	 * @return boolean mixed
	 */
	public static function setDomainKey($domainName)
	{
		$str2 = trim(preg_replace("/[^0-9a-zA-Z\x{4e00}-\x{9fa5}\.@_\-', ]/u", "", $domainName));
		if('' === $str2 and trim($domainName) !== '')
		{
			return false;
		}
		if($domainName)
		{
			$domainName = preg_replace('/(http[s]?:\/\/)/', '', $domainName);
			$domainName = preg_replace('/。/', '.', $domainName);
			$domainName = str_replace(array(",","，"), ",", $domainName);
			return $domainName;
		}
		return false;
	}

	/**
	 * 后缀搜索处理
	 *
	 * @param unknown $tld
	 * @return unknown boolean
	 */
	public static function setTldKey($tld)
	{
		if($tld)
		{
			$domainTLD = array();
			$tld = (array)$tld;
			foreach($tld as $val)
			{
				if(! intval($val))
				{
					continue;
				}
				$domainTLD = array_merge(explode(',', \core\Config::item('newtld')->toArray()[intval($val)][1]), 
					$domainTLD);
			}
			return $domainTLD;
		}
		return false;
	}

	/**
	 * 域名与后缀同时搜索处理
	 *
	 * @param unknown $domainName
	 * @param unknown $tld
	 * @return multitype:Ambigous <> Ambigous <\lib\agent\unknown, boolean,
	 * unknown> |multitype:Ambigous <boolean, \lib\agent\mixed, mixed> Ambigous
	 * <\lib\agent\unknown, boolean, unknown> |multitype:boolean Ambigous <>
	 * |multitype:Ambigous <> unknown
	 */
	public static function setDomainAndTld($domainName, $tld)
	{
		$domainName = self::setDomainKey($domainName);
		$domainArr = explode('.', $domainName);
		if($tld)
		{ // 如果有后缀
			return array($domainArr[0],array_filter($tld));
		}
		else
		{ // 无后缀
		  // 如果表单没传后缀，则分析传过来的字符串是否有后缀
			if(strpos($domainName, '.') === FALSE)
			{
				// 无后缀
				return array($domainName,false);
			}
			$Tld = str_replace("{$domainArr[0]}", '', $domainName);
			/*
			 * $tldConfig = array();
			 * foreach(\core\Config::item('newtld')->toArray() as $val) {
			 * $tldConfig[$val[0]] = $val[1]; } if(! array_key_exists($Tld,
			 * $tldConfig)) { return array($domainArr[0],false); } $tldVal =
			 * $tldConfig[$Tld]; $domainTLD = explode(',', $tldVal); $domainTLD
			 * = count($domainTLD) == 2? $domainTLD: $domainTLD[0];
			 */
			if($domainTLD = array_search($Tld, \core\Config::item('ts_domaintld')->toArray()))
			{
				return array($domainArr[0],$domainTLD);
			}
			return array($domainArr[0],null);
		}
	}

	/**
	 * 结束时间搜索处理
	 *
	 * @param unknown $finishTime
	 * @return number boolean
	 */
	public function setFinishTimedKey($finishTime)
	{
		if($finishTime)
		{
			
			$temp = \core\Config::item('finishtime')->toArray()[$finishTime][1];
			return time() + $temp;
		}
		return false;
	}

	/**
	 * 获取搜索的域名长度及系统分组
	 *
	 * @param array $param 搜索的数组参数
	 * @param class $tlLib transLib对象
	 * @return unknown
	 * @author huangyy
	 */
	public static function getDomainGroup($group)
	{
		$sysGroupOne = $sysGroupTwo = $domainLen = null;
		if($group)
		{
			list($sysGroupOne, $sysGroupTwo, $domainLen) = self::getSysGroup($group);
		}
		return array($sysGroupTwo,$sysGroupOne,$domainLen);
	}

	/**
	 * 获取域名分组和长度
	 *
	 * @param unknown $type 系统分组值
	 * @return array
	 * @author huangyy
	 */
	public static function getSysGroup($group)
	{
		$sysGroupOne = $sysGroupTwo = $domainLen = null;
		$tsDomainGroup = \core\Config::item('domaingroup')->toArray(); // 读取分组配置
		if($group)
		{
			$temp = $tsDomainGroup[$group][1];
			if(isset($temp['rank']))
			{
				$sysGroupOne = array($temp['rank'][0],$temp['rank'][1]);
				if(isset($temp['rank'][2]))
				{
					$domainLen = $temp['rank'][2];
				}
			}
			elseif(isset($temp['sysone']))
			{
				$sysGroupOne = $temp['sysone'];
			}
			elseif(isset($temp['systwo']))
			{
				$sysGroupTwo = $temp['systwo'];
			}
			unset($temp);
		}
		return array($sysGroupOne,$sysGroupTwo,$domainLen);
	}

	/**
	 * 过滤排序
	 *
	 * @param unknown $sort
	 * @param array $allowfiled
	 * @param array $orderRule
	 * @return boolean multitype:unknown
	 */
	public function setOrder($sort, array $allowfiled, array $orderRule)
	{
		if($sort)
		{
			if(count(explode('-', $sort)) != 2)
			{
				return false;
			}
			list($orderField, $order) = explode('-', $sort);
			if(! in_array($orderField, $allowfiled) || ! in_array(strtolower($order), $orderRule))
			{
				return false;
			}
			return array($orderField,$order);
		}
		return false;
	}

	/**
	 * 设置范围搜索条件
	 *
	 * @param unknown $start
	 * @param unknown $end
	 * @param string $zero
	 * @return multitype:unknown number |number|multitype:number unknown
	 * |boolean
	 */
	public function setRang($start, $end, $zero = false)
	{
		if($zero)
		{
			$start or $start = 0;
		}
		else
		{
			$start or $start = 1;
		}
		if($start && $end)
		{
			if($end >= $start)
			{
				return array($start,$end);
			}
			return $start;
		}
		elseif($start)
		{
			return $start;
		}
		elseif($end)
		{
			return array(0,$end);
		}
		return false;
	}

	/**
	 * 格式化域名分销ID
	 *
	 * @param unknown $data
	 * @return multitype:NULL
	 */
	public function FormatByDomainAgentId($data)
	{
		$id = array();
		foreach($data as $value)
		{
			$id[] = $value->DomainAgentId;
		}
		return $id;
	}

	/**
	 * 格式化分销ID
	 *
	 * @param unknown $AgentId
	 * @return multitype:number |array|boolean
	 */
	public function filterAgentId($AgentId)
	{
		$newVal = array();
		if(is_array($AgentId) && ! empty($AgentId))
		{
			return array_map('intval', $AgentId);
		}
		elseif($AgentId)
		{
			return (array)intval($AgentId);
		}
		return false;
	}

	/**
	 * 设置JSON格式
	 *
	 * @param unknown $data
	 * @return string boolean
	 */
	public function setJson($data)
	{
		if(is_array($data) && ! empty($data))
		{
			return '{"start":' . $data[0] . ',"end":' . $data[1] . '}';
		}
		elseif($data)
		{
			return '{"start":' . $data . '}';
		}
		return false;
	}

	/**
	 * 格式域名分销数据
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	public function formatDomainAgent($data)
	{
		foreach($data as $key => $val)
		{
			$data[$key]->TransTypeCn = \core\Config::item('TransType')->toArray()[$data[$key]->TransType];
			$data[$key]->FinishTime = AgentManagerLib::newTimeToDHIS($val->FinishTime);
			$data[$key]->TransId=\common\common\Common::getSortUrl($val->TransId);
		}
		return $data;
	}

	/**
	 * 格式店铺分销数据
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	public function formatShopAgent($data)
	{
		foreach($data as $key => $val)
		{
			$data[$key]->FinishTime = AgentManagerLib::newTimeToDHIS($val->FinishTime);
		}
		return $data;
	}

	/**
	 * 格式广告URL
	 *
	 * @param unknown $data
	 * @param unknown $AgentType
	 * @return unknown
	 */
	public function formatAdData($data, $AgentType)
	{
		$advertLogic = new Advert();
		$idKey = $AgentType == 1? 'DomainAgentId': 'ShopAgeId';
		foreach($data as $key => $val)
		{
			$data[$key]->url = $advertLogic->makeAgentUrl($data[$key]->$idKey, $AgentType, 0);
		}
		return $data;
	}

	/**
	 * 设置排序符号
	 *
	 * @param unknown $orderField
	 * @param unknown $orderby
	 * @return multitype:string
	 */
	public function setSort($orderField, $orderby)
	{
		$priceSymbol = $finishTimeSymbol = $commissionSymbol = '';
		$priceSort = $finishTimeSort = $commissionSort = 'desc';
		switch($orderField)
		{
			case 'price':
				if($orderby == 'asc')
				{
					$priceSymbol = '↑';
					$priceSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$priceSymbol = '↓';
					$priceSort = 'asc';
				}
				break;
			case 'percent':
				if($orderby == 'asc')
				{
					$commissionSymbol = '↑';
					$commissionSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$commissionSymbol = '↓';
					$commissionSort = 'asc';
				}
				break;
			case 'finishtime':
				if($orderby == 'asc')
				{
					$finishTimeSymbol = '↑';
					$finishTimeSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$finishTimeSymbol = '↓';
					$finishTimeSort = 'asc';
				}
				break;
			default:
				break;
		}
		return array($priceSymbol,$finishTimeSymbol,$commissionSymbol,$priceSort,$finishTimeSort,$commissionSort);
	}

	/**
	 * 设置排序符号(店铺)
	 *
	 * @param unknown $orderField
	 * @param unknown $orderby
	 * @return multitype:string
	 */
	public function setShopSort($orderField, $orderby)
	{
		$domainnumSymbol = $goodratingSymbol = $creditSymbol = $percentSymbol = $finishtimeSymbol = '';
		$finishtimeSort = $domainnumSort = $goodratingSort = $creditSort = $percentSort = 'desc';
		switch($orderField)
		{
			case 'domainnum':
				if($orderby == 'asc')
				{
					$domainnumSymbol = '↑';
					$domainnumSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$domainnumSymbol = '↓';
					$domainnumSort = 'asc';
				}
				break;
			case 'goodrating':
				if($orderby == 'asc')
				{
					$goodratingSymbol = '↑';
					$goodratingSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$goodratingSymbol = '↓';
					$goodratingSort = 'asc';
				}
				break;
			case 'credit':
				if($orderby == 'asc')
				{
					$creditSymbol = '↑';
					$creditSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$creditSymbol = '↓';
					$creditSort = 'asc';
				}
				break;
			case 'percent':
				if($orderby == 'asc')
				{
					$percentSymbol = '↑';
					$percentSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$percentSymbol = '↓';
					$percentSort = 'asc';
				}
				break;
			case 'finishtime':
				if($orderby == 'asc')
				{
					$finishtimeSymbol = '↑';
					$finishtimeSort = 'desc';
				}
				elseif($orderby == 'desc')
				{
					$finishtimeSymbol = '↓';
					$finishtimeSort = 'asc';
				}
				break;
			default:
				break;
		}
		return array($domainnumSymbol,$goodratingSymbol,$creditSymbol,$percentSymbol,$finishtimeSymbol,$domainnumSort,
				$goodratingSort,$creditSort,$percentSort,$finishtimeSort);
	}

	/**
	 * 设置渠道统计的条件
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $type
	 * @param unknown $name
	 * @return multitype:string unknown NULL
	 */
	public function setPlatformSWhere($startDate, $endDate, $type, $name)
	{
		$where = array();
		$date = self::setTimeRange($startDate, $endDate);
		$where['CreateTime'] = $date;
		$where['PlatformType'] = $type;
		$name && ($type == 2? $where['FromUrl'] = '%' . $name . '%': $where['PlatformId'] = self::getPlatformIdByName(
			$name, $type));
		return $where;
	}

	/**
	 * 通过ID获取渠道名字
	 *
	 * @param unknown $name
	 * @param unknown $type
	 * @return multitype:NULL
	 */
	public static function getPlatformIdByName($name, $type)
	{
		$model = new ModelBase('platform');
		$id = $model->getData('PlatformId', array('Name'=> "%{$name}%",'PlatformType'=> $type));
		$newId = array();
		if($id)
		{
			foreach($id as $v)
			{
				$newId[] = $v->PlatformId;
			}
		}
		return $newId;
	}

	/**
	 * 設置時間範圍
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @return multitype:string number |multitype:string multitype:number
	 * |boolean
	 */
	public static function setTimeRange($startDate, $endDate)
	{
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);
		if($startDate && $endDate)
		{
			if($endDate < $startDate)
			{
				return array('>=',$startDate);
			}
			return array('between',array($startDate,$endDate + 86399));
		}
		elseif($endDate)
		{
			return array('<',$endDate + 86400);
		}
		elseif($startDate)
		{
			return array('>=',$startDate);
		}
		else
		{
			return array('between',array(strtotime(date('Y-m-d')),strtotime(date('Y-m-d')) + 86399));
		}
		return false;
	}

	/**
	 * 設置推廣域名記錄的搜索條件
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $domainName
	 * @param unknown $status
	 * @param unknown $PlatformType
	 * @return multitype:string multitype:string unknown unknown
	 * multitype:string Ambigous <\lib\agent\multitype:unknown, boolean,
	 * unknown, multitype:unknown unknown , number, multitype:number unknown >
	 * Ambigous <\lib\agent\multitype:string, boolean, multitype:string number ,
	 * multitype:string unknown , multitype:string multitype:number unknown ,
	 * multitype:string multitype:number >
	 */
	public function setSpreadDomainWhere($startDate, $endDate, $startCommission, $endCommission, $domainName, $status, 
		$PlatformType, $topic)
	{
		$where = array();
		$where['CreateTime'] = self::setTimeRange($startDate, $endDate);
		$Commission = $this->setRang($startCommission, $endCommission, true);
		$Commission && is_array($Commission)? $where['Percent'] = array('between',$Commission): $where['Percent'] = array(
				'>=',$Commission);
		$domainName && $where['DomainName'] = '%' . $domainName . '%';
		$status && $where['Status'] = $status;
		$PlatformType && $where['PlatformType'] = $PlatformType;
		$where['topic'] = $topic;
		return $where;
	}

	/**
	 * 設置推店鋪名記錄的搜索條件
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $Name
	 * @param unknown $status
	 * @param unknown $PlatformType
	 * @return multitype:string multitype:string unknown unknown
	 * multitype:string Ambigous <\lib\agent\multitype:unknown, boolean,
	 * unknown, multitype:unknown unknown , number, multitype:number unknown >
	 * Ambigous <\lib\agent\multitype:string, boolean, multitype:string number ,
	 * multitype:string unknown , multitype:string multitype:number unknown ,
	 * multitype:string multitype:number >
	 */
	public function setSpreadShopWhere($startDate, $endDate, $startCommission, $endCommission, $Name, $status, 
		$PlatformType)
	{
		$where = array();
		$where['CreateTime'] = self::setTimeRange($startDate, $endDate);
		$Commission = $this->setRang($startCommission, $endCommission);
		$Commission && is_array($Commission)? $where['Percent'] = array('between',$Commission): $where['Percent'] = array(
				'>=',$Commission);
		$Name && $where['Name'] = '%' . $Name . '%';
		$status && $where['Status'] = $status;
		$PlatformType && $where['PlatformType'] = $PlatformType;
		return $where;
	}
}