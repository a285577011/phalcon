<?php
namespace logic\ad;
use \core\ModelBase;
use lib\ad\AdvertLib;
use \solr\DomainAuctionSolr;
use \Phalcon\Mvc\Url;
use Phalcon\Dispatcher;
use es\DomainElasticSearch;

class Advert
{


	public function __construct()
	{}

	/**
	 * 获取广告数据 ajax ro PHP
	 */
	public function getAdInfo($posId)
	{
		if(! $posId)
		{
			return false;
		}
		defined('IS_MOBILE') or define('IS_MOBILE', \logic\common\Common::isMobile());
		$model = new ModelBase('ad_pos');
		$SpreadType = $model->getData('SpreadType', array('PosId'=> $posId), $model::FETCH_COLUMN);
		if($SpreadType == 1 || $SpreadType == 2)
		{
			return $this->getContentDie($posId, $model);
		}
		elseif($SpreadType == 3)
		{
			return $this->getContentLive($posId, $model);
		}
		return false;
	}

	/**
	 * 生成广告连接
	 *
	 * @param unknown $posId
	 */
	public static function makeAgentUrl($agentId, $type, $posId, \Phalcon\Mvc\Url $url, $isRpc = false)
	{
		$str = "{$posId}_-{$agentId}_-{$type}";
		$lib = new AdvertLib();
		$str = $lib->encrypt($str);
		$siteurl = 'http://' . ($isRpc? 'www.ename.com.cn': @$_SERVER['HTTP_HOST']) .
			 $url->get("advert/click", array('c'=> $str));
		return $siteurl;
	}

	/**
	 * 生成广告位连接 多个链接
	 *
	 * @param unknown $posId
	 * @throws \Exception
	 * @return multitype:string
	 */
	public function makePosAdUrl($posId)
	{
		$adurl = array();
		$model = new ModelBase('agent_pos');
		$data = $model->getData('AgentId,AgentType', array('PosId'=> $posId));
		$modelPos = new ModelBase('ad_pos');
		$url = new Url();
		if(! empty($data))
		{
			foreach($data as $val)
			{
				$adurl[] = self::makeAgentUrl($val->AgentId, $val->AgentType, $posId, $url);
			}
		}
		return $adurl;
	}

	/**
	 * 生成推广链接
	 *
	 * @param unknown $posId
	 * @throws \Exception
	 * @return string
	 */
	public function creatSpreadCode($posId)
	{
		$urlArr = $this->makePosAdUrl($posId);
		if(empty($urlArr))
		{
			throw new \Exception('系统出错');
		}
		$urlArr = implode('  ', $urlArr);
		return $urlArr;
	}

