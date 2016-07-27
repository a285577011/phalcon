<?php
namespace logic\agent;
use core\ModelBase;
use core\Page;
use lib\agent\AgentGuestsLib;
use logic\ad\Advert;
use Phalcon\DI;
use Phalcon\Mvc\Url;
use \logic\common\Common;

class AgentGuests
{

	const AD_WIDTH_NAME = 'ename_ad_width';

	const AD_HEIGHT_NAME = 'ename_ad_height';

	const AD_POSID_NAME = 'ename_ad_solt';

	const DATA_SOLR = 0;

	protected $lib;

	protected $enameId;

	public function __construct($enameId = '')
	{
		$this->enameId = $enameId;
		$this->lib = new AgentGuestsLib();
	}

	/**
	 * 获取分销域名列表
	 *
	 * @param unknown $domainName
	 * @param unknown $tld
	 * @param unknown $finishTime
	 * @param unknown $startPrice
	 * @param unknown $endPrice
	 * @param unknown $group
	 * @param unknown $transType
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $start
	 * @param unknown $sort
	 * @return multitype:NULL Ambigous <boolean, multitype:unknown multitype: >
	 */
	public function getDomainAgentList($domainName, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
		$startCommission, $endCommission, $start, $sort, $topic)
	{
		switch(self::DATA_SOLR)
		{
			case 0:
				return $this->getDomainByDb($domainName, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
					$startCommission, $endCommission, $start, $sort, $topic);
				break;
			case 1:
				// SOLR
				break;
		}
	}

	/**
	 * 通过数据库获取域名数据
	 *
	 * @param unknown $domainName
	 * @param unknown $tld
	 * @param unknown $finishTime
	 * @param unknown $startPrice
	 * @param unknown $endPrice
	 * @param unknown $group
	 * @param unknown $transType
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $start
	 * @param unknown $sort
	 * @return multitype:NULL Ambigous <boolean, multitype:unknown multitype: >
	 */
	public function getDomainByDb($domainName, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
		$startCommission, $endCommission, $start, $sort, $topic)
	{
		$model = new ModelBase('domain_agent');
		$field = 'DomainName,DomainAgentId,TransId,TransType,FinishTime,Percent,SimpleDec,Price,Topic';
		$where = $this->setDomainAgentWhere($domainName, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
			$startCommission, $endCommission, $topic);
		$order = $this->lib->setOrder($sort, array('finishtime','percent','price'), array('asc','desc'));
		$orderField = 'finishtime';
		$orderby = 'desc';
		if(is_array($order))
		{
			list($orderField, $orderby) = $order;
		}
		$limit = array($start,\core\Config::item('pagesize'));
		$data['list'] = $model->getData($field, $where, $model::FETCH_ALL, $orderField . ' ' . $orderby, $limit);
		$data['list'] = $this->lib->formatDomainAgent($data['list']);
		$count = $model->count($where, 'DomainAgentId');
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		list($priceSymbol, $finishTimeSymbol, $commissionSymbol, $priceSort, $finishTimeSort, $commissionSort) = $this->lib->setSort(
			$orderField, $orderby);
		$data['order'] = array('priceSymbol'=> $priceSymbol,'finishTimeSymbol'=> $finishTimeSymbol,
				'commissionSymbol'=> $commissionSymbol,'priceSort'=> $priceSort,'finishTimeSort'=> $finishTimeSort,
				'commissionSort'=> $commissionSort);
		$data['tld'] = \core\Config::item('tld')->toArray();
		$data['domaingroup'] = \core\Config::item('domaingroup')->toArray();
		$data['finishtime'] = \core\Config::item('finishtime')->toArray();
		$data['AgentType'] = 1;
		return $data;
	}

	/**
	 * 设置域名分销列表搜索条件
	 *
	 * @param unknown $domainName
	 * @param unknown $tld
	 * @param unknown $finishTime
	 * @param unknown $startPrice
	 * @param unknown $endPrice
	 * @param unknown $group
	 * @param unknown $transType
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @return multitype:string unknown
	 */
	public function setDomainAgentWhere($domainName, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
		$startCommission, $endCommission, $topic = false)
	{
		$where = array();
		list($domainName, $newTld) = $this->lib->setDomainAndTld($domainName, $tld);
		$domainName && $where['DomainName'] = '%' . $domainName . '%';
		$where['TLD'] = $newTld;
		$finishTime = $this->lib->setFinishTimedKey($finishTime);
		$price = $this->lib->setRang($startPrice, $endPrice, true);
		$price? is_array($price)? $where['Price'] = array('between',$price): $where['Price'] = array('>=',$price): '';
		$finishTime? $where['FinishTime'] = array('between',array(time(),$finishTime)): $where['FinishTime'] = array(
				'>',time());
		$lib = new \lib\agent\AgentManagerLib();
		list($where['GroupOne'], $where['GroupTwo'], $where['GroupThree'], $where['DomainLen']) = $lib->getSysGroupVal(
			$group);
		$transType && $where['TransType'] = $transType;
		$Commission = $this->lib->setRang($startCommission, $endCommission, true);
		$Commission && is_array($Commission)? $where['Percent'] = array('between',$Commission): $where['Percent'] = array(
				'>=',$Commission);
		$where['CreateTime'] = array('<=',time() - \core\Config::item('edittime'));
		$topic && $where['topic'] = $topic;
		return $where;
	}

