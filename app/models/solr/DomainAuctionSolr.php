<?php
namespace solr;
use \core\driver\Solr;
use core\Config;

class DomainAuctionSolr extends Solr
{

	/**
	 *
	 * @var \SolrQuery
	 */
	protected $query;

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct(\core\Config::item('solrAuction'));
		$this->query = new \SolrQuery();
	}

	public function __destruct()
	{
		unset($this->query);
	}

	/**
	 * solr测试代码
	 *
	 * @return array 对象数组或空数组
	 */
	public function test()
	{
		$query = new \SolrQuery();
		$query->setQuery('*');
		$query->addFilterQuery("TransStatus:1");
		$query->addFilterQuery("SimpleDec:*你* OR *16*");
		$query->addFilterQuery("-DomainSLD:(*6*)");
		
		$query->setStart(0);
		$query->setRows(10);
		$response = $this->query($query);
		$r = $response->getResponse();
		if($r->response->docs)
		{
			return $r->response->docs;
		}
		
		return array();
	}

	/**
	 * 淘域名搜索
	 *
	 * @param int $enameId
	 * @param string $domainName
	 * @param int|array $TLD
	 * @param int $sysGroupOne
	 * @param int $sysGroupTwo
	 * @param int $domainLen
	 * @param int $bidPrice
	 * @param int $finishTime
	 * @param int $transType
	 * @param unknown $orderField
	 * @param unknown $order
	 * @param number $page
	 * @param number $num
	 * @param bool $bidPriceEnd
	 * @return \stdClass array
	 */
	public function getTransSearch($enameId, $domainName, $TLD, $sysGroupOne, $sysGroupTwo, $domainLen, $bidPrice, 
		$finishTime, $transType, $orderField, $order, $page, $bidPriceEnd = TRUE)
	{
		$query = new \SolrQuery();
		$leftTime = Config::item('lefttime');
		$time = $this->dateFormat(strtotime("+ {$leftTime} second")); // 交易剩余时间大于两个小时另外多加10秒缓冲
		$domain = $this->removeCharacter($domainName);
		
		// 如果要查询的关键字都是表达式字符 搜索结果为空
		if(FALSE === $domain)
		{
			$object = new \stdClass();
			$object->numFound = 0;
			$object->docs = array();
			return $object;
		}
		
		$query->setQuery('*');
		
		$query->addFilterQuery("TransStatus:1");
		$query->addFilterQuery("Seller:{$enameId}");
		$domain && $query->addFilterQuery("DomainName:*{$domain}*");
		
		// 过滤后缀
		if($TLD)
		{
			if(strpos($TLD, ',') !== FALSE)
			{
				$tlds = str_replace(',', ' OR ', $TLD);
				$query->addFilterQuery("DomainTLD:($tlds)");
			}
			else
			{
				$query->addFilterQuery("DomainTLD:{$TLD}");
			}
		}
		
		// 过滤分类 纯数字、纯字母、杂米...
		if($sysGroupOne)
		{
			is_array($sysGroupOne)? $query->addFilterQuery("SysGroupOne:[{$sysGroupOne[0]} TO {$sysGroupOne[1]}]"): $query->addFilterQuery(
				"SysGroupOne:{$sysGroupOne}");
		}
		
		$sysGroupTwo && $query->addFilterQuery("SysGroupTwo:{$sysGroupTwo}");
		
		// 出价
		if($bidPrice)
		{
			if(is_array($bidPrice))
			{
				$query->addFilterQuery("BidPrice:[{$bidPrice[0]} TO {$bidPrice[1]}]");
			}
			elseif($bidPriceEnd)
			{
				$query->addFilterQuery("BidPrice:{$bidPrice}");
			}
			else
			{
				$query->addFilterQuery("BidPrice:[{$bidPrice} TO *]");
			}
		}
		
		// 域名长度
		if($domainLen)
		{
			$query->addFilterQuery("DomainLen:{$domainLen}");
		}
		
		// 结束时间
		$finishTimeFilter = "FinishDate:{{$time} TO *}";
		if($finishTime)
		{
			$ft = $this->dateFormat(strtotime($finishTime));
			
			$finishTimeFilter = str_replace('*', $ft, $finishTimeFilter);
		}
		$query->addFilterQuery($finishTimeFilter);
		
		// 交易类型
		if($transType)
		{
			if(is_array($transType))
			{
				$transT = implode(',', $transType);
				$transT = str_replace(',', ' OR ', $transT);
				
				$query->addFilterQuery("TransType:($transT)");
			}
			else
			{
				$query->addFilterQuery("TransType:{$transType}");
			}
		}
		
		// 排序
		$orderBy = $order? \SolrQuery::ORDER_DESC: \SolrQuery::ORDER_ASC;
		switch($orderField)
		{
			case 1:
				$query->addSortField('BidPrice', $orderBy);
				break;
			
			default:
				$query->addSortField('FinishDate', $orderBy);
				break;
		}
		$query->addSortField('DomainName', \SolrQuery::ORDER_ASC);
		
		// 要返回的字段
		// IsDomainInEname,FinishDate,FinishDateFlag,BidCount,BidPrice,Seller,TransType,AuditListId,DomainName,SimpleDec,
		// TransTopic, AskingPrice
		$query->addField('FinishDate')
			->addField('BidPrice')
			->addField('Seller')
			->addField('TransType')
			->addField('AuditListId')
			->addField('DomainName')
			->addField('AskingPrice')
			->addField('TransTopic');
		
		// limit
		$query->setStart((int)$page[0]);
		$query->setRows((int)$page[1]);
		
		$query_response = $this->query($query);
		
		$r = $query_response->getResponse();
		
		if($r->response->docs)
		{
			$r->response->docs = array_map(function ($o)
			{
				return (array)$o;
			}, $r->response->docs);
		}
		return $r->response;
	}

	public function getByIdName($enameId, $Domain, $tld)
	{
		if(! $enameId || ! $Domain)
		{
			return false;
		}
		
		$query = new \SolrQuery();
		
		$query->setQuery('*');
		$query->addFilterQuery("Seller:{$enameId}");
		$query->addFilterQuery("DomainName:{$Domain}");
		$query->addFilterQuery("DomainTLD:{$tld}");
		$query->addFilterQuery("TransStatus:1");
		// 要返回的字段
		// IsDomainInEname,FinishDate,FinishDateFlag,BidCount,BidPrice,Seller,TransType,AuditListId,DomainName,SimpleDec,
		// TransTopic, AskingPrice
		$query->addField('BidPrice')
			->addField('FinishDate')
			->addField('AuditListId');
		
		// limit
		$query->setStart(0);
		
		$query->setRows(1);
		
		$query_response = $this->query($query);
		
		$r = $query_response->getResponse();
		
		if($r->response->docs)
		{
			$r->response->docs = array_map(function ($o)
			{
				return (array)$o;
			}, $r->response->docs);
		}
		
		return $r->response;
	}

	public function getById($id)
	{
		if(! $id)
		{
			return false;
		}
		
		$query = new \SolrQuery();
		
		$query->setQuery('*');
		$query->addFilterQuery("AuditListId:{$id}");
		// 要返回的字段
		// IsDomainInEname,FinishDate,FinishDateFlag,BidCount,BidPrice,Seller,TransType,AuditListId,DomainName,SimpleDec,
		// TransTopic, AskingPrice
		$query->addField('BidPrice')
			->addField('FinishDate')
			->addField('AuditListId');
		
		// limit
		$query->setStart(0);
		
		$query->setRows(1);
		
		$query_response = $this->query($query);
		
		$r = $query_response->getResponse();
		
		if($r->response->docs)
		{
			$r->response->docs = array_map(function ($o)
			{
				return (array)$o;
			}, $r->response->docs);
		}
		
		return $r->response;
	}

	/**
	 * 根据域名和用户ID查询solr数据
	 *
	 * @param array|int $transId
	 * @return array
	 */
	public function getTransByUser($enameId, $domainName = '', $tld = 0, $offset = 0, $pageSize = 1)
	{
		$query = new \SolrQuery();
		$query->setQuery('*');
		$query->addFilterQuery("TransStatus:1");
		$query->addFilterQuery("Seller:{$enameId}");
		
		if($domainName)
		{
			$domain = $this->removeCharacter($domainName);
			
			// 如果要查询的关键字都是表达式字符或者是传了域名，但是后缀为空，返回空
			if(FALSE === $domain || ! $tld)
			{
				$object = new \stdClass();
				$object->numFound = 0;
				$object->docs = array();
				return $object;
			}
			$query->addFilterQuery("DomainName:{$domain}");
			$query->addFilterQuery("DomainTLD:{$tld}");
		}
		
		$leftTime = Config::item('lefttime');
		$finishTime = $this->dateFormat(strtotime("+ {$leftTime} second")); // 交易剩余时间大于两个小时
		$query->addFilterQuery("FinishDate:{{$finishTime} TO *}");
		
		// 1竞价或者4一口价
		$query->addFilterQuery("TransType:(1 OR 4)");
		$query->addFilterQuery("BidPrice:[1 TO *]");
		$query->addSortField('DomainName', \SolrQuery::ORDER_ASC);
		
		// 要返回的字段:
		// FinishDate,FinishDateFlag,BidCount,BidPrice,Seller,TransType,AuditListId,DomainName,SimpleDec
		$query->addField('FinishDate')
			->addField('BidPrice')
			->addField('Seller')
			->addField('TransType')
			->addField('AuditListId')
			->addField('DomainName')
			->addField('SimpleDec')
			->addField('TransTopic');
		
		// limit
		$query->setStart($offset);
		$query->setRows($pageSize);
		
		$query_response = $this->query($query);
		
		$r = $query_response->getResponse();
		
		if($r->response->docs)
		{
			$r->response->docs = array_map(function ($o)
			{
				return (array)$o;
			}, $r->response->docs);
		}
		
		return $r->response;
	}

	/**
	 * 根据域名和用户ID查询solr数据
	 *
	 * @param array|int $transId
	 * @return array
	 */
	public function getTransByTopic($topicId, $time = 0, $offset = 0, $pageSize = 100)
	{
		$query = new \SolrQuery();
		$query->setQuery('*');
		$time = $this->dateFormat($time);
		$query->addFilterQuery("TransStatus:1");
		$query->addFilterQuery("TransTopic:{$topicId}");
		$query->addFilterQuery("CreateDate:[{$time} TO *]");
		// 要返回的字段:
		// FinishDate,FinishDateFlag,BidCount,BidPrice,Seller,TransType,AuditListId,DomainName,SimpleDec
		$query->addField('FinishDate')
			->addField('BidPrice')
			->addField('Seller')
			->addField('TransType')
			->addField('AuditListId')
			->addField('DomainName')
			->addField('TransTopic')
			->addField('SimpleDec');
		// limit
		$query->setStart($offset);
		$query->setRows($pageSize);
		$query_response = $this->query($query);
		
		$r = $query_response->getResponse();
		
		if($r->response->docs)
		{
			$r->response->docs = array_map(function ($o)
			{
				return (array)$o;
			}, $r->response->docs);
		}
		
		return $r->response;
	}
	// 转为solr时间查询格式
// 	public function dateFormat($date)
// 	{
// 		if($date)
// 		{
// 			return date('Y-m-d\TH:i:s\Z', $date);
// 		}
// 		else
// 		{
// 			return FALSE;
// 		}
// 	}
}