	/**
	 * 统计点击并调转
	 *
	 * @param unknown $str
	 * @return boolean
	 */
	public function click($str,\Phalcon\Mvc\DispatcherInterface $dispatcher)
	{
		$lib = new AdvertLib();
		$domainEs = new DomainElasticSearch();
		$AgentMlib = new \lib\agent\AgentManagerLib();
		$str = $lib->decrypt($str);
		list($posId, $agentId, $type) = $lib->checkArr($str, $dispatcher); // 检查
		$apModel = new ModelBase('ad_pos');
		$data = $apModel->getData('PlatformType,EnameId,PlatformId', array('PosId'=> $posId), $apModel::FETCH_ROW);
		$model = new ModelBase('visit_record');
		$jumpUrl = \core\Config::item('agent_jump_url');
		$fromUrl = isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']: '';
		switch($type)
		{
			case 1:
			case 3:
				$agentModel = new ModelBase('domain_agent');
				$solr = new DomainAuctionSolr();
				if(! $solr->ping())
				{
					return $dispatcher->forward(array('controller'=> 'Common','action'=> 'urlFail'));
					exit();
				}
				$content = $agentModel->getData('EnameId,DomainName,TransType,GroupOne,GroupTwo,DomainLen,GroupThree', 
					array('DomainAgentId'=> $agentId,'CreateTime'=> array('<=',time() - \core\Config::item('edittime'))), 
					$agentModel::FETCH_ROW);
				if(! $content)
				{
					return $dispatcher->forward(array('controller'=> 'Common','action'=> 'urlFail'));
					exit();
				}
				list($name, $tld) = $AgentMlib->getDomainSearch($content->DomainName);
				switch(\core\Config::item('ts_data'))
				{
					case 1:
						$solrData = $solr->getByIdName($content->EnameId, $name, $tld);
						$total = $solrData['numFound'];
						$solrData && $transId = $solrData->docs[0]['AuditListId'];
						break;
					case 2:
						 $solrData = $domainEs->getInfoByUser($content->EnameId, array($name,$tld));
						$total = $solrData['total'];
						$solrData && $transId = $solrData['data'][0]['_id'];
						break;
					default:
						break;
				}
				if(! $solrData || ! $total)
				{
					return $dispatcher->forward(array('controller'=> 'Common','action'=> 'urlFail'));
				}
				$encryptStr = $lib->encrypt(
					$type . '_' . $data->EnameId . '_' . $transId . '_' . $content->DomainName . '_' . $agentId . '_' .
						 $posId);
				$agentModel->query('UPDATE domain_agent SET ClickNum=ClickNum+1 WHERE DomainAgentId =:DomainAgentId', 
					array('DomainAgentId'=> $agentId));
				$insert = array('CreateTime'=> time(),'Ip'=> \common\Client::getClientIp(),'AgentId'=> $agentId,
						'AgentType'=> $type,'PosId'=> $posId,'TransType'=> $content->TransType,
						'PlatformType'=> $data->PlatformType,'EnameId'=> $data->EnameId,'SellerId'=> $content->EnameId,
						'DomainName'=> $content->DomainName,'GroupOne'=> $content->GroupOne,
						'GroupTwo'=> $content->GroupTwo,'DomainLen'=> $content->DomainLen,
						'PlatformId'=> $data->PlatformId,'FromUrl'=> $fromUrl,'GroupThree'=> $content->GroupThree);
				if(! $model->insert($insert))
				{
					\core\Logger::write('Advert', '记录广告访问记录表失败,分销ID' . $agentId . '广告位ID' . $posId);
				}
				header("Location:{$jumpUrl}{$encryptStr}");
				exit();
				break;
			case 2:
				$agentModel = new ModelBase('shop_agent');
				$enameId = $agentModel->getData('EnameId', 
					array('ShopAgeId'=> $agentId,'Status'=> 1,
							'CreateTime'=> array('<=',time() - \core\Config::item('edittime')),
							'FinishTime'=> array('>=',time())), $agentModel::FETCH_COLUMN);
				if(! $enameId)
				{
					return $dispatcher->forward(array('controller'=> 'Common','action'=> 'urlFail'));
					exit();
				}
				$encryptStr = $lib->encrypt(
					$type . '_' . $enameId . '_' . $data->EnameId . '_' . $agentId . '_' . $posId);
				$insert = array('CreateTime'=> time(),'Ip'=> \common\Client::getClientIp(),'AgentId'=> $agentId,
						'AgentType'=> $type,'PosId'=> $posId,'TransType'=> 0,'PlatformType'=> $data->PlatformType,
						'EnameId'=> $data->EnameId,'SellerId'=> $enameId,'DomainName'=> '',
						'PlatformId'=> $data->PlatformId,'FromUrl'=> $fromUrl);
				if(! $model->insert($insert))
				{
					\core\Logger::write('Advert', '记录广告访问记录表失败,分销ID' . $agentId . '广告位ID' . $posId);
				}
				header("Location:{$jumpUrl}{$encryptStr}");
				exit();
				break;
		}
	}

	/**
	 * 广告静态广告数据
	 *
	 * @param unknown $posId
	 * @param unknown $adposmodel
	 * @return boolean unknown
	 */
	public function getContentDie($posId,\core\ModelBase $adposmodel)
	{
		$model = new ModelBase('agent_pos');
		$data = $model->getData('AgentId,AgentType', array('PosId'=> $posId), $model::FETCH_ALL);
		$lib = new AdvertLib();
		$agentId = $lib->coverAgentId($data);
		if(! $agentId)
		{
			return false;
		}
		switch($data[0]->AgentType)
		{
			case 1:
				$adData = array();
				$agentModel = new ModelBase('domain_agent');
				$agentBackupM = new ModelBase('agent_backup');
				foreach($agentId as $id)
				{
					$rs = $agentModel->getData('DomainAgentId,DomainName,SimpleDec,Price,FinishTime', 
						array('DomainAgentId'=> $id), $agentModel::FETCH_ROW);
					if(! $rs)
					{
						$rs = $agentBackupM->getData('DomainAgentId,DomainName,SimpleDec,Price', 
							array('DomainAgentId'=> $id), $agentModel::FETCH_ROW);
						$rs && $rs->FinishTime = 0;
					}
					$adData[] = $rs;
				}
				return $this->getAdContent($adData, $posId, 1);
				break;
			case 2:
				$agentModel = new ModelBase('shop_agent');
				$adData = $agentModel->getData('ShopAgeId,Name,Recommands', array('ShopAgeId'=> $agentId));
				return $this->getAdContent($adData, $posId, 2);
				break;
		}
	}