	/**
	 * 获取店铺分销列表
	 *
	 * @param unknown $shopName
	 * @param unknown $startCredit
	 * @param unknown $endCredit
	 * @param unknown $starGoodRating
	 * @param unknown $endGoodRating
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $start
	 * @param unknown $sort
	 * @return multitype:string Ambigous <NULL, boolean, multitype:unknown
	 * multitype: >
	 */
	public function getShopAgentList($shopName, $startCredit, $endCredit, $starGoodRating, $endGoodRating, 
		$startCommission, $endCommission, $start, $sort)
	{
		switch(self::DATA_SOLR)
		{
			case 0:
				return $this->getShopByDb($shopName, $startCredit, $endCredit, $starGoodRating, $endGoodRating, 
					$startCommission, $endCommission, $start, $sort);
				break;
			case 1:
				// SOLR
				break;
		}
	}

	/**
	 * 通过数据库获取店铺数据
	 *
	 * @param unknown $shopName
	 * @param unknown $startCredit
	 * @param unknown $endCredit
	 * @param unknown $starGoodRating
	 * @param unknown $endGoodRating
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $start
	 * @param unknown $sort
	 * @return multitype:string Ambigous <NULL, boolean, multitype:unknown
	 * multitype: >
	 */
	public function getShopByDb($shopName, $startCredit, $endCredit, $starGoodRating, $endGoodRating, $startCommission, 
		$endCommission, $start, $sort)
	{
		$model = new ModelBase('shop_agent');
		$orderField = $orderby = null;
		$field = 'ShopAgeId,EnameId,Name,DomainNum,Notice,GoodRating,Credit,Percent,FinishTime';
		$where = $this->setShopAgentWhere($shopName, $startCredit, $endCredit, $starGoodRating, $endGoodRating, 
			$startCommission, $endCommission, $sort);
		$order = $this->lib->setOrder($sort, array('domainnum','goodrating','credit','percent','finishtime'), 
			array('asc','desc'));
		$orderField = 'percent';
		$orderby = 'desc';
		if(is_array($order))
		{
			list($orderField, $orderby) = $order;
		}
		$limit = array($start,\core\Config::item('pagesize'));
		$data['list'] = $model->getData($field, $where, $model::FETCH_ALL, $orderField . ' ' . $orderby, $limit);
		$data['list'] = $this->lib->formatShopAgent($data['list']);
		$count = $model->count($where, 'ShopAgeId');
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		$data['AgentType'] = 2;
		list($domainnumSymbol, $goodratingSymbol, $creditSymbol, $percentSymbol, $finishtimeSymbol, $domainnumSort, $goodratingSort, $creditSort, $percentSort, $finishtimeSort) = $this->lib->setShopSort(
			$orderField, $orderby);
		$data['order'] = array('domainnumSymbol'=> $domainnumSymbol,'goodratingSymbol'=> $goodratingSymbol,
				'creditSymbol'=> $creditSymbol,'percentSymbol'=> $percentSymbol,'finishtimeSymbol'=> $finishtimeSymbol,
				'domainnumSort'=> $domainnumSort,'goodratingSort'=> $goodratingSort,'creditSort'=> $creditSort,
				'percentSort'=> $percentSort,'finishtimeSort'=> $finishtimeSort);
		return $data;
	}

	/**
	 * 设置店铺分销搜索条件
	 *
	 * @param unknown $shopName
	 * @param unknown $startCredit
	 * @param unknown $endCredit
	 * @param unknown $starGoodRating
	 * @param unknown $endGoodRating
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $sort
	 * @return multitype:string multitype:string unknown multitype:string
	 * Ambigous <boolean, unknown, multitype:unknown , number>
	 */
	public function setShopAgentWhere($shopName, $startCredit, $endCredit, $starGoodRating, $endGoodRating, 
		$startCommission, $endCommission, $sort)
	{
		$where = array();
		$shopName && $where['Name'] = '%' . $shopName . '%';
		$Credit = $this->lib->setRang($startCredit, $endCredit, true);
		$Credit? is_array($Credit)? $where['Credit'] = array('between',$Credit): $where['Credit'] = array('>=',$Credit): '';
		$GoodRating = $this->lib->setRang($starGoodRating, $endGoodRating, true);
		$GoodRating? is_array($GoodRating)? $where['GoodRating'] = array('between',$GoodRating): $where['GoodRating'] = array(
				'>=',$GoodRating): '';
		$Commission = $this->lib->setRang($startCommission, $endCommission);
		$Commission && is_array($Commission)? $where['Percent'] = array('between',$Commission): $where['Percent'] = array(
				'>=',$Commission);
		$where['CreateTime'] = array('<=',time() - \core\Config::item('edittime'));
		$where['FinishTime'] = array('>=',time());
		$where['DomainNum'] = array('>',0);
		$where['status'] = 1;
		return $where;
	}

