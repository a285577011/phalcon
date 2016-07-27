<?php
namespace logic\index;
use OrderRecord;
use Phalcon\Cache\Frontend\Data;
use Phalcon\Cache\Backend\File;
use core\Logger;
use common\domain\Domain;

class Index
{

	/**
	 * 排行榜
	 *
	 * @param number $num
	 * @return Ambigous <\Phalcon\Cache\Backend\mixed, multitype:unknown
	 * Ambigous <\driver\mixed, \core\mixed> >
	 */
	public function TopList($num = 5)
	{
		$frontCache = new Data(array("lifetime"=> 86400));
		$cache = new File($frontCache, array("cacheDir"=> ROOT_PATH . "app/cache/"));
		$cacheKey = "agent_top.cache";
		$topList = $cache->get($cacheKey);
		if($topList === null)
		{
			$order = new OrderRecord();
			
			// 米掌柜
			$fields = 'COUNT(*) AS Num,EnameId';
			$condition['Status'] = array('!=',3);
			$groupBy = '`EnameId`';
			$orderBy = 'Num DESC';
			$limit = "$num";
			$sellerList = $order->getData($fields, $condition, $order::FETCH_ALL, $orderBy, $limit, $groupBy);
			foreach($sellerList as $seller)
			{
				$seller->EnameId = substr_replace($seller->EnameId, '***', - 3);
			}
			
			// 米农
			$fields = 'COUNT(*) AS Num,AgentEnameId';
			$groupBy = 'AgentEnameId';
			$guestList = $order->getData($fields, $condition, $order::FETCH_ALL, $orderBy, $limit, $groupBy);
			foreach($guestList as $guest)
			{
				$guest->AgentEnameId = substr_replace($guest->AgentEnameId, '***', - 3);
			}
			
			// 佣金
			$fields = "AgentEnameId,Income,DomainName";
			$orderBy = '`Income` DESC';
			$moneyList = $order->getData($fields, $condition, $order::FETCH_ALL, $orderBy, $limit);
			foreach($moneyList as $money)
			{
				$money->AgentEnameId = substr_replace($money->AgentEnameId, '***', - 3);
				$money->DomainName = $this->replaceStr($money->DomainName, '.');
			}
			$topList = array('sellerList'=> $sellerList,'moneyList'=> $moneyList,'guestList'=> $guestList);
			$cache->save($cacheKey, $topList);
		}
		
		return $topList;
	}

	private function replaceStr($string, $needle = '.', $replaceStr = '*')
	{
		$strLen = stripos($string, $needle);
		if($strLen > 1)
		{
			$length = $strLen > 3? 3: $strLen - 1;
			$replace = '';
			$temp = $length;
			while($temp > 0)
			{
				$replace .= $replaceStr;
				$temp--;
			}
			$string = substr_replace($string, $replace, $strLen - $length, $length);
		}
		return $string;
	}
}