	/**
	 * 获取广告URL及其他广告内容
	 *
	 * @param unknown $adData
	 * @param unknown $posId
	 * @param unknown $agentType
	 * @return multitype: Ambigous string>
	 */
	public function getAdContent($adData, $posId, $agentType)
	{
		$content = array();
		$adposmodel = new ModelBase('ad_pos');
		$agent = $adposmodel->getData('EnameId,StyleId,PlatformId,PlatformType', array('PosId'=> $posId), 
			$adposmodel::FETCH_ROW);
		if(! $adData)
		{
			return false;
		}
		if(! $this->checkStyleId($agent->PlatformType, $agent->StyleId))
		{
			return false;
		}
		$idKey = $agentType == 1? 'DomainAgentId': 'ShopAgeId';
		foreach($adData as $val)
		{
			if(property_exists($val, 'Recommands'))
			{
				$tmpArr = explode(',', $val->Recommands);
				$val->Recommands = "";
				if($agent->PlatformType == 2)
				{
					foreach($tmpArr as $k => $v)
					{
						if($k >= 4)
						{
							break;
						}
						$val->Recommands .= ! IS_MOBILE? "<div class='text-bottom'>{$v}</div>": "<p class='domain'><span class='c_color'>{$v}</p>";
					}
				}
				else
				{
					foreach($tmpArr as $k => $v)
					{
						if($k >= 4)
						{
							break;
						}
						$val->Recommands .= "<li><span>{$v}</span></li>";
					}
				}
			}
			if(property_exists($val, 'FinishTime'))
			{
				$val->FinishTime = \lib\agent\AgentManagerLib::newTimeToDHIS($val->FinishTime);
			}
			$content['data']['data'][] = $val;
			$content['data']['url'][] = self::makeAgentUrl($val->$idKey, $agentType, $posId, new Url());
		}
		if(empty($content))
		{
			return false;
		}
		$content['PlatformType'] = $agent->PlatformType;
		if(IS_MOBILE)
		{
			$content['html'] = ($agentType == 1? \core\Config::item('ad_zhanshiye_style_m')->toArray()[$agent->StyleId]: \core\Config::item(
				'ad_zhanshiye_style_shop_m')->toArray()[$agent->StyleId]);
		}
		else
		{
			$content['html'] = $agent->PlatformType == 2? ($agentType == 1? \core\Config::item('ad_zhanshiye_style')->toArray()[$agent->StyleId]: \core\Config::item(
				'ad_zhanshiye_style_shop')->toArray()[$agent->StyleId]): ($agentType == 1? \core\Config::item('adstyle')->toArray()[$agent->StyleId]: \core\Config::item(
				'adstyle_shop')->toArray()[$agent->StyleId]);
		}
		$content['agenttype'] = $agentType;
		return $content;
	}

	public function checkStyleId($PlatformType, $StyleId)
	{
		switch($PlatformType)
		{
			case 1:
				if($StyleId > count(\core\Config::item('adstyle')->toArray()) || $StyleId <= 0)
				{
					return false;
				}
				return true;
				break;
			case 2:
				if(! in_array($StyleId, \core\Config::item('sysStyleId')->toArray()) || $StyleId <= 0)
				{
					return false;
				}
				return true;
				break;
			case 3:
				return true;
				break;
			default:
				return false;
		}
	}