	/**
	 * 获取自动分销的域名列表并生成code
	 *
	 * @param unknown $tld
	 * @param unknown $finishTime
	 * @param unknown $startPrice
	 * @param unknown $endPrice
	 * @param unknown $group
	 * @param unknown $transType
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $PlatformId
	 * @param unknown $PlatformType
	 * @param unknown $StyleId
	 * @param unknown $limit
	 * @return string
	 */
	public function getAutoAgentList($Agreement, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
		$startCommission, $endCommission, $PlatformId, $PlatformType, $StyleId, $TemplateDId = false)
	{
		$this->checkStyleId($PlatformType, $StyleId);
		$where = $this->setDomainAgentWhere(null, null, $finishTime, $startPrice, $endPrice, $group, $transType, 
			$startCommission, $endCommission);
		$GroupOne = isset($where['GroupOne'])? $where['GroupOne']: false;
		$GroupThree = isset($where['GroupThree'])? $where['GroupThree']: false;
		$GroupTwo = isset($where['GroupTwo'])? is_array($where['GroupTwo'])? $where['GroupTwo'][1]: $where['GroupTwo']: false;
		$DomainLen = isset($where['DomainLen'])? is_array($where['DomainLen'])? $where['DomainLen'][1][1]: $where['DomainLen']: false;
		$price = isset($where['Price'])? $where['Price'][1]: false;
		$Commission = isset($where['Percent'])? $where['Percent'][1]: false;
		$price = $this->lib->setJson($price);
		$Commission = $this->lib->setJson($Commission);
		if(! $posId = $this->checkAdPos($PlatformId, $PlatformType, $StyleId, $SpreadType = 3, null, $transType, 
			$GroupOne, $GroupTwo, $DomainLen, $price, $Commission, $tld, $GroupThree))
		{
			$adModel = new ModelBase('ad_pos');
			$insert = array('CreateTime'=> time(),'EnameId'=> $this->enameId,'TransType'=> $transType,
					'GroupOne'=> $GroupOne,'GroupTwo'=> $GroupTwo,'DomainLen'=> $DomainLen,'PlatformId'=> $PlatformId,
					'PlatformType'=> $PlatformType,'StyleId'=> $StyleId,'SpreadType'=> $SpreadType,'PriceRange'=> $price,
					'CommissionRange'=> $Commission,'TLD'=> $tld,'GroupThree'=> $GroupThree);
			$posId = $adModel->insert($insert);
			$posidM = new ModelBase('posid_key');
			$str = self::makeCrcAuto($tld, $price, $GroupOne, $GroupTwo, $DomainLen, $transType, $Commission, 
				$PlatformId, $PlatformType, $StyleId, $SpreadType, $this->enameId, $GroupThree);
			$posidM->insert(array('PosId'=> $posId,'IdKey'=> $str));
		}
		\core\Logger::write('spread_agreement', 
			array('enamID:' . $this->enameId,'Agreement:' . $Agreement,'ip:' . \common\Client::getClientIp(0),
					'posId:' . $posId));
		$adKey = $PlatformType == 2? 'ad_zhanshiye_style': 'adstyle';
		$styleData = \core\Config::item($adKey)->toArray();
		if(! array_key_exists($StyleId, $styleData))
		{
			throw new \Exception('样式ID错误!');
		}
		if($PlatformType == 2 && $PlatformId)
		{
			return array($posId,$PlatformId);
		}
		/*
		 * $jscode = '<script>var adInfo = {' . self::AD_POSID_NAME . ':' .
		 * $posId . ',' . self::AD_WIDTH_NAME . ':' .
		 * $styleData[$StyleId]['width'] . ',' . self::AD_HEIGHT_NAME . ':' .
		 * $styleData[$StyleId]['height'] . '};</script>';
		 */
		$jscode = '<script>var adInfo = {' . self::AD_POSID_NAME . ':' . $posId . '}</script>';
		$jscode .= '<script type="text/javascript" src="http://' . @$_SERVER['HTTP_HOST'] . '/js/show_o.js"></script>';
		return array($jscode,$posId);
	}

	public function checkStyleId($PlatformType, $StyleId)
	{
		switch($PlatformType)
		{
			case 1:
				if($StyleId > count(\core\Config::item('adstyle')->toArray()) || $StyleId <= 0)
				{
					throw new \Exception('样式错误!');
				}
				break;
			case 2:
				if(! in_array($StyleId, \core\Config::item('sysStyleId')->toArray()) || $StyleId <= 0)
				{
					throw new \Exception('样式错误!');
				}
				break;
			case 3:
				break;
			default:
				throw new \Exception('渠道类型错错误!');
		}
	}

	/**
	 * 检查自动筛选是否有结果并返回结果
	 *
	 * @param unknown $tld
	 * @param unknown $finishTime
	 * @param unknown $startPrice
	 * @param unknown $endPrice
	 * @param unknown $group
	 * @param unknown $transType
	 * @param unknown $startCommission
	 * @param unknown $endCommission
	 * @param unknown $limit
	 * @throws \Exception
	 */
	public function checkDomain($tld, $finishTime, $startPrice, $endPrice, $group, $transType, $startCommission, 
		$endCommission, $limit)
	{
		$model = new ModelBase('domain_agent');
		$field = 'DomainAgentId';
		$where = $this->setDomainAgentWhere(null, $tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
			$startCommission, $endCommission);
		$where['topic'] = 0;
		$data = $model->getData($field, $where, $model::FETCH_ALL, false, $limit);
		if(empty($data))
		{
			return false;
		}
		return $data;
	}

	/**
	 * 推广分销
	 *
	 * @param unknown $AgentId
	 * @param unknown $AgentType
	 * @param unknown $PlatformId
	 * @param unknown $PlatformType
	 * @param unknown $StyleId
	 * @return string
	 */
	public function spreadAgent($Agreement, $AgentId, $AgentType, $PlatformId, $PlatformType, $StyleId, 
		$isreview = false)
	{
		$this->checkStyleId($PlatformType, $StyleId);
		$posId = $this->creatPosAd($AgentId, $AgentType, $PlatformId, $PlatformType, $StyleId);
		\core\Logger::write('spread_agreement', 
			array('enamID:' . $this->enameId,'Agreement:' . $Agreement,'ip:' . \common\Client::getClientIp(0),
					'posId:' . $posId));
		if($PlatformType == 2 && $PlatformId)
		{
			return array($posId,$PlatformId);
		}
		switch($PlatformType)
		{
			case 1:
			case 2:
				$adKey = $PlatformType == 2? 'ad_zhanshiye_style': 'adstyle';
				$styleData = \core\Config::item($adKey)->toArray();
				if(! array_key_exists($StyleId, $styleData))
				{
					throw new \Exception('样式ID错误!');
				}
				/*
				 * $jscode = '<script>var adInfo = {' . self::AD_POSID_NAME .
				 * ':' . $posId . ',' . self::AD_WIDTH_NAME . ':' .
				 * $styleData[$StyleId]['width'] . ',' . self::AD_HEIGHT_NAME .
				 * ':' . $styleData[$StyleId]['height'] . '};</script>';
				 */
				$jscode = '<script>var adInfo = {' . self::AD_POSID_NAME . ':' . $posId . '}</script>';
				$jscode .= '<script type="text/javascript" src="http://' . @$_SERVER['HTTP_HOST'] .
					 '/js/show_o.js"></script>';
				if($isreview)
				{
					$review = $this->reviewAd($AgentId, $AgentType, $StyleId);
					return array($review,$jscode);
				}
				return $jscode;
				break;
			case 3:
				$logic = new Advert();
				return $logic->creatSpreadCode($posId);
				break;
		}
	}

