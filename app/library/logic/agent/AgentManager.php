<?php
namespace logic\agent;
use lib\agent\AgentManagerLib;
use solr\DomainAuctionSolr;
use core\Config;
use common\domain\Domain;
use table\DomainAgentTable;
use core\driver\Redis;
use core\ModelBase;
use core\EnameApi;
use logic\common\Common;
use es\DomainElasticSearch;

class AgentManager
{

	/**
	 *
	 * @var int
	 */
	private $enameId;

	/**
	 *
	 * @var AgentManagerLib
	 */
	private $lib;

	/**
	 *
	 * @var \DomainAgent
	 */
	private $model;

	/**
	 *
	 * @var \TemporarySolr
	 */
	private $temp;

	/**
	 *
	 * @var \AgentBackup
	 */
	private $backup;

	public function __construct($enameId = 0, $formData = array())
	{
		$this->enameId = $enameId;
		$this->lib = new AgentManagerLib();
		$this->model = new \DomainAgent();
		$this->backup = new \AgentBackup();
		$this->temp = new \TemporarySolr();
	}

	public function __destruct()
	{
		unset($this->lib);
		unset($this->enameId);
		unset($this->model);
		unset($this->temp);
		unset($this->backup);
	}

	/**
	 *
	 * @param string $domain
	 * @param string $sort
	 * @param number $tld
	 * @param number $transType交易类型 1：一口价 2：竞价 3：竞价(预订竞价) 4：竞价(专题拍卖)
	 * 5：竞价(易拍易卖)6:一口价(sedo) 8:拍卖会
	 * @param number $finishTime
	 * @param number $domainGroup
	 * @param number $priceStart
	 * @param number $priceEnd
	 * @param number $offset
	 * @param number $pageSize
	 * @return multitype:unknown Ambigous <multitype:boolean multitype: number
	 * unknown , multitype:boolean number multitype: Ambigous
	 * <\lib\agent\multitype:string, multitype:string > , multitype:boolean
	 * unknown Ambigous <multitype:, boolean, unknown> Ambigous <> >
	 */
	public function getSolrData($domain = '', $sort = '', $tld = 0, $transType = 0, $finishTime = 0, $domainGroup = 0, 
		$priceStart = 1, $priceEnd = 0, $offset = 0, $pageSize = 30)
	{
		$error = 0;
		$sortArr = $this->lib->setOrder($sort, FALSE);
		$domain = $this->lib->getFilterDomain($domain);
		$dn = $this->lib->getDomainSearch($domain, $tld);
		$orderList = $this->lib->setSort($sortArr[0], $sortArr[1]);
		$page = array($offset,$pageSize);
		if(4 == $transType)
		{
			$transType = array(1);
		}
		elseif($transType == 1)
		{
			$transType = array(4,6,7,8);
		}
		elseif(! $transType)
		{
			$transType = array(1,4,6,7,8);
		}
		
		switch(\core\Config::item('ts_data'))
		{
			case 1:
				list($domainList, $count, $error) = $this->solrData($dn, $sortArr, $transType, $finishTime, 
					$domainGroup, $priceStart, $priceEnd, $page);
				break;
			case 2:
				list($count, $domainList) = $this->elasticSearchData($dn, $sortArr, $transType, $finishTime, 
					$domainGroup, $priceStart, $priceEnd, $page);
				break;
			default:
				list($domainList, $count, $error) = $this->solrData($dn, $sortArr, $transType, $finishTime, 
					$domainGroup, $priceStart, $priceEnd, $page);
				break;
		}
		$isEmpty = empty($domainList);
		return array($isEmpty,$domainList,$count,$orderList,$error);
	}

	/**
	 * 通过elasticsearch获取米仓数据
	 *
	 * @param unknown $dn
	 * @param string $sort
	 * @param number $transType
	 * @param number $finishTime
	 * @param number $domainGroup
	 * @param number $priceStart
	 * @param number $priceEnd
	 * @param unknown $page
	 * @return multitype:Ambigous <multitype:, boolean, unknown> Ambigous
	 * <number, multitype:, NULL, boolean>
	 */
	private function elasticSearchData($dn = array(), $sort = '', $transType = 0, $finishTime = 0, $domainGroup = 0, $priceStart = 1, 
		$priceEnd = 0, $page)
	{
		$domainList = array();
		$price = array($priceStart,$priceEnd);
		list($class, $length) = $this->lib->taoSysGroup($domainGroup);
		$end = $finishTime? \core\Config::item('finishtime')->toArray()[$finishTime][1]: 0;
		$endTime = array(\core\Config::item('lefttime') + time(),$end);
		
		$es = new DomainElasticSearch();
		$data = $es->taoDomainData($dn, $this->enameId, $transType, $class, $length, $price, $endTime, $sort, $page);
		foreach($data['data'] as $key => $val)
		{
			$source = $val['_source'];
			$domainList[$key]['DomainName'] = $source['t_dn'];
			$domainList[$key]['TransType'] = $source['t_type'];
			$domainList[$key]['Price'] = round($source['t_now_price'], 2);
			$domainList[$key]['FinishTime'] = $this->lib->newTimeToDHIS($source['t_complate_time'], TRUE);
			$domainList[$key]['Topic'] = $source['t_topic'];
			$domainList[$key]['AuditId'] = $source['t_id'];
			$agent = $this->model->getData('DomainAgentId,Percent,CreateTime', 
				array('DomainName'=> $source['t_dn'],'EnameId'=> $this->enameId,'TransId'=> $source['t_id'],
						'FinishTime'=> array('>',time())), \DomainAgent::FETCH_ROW, 'CreateTime Desc');
			if(! empty($agent))
			{
				$domainList[$key]['Percent'] = round($agent->Percent, 2);
				$domainList[$key]['IsEdit'] = $agent->CreateTime > time() - Config::item('edittime');
			}
		}
		return array($data['total'],$domainList);
	}

