<?php
namespace lib\agent;
use core\Config;
use common\domain\Domain;

class AgentManagerLib
{

	/**
	 * 获取域名分组和长度
	 *
	 * @param int $type 系统分组值
	 * @return array
	 * @author huangyy
	 */
	public function getSysGroup($type)
	{
		$sysGroupOne = 0;
		$sysGroupTwo = 0;
		$domainLen = 0;
		$domainGroup = \core\Config::item('domaingroup')->toArray(); // 读取分组配置
		if($type)
		{
			$temp = $domainGroup[$type][1];
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

	public function taoSysGroup($domaingroup, $threeClass = array())
	{
		$class = array();
		$domainlenstart = $domainlenend = 0;
		if($domaingroup)
		{
			$dnGroupConf = \core\Config::item('ts_domaingroup')[$domaingroup];
			$dnClass = explode("_", $dnGroupConf[1]);
			if(4 == count($dnClass))
			{
				if($threeClass)
				{
					$threeClass = is_array($threeClass)? $threeClass: array($threeClass);
				}
				$class = array($dnClass[0],$dnClass[1],$threeClass);
				if(isset($dnClass[3]) && intval($dnClass[3]))
				{
					$domainlenstart = $domainlenend = intval($dnClass[3]);
				}
			}
		}
		$length = array($domainlenstart,$domainlenend);
		return array($class,$length);
	}

	public function getSysGroupVal($domainGroup)
	{
		$groupOne = $groupTwo = $groupThree = $domainLen = null;
		list($class, $length) = $this->taoSysGroup($domainGroup);
		if(is_array($class) && (isset($class[0]) || isset($class[1]) || isset($class[2])))
		{
			$one = isset($class[0])? intval($class[0]): false;
			$two = isset($class[1])? intval($class[1]): false;
			$three = isset($class[2])? intval($class[2]): false;
			if($one)
			{
				$groupOne = $class[0];
			}
			if($two)
			{
				if(10 == $two)
				{
					$groupTwo = array(10,12);
				}
				elseif(2 == $two)
				{
					$groupTwo = array(2,12);
				}
				else
				{
					$groupTwo = 9999 == $two? 0: $two;
				}
			}
			if($three)
			{
				$groupThree = $three[0];
			}
		}
		if(is_array($length) && array_filter($length))
		{
			$domainLen = array('BETWEEN',$length);
		}
		return array($groupOne,$groupTwo,$groupThree,$domainLen);
	}

	/**
	 * 搜索范围
	 *
	 * @param number $start 数组开始索引
	 * @param number $end 数组结束索引
	 * @param array $search 查询数组
	 * @return Ambigous <number, multitype:number >|Ambigous <string, number>
	 */
	public function getRange($start, $end)
	{
		$isEnd = FALSE;
		if(! $start && ! $end)
		{
			return array($start,$isEnd);
		}
		$isEnd = $start == $end;
		$data = $start < $end? array($start,$end): $start;
		return array($data,$isEnd);
	}

	/**
	 * 格式化剩余时间
	 *
	 * @param int $time
	 * @return string
	 * @author huangyy
	 */
	public static function newTimeToDHIS($time, $allFlag = FALSE)
	{
		$now = time();
		$time = $time - $now;
		if($time <= 0)
		{
			return '-';
		}
		$timeStr = '';
		$nY = intval($time / 60 / 60 / 24 / 365);
		$nD = ($time / 60 / 60 / 24) % 365;
		$nH = ($time / 60 / 60) % 24;
		$nI = ($time / 60) % 60;
		$nS = ($time % 60);
		$nD = $nY * 365 + $nD;
		if($allFlag)
		{
			$leftTime = $nY? $nY . '年': '';
			$leftTime .= $nD? $nD . '天': '';
			$leftTime .= $nH? $nH . '时': '';
			$leftTime .= $nI? $nI . '分': '';
			$leftTime .= $nS? $nS . '秒': '';
			
			return $leftTime;
		}
		if($nD >= 7)
		{
			$timeStr .= $nD . '天';
		}
		elseif($nD >= 1)
		{
			$timeStr .= $nD . '天';
			$timeStr .= $nH? $nH . '时': '';
		}
		elseif($nH >= 1)
		{
			$timeStr .= $nH . '时';
			$timeStr .= $nI? $nI . '分': '';
		}
		elseif($nI >= 1)
		{
			$timeStr .= $nI . '分';
			$timeStr .= $nS? $nS . '秒': '';
		}
		else
		{
			$timeStr .= $nS . '妙';
		}
		return $timeStr;
	}

	/**
	 * 设置剩余时间
	 *
	 * @param int $finishTime
	 * @return number
	 */
	public function setFinishTime($finishTime, $timeFlag = false)
	{
		$finishTimeList = Config::item('finishtime')->toArray();
		$leftTime = $finishTimeList[$finishTime][1];
		$finishTime = time() + $leftTime;
		if($timeFlag)
		{
			return date('Y-m-d H:i:s', $finishTime);
		}
		
		return $finishTime;
	}

	/**
	 * 排序
	 *
	 * @param string $sort 拼接形式"finishtime-desc"
	 * @return multitype:number boolean
	 */
	public function setOrder($sort, $isStr = TRUE)
	{
		if($sort)
		{
			$orderList = explode('-', $sort);
			if($orderList)
			{
				switch(strtolower($orderList[0]))
				{
					case 'price':
						$orderField = 1;
						$fieldStr = 'Price';
						break;
					case 'percent':
						$orderField = 2;
						$fieldStr = 'Percent';
						break;
					case 'finishtime':
						$orderField = 3;
						$fieldStr = 'FinishTime';
						break;
					default:
						$orderField = 3;
						$fieldStr = 'FinishTime';
						break;
				}
				
				switch(strtolower($orderList[1]))
				{
					case 'desc':
						$isDesc = TRUE;
						break;
					case 'asc':
						$isDesc = FALSE;
						break;
					default:
						$isDesc = TRUE;
				}
				return $isStr? array($fieldStr,strtoupper($orderList[1])): array($orderField,$isDesc);
			}
		}
		return $isStr? array('FinishTime','DESC'): array(3,TRUE);
	}

	/**
	 *
	 * @param unknown $orderField
	 * @param unknown $orderby
	 * @return multitype:string
	 */
	public function setSort($orderField, $orderby)
	{
		$priceSymbol = $finishTimeSymbol = $commissionSymbol = '';
		$priceSort = $finishTimeSort = $commissionSort = 'desc';
		if($orderField == 1 || $orderField == 'Price')
		{
			$priceSymbol = '↓';
			$priceSort = 'asc';
			if($orderby == FALSE || $orderby != 'DESC')
			{
				$priceSymbol = '↑';
				$priceSort = 'desc';
			}
		}
		elseif($orderField == 2 || $orderField == 'Percent')
		{
			$commissionSymbol = '↓';
			$commissionSort = 'asc';
			if($orderby == FALSE || $orderby != 'DESC')
			{
				$commissionSymbol = '↑';
				$commissionSort = 'desc';
			}
		}
		else
		{
			$finishTimeSymbol = '↓';
			$finishTimeSort = 'asc';
			if($orderby == FALSE || $orderby != 'DESC')
			{
				$finishTimeSymbol = '↑';
				$finishTimeSort = 'desc';
			}
		}
		return array('priceSymbol'=> $priceSymbol,'finishTimeSymbol'=> $finishTimeSymbol,
				'commissionSymbol'=> $commissionSymbol,'priceSort'=> $priceSort,'finishTimeSort'=> $finishTimeSort,
				'commissionSort'=> $commissionSort);
	}

	/**
	 * 过滤域名
	 *
	 * @param string $domainName
	 * @return mixed
	 */
	public function getFilterDomain($domainName)
	{
		if($domainName)
		{
			$word = Domain::getDomainBody($domainName);
			$isEn = preg_match('/^[a-zA-Z0-9]+$/', $word); // 是否是英文或数字
			if($isEn)
			{
				$domainName = strtolower($domainName);
			}
			
			$domainName = preg_replace('/(http[s]?:\/\/)/', '', $domainName);
			$domainName = preg_replace('/。/', '.', $domainName);
		}
		
		return $domainName;
	}

	/**
	 * 域名搜索
	 *
	 * @param string $domain 域名
	 * @param int $tld 域名后缀
	 * @param array $searchTldConf 后缀配置 后缀名=>后缀值
	 * @param array $formTldConf 表单后缀配置 后缀值=>后缀KEY
	 * @param boolean $array 是否以数组形式返回
	 * @return array
	 * @author huangyy
	 */
	public function getDomainSearch($domain, $tld = 0)
	{
		$domainBody = Domain::getDomainBody($domain);
		// $tldList = \core\Config::item('ts_domaintld')->toArray();
		
		// 判断是否传了后缀搜索
		if($tld)
		{
			// 如果表单有传后缀，则以表单的为准
			// $domainTld = $tldList[$tld][1];
			return array($domainBody,$tld);
		}
		else
		{
			// 如果表单没传后缀，则分析传过来的字符串是否有后缀
			if(strpos($domain, '.') === FALSE)
			{
				// 无后缀
				return array($domain,false);
			}
			
			$domainTld = Domain::tldValue($domain);
			return array($domainBody,$domainTld);
		}
	}
}