	/**
	 * 创建广告位(普通推广)
	 *
	 * @param unknown $AgentId
	 * @param unknown $AgentType
	 * @param unknown $PlatformId
	 * @param unknown $PlatformType
	 * @param unknown $StyleId
	 * @return Ambigous <number, string>
	 */
	public function creatPosAd($AgentId, $AgentType, $PlatformId, $PlatformType, $StyleId)
	{
		$AgentId = $this->lib->filterAgentId($AgentId);
		$posId = $this->checkAdPos($PlatformId, $PlatformType, $StyleId, $AgentType, $AgentId);
		if(! $posId)
		{
			$adModel = new ModelBase('ad_pos');
			$insert = array('CreateTime'=> time(),'EnameId'=> $this->enameId,'TransType'=> 0,'GroupOne'=> 0,
					'GroupTwo'=> 0,'DomainLen'=> 0,'PlatformId'=> $PlatformId,'PlatformType'=> $PlatformType,
					'StyleId'=> $StyleId,'SpreadType'=> $AgentType,'PriceRange'=> '','CommissionRange'=> '');
			$posId = $adModel->insert($insert);
			if(is_array($AgentId) && ! empty($AgentId))
			{
				$agentBackupM = new ModelBase('agent_backup');
				$agadModel = new ModelBase('agent_pos');
				$recordM = $AgentType == 2? new ModelBase('spread_shop_record'): $recordM = new ModelBase(
					'spread_domain_record');
				$agentM = $AgentType == 2? new ModelBase('shop_agent'): new ModelBase('domain_agent');
				$url = new Url();
				foreach($AgentId as $value)
				{
					$id = $agadModel->insert(
						array('AgentId'=> $value,'AgentType'=> $AgentType,'CreateTime'=> time(),'PosId'=> $posId));
					if(! $id)
					{
						\core\Logger::write('Agent_Guests', 
							'创建普通广告位插入广告位对应分销(agent_pos)表失败,分销ID' . $value . '广告位ID' . $posId);
					}
					$code = '';
					switch($PlatformType)
					{
						case 1:
						case 2:
							$code = '<script>var adInfo = {' . self::AD_POSID_NAME . ':' . $posId . '}</script>';
							$code .= '<script type="text/javascript" src="http://' . @$_SERVER['HTTP_HOST'] .
								 '/js/show_o.js"></script>';
							break;
						case 3:
							$type = $AgentType == 2? 2: 1;
							$code = Advert::makeAgentUrl($value, $type, $posId, $url);
							break;
					}
					switch($AgentType)
					{
						case 1:
						case 3:
							$status = 1;
							$data = $agentM->getData('Percent,DomainName,Topic', array('DomainAgentId'=> $value), 
								$agentM::FETCH_ROW);
							if(! $data)
							{
								$data = $agentBackupM->getData('Percent,DomainName', array('DomainAgentId'=> $value), 
									$agentModel::FETCH_ROW);
								$status = - 1;
							}
							$jscode = '<script>var adInfo = {' . self::AD_POSID_NAME . ':' . $posId . '}</script>';
							$jscode .= '<script type="text/javascript" src="http://' . @$_SERVER['HTTP_HOST'] .
								 '/js/show_o.js"></script>';
							$insert = array('EnameId'=> $this->enameId,'DomainAgentId'=> $value,
									'Percent'=> $data->Percent,'Code'=> $code,'PosId'=> $posId,
									'PlatformType'=> $PlatformType,'DomainName'=> $data->DomainName,
									'CreateTime'=> time(),'PlatformId'=> $PlatformId,'Status'=> $status,
									'Topic'=> $data->Topic);
							$recordM->insert($insert);
							break;
						case 2:
							$data = $agentM->getData('Name,Percent,FinishTime,Status', array('ShopAgeId'=> $value), 
								$agentM::FETCH_ROW);
							$status = $data->Status == 1? 1: - 1;
							$insert = array('EnameId'=> $this->enameId,'ShopAgentId'=> $value,
									'Percent'=> $data->Percent,'Code'=> $code,'PosId'=> $posId,
									'PlatformType'=> $PlatformType,'Name'=> $data->Name,'CreateTime'=> time(),
									'PlatformId'=> $PlatformId,'FinishTime'=> $data->FinishTime,'Status'=> $status);
							$recordM->insert($insert);
							break;
					}
				}
			}
			else
			{
				throw new \Exception('请选择!');
			}
			$posidM = new ModelBase('posid_key');
			$key = self::makeCrc($AgentId, $AgentType, $PlatformId, $PlatformType, $StyleId, $this->enameId);
			$posidM->insert(array('PosId'=> $posId,'IdKey'=> $key));
		}
		return $posId;
	}