	/**
	 * 通过solr获取米仓数据
	 *
	 * @param unknown $dn
	 * @param string $sort
	 * @param number $transType
	 * @param number $finishTime
	 * @param number $domainGroup
	 * @param number $priceStart
	 * @param number $priceEnd
	 * @param unknown $page
	 * @return multitype:number multitype: |multitype:number Ambigous
	 * <multitype:, boolean, unknown> Ambigous <>
	 */
	private function solrData($dn = array(), $sort = '', $transType = 0, $finishTime = 0, $domainGroup = 0, $priceStart = 1, 
		$priceEnd = 0, $page)
	{
		$domainList = array();
		$domainSolr = new DomainAuctionSolr();
		$errorMsg = 0;
		
		// 搜索参数
		$finishTime = $finishTime? $this->lib->setFinishTime($finishTime, true): '';
		list($bidPrice, $isBidPriceEnd) = $this->lib->getRange($priceStart, $priceEnd); // 竞价
		list($sysGroupOne, $sysGroupTwo, $domainLen) = $this->lib->getSysGroup($domainGroup); // 系统分组
		
		if(! $domainSolr->ping())
		{
			$errorMsg = 1;
			return array($domainList,0,$errorMsg);
		}
		// 返回的solr数据
		$domainData = $domainSolr->getTransSearch($this->enameId, $dn[0], $dn[1], $sysGroupOne, $sysGroupTwo, 
			$domainLen, $bidPrice, $finishTime, $transType, $sort[0], $sort[1], $page, $isBidPriceEnd);
		
		if($domainData['numFound'] > 0 && ! empty($domainData['docs']))
		{
			foreach($domainData['docs'] as $k => $domain)
			{
				$finishDate = str_replace(array('T','Z'), ' ', $domain['FinishDate']);
				$domainList[$k]['FinishTime'] = $this->lib->newTimeToDHIS(strtotime($finishDate), TRUE);
				$domainList[$k]['Price'] = round(strtr($domain['BidPrice'], array(',CNY'=> '')));
				$domainList[$k]['Seller'] = $domain['Seller'];
				$domainList[$k]['DomainName'] = $domain['DomainName'];
				$domainList[$k]['TransType'] = $domain['TransType'];
				$domainList[$k]['AuditId'] = $domain['AuditListId'];
				$domainList[$k]['Topic'] = $domain['TransTopic'];
				
				// 查询是否已经设置分销
				$fields = 'DomainAgentId,Percent,CreateTime';
				$condition['DomainName'] = $domain['DomainName'];
				$condition['EnameId'] = $domain['Seller'];
				$condition['FinishTime'] = array('>',time());
				$agent = $this->model->getData($fields, $condition, \DomainAgent::FETCH_ROW, 'CreateTime Desc');
				if(! empty($agent))
				{
					$domainList[$k]['Percent'] = round($agent->Percent, 2);
					$domainList[$k]['IsEdit'] = $agent->CreateTime > time() - Config::item('edittime');
				}
			}
		}
		return array($domainList,$domainData['numFound'],$errorMsg);
	}

	/**
	 * (批量)设置域名分销
	 *
	 * @param array $param
	 * @throws \Exception
	 * @return boolean
	 */
	public function setAgent($domain, $percent, $isAgree)
	{
		$data = array();
		is_array($domain) || $domain = (array)$domain;
		$percent = floatval($percent);
		if(empty($domain))
		{
			return array('flag'=> 1);
		}
		if(! $percent)
		{
			return array('flag'=> 6);
		}
		if(! $isAgree)
		{
			return array('flag'=> 4);
		}
		\core\Logger::write('domain_agent_agreement', 
			array('EnameID:' . $this->enameId,'Agreement:' . $isAgree,'IP:' . \common\Client::getClientIp(0),
					'Domain:' . implode(',', $domain)));
		
		switch(\core\Config::item('ts_data'))
		{
			case 1:
				$data = $this->setAgentBySolr($domain, $percent);
				break;
			case 2:
				$data = $this->setAgentByElasticSearch($domain, $percent);
				break;
			default:
				$data = $this->setAgentBySolr($domain, $percent);
				break;
		}
		
		return $data;
	}

	/**
	 *
	 * @param unknown $domainName
	 * @param unknown $percent
	 * @param unknown $isAgree
	 * @return multitype:number |multitype:number string |multitype:number
	 * multitype:Ambigous <\logic\agent\multitype:string, boolean, number,
	 * string>
	 */
	public function setAgentBySolr($domainName, $percent)
	{
		$data = array();
		$domainSolr = new DomainAuctionSolr();
		if(! $domainSolr->ping())
		{
			return array('flag'=> 5);
		}
		
		$failed = $this->isInAgentTime($domainName, $domainSolr);
		if(! empty($failed))
		{
			return array('flag'=> 2,'msg'=> implode(', ', $failed) . '超出限定的条件，不能设置推广'); // 在规定时间外
		}
		
		foreach($domainName as $name)
		{
			$name = $this->lib->getFilterDomain($name);
			$status = $this->isAgented($name); // 针对批量分销的
			switch($status)
			{
				case 1: // 未设置分销
					$data[] = $this->newAgent($name, $percent, $domainSolr);
					break;
				case 2: // 时间不超过半小时
					$update['Percent'] = $percent;
					$update['UpdateTime'] = time();
					$where['DomainName'] = $name;
					$where['EnameId'] = $this->enameId;
					$this->model->update($update, $where);
					break;
				default:
					break;
			}
		}
		
		return array('flag'=> 3,'id'=> $data);
	}

