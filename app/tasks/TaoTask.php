<?php
use core\ModelBase;

class TaoTask
{

	public function updateBackupAttriAction()
	{
		$agent_backup = new ModelBase('agent_backup');
		$count = $agent_backup->count(array(), 'AgentBackupId');
		$size = ceil($count / 100);
		for($i = 0; $i < $size; $i++)
		{
			
			$data = $agent_backup->getData('AgentBackupId,DomainName', array(), $agent_backup::FETCH_ALL, false, 
				array($i * 100,100));
			if(! $data)
			{
				continue;
			}
			foreach($data as $val)
			{
				list($domainSysOne, $domainSysTwo, $groupThree, $domainLength) = \common\domain\Domain::getDomainGroupAll(
					$val->DomainName);
				$tld = \common\domain\Domain::tldValue($val->DomainName);
				if(! is_int(
					$rs = $agent_backup->update(
						array('TLD'=> $tld,'GroupOne'=> $domainSysOne,'GroupTwo'=> $domainSysTwo,
								'DomainLen'=> $domainLength,'GroupThree'=> $groupThree), 
						array('AgentBackupId'=> $val->AgentBackupId))))
				{
					\core\Logger::write('updateBackup', 
						array('updateBackup:','fail','AgentBackupId:' . $val->AgentBackupId));
				}
			}
		}
		echo 'sucess';
	}

	public function updateDomainAgentAction()
	{
		$domain_agent = new ModelBase('domain_agent');
		$count = $domain_agent->count(array(), 'DomainAgentId');
		$size = ceil($count / 100);
		for($i = 0; $i < $size; $i++)
		{
			
			$data = $domain_agent->getData('DomainAgentId,DomainName', array(), $domain_agent::FETCH_ALL, false, 
				array($i * 100,100));
			if(! $data)
			{
				continue;
			}
			foreach($data as $val)
			{
				list($domainSysOne, $domainSysTwo, $groupThree, $domainLength) = \common\domain\Domain::getDomainGroupAll(
					$val->DomainName);
				$tld = \common\domain\Domain::tldValue($val->DomainName);
				if(! is_int(
					$domain_agent->update(
						array('TLD'=> $tld,'GroupOne'=> $domainSysOne,'GroupTwo'=> $domainSysTwo,
								'DomainLen'=> $domainLength,'GroupThree'=> $groupThree), 
						array('DomainAgentId'=> $val->DomainAgentId))))
				{
					\core\Logger::write('updateDomainAgent', 
						array('updateDomainAgent:','fail','DomainAgentId:' . $val->DomainAgentId));
				}
			}
		}
		echo 'sucess';
	}

	public function updateOrderAttriAction()
	{
		$order_record = new ModelBase('order_record');
		$count = $order_record->count(array(), 'OrderId');
		$size = ceil($count / 100);
		for($i = 0; $i < $size; $i++)
		{
			
			$data = $order_record->getData('OrderId,DomainName', array(), $order_record::FETCH_ALL, false, 
				array($i * 100,100));
			if(! $data)
			{
				continue;
			}
			foreach($data as $val)
			{
				list($domainSysOne, $domainSysTwo, $groupThree, $domainLength) = \common\domain\Domain::getDomainGroupAll(
					$val->DomainName);
				// $tld = \common\domain\Domain::tldValue($val->DomainName);
				if(! is_int(
					$order_record->update(
						array('GroupOne'=> $domainSysOne,'GroupTwo'=> $domainSysTwo,'DomainLen'=> $domainLength,
								'GroupThree'=> $groupThree), array('OrderId'=> $val->OrderId))))
				{
					\core\Logger::write('updateOrder', array('updateOrder:','fail','OrderId:' . $val->OrderId));
				}
			}
		}
		echo 'sucess';
	}