	/**
	 * 检测是否有存在广告位
	 *
	 * @param unknown $AgentId
	 * @param unknown $AgentType
	 * @param unknown $PlatformId
	 * @param unknown $PlatformType
	 * @param unknown $StyleId
	 * @return Ambigous <\driver\mixed, \core\mixed>|boolean
	 */
	public function checkAdPos($PlatformId, $PlatformType, $StyleId, $SpreadType, $AgentId = array(), $transType = null, 
		$GroupOne = null, $GroupTwo = null, $DomainLen = null, $price = null, $Commission = null, $tld = null, $GroupThree = null)
	{
		$posidM = new ModelBase('posid_key');
		$key = false;
		if($SpreadType == 1 || $SpreadType == 2)
		{
			$key = self::makeCrc($AgentId, $SpreadType, $PlatformId, $PlatformType, $StyleId, $this->enameId);
		}
		elseif($SpreadType == 3)
		{
			$key = self::makeCrcAuto($tld, $price, $GroupOne, $GroupTwo, $DomainLen, $transType, $Commission, 
				$PlatformId, $PlatformType, $StyleId, $SpreadType, $this->enameId, $GroupThree);
		}
		return $posidM->getData('PosId', array('IdKey'=> $key), $posidM::FETCH_COLUMN);
	}

	/**
	 * 获取广告数据
	 *
	 * @param unknown $AgentId
	 * @param unknown $AgentType
	 * @param unknown $templateId
	 * @param unknown $PlatformType
	 * @return boolean multitype:number \driver\mixed Ambigous
	 * <\lib\agent\unknown, unknown> Ambigous <\driver\mixed, \core\mixed>
	 */
	public function getAdInfo($AgentId, $AgentType, $templateId, $PlatformType)
	{
		$data = array();
		switch($AgentType)
		{
			case 1:
				$model = new ModelBase('domain_agent');
				$data['list'] = $model->getData('DomainAgentId,DomainName,SimpleDec,Price', 
					array('DomainAgentId'=> $AgentId));
				break;
			case 2:
				// $where=array();
				// $where['CreateTime'] = array('<=',time() -
				// \core\Config::item('edittime'));
				// $where['FinishTime'] = array('>=',time());
				// $where['status'] = 1;
				$where['ShopAgeId'] = $AgentId;
				$model = new ModelBase('shop_agent');
				$data['list'] = $model->getData('ShopAgeId,Name,Notice', $where);
				break;
			default:
				return false;
				break;
		}
		$model = new ModelBase('domain_agent');
		$data['list'] = $this->lib->formatAdData($data['list'], $AgentType);
		$data['templateId'] = $templateId;
		$data['status'] = 1;
		return $data;
	}

	/**
	 * 广告预览
	 *
	 * @param unknown $AgentId
	 * @param unknown $AgentType
	 * @param unknown $PlatformId
	 * @param unknown $PlatformType
	 * @param unknown $StyleId
	 */
	public function reviewAd($AgentId, $AgentType, $StyleId)
	{
		switch($AgentType)
		{
			case 1:
				$model = new ModelBase('domain_agent');
				$data = $model->getData('DomainAgentId,DomainName,SimpleDec,Price,FinishTime', 
					array('DomainAgentId'=> $AgentId));
				break;
			case 2:
				// $where=array();
				// $where['CreateTime'] = array('<=',time() -
				// \core\Config::item('edittime'));
				// $where['FinishTime'] = array('>=',time());
				// $where['status'] = 1;
				$where['ShopAgeId'] = $AgentId;
				$model = new ModelBase('shop_agent');
				$data = $model->getData('ShopAgeId,Name,Notice,Recommands', $where);
				break;
			default:
				return false;
				break;
		}
		$styleData = $AgentType == 1? \core\Config::item('adstyle')->toArray(): \core\Config::item('adstyle_shop')->toArray();
		if(! array_key_exists($StyleId, $styleData))
		{
			throw new \Exception('样式ID错误!');
		}
		$html = $styleData[$StyleId];
		$content = $html['html']['head'];
		$nameKey = $AgentType == 1? 'DomainName': 'Name';
		$SimpleDecKey = $AgentType == 1? 'SimpleDec': 'Notice';
		if($data)
		{
			foreach($data as $k => $val)
			{
				if(property_exists($val, 'Recommands'))
				{
					$tmpArr = explode(',', $val->Recommands);
					$val->Recommands = "";
					foreach($tmpArr as $k => $v)
					{
						if($k >= 4)
						{
							break;
						}
						$val->Recommands .= "<li><span>{$v}</span></li>";
					}
				}
				if(property_exists($val, 'FinishTime'))
				{
					$val->FinishTime = \lib\agent\AgentManagerLib::newTimeToDHIS($val->FinishTime);
				}
				$content .= $AgentType == 1? str_replace(array('{Url}','{Name}','{SimpleDec}','{Price}','{FinishTime}'), 
					array('javascript:void(0);',$val->$nameKey,$val->$SimpleDecKey,$val->Price,$val->FinishTime), 
					$html['html']['content']): str_replace(array('{Url}','{Name}','{SimpleDec}','{Recommands}'), 
					array('javascript:void(0);',$val->$nameKey,$val->$SimpleDecKey,$val->Recommands), 
					$html['html']['content']);
			}
		}
		$content .= $html['html']['end'];
		return $content;
	}