	/**
	 *
	 * @param unknown $domain
	 * @param unknown $percent
	 */
	public function setAgentByElasticSearch($domain, $percent)
	{
		$failure = array();
		foreach($domain as $dn)
		{
			$dn = $this->lib->getFilterDomain($dn);
			
			list($data, $status) = $this->isAgented($dn); // 针对批量分销的
			if ($status != 3)
			{
				$info = $data['data'][0]['_source'];;
			}
			switch($status)
			{
				case 1: // 未设置分销
					$this->newAgentByEs($info, $percent);
					break;
				case 2: // 时间不超过半小时
					$update['Percent'] = $percent;
					$update['UpdateTime'] = time();
					$where['DomainName'] = $dn;
					$where['EnameId'] = $this->enameId;
					$this->model->update($update, $where);
					break;
				default:
					$failure[] = $dn;
					break;
			}
		}
		if(! empty($failure))
		{
			return array('flag'=> 2,'msg'=> implode(',', $failure) . '超出限定的条件，不能设置推广');
		}
		return array('flag'=> 3);
	}

	private function newAgentByEs($data, $percent)
	{
		// 插入分销表中
		$insert['FinishTime'] = $data['t_complate_time'];
		$insert['Percent'] = $percent;
		$insert['DomainName'] = $data['t_dn'];
		$insert['EnameId'] = $this->enameId;
		$insert['TransId'] = $data['t_id'];
		$insert['DomainLen'] = $data['t_len'];
		$insert['GroupOne'] = $data['t_class_name'];
		$insert['GroupTwo'] = $data['t_two_class'];
		$insert['GroupThree'] = $data['t_three_class'];
		$insert['Price'] = $data['t_now_price'];
		$insert['CreateTime'] = $insert['UpdateTime'] = time();
		$insert['SimpleDec'] = $data['t_desc'];
		switch($data['t_type'])
		{
			case 4:
			case 6:
			case 7:
			case 8:
				$insert['TransType'] = 1;
				break;
			case 1:
				$insert['TransType'] = 4;
		}
		$insert['TLD'] = $data['t_tld'];
		$insertId = $this->model->insert($insert);
		if(! $insertId)
		{
			\core\Logger::write('domain_agent', "Insert into domain_agent success!({$data['t_dn']},{$this->enameId})");
		}
		Common::addScore($this->enameId, 1, "推广域名：{$data['t_dn']}成功");
		// 删除临时表中的数据
		$where['DomainName'] = $data['t_dn'];
		$where['EnameId'] = $this->enameId;
		$row = $this->temp->delete($where);
		if(! $row)
		{
			\core\Logger::write('domain_agent', "Delete temporary_solr failure!({$data['t_dn']},{$this->enameId})");
		}
	}

	/**
	 * 判断是否在可设置分销时间内（两个小时）
	 *
	 * @param array $domainName
	 * @return boolean
	 */
	private function isInAgentTime($domainName, DomainAuctionSolr $domainSolr)
	{
		$failed = array();
		foreach($domainName as $domain)
		{
			list($name, $tld) = $this->lib->getDomainSearch($domain);
			$domainSolrData = $domainSolr->getTransByUser($this->enameId, $name, $tld);
			$flag = $domainSolrData['numFound'] > 0? : FALSE; // 在规定时间外不可以设置分销
			if($flag === FALSE)
			{
				$failed[] = $domain;
			}
		}
		
		return $failed;
	}

	/**
	 * 插入新的分销记录
	 *
	 * @param string $domainName
	 * @throws \Exception
	 * @return multitype:string boolean
	 */
	private function newAgent($domainName, $percent, $domainSolr)
	{
		$domainLength = $domainSysOne = $domainSysTwo = 0;
		list($name, $tld) = $this->lib->getDomainSearch($domainName);
		$domainData = $domainSolr->getTransByUser($this->enameId, $name, $tld);
		$domainDocs = $domainData['numFound'] > 0? $domainData['docs']: array();
		
		if(! empty($domainData))
		{
			foreach($domainDocs as $domain)
			{
				$finishDate = str_replace(array('T','Z'), ' ', $domain['FinishDate']);
				$finishTime = strtotime($finishDate);
				$domainName = $domain['DomainName'];
				extract(Domain::getDomainGroup($domainName), EXTR_REFS);
				
				// 插入分销表中
				$insert['FinishTime'] = $finishTime;
				$insert['Percent'] = $percent;
				$insert['DomainName'] = $domainName;
				$insert['EnameId'] = $domain['Seller'];
				$insert['TransId'] = $domain['AuditListId'];
				$insert['DomainLen'] = $domainLength;
				$insert['GroupOne'] = $domainSysOne;
				$insert['GroupTwo'] = $domainSysTwo;
				$insert['Price'] = $domain['BidPrice'];
				$insert['CreateTime'] = $insert['UpdateTime'] = time();
				$insert['SimpleDec'] = $domain['SimpleDec'];
				$insert['TransType'] = $domain['TransType'];
				$insert['TLD'] = Domain::getDomainLtd($domainName);
				$insertId = $this->model->insert(array_filter($insert));
				if($insertId)
				{
					Common::addScore($domain['Seller'], 1, "推广域名：{$domainName}成功");
					// 删除临时表中的数据
					$where['DomainName'] = $domainName;
					$where['EnameId'] = $domain['Seller'];
					$this->temp->delete($where);
				}
			}
			return $insertId;
		}
		return FALSE;
	}