	public function updateVisitAttriAction()
	{
		$visit_record = new ModelBase('visit_record');
		$count = $visit_record->count(array(), 'VisitRecId');
		$size = ceil($count / 100);
		for($i = 0; $i < $size; $i++)
		{
			
			$data = $visit_record->getData('VisitRecId,DomainName', array(), $visit_record::FETCH_ALL, false, 
				array($i * 100,100));
			if(! $data)
			{
				continue;
			}
			foreach($data as $val)
			{
				list($domainSysOne, $domainSysTwo, $groupThree, $domainLength) = \common\domain\Domain::getDomainGroupAll(
					$val->DomainName);
				// $tld = \common\domain\Domain::tldValue($val->DomainName);
				if(! is_int(
					$visit_record->update(
						array('GroupOne'=> $domainSysOne,'GroupTwo'=> $domainSysTwo,'DomainLen'=> $domainLength,
								'GroupThree'=> $groupThree), array('VisitRecId'=> $val->VisitRecId))))
				{
					\core\Logger::write('updateVisit', array('updateVisit:','fail','VisitRecId:' . $val->VisitRecId));
				}
			}
		}
		echo 'sucess';
	}

	public function updateTempAttriAction()
	{
		$temporary_solr = new ModelBase('temporary_solr');
		$count = $temporary_solr->count(array(), 'TempSolrId');
		$size = ceil($count / 100);
		for($i = 0; $i < $size; $i++)
		{
			
			$data = $temporary_solr->getData('TempSolrId,DomainName', array(), $temporary_solr::FETCH_ALL, false, 
				array($i * 100,100));
			if(! $data)
			{
				continue;
			}
			foreach($data as $val)
			{
				list($domainSysOne, $domainSysTwo, $groupThree, $domainLength) = \common\domain\Domain::getDomainGroupAll(
					$val->DomainName);
				$tld = \common\domain\Domain::tldValue($val->DomainName);
				if(! is_int(
					$temporary_solr->update(
						array('TLD'=> $tld,'GroupOne'=> $domainSysOne,'GroupTwo'=> $domainSysTwo,
								'DomainLen'=> $domainLength,'GroupThree'=> $groupThree), 
						array('TempSolrId'=> $val->TempSolrId))))
				{
					\core\Logger::write('updateBackup', 
						array('updateTempAttri:','fail','TempSolrId:' . $val->TempSolrId));
				}
			}
		}
		echo 'sucess';
	}

	public function updateAdPosAction()
	{
		$model = new ModelBase('ad_pos');
		$oldGroup = \core\Config::item('domaingroup')->toArray();
		$newGroup = \core\Config::item('ts_domaingroup')->toArray();
		$oldTldArr = \core\Config::item('newtld')->toArray();
		$newTldArr = \core\Config::item('ts_domaintld')->toArray();
		
		foreach($oldGroup as $k => $v)
		{ // 更新分组 { 
			$temp = $oldGroup[$k][1];
			$newTemp = $newGroup[$k][1];
			$classArr = explode("_", $newTemp);
			if(isset($temp['rank']))
			{
				$sysGroupOne = array($temp['rank'][0],$temp['rank'][1]);
				$model->update(array('GroupOne'=> $classArr[0]), array('GroupOne'=> array('between',$sysGroupOne)));
			}
			elseif(isset($temp['sysone']))
			{
				$sysGroupOne = $temp['sysone'];
				$model->update(array('GroupOne'=> $classArr[0]), array('GroupOne'=> $sysGroupOne));
			}
			elseif(isset($temp['systwo']))
			{
				$sysGroupTwo = $temp['systwo'];
				$model->update(array('GroupTwo'=> $classArr[1]), array('GroupTwo'=> $sysGroupTwo));
			}
			unset($newTemp);
			unset($temp);
		}
		$count = $model->count(array(), 'PosId');
		$size = ceil($count / 100);
		for($i = 0; $i < $size; $i++)
		{
			$tldArr = $model->getData('PosId,TLD', array(), $model::FETCH_ALL, false, array($i * 100,100));
			foreach($tldArr as $val)
			{
				if($val->TLD)
				{
					$tlds = @explode(',', $val->TLD);
					$newTldString = '';
					foreach($tlds as $v)
					{
						if($v)
						{
							$oldTld = $oldTldArr[$v][0];
							$newTldString .= array_search($oldTld, $newTldArr) . ',';
						}
					}
					$newTldString = rtrim($newTldString, ',');
					if($newTldString == $val->TLD)
					{
						continue;
					}
					if(! $model->update(array('TLD'=> $newTldString), array('PosId'=> $val->PosId)))
					{
						\core\Logger::write('updateAdPos', array('updateAdPos:','fail','POSID:' . $val->PosId));
					}
				}
			}
		}
		echo $count;
	}
}