	/**
	 * 推广记录
	 *
	 * @param unknown $type
	 * @param unknown $start
	 * @return boolean
	 */
	public function spreadDomain($start, $startDate, $endDate, $startCommission, $endCommission, $domainName, $sort, 
		$status, $PlatformType, $topic)
	{
		$limit = array($start,\core\Config::item('pagesize'));
		$recordM = new ModelBase('spread_domain_record');
		$agentDomainM = new ModelBase('domain_agent');
		$baseData = array();
		$where = $this->lib->setSpreadDomainWhere($startDate, $endDate, $startCommission, $endCommission, $domainName, 
			$status, $PlatformType, $topic);
		$where['EnameId'] = $this->enameId;
		$baseData['list'] = $recordM->getData('SQL_CALC_FOUND_ROWS AgentDRId,DomainName,Percent,Status', $where, 
			$recordM::FETCH_ALL, 'Status DESC', $limit, 'DomainName');
		$recordM->query('SELECT FOUND_ROWS()');
		$count = $recordM->getOne();
		$page = new Page($count, \core\Config::item('pagesize'));
		$baseData['page'] = $page->show();
		$priceSort = array();
		$percentSort = array();
		foreach($baseData['list'] as $k => $val)
		{
			$baseData['list'][$k] = (array)$val;
			$baseData['list'][$k]['DomainInfo'] = $agentDomainM->getData('Price,Percent', 
				array('DomainName'=> $val->DomainName,'FinishTime'=> array('>',time())), $agentDomainM::FETCH_ROW);
			$baseData['list'][$k]['StatusCn'] = $baseData['list'][$k]['DomainInfo']? '推广中': '推广结束';
			$priceSort[$k] = $baseData['list'][$k]['DomainInfo']? $baseData['list'][$k]['DomainInfo']->Price: 0;
			$percentSort[$k] = $baseData['list'][$k]['DomainInfo']? $baseData['list'][$k]['DomainInfo']->Percent: 0;
		}
		if(is_array($clickOrder = $this->lib->setOrder($sort, array('price'), array('asc','desc'))))
		{
			list($orderField, $orderby) = $clickOrder;
			$by = $orderby == 'asc'? SORT_ASC: SORT_DESC;
			array_multisort($priceSort, $by, $baseData['list']);
		}
		elseif(is_array($clickOrder = $this->lib->setOrder($sort, array('percent'), array('asc','desc'))))
		{
			list($orderField, $orderby) = $clickOrder;
			$by = $orderby == 'asc'? SORT_ASC: SORT_DESC;
			array_multisort($percentSort, $by, $baseData['list']);
		}
		else
		{
			$orderField = 'percent';
			$orderby = 'desc';
			array_multisort($percentSort, SORT_DESC, $baseData['list']);
		}
		list($priceSymbol, $finishTimeSymbol, $commissionSymbol, $priceSort, $finishTimeSort, $commissionSort) = $this->lib->setSort(
			$orderField, $orderby);
		$baseData['order'] = array('priceSymbol'=> $priceSymbol,'commissionSymbol'=> $commissionSymbol,
				'priceSort'=> $priceSort,'commissionSort'=> $commissionSort);
		$baseData['PlatformType'] = array(1=> '自有网站',2=> '展示页',3=> '其他平台');
		$baseData['status'] = array(1=> '正常',- 1=> '失效');
		return $baseData;
	}

	/**
	 * 推广详情记录
	 *
	 * @param unknown $domainName
	 * @param ModelBase $recordM
	 * @param unknown $where
	 * @param number $type
	 * @return string
	 */
	public function spreadDetail($startDate, $endDate, $startCommission, $endCommission, $Name, $sort, $status, 
		$PlatformType, $agentType, $topic)
	{
		$recordM = $agentType == 1? new ModelBase('spread_domain_record'): $recordM = new ModelBase('spread_shop_record');
		$where = $agentType == 1? $this->lib->setSpreadDomainWhere($startDate, $endDate, $startCommission, 
			$endCommission, $Name, $status, $PlatformType, $topic): $this->lib->setSpreadShopWhere($startDate, $endDate, 
			$startCommission, $endCommission, $Name, $status, $PlatformType);
		$where['EnameId'] = $this->enameId;
		return $this->getSpreadDetail($Name, $recordM, $where, $agentType);
	}

	/**
	 * 推广详情记录
	 *
	 * @param unknown $domainName
	 * @param ModelBase $recordM
	 * @param unknown $where
	 * @param number $type
	 * @return string
	 */
	public function getSpreadDetail($domainName, ModelBase $recordM, $where, $type = 1)
	{
		$data = array();
		$templateDataM = new ModelBase('template_data');
		$platformM = new ModelBase('platform');
		$vrModel = new ModelBase('visit_record');
		$name = $type == 1? 'DomainName': 'Name';
		$where[$name] = $domainName;
		$id = $type == 1? 'DomainAgentId': 'ShopAgentId';
		$data = $recordM->getData(
			'EnameId,' . $id . ',Percent,Code,PlatformType,' . $name . ',CreateTime,PosId,PlatformId,Status,IsOrder', 
			$where, $recordM::FETCH_ALL);
		foreach($data as $key => $val)
		{
			$data[$key] = (array)$val;
			$data[$key]['ClickNum'] = $vrModel->count(
				array('EnameId'=> $this->enameId,'AgentId'=> $val->$id,'AgentType'=> $type,'PosId'=> $val->PosId,
						'CreateTime'=> $where['CreateTime']), 'VisitRecId');
			$data[$key]['CreateTime'] = date('Y-m-d', $val->CreateTime);
			switch($val->PlatformType)
			{
				case 1:
					$data[$key]['PlatformName'] = '自有网站：' .
						 $platformM->getData('Name', array('PlatformId'=> $val->PlatformId), $platformM::FETCH_COLUMN);
					break;
				case 2:
					$data[$key]['PlatformName'] = $val->PlatformId? '展示页：' . $templateDataM->getData('TemplateName', 
						array('TemplateDId'=> $val->PlatformId), $platformM::FETCH_COLUMN): '展示页';
					break;
				case 3:
					$data[$key]['PlatformName'] = '其他平台：' .
						 $platformM->getData('Name', array('PlatformId'=> $val->PlatformId), $platformM::FETCH_COLUMN);
					break;
			}
		}
		return $data;
	}