	/**
	 * 显示店铺信息
	 *
	 * @return array
	 */
	public function getShopData()
	{
		$api = new EnameApi();
		
		// 查找数据库是否有记录
		$shopArr = $this->shopInfo();
		if(array_filter($shopArr))
		{
			$shopArr['IsEdit'] = $shopArr['CreateTime'] > time() - Config::item('edittime');
			$shopArr['LeftDate'] = $this->lib->newTimeToDHIS($shopArr['FinishTime'], TRUE);
			return array('data'=> $shopArr,'status'=> 1);
		}
		
		// 如果还未设置调api的接口获取店铺的信息
		$shopJson = $api->sendCmd('agent/shopinfo', array('EnameId'=> $this->enameId)); // 调用交易接口获取用户店铺的信息
		$data = json_decode($shopJson, TRUE);
		if($data['code'] == 100000)
		{
			$shop = $data['msg'];
			$newShop['Name'] = $shop['ShopName'];
			$newShop['DomainNum'] = $shop['DomainCount']; // 域名数量
			$newShop['Logo'] = $shop['Avatar']; // logo
			$newShop['Notice'] = $shop['ShopAnnounce']; // 店铺简介
			
			return array('data'=> $newShop,'status'=> 3); // 还未设置分销
		}
		elseif($data['code'] == 120001 || $data['code'] == 120002 || $data['code'] == 120003 || $data['code'] == 120004)
		{
			return array('data'=> array(),'status'=> 2); // 未开通店铺
		}
		else
		{
			\core\Logger::write('seller_agent_shopinfo', $data);
			throw new \Exception('获取信息失败，请确认店铺是否开启');
		}
	}

	/**
	 *
	 * @return multitype:array boolean
	 */
	private function shopInfo($id = 0)
	{
		$shopModel = new \ShopAgent();
		$fields = 'ShopAgeId,Name,Notice,Logo,DomainNum,Percent,FinishTime,CreateTime,Status';
		$condition['EnameId'] = $this->enameId;
		$id && $condition['ShopAgeId'] = $id;
		$shopArr = (array)$shopModel->getData($fields, $condition, $shopModel::FETCH_ROW);
		
		return $shopArr;
	}

	/**
	 * 获取已设置推广店铺信息
	 *
	 * @param int $shopAgentId
	 * @return multitype:boolean string |multitype:boolean multitype:string
	 * Ambigous <>
	 */
	public function getShopInfo($shopAgentId)
	{
		if(! $shopAgentId)
		{
			throw new \Exception('请先选择店铺！');
		}
		$shopInfo = $this->shopInfo($shopAgentId);
		if(! array_filter($shopInfo))
		{
			return array('flag'=> false,'data'=> '该店铺未设置推广！');
		}
		$data = array();
		$data['startDate'] = date('Y-m-d', $shopInfo['CreateTime']);
		$data['endDate'] = date('Y-m-d', $shopInfo['FinishTime']);
		$data['percent'] = $shopInfo['Percent'];
		
		return array('flag'=> true,'data'=> $data);
	}

	/**
	 * 设置店铺分销比例
	 *
	 * @param array $param
	 * @throws \Exception
	 * @return boolean
	 */
	public function setShopAgent($percent, $finishDate, $isAgree, $id = FALSE)
	{
		if(! $percent || ! $finishDate)
		{
			throw new \Exception('请先设置佣金比例/推广时间！');
		}
		if(! $isAgree)
		{
			throw new \Exception('请勾选并同意《域名联盟推广服务协议》');
		}
		\core\Logger::write('shop_agent_agreement', 
			array('EnameID:' . $this->enameId,'Agreement:' . $isAgree,'IP:' . \common\Client::getClientIp(0)));
		$flag = 0;
		$api = new EnameApi();
		$shopModel = new \ShopAgent();
		$minDays = 15 * 24 * 60 * 60; // 15天
		$time = strtotime($finishDate . " " . date('H') . ':' . date('i') . ':' . date('s')); // 推广时间
		$editTime = Config::item('edittime');
		$finishTime = strtotime("+{$editTime} second", $time);
		if($finishTime < time() + $minDays)
		{
			throw new \Exception('最少推广时间为15天！');
		}
		
		$shopJson = $api->sendCmd('agent/shopinfo', array('EnameId'=> $this->enameId)); // 调用交易接口获取用户店铺的信息
		$data = json_decode($shopJson, TRUE);
		
		if($data['code'] == 100000)
		{
			$shop = $data['msg'];
			$shopData['Name'] = $shop['ShopName'];
			$shopData['DomainNum'] = $shop['DomainCount']; // 域名数量
			$shopData['Recommands'] = implode(',', $shop['shopRecommands']);
			$shopData['GoodRating'] = $shop['sellerGoodRate'] * 100; // 好评率
			$shopData['Logo'] = $shop['Avatar']; // logo
			$shopData['Notice'] = $shop['ShopAnnounce']; // 店铺简介
			$shopData['Credit'] = $shop['sellerLevel']; // 店铺信用
			$shopData['Percent'] = $percent;
			$shopData['FinishTime'] = $finishTime;
			$shopData['Status'] = 1;
			$shopData['CreateTime'] = $shopData['UpdateTime'] = time();
			if($id)
			{
				$where['ShopAgeId'] = $id;
				$where['EnameId'] = $this->enameId;
				$row = $shopModel->update($shopData, $where);
				$row && $flag = 1;
			}
			else
			{
				$shopData['EnameId'] = $this->enameId;
				$id = $shopModel->insert($shopData);
				$id && $flag = 1;
			}
			$flag == 1 && Common::addScore($this->enameId, 5, "推广店铺：{$this->enameId}成功");
		}
		elseif($data['code'] == 120001 || $data['code'] == 120002 || $data['code'] == 120003 || $data['code'] == 120004)
		{
			$flag = 2; // 未开通店铺
		}
		else
		{
			\core\Logger::write('seller_agent_shopinfo', $data);
			throw new \Exception('获取信息失败，请确认店铺是否开启');
		}
		return $flag;
	}

	/**
	 * 店铺是否开启
	 *
	 * @throws \Exception
	 * @return boolean
	 */
	public function isOpen()
	{
		$api = new EnameApi();
		$shopJson = $api->sendCmd('agent/shopinfo', array('EnameId'=> $this->enameId)); // 调用交易接口获取用户店铺的信息
		$data = json_decode($shopJson, TRUE);
		
		if($data['code'] == 120001 || $data['code'] == 120002 || $data['code'] == 120003 || $data['code'] == 120004)
		{
			return 1; // 未开通店铺
		}
		elseif($data['code'] == 100000)
		{
			return 2;
		}
		else
		{
			\core\Logger::write('seller_agent_shopinfo', $data);
			return 3;
		}
	}