	/**
	 * 广告动态筛选数据
	 *
	 * @param unknown $posId
	 * @param unknown $model
	 * @return boolean unknown
	 */
	public function getContentLive($posId, ModelBase $model)
	{
		$field = 'TransType,DomainLen,GroupTwo,GroupOne,TLD,PriceRange,CommissionRange,StyleId,EnameId,PlatformId,PlatformType,GroupThree';
		$data = $model->getData($field, array('PosId'=> $posId), $model::FETCH_ROW);
		$where = $this->setWhere($data);
		$AgentModel = new ModelBase('domain_agent');
		$AgentArr = $AgentModel->getData('DomainAgentId,DomainName,SimpleDec,Price,FinishTime', $where, 
			$AgentModel::FETCH_ALL, false, array(0,\core\Config::item('auto_agent_page')));
		if(! $AgentArr)
		{
			$AgentArr = $AgentModel->getData('DomainAgentId,DomainName,SimpleDec,Price,FinishTime', 
				array('FinishTime'=> array('>',time()),
						'CreateTime'=> array('<=',time() - \core\Config::item('edittime')),'Topic'=> 0), 
				$AgentModel::FETCH_ALL, false, array(0,\core\Config::item('auto_agent_page')));
		}
		if(! $AgentArr)
		{
			return false;
		}
		if(! $this->checkStyleId($data->PlatformType, $data->StyleId))
		{
			return false;
		}
		foreach($AgentArr as $val)
		{
			$content['data']['data'][] = array('DomainName'=> $val->DomainName,'SimpleDec'=> $val->SimpleDec,
					'Price'=> $val->Price,'FinishTime'=> \lib\agent\AgentManagerLib::newTimeToDHIS($val->FinishTime));
			$content['data']['url'][] = self::makeAgentUrl($val->DomainAgentId, 3, $posId, new \Phalcon\Mvc\Url());
		}
		$content['PlatformType'] = $data->PlatformType;
		if(IS_MOBILE)
		{
			$content['html'] = \core\Config::item('ad_zhanshiye_style_m')->toArray()[$data->StyleId];
		}
		else
		{
			$content['html'] = $data->PlatformType == 2? \core\Config::item('ad_zhanshiye_style')->toArray()[$data->StyleId]: \core\Config::item(
				'adstyle')->toArray()[$data->StyleId];
		}
		$content['agenttype'] = 1;
		return $content;
	}

	/**
	 * 设置广告动态where条件
	 *
	 * @param unknown $data
	 * @return multitype:multitype:string number NULL Ambigous <string,
	 * multitype:string unknown > Ambigous <string, multitype:string unknown ,
	 * multitype:string Ambigous <boolean, unknown, mixed> >
	 */
	public function setWhere($data)
	{
		$where = array();
		$data->TransType && $where['TransType'] = $data->TransType;
		$data->DomainLen && $where['DomainLen'] = $data->DomainLen;
		$data->GroupOne && $where['GroupOne'] = $data->GroupOne;
		if($data->GroupTwo)
		{
			if(10 == $data->GroupTwo)
			{
				$where['GroupTwo'] = array(10,12);
			}
			elseif(2 == $data->GroupTwo)
			{
				$where['GroupTwo'] = array(2,12);
			}
			else
			{
				$where['GroupTwo'] = 9999 == $data->GroupTwo? 0: $data->GroupTwo;
			}
		}
		$data->GroupThree && $where['GroupThree'] = $data->GroupThree;
		$data->TLD && $where['TLD'] = @explode(',', $data->TLD);
		$lib = new AdvertLib();
		$Price = $lib->coverJson($data->PriceRange);
		$where['Price'] = is_array($Price)? array('between',$Price): $Price? array('>=',$Price): '';
		$CommissionRange = $lib->coverJson($data->CommissionRange);
		if(is_array($CommissionRange))
		{
			$where['Percent'] = array('between',$CommissionRange);
		}
		elseif($CommissionRange)
		{
			$where['Percent'] = array('>=',$CommissionRange);
		}
		$where['FinishTime'] = array('>',time());
		$where['CreateTime'] = array('<=',time() - \core\Config::item('edittime'));
		$where['Topic'] = 0;
		return $where;
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
				$data = $model->getData('DomainAgentId,DomainName,SimpleDec,Price', array('DomainAgentId'=> $AgentId));
				break;
			case 2:
				// $where=array();
				// $where['CreateTime'] = array('<=',time() -
				// \core\Config::item('edittime'));
				// $where['FinishTime'] = array('>=',time());
				// $where['status'] = 1;
				$where['ShopAgeId'] = $AgentId;
				$model = new ModelBase('shop_agent');
				$data = $model->getData('ShopAgeId,Name,Notice', $where);
				break;
			default:
				return false;
				break;
		}
		$styleData = \core\Config::item('adstyle')->toArray();
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
			foreach($data as $val)
			{
				$content .= str_replace(array('{Url}','{Name}','{SimpleDec}'), 
					array('javascript:void(0);',$val->$nameKey,$val->$SimpleDecKey), $html['html']['content']);
			}
		}
		$content .= $html['html']['end'];
		return $content;
	}
}