	/**
	 * 推广记录(店铺)
	 *
	 * @param unknown $type
	 * @param unknown $start
	 * @return boolean
	 */
	public function spreadShop($start, $startDate, $endDate, $startCommission, $endCommission, $Name, $sort, $status, 
		$PlatformType)
	{
		$limit = array($start,\core\Config::item('pagesize'));
		$recordM = new ModelBase('spread_shop_record');
		$vrModel = new ModelBase('visit_record');
		$platformM = new ModelBase('platform');
		$shopDomainM = new ModelBase('shop_agent');
		$baseData = array();
		$where = $this->lib->setSpreadShopWhere($startDate, $endDate, $startCommission, $endCommission, $Name, $status, 
			$PlatformType);
		$where['EnameId'] = $this->enameId;
		$baseData['list'] = $recordM->getData('SQL_CALC_FOUND_ROWS SpreadSRId,Name,Status,ShopAgentId', $where, 
			$recordM::FETCH_ALL, 'Status DESC', $limit, 'Name');
		$percentSort = $statusSort = array();
		foreach($baseData['list'] as $k => $val)
		{
			$shopData = $shopDomainM->getData('Status,Percent', array('ShopAgeId'=> $val->ShopAgentId), 
				$shopDomainM::FETCH_ROW);
			$baseData['list'][$k]->StatusCn = $shopData->Status == 1? '推广中': '推广结束';
			$baseData['list'][$k]->Percent = $shopData->Percent;
			$percentSort[$k] = $shopData->Percent;
			$statusSort[$k] = $shopData->Status;
		}
		$recordM->query('SELECT FOUND_ROWS()');
		$count = $recordM->getOne();
		$page = new Page($count, \core\Config::item('pagesize'));
		$baseData['page'] = $page->show();
		if(is_array($clickOrder = $this->lib->setOrder($sort, array('percent'), array('asc','desc'))))
		{
			list($orderField, $orderby) = $clickOrder;
			$by = $orderby == 'asc'? SORT_ASC: SORT_DESC;
			array_multisort($percentSort, $by, $statusSort, SORT_ASC, $baseData['list']);
		}
		else
		{
			$orderField = 'percent';
			$orderby = 'desc';
			array_multisort($percentSort, SORT_DESC, $statusSort, SORT_ASC, $baseData['list']);
		}
		// echo '<pre>';
		// print_r($baseData['list']);die;
		list($priceSymbol, $finishTimeSymbol, $commissionSymbol, $priceSort, $finishTimeSort, $commissionSort) = $this->lib->setSort(
			$orderField, $orderby);
		$baseData['order'] = array('commissionSymbol'=> $commissionSymbol,'commissionSort'=> $commissionSort);
		$baseData['PlatformType'] = array(1=> '自有网站',2=> '展示页',3=> '其他平台');
		$baseData['status'] = array(1=> '正常',- 1=> '失效');
		return $baseData;
	}

	/**
	 * 渠道统计
	 *
	 * @param unknown $start
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $type
	 * @param unknown $name
	 * @return multitype:multitype:string string Ambigous <\driver\mixed,
	 * \core\mixed>
	 */
	public function platformStatistics($start, $startDate, $endDate, $type, $name)
	{
		$limit = array($start,\core\Config::item('pagesize'));
		$orderBy = 'ClickNum DESC';
		$groupBy = $type == 2? 'FromUrl': 'PlatformId';
		$vrModel = new ModelBase('visit_record');
		$platformM = new ModelBase('platform');
		$data = array();
		$where = $this->lib->setPlatformSWhere($startDate, $endDate, $type, $name);
		$where['EnameId'] = $this->enameId;
		$where['Status'] = 1;
		$fields = 'SQL_CALC_FOUND_ROWS PlatformType,FromUrl,PlatformId,COUNT(VisitRecId) AS ClickNum';
		list($whereStr, $params) = $this->setplatformStatisticsSql($where, $groupBy, $orderBy, $limit, $vrModel, $type);
		$sql = 'SELECT ' . $fields . ' FROM visit_record' . $whereStr;
		$vrModel->query($sql, $params);
		$data['list'] = $vrModel->getAll();
		$clickSort = array();
		$vrModel->query('SELECT FOUND_ROWS()');
		$count = $vrModel->getOne();
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		foreach($data['list'] as $key => $val)
		{
			$data['list'][$key] = (array)$val;
			$clickSort[$key] = $data['list'][$key]['ClickNum'];
			$data['list'][$key]['PlatformName'] = $type == 2? $val->FromUrl: $platformM->getData('Name', 
				array('PlatformId'=> intval($val->PlatformId)), $platformM::FETCH_COLUMN);
			switch($val->PlatformType)
			{
				case 1:
					$data['list'][$key]['PlatformTypeCn'] = '自有网站';
					break;
				case 2:
					$data['list'][$key]['PlatformTypeCn'] = '展示页';
					break;
				case 3:
					$data['list'][$key]['PlatformTypeCn'] = '其他平台';
					break;
			}
		}
		$data['PlatformType'] = array(1=> '自有网站',2=> '展示页',3=> '其他平台');
		return $data;
	}

	/**
	 * 设置渠道统计的SQL语句和参数
	 *
	 * @param unknown $where
	 * @param unknown $groupBy
	 * @param unknown $orderBy
	 * @param unknown $limit
	 * @param ModelBase $vrModel
	 * @param unknown $type
	 * @return multitype:Ambigous <string, unknown> unknown
	 */
	public function setplatformStatisticsSql($where, $groupBy, $orderBy, $limit, ModelBase $vrModel, $type)
	{
		$whereStr = '';
		$values = array();
		list($whereStr, $values) = $vrModel->where($where);
		$whereStr .= $type == 2? ' AND FromUrl!=""': ' AND PlatformId!=0';
		if(trim($groupBy))
		{
			$whereStr .= ' GROUP BY ' . $groupBy;
		}
		if(trim($orderBy))
		{
			$whereStr .= ' ORDER BY ' . $orderBy;
		}
		if($limit)
		{
			list($limitSql, $values) = $vrModel->limit($limit, $values);
			$whereStr .= $limitSql;
		}
		return array($whereStr,$values);
	}