	/**
	 * 删除店铺分销
	 *
	 * @param unknown $shopAgentId
	 * @return multitype:boolean string |multitype:string boolean
	 */
	public function deleteShop($shopAgentId)
	{
		$shop = new \ShopAgent();
		$editTime = Config::item('edittime');
		$where['ShopAgeId'] = $shopAgentId;
		$where['EnameId'] = $this->enameId;
		$where['CreateTime'] = array('>',time() - $editTime);
		$row = $shop->delete($where);
		
		if(! $row)
		{
			return array('status'=> FALSE,'msg'=> '超过规定时间，不可以修改/删除！');
		}
		return array('status'=> TRUE,'msg'=> '');
	}

	/**
	 * 修改店铺分销
	 *
	 * @param int $agentId
	 * @param int $percent
	 * @throws \Exception
	 * @return boolean
	 */
	public function editShop($agentId, $percent, $finishDate)
	{
		$api = new EnameApi();
		$shop = new \ShopAgent();
		$minDays = 15 * 24 * 60 * 60;
		$createTime = $shop->getData('CreateTime', array('ShopAgeId'=> $agentId,'EnameId'=> $this->enameId), 
			$shop::FETCH_COLUMN);
		$time = strtotime(
			$finishDate . ' ' . date('H', $createTime) . ':' . date('i', $createTime) . ':' . date('s', $createTime));
		$finishTime = strtotime("+" . Config::item('edittime') . " second", $time);
		if($finishTime < time() + $minDays)
		{
			throw new \Exception('最少推广时间为15天');
		}
		
		$editTime = time() - Config::item('edittime');
		$where['ShopAgeId'] = $agentId;
		$where['EnameId'] = $this->enameId;
		$where['CreateTime'] = array('>',$editTime);
		$update['Percent'] = $percent;
		$update['FinishTime'] = $finishTime;
		$update['UpdateTime'] = time();
		$row = $shop->update($update, $where);
		
		return $row > 0;
	}

	/**
	 *
	 * @param int $id
	 * @return multitype:boolean string |multitype:string boolean
	 */
	public function checkShop($id)
	{
		$shopArr = $this->shopInfo($id);
		if(array_filter($shopArr))
		{
			$shopArr['IsEdit'] = $shopArr['CreateTime'] > time() - Config::item('edittime');
			$shopArr['FinishTime'] = $this->lib->newTimeToDHIS($shopArr['FinishTime'], TRUE);
			return array('shopInfo'=> $shopArr,'flag'=> true);
		}
		
		return array('shopInfo'=> '','flag'=> false);
	}

	/**
	 * 获取已分销列表
	 *
	 * @param mixed $param
	 * @return Ambigous <\driver\mixed, \core\mixed>
	 */
	public function getAgentedList($param, $pageSize)
	{
		$offset = array_key_exists('limit_start', $param)? intval($param['limit_start']): 0;
		$sort = array_key_exists('sort', $param)? $param['sort']: '';
		list($orderField, $order) = $this->lib->setOrder($sort);
		$orderList = $this->lib->setSort($orderField, $order);
		
		$condition = $this->getWhereList($param);
		$count = $this->model->count($condition);
		$isEmpty = $count <= 0? : FALSE;
		
		if($count > 0)
		{
			$fields = "DomainAgentId,TransId,DomainName,Price,TransType,FinishTime,Percent,EnameId,Topic,CreateTime,Topic";
			$orderBy = "{$orderField} {$order}";
			$limit = array($offset,$pageSize);
			$domainAgentList = $this->model->getData($fields, $condition, \DomainAgent::FETCH_ALL, $orderBy, $limit, 
				FALSE, FALSE, 'table\DomainAgentTable');
			$isEmpty = empty($domainAgentList)? : FALSE;
			return array($domainAgentList,$isEmpty,$count,$orderList);
		}
		
		return array(array(),TRUE,0,$orderList);
	}

	/**
	 * 获取域名详情页
	 *
	 * @param string $domainName
	 * @return multitype:number Ambigous <\driver\mixed, \core\mixed>
	 * |multitype:number boolean
	 */
	public function getDomainDetail($domainName, $domainAgentId)
	{
		if(! $domainName || ! $domainAgentId)
		{
			throw new \Exception('请先选择域名');
		}
		$status = 0;
		
		$condition['EnameId'] = $this->enameId;
		$condition['DomainName'] = $domainName;
		$condition['DomainAgentId'] = $domainAgentId;
		$fields = 'DomainAgentId,DomainName,Price,Percent,ClickNum';
		$orderBy = 'CreateTime Desc';
		
		// 分销中
		$agentDetails = $this->model->getData($fields, array_merge($condition, array('FinishTime'=> array('>',time()))), 
			\DomainAgent::FETCH_ROW, $orderBy);
		if(! empty($agentDetails))
		{
			return array('status'=> 6,'data'=> $agentDetails);
		}
		
		// 已完成分销|已结束分销
		$backupFields = 'AgentBackupId,DomainName,Price,Percent,ClickNum,BackupType';
		$backupDetails = $this->backup->getData($backupFields, $condition, \AgentBackup::FETCH_ROW, $orderBy);
		if(! empty($backupDetails))
		{
			$status = $backupDetails->BackupType;
			return array('status'=> $status,'data'=> $backupDetails);
		}
		
		if($status == 0)
		{
			throw new \Exception('未设置推广'); // 未设置分销
		}
	}

	/**
	 * 获取已售出域名
	 *
	 * @param array $param 搜索条件数组
	 * @param int $pageSize
	 * @return array
	 */
	public function getSoldDomain($param, $pageSize)
	{
		$offset = array_key_exists('limit_start', $param)? intval($param['limit_start']): 0;
		$sort = array_key_exists('sort', $param)? $param['sort']: 'percent-desc';
		list($orderField, $order) = $this->lib->setOrder($sort);
		$orderList = $this->lib->setSort($orderField, $order);
		
		$fields = 'AgentBackupId,DomainAgentId,DomainName,TransType,Price,Topic,Percent';
		$condition = $this->getWhereList($param, TRUE, FALSE);
		$condition['BackupType'] = 3;
		$count = $this->backup->count($condition);
		if($count > 0)
		{
			$orderBy = "{$orderField} {$order}";
			$limit = array($offset,$pageSize);
			$soldDomain = $this->backup->getData($fields, $condition, \AgentBackup::FETCH_ALL, $orderBy, $limit);
			$isEmpty = empty($soldDomain)? : FALSE;
			return array($soldDomain,$isEmpty,$count,$orderList);
		}
		return array(array(),TRUE,0,$orderList);
	}

	/**
	 * 修改分销比例
	 *
	 * @param int $domainAgentId
	 * @param int $percent
	 * @return multitype:number string
	 */
	public function editPercent($domainAgentId, $percent)
	{
		$domainAgentId = is_array($domainAgentId)? $domainAgentId: (array)$domainAgentId;
		if(! array_filter($domainAgentId) || ! $percent)
		{
			return array(1,''); // 参数有误
		}
		
		$failed = array();
		foreach($domainAgentId as $id)
		{
			$agentData = $this->domainAgent($id, 'DomainAgentId,DomainName,CreateTime,Percent');
			if(empty($agentData))
			{
				continue;
			}
			if($agentData->CreateTime < time() - Config::item('edittime'))
			{
				$failed[1][] = $agentData->DomainName; // 超过半个小时
				continue;
			}
			
			$update['Percent'] = $percent;
			$update['UpdateTime'] = time();
			$where['DomainAgentId'] = $id;
			$where['EnameId'] = $this->enameId;
			$row = $this->model->update($update, $where); // 修改分销比例
			if(! $row)
			{
				$failed[2][] = $agentData->DomainName;
			}
			else
			{
				\core\Logger::write('seller_edit_percent', 
					"DomainAgentId:{$agentData->DomainAgentId}, Domain:{$agentData->DomainName}, Old:{$agentData->Percent}%, New:{$percent}%, EnameId:{$this->enameId}");
			}
		}
		$status = ! empty($failed)? 2: 3;
		return array($status,$failed);
	}

	/**
	 * 删除域名分销
	 *
	 * @param int $domainAgentId
	 * @return multitype:boolean string
	 */
	public function deletePercent($domainAgentId)
	{
		$domainAgentId = is_array($domainAgentId)? $domainAgentId: (array)$domainAgentId;
		if(! array_filter($domainAgentId))
		{
			return array(1,'');
		}
		
		$failed = array();
		foreach($domainAgentId as $id)
		{
			$agentData = $this->domainAgent($id, '*');
			if(empty($agentData))
			{
				continue;
			}
			if($agentData->CreateTime < time() - Config::item('edittime'))
			{
				$failed[1][] = $agentData->DomainName; // 超过半个小时
				continue;
			}
			
			$this->saveTemp($agentData); // 插入到未设置临时表中
			$where['DomainAgentId'] = $id;
			$where['EnameId'] = $this->enameId;
			$row = $this->model->delete($where); // 删除分销比例
			if(! $row)
			{
				$failed[2][] = $agentData->DomainName;
			}
			else
			{
				\core\Logger::write('seller_delete_percent', 
					"DomainAgentId:{$agentData->DomainAgentId}, Domain:{$agentData->DomainName}, Percent:{$agentData->Percent}%, EnameId:{$this->enameId}");
			}
		}
		$status = ! empty($failed)? 2: 3;
		return array($status,$failed);
	}

	/**
	 * 插入到备份表
	 *
	 * @param \stdClass $agentData
	 */
	private function saveTemp(\stdClass $agentData)
	{
		$insert['EnameId'] = $agentData->EnameId;
		$insert['DomainName'] = $agentData->DomainName;
		$insert['Price'] = $agentData->Price;
		$insert['TransType'] = $agentData->TransType;
		$insert['TLD'] = $agentData->TLD;
		$insert['GroupOne'] = $agentData->GroupOne;
		$insert['GroupTwo'] = $agentData->GroupTwo;
		$insert['DomainLen'] = $agentData->DomainLen;
		$insert['CreateTime'] = $insert['UpdateTime'] = time();
		$insert['SimpleDec'] = $agentData->SimpleDec;
		$insert['FinishTime'] = $agentData->FinishTime;
		$this->temp->insert($insert);
	}

	/**
	 * 根据分销id获取记录
	 *
	 * @param unknown $domainAgentId
	 * @return Ambigous <\driver\mixed, \core\mixed>
	 */
	private function domainAgent($domainAgentId, $fields = '*')
	{
		$condition['DomainAgentId'] = $domainAgentId;
		$condition['EnameId'] = $this->enameId;
		$agentData = $this->model->getData($fields, $condition, \DomainAgent::FETCH_ROW, 'CreateTime Desc');
		
		return $agentData;
	}