	/**
	 * 根据广告为ID更新展示页模板ID
	 *
	 * @param unknown $posId
	 * @param unknown $templateDId
	 * @return boolean
	 */
	public function updatePosById($posId, $templateDId)
	{
		if(! $posId)
		{
			return true;
		}
		$model = new ModelBase('ad_pos');
		if($data = $model->getData('*', array('PosId'=> $posId), $model::FETCH_ROW))
		{
			if(! $data->PlatformId)
			{
				if(! $model->update(array('PlatformId'=> $templateDId), array('PosId'=> $posId)))
				{
					throw new \Exception('更新广告位模板ID失败！');
				}
				$posidM = new ModelBase('posid_key');
				
				$str = sprintf("%u", 
					crc32(
						"Auto-{$data->TLD}-{$data->PriceRange}-{$data->GroupOne}-{$data->GroupTwo}-{$data->DomainLen}-{$data->TransType}-{$data->CommissionRange}-{$templateDId}-{$data->PlatformType}-{$data->StyleId}-3-{$this->enameId}"));
				$posidM->update(array('IdKey'=> $str), array('PosId'=> $posId));
			}
		}
		return true;
	}

	public function makeAgentUrlById($transId, $enameId)
	{
		$model = new ModelBase('domain_agent');
		$url = new Url();
		$platformM = new ModelBase('platform');
		$where['FinishTime'] = array('>',time());
		$where['CreateTime'] = array('<=',time() - \core\Config::item('edittime'));
		$where['TransId'] = $transId;
		if(! $agentData = $model->getData('DomainAgentId,EnameId', $where, $model::FETCH_ROW))
		{
			throw new \Exception('域名没有加入到米市，不能分销', 100001);
		}
		$this->enameId = $enameId;
		$PlatformId = $platformM->getData('PlatformId', array('EnameId'=> 1000), $platformM::FETCH_COLUMN);
		Common::initUserInfo($this->enameId);
		if(! $posId = $this->checkAdPos($PlatformId, 3, 0, 1, (array)$agentData->DomainAgentId))
		{
			$adModel = new ModelBase('ad_pos');
			$insert = array('CreateTime'=> time(),'EnameId'=> $this->enameId,'TransType'=> 0,'GroupOne'=> 0,
					'GroupTwo'=> 0,'DomainLen'=> 0,'PlatformId'=> $PlatformId,'PlatformType'=> 3,'StyleId'=> 0,
					'SpreadType'=> 1,'PriceRange'=> '','CommissionRange'=> '');
			$posId = $adModel->insert($insert);
			$code = Advert::makeAgentUrl($agentData->DomainAgentId, 1, $posId, $url, true);
			$agentBackupM = new ModelBase('agent_backup');
			$agadModel = new ModelBase('agent_pos');
			$recordM = new ModelBase('spread_domain_record');
			$id = $agadModel->insert(
				array('AgentId'=> $agentData->DomainAgentId,'AgentType'=> 1,'CreateTime'=> time(),'PosId'=> $posId));
			if(! $id)
			{
				\core\Logger::write('Agent_Guests', '创建普通广告位插入广告位对应分销(agent_pos)表失败,分销ID' . $value . '广告位ID' . $posId);
			}
			$status = 1;
			$data = $model->getData('Percent,DomainName', array('DomainAgentId'=> $agentData->DomainAgentId), 
				$model::FETCH_ROW);
			if(! $data)
			{
				$data = $agentBackupM->getData('Percent,DomainName', array('DomainAgentId'=> $agentData->DomainAgentId), 
					$agentBackupM::FETCH_ROW);
				$status = - 1;
			}
			$insert = array('EnameId'=> $this->enameId,'DomainAgentId'=> $agentData->DomainAgentId,
					'Percent'=> $data->Percent,'Code'=> $code,'PosId'=> $posId,'PlatformType'=> 3,
					'DomainName'=> $data->DomainName,'CreateTime'=> time(),'PlatformId'=> $PlatformId,'Status'=> $status,
					'Topic'=> 8);
			$recordM->insert($insert);
			$posidM = new ModelBase('posid_key');
			$str = self::makeCrc($agentData->DomainAgentId, 1, $PlatformId, 3, 0, $this->enameId);
			$posidM->insert(array('PosId'=> $posId,'IdKey'=> $str));
		}
		else
		{
			$code = Advert::makeAgentUrl($agentData->DomainAgentId, 1, $posId, $url, true);
		}
		return $code;
	}

	public static function makeCrc($AgentId, $SpreadType, $PlatformId, $PlatformType, $StyleId, $enameId)
	{
		$key = sprintf("%u", 
			crc32(@implode(',', (array)$AgentId) . "-{$SpreadType}-{$PlatformId}-{$PlatformType}-{$StyleId}-{$enameId}"));
		return $key;
	}

	public static function makeCrcAuto($tld, $price, $GroupOne, $GroupTwo, $DomainLen, $transType, $Commission, 
		$PlatformId, $PlatformType, $StyleId, $SpreadType, $enameId, $GroupThree = null)
	{
		$key = "Auto-{$tld}-{$price}-{$GroupOne}-{$GroupTwo}-{$DomainLen}-{$transType}-{$Commission}-{$PlatformId}-{$PlatformType}-{$StyleId}-{$SpreadType}-{$enameId}";
		if($GroupThree)
		{
			$key .= "-{$GroupThree}";
		}
		$key = sprintf("%u", crc32($key));
		return $key;
	}
}