	/**
	 * 未设置分销
	 *
	 * @param array $param
	 * @param int $pageSize
	 * @return array boolean
	 */
	public function getUnset($param, $pageSize)
	{
		$insert = array();
		$redis = Redis::getInstance();
		$key = "agent_{$this->enameId}_request_time";
		$offset = array_key_exists('limit_start', $param)? intval($param['limit_start']): 0;
		$sort = array_key_exists('sort', $param)? $param['sort']: '';
		list($orderField, $order) = $this->lib->setOrder($sort);
		$orderList = $this->lib->setSort($orderField, $order);
		
		if(! $redis->exists($key)) // 判断上次请求时间在十五分钟之外
		{
			switch(\core\Config::item('ts_data'))
			{
				case 1:
					$domainSolr = new DomainAuctionSolr();
					if(! $domainSolr->ping())
					{
						return array(array(),TRUE,0,$orderList,TRUE);
					}
					$flag = $this->compareToAgent($domainSolr);
					break;
				case 2:
					$flag = $this->compareToAgentByEs();
					break;
				default:
					break;
			}
			if(! $flag)
			{
				return array(array(),TRUE,0,$orderList,FALSE);
			}
			$redis->setex($key, Config::item('requesttime'), 1); // 设置用户请求时间
		}
		
		// 读取临时表的数据
		$fields = 'TempSolrId,DomainName,EnameId,TransType,Price,FinishTime,Topic';
		$condition = $this->getWhereList($param, FALSE);
		$num = $this->temp->count($condition);
		if($num <= 0)
		{
			return array(array(),TRUE,0,$orderList,FALSE);
		}
		$orderBy = "{$orderField} {$order}";
		$limit = array($offset,$pageSize);
		$unset = $this->temp->getData($fields, $condition, \TemporarySolr::FETCH_ALL, $orderBy, $limit);
		$isEmpty = empty($unset)? : FALSE;
		if(! $isEmpty)
		{
			foreach($unset as $k => $value)
			{
				$data[$k]['TempSolrId'] = $value->TempSolrId;
				$data[$k]['DomainName'] = $value->DomainName;
				$data[$k]['EnameId'] = $value->EnameId;
				$data[$k]['TransType'] = $value->TransType;
				$data[$k]['Price'] = round($value->Price, 2);
				$data[$k]['FinishTime'] = $this->lib->newTimeToDHIS($value->FinishTime);
				$data[$k]['Topic'] = $value->Topic;
			}
		}
		return array($data,$isEmpty,$num,$orderList,FALSE);
	}

	private function compareToAgentByEs()
	{
		$status = 0;
		$offset = 0;
		$count = $pageSize = 1000;
		$allDomains = $insert = $newData = array();
		
		$domainEs = new DomainElasticSearch();
		while($offset < $count)
		{
			$allData = $domainEs->getInfoByUser($this->enameId, '', $offset, $pageSize);
			$count = $allData['total'];
			if(0 >= $count)
			{
				break;
			}
			
			foreach($allData['data'] as $key => $val)
			{
				$newData[$key] = $val['_source'];
			}
			$allDomains = array_merge($allDomains, $newData);
			$offset += $pageSize;
		}
		
		if(empty($allDomains))
		{
			return FALSE;
		}
		
		// 分销表的数据
		$fields = 'DomainAgentId,EnameId,DomainName,TransId';
		$agentedData = $this->model->getData($fields, 
			array('EnameId'=> $this->enameId,'FinishTime'=> array('>',time())));
		
		// 清空临时表中的数据
		$this->temp->delete(array('EnameId'=> $this->enameId), NULL);
		foreach($allDomains as $key => $domain)
		{
			if(! empty($agentedData))
			{
				foreach($agentedData as $agented)
				{
					if($agented->EnameId == $domain['t_enameId'] && $agented->DomainName == $domain['t_dn'] &&
						 $agented->TransId == $domain['t_id'])
					{
						$where['EnameId'] = $agented->EnameId;
						$where['DomainName'] = $agented->DomainName;
						$this->temp->delete($where);
						continue 2;
					}
				}
			}
			$insert[] = $this->newTempSolr($domain);
		}
		
		if(empty($insert))
		{
			return FALSE;
		}
		
		// 比对完后插入临时表中
		foreach($insert as $value)
		{
			$value['CreateTime'] = $value['UpdateTime'] = time();
			$this->temp->insert($value);
		}
		return TRUE;
	}

	/**
	 * 用户solr数据与本地分销比对
	 *
	 * @return boolean
	 */
	private function compareToAgent(DomainAuctionSolr $domainSolr)
	{
		$status = 0;
		$offset = 0;
		$count = $pageSize = 1000;
		$allDomains = $insert = array();
		
		// solr数据
		while($offset < $count)
		{
			$allData = $domainSolr->getTransByUser($this->enameId, '', 0, $offset, $pageSize); // 根据用户ID查询正在交易中的数据
			$count = $allData['numFound']; // 总数
			if($count == 0)
			{
				break;
			}
			$allDomains = array_merge($allDomains, $allData['docs']);
			$offset += $pageSize;
		}
		
		// 分销表的数据
		$fields = 'DomainAgentId,EnameId,DomainName,TransId';
		$agentedData = $this->model->getData($fields, 
			array('EnameId'=> $this->enameId,'FinishTime'=> array('>',time())), \DomainAgent::FETCH_ALL, 
			'DomainName ASC');
		
		$this->temp->delete(array('EnameId'=> $this->enameId), NULL); // 清空临时表中的数据
		
		/* 比对本地分销表与solr的数据 */
		if(empty($allDomains))
		{
			return FALSE;
		}
		
		foreach($allDomains as $key => $domain)
		{
			if(! empty($agentedData))
			{
				foreach($agentedData as $agented)
				{
					if($agented->EnameId == $domain['Seller'] && $agented->DomainName == $domain['DomainName'] &&
						 $agented->TransId == $domain['AuditListId'])
					{
						$where['EnameId'] = $agented->EnameId;
						$where['DomainName'] = $agented->DomainName;
						$this->temp->delete($where);
						continue 2;
					}
				}
			}
			$insert[] = $this->newTempSolr($domain);
		}
		
		if(empty($insert))
		{
			return FALSE;
		}
		
		// 比对完后插入临时表中
		foreach($insert as $value)
		{
			$value['CreateTime'] = $value['UpdateTime'] = time();
			$this->temp->insert($value);
		}
		return TRUE;
	}

	/**
	 * 拼接插入临时表的数组
	 *
	 * @param array $domain
	 * @return number
	 */
	private function newTempSolr($domain)
	{
		$domainSysOne = $domainSysTwo = $domainSysThree = $domainLength = 0;
		switch(\core\Config::item('ts_data'))
		{
			case 1:
				extract(Domain::getDomainGroup($domain['DomainName']), EXTR_REFS);
				$dn = $domain['DomainName'];
				$enameId = $domain['Seller'];
				$transType = $domain['TransType'];
				$tld = Domain::getDomainLtd($domain['DomainName']);
				$finishTime = strtotime(str_replace(array('T','Z'), ' ', $domain['FinishDate']));
				$price = $domain['BidPrice'];
				$simpleDesc = $domain['SimpleDec'];
				$topic = $domain['TransTopic'];
				break;
			case 2:
				$dn = $domain['t_dn'];
				$enameId = $domain['t_enameId'];
				switch($domain['t_type'])
				{
					case 4:
					case 6:
					case 7:
					case 8:
						$transType = 1;
						break;
					case 1:
						$transType = 4;
				}
				$tld = $domain['t_tld'];
				$finishTime = $domain['t_complate_time'];
				$price = $domain['t_now_price'];
				$simpleDesc = $domain['t_desc'];
				$topic = $domain['t_topic'];
				$domainSysOne = $domain['t_class_name'];
				$domainSysTwo = $domain['t_two_class'];
				$domainSysThree = $domain['t_three_class'];
				$domainLength = $domain['t_len'];
				break;
			default:
				break;
		}
		$insert['DomainName'] = $dn;
		$insert['EnameId'] = $enameId;
		$insert['TransType'] = $transType;
		$insert['TLD'] = $tld;
		$insert['FinishTime'] = $finishTime;
		$insert['GroupOne'] = $domainSysOne;
		$insert['GroupTwo'] = $domainSysTwo;
		$insert['DomainLen'] = $domainLength;
		$insert['Price'] = $price;
		$insert['SimpleDec'] = $simpleDesc;
		$insert['Topic'] = $topic;
		$insert['GroupThree'] = $domainSysThree;
		
		return $insert;
	}

	/**
	 * 设置搜索条件数组
	 *
	 * @param array $param
	 * @return array $condition
	 */
	public function getWhereList(array $param, $isPercent = TRUE, $isTime = TRUE)
	{
		$condition['EnameId'] = $this->enameId;
		// $tldList = \core\Config::item('tld')->toArray();
		isset($param['domaintld']) && $param['domaintld'] && $condition['TLD'] = intval($param['domaintld']);
		isset($param['transtype']) && $param['transtype'] && $condition['TransType'] = intval($param['transtype']);
		isset($param['domainname']) && $param['domainname'] &&
			 $condition['DomainName'] = '%' . $this->lib->getFilterDomain($param['domainname']) . '%';
		
		// 系统分组
		if(isset($param['domaingroup']))
		{
			list($condition['GroupOne'], $condition['GroupTwo'], $condition['GroupThree'], $condition['DomainLen']) = $this->lib->getSysGroupVal(
				$param['domaingroup']);
		}
		
		// 竞价范围
		$priceStart = isset($param['pricestart'])? intval($param['pricestart']): 0;
		$priceEnd = isset($param['priceend'])? intval($param['priceend']): 0;
		list($price, $isEnd) = $this->lib->getRange($priceStart, $priceEnd);
		$condition['Price'] = is_array($price)? array('BETWEEN',$price): ($isEnd? $price: array('>=',$price));
		
		// 分销比例范围
		if($isPercent)
		{
			$percentStart = isset($param['percentstart'])? intval($param['percentstart']): 0;
			$percentEnd = isset($param['percentend'])? intval($param['percentend']): 0;
			list($percent, $isEnd) = $this->lib->getRange($percentStart, $percentEnd);
			$condition['Percent'] = is_array($percent)? array('BETWEEN',$percent): ($isEnd? $percent: array('>=',
					$percent));
		}
		
		// 剩余时间
		if($isTime)
		{
			$condition['FinishTime'] = array('>',time());
			if(isset($param['finishtime']) && $param['finishtime'])
			{
				$lastTime = $this->lib->setFinishTime($param['finishtime']);
				$condition['FinishTime'] = array('BETWEEN',array(time(),$lastTime));
			}
		}
		
		return $condition;
	}

	/**
	 * 根据ID查找分销记录
	 *
	 * @param int $id
	 * @return multitype:boolean
	 */
	public function getInfoById($id)
	{
		$data = $this->domainAgent($id, 'DomainAgentId,CreateTime');
		if(! empty($data))
		{
			$isEdit = $data->CreateTime > time() - Config::item('edittime');
			$leftTime = Config::item('edittime') - time() + $data->CreateTime;
			return array('status'=> TRUE,'isEdit'=> $isEdit,'leftTime'=> $leftTime);
		}
		
		return array('status'=> FALSE,'isEdit'=> FALSE,'leftTime'=> 0);
	}

	/**
	 * 判断是否已经设置过佣金比例
	 *
	 * @param string $domain
	 * @return num $status 域名状态
	 */
	private function isAgented($domain)
	{
		$status = 1; // 1还未设置分销
		$domainEs = new DomainElasticSearch();
		$dn = $this->lib->getDomainSearch($domain);
		$data = $domainEs->getInfoByUser($this->enameId, $dn);
		if(empty($data['data']) && 0 >= $data['total'])
		{
			return array($data, 3); // 找不到淘域名的数据
		}
		else
		{
			$condition['DomainName'] = $domain;
			$condition['EnameId'] = $this->enameId;
			$fields = 'DomainAgentId,CreateTime';
			$orderBy = 'CreateTime DESC';
			$agent = $this->model->getData($fields, $condition, \DomainAgent::FETCH_ROW, $orderBy);
			if($agent)
			{
				$status = $agent->CreateTime > time() - Config::item('edittime')? 2: 0; // 2：修改时间还没超过半个小时，0：修改时间超过半个小时的
			}
		}
		
		return array($data, $status);
	}
}