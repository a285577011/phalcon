<?php
namespace logic\task;
use \core\ModelBase;
use \lib\custompage\CustomPageLib;
use core\CustomPageIDNALib;

class TaskLogic
{

	private $cusLib;

	/**
	 * 获取分销过该店铺的分销ENAMEID
	 *
	 * @param unknown $enameId
	 */
	public function getAgentEnameId($enameId)
	{
		$agentEnameId = $new = array();
		$shopM = new ModelBase('shop_agent');
		$agentPosM = new ModelBase('agent_pos');
		$adPosM = new ModelBase('ad_pos');
		$user = new \core\ModelBase('user_list');
		$agentId = $shopM->getData('ShopAgeId', array('EnameId'=> $enameId), $shopM::FETCH_COLUMN);
		if(! $agentId)
		{
			return array();
		}
		$posIdArr = $agentPosM->getData('PosId', array('AgentId'=> $agentId,'AgentType'=> 2));
		if($posIdArr)
		{
			foreach($posIdArr as $val)
			{
				$agentEnameId[] = $adPosM->getData('EnameId', array('PosId'=> $val->PosId), $adPosM::FETCH_COLUMN);
			}
		}
		$agentEnameId = array_unique(array_filter($agentEnameId));
		foreach($agentEnameId as $val)
		{
			if(! $val)
			{
				continue;
			}
			$unsubscribe = $user->getData('isAgree', array('EnameId'=> $val), $user::FETCH_COLUMN);
			if($unsubscribe && (1 == $unsubscribe || 3 == $unsubscribe))
			{
				continue;
			}
			$new[] = $val;
		}
		return $new;
	}

	public function addCustompage()
	{
		$this->cusLib = new CustomPageLib();
		$CDsdk = new ModelBase('custompage_domain');
		$cuspageStatus = \core\Config::item('page_domain_status')->toArray();
		$pageTemStatus = \core\Config::item('page_template_status')->toArray();
		$cnameServer = \core\Config::item('page_cname_server');
		$domainReg = \core\Config::item('page_domain_reg')->toArray();
		$status = array($cuspageStatus['page'][0],$cuspageStatus['cname'][0]);
		$nextId = 0;
		$limit = 1000;
		// 每次取出500条，直到没有为止
		while($data = $CDsdk->getData(
			'CustompageDId,EnameId,DomainName,Reg,Status,HoldStatus,UpdateTime,CreateTime,errowInfo,Description,errowInfo,TemplateDId', 
			array(/*'Status'=> array('IN',$status),*/'CustompageDId'=> array('>',$nextId)), $CDsdk::FETCH_ALL, 
			'CustompageDId ASC', array(0,$limit)))
		{
			$dataNew = $data;
			if(! $dataNew)
			{
				continue;
			}
			$nextId = array_pop($dataNew)->CustompageDId;
			\core\Logger::write('crontab_addCutompage', 
				array('get domains the status is  cname or page',__FILE__,__LINE__));
			$cnameRs = $this->checkCname($data, $domainReg);
			if(! $cnameRs)
			{
				\core\Logger::write('crontab_addCutompage',
						array('get go fail',__FILE__,__LINE__));
				continue;
			}
			\core\Logger::write('crontab_addCutompage',
					array('go return value', $cnameRs,__FILE__,__LINE__));
			$data = $this->mergeCheckName($data, json_decode($cnameRs));
			foreach($data as $val)
			{
				// echo $val->DomainName;
				if(! empty($errorDomain) && count($errorDomain) > 300)
				{
					$adminApi = new \core\EnameApi();
					// 发送消息
					$rs = $adminApi->sendCmd('member/addsitemessage', 
						array(
								'data'=> array('enameid'=> 701769,'title'=> '我司IIDNS域名解析未生效警告',
										'content'=> implode(',', $errorDomain)),'enameId'=> 701769,'templateId'=> '',
								'type'=> 99));
					if($rs)
						$errorDomain = array();
				}
				// $this->cusLib = new CustomPageLib();
				// $rs = $this->cusLib->checkCname($val->DomainName,
				// $regenameid);
				\core\Logger::write('crontab_addCutompage', 
					array('checkcname',json_encode($val->Flag),$val->DomainName,$val->EnameId,__FILE__,__LINE__));
				if($val->Flag)
				{
					\core\Logger::write('crontab_addCutompage', array($val->DomainName,$val->EnameId,__FILE__,__LINE__));
					
					// 设置等待生成展示页状态
					if($val->Status == $cuspageStatus['cname'][0])
					{
						$cnameStatus = $this->cusLib->setPageDomainStatus($val->CustompageDId, 
							$cuspageStatus['page'][0]);
						if($cnameStatus)
							\core\Logger::write('crontab_addCutompage', 
								array('setstatustopagetrue',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
						else
						{
							\core\Logger::write('crontab_addCutompage', 
								array('setstatustopagefalse',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
							continue;
						}
					}
					// 若是我司则检查域名是否属于用户
					// if($val->Reg == $domainReg['inename'][0])
					// {
					// if(! $this->cusLib->getDomainForUser($val->DomainName,
					// $val->EnameId))
					// continue;
					// }
					// 获取出售页data模板信息
					$dataInfo = $this->cusLib->getOldSystemTemInfo($val->TemplateDId, $val->EnameId);
					if(! $dataInfo)
						continue;
					$result = $this->cusLib->createPageDomain($val->DomainName, $val->TemplateDId, $val->EnameId, 
						$val->Description, $val->TransInfo, $val->errowInfo);
					// 生成展示页成功则修改状态
					if($result)
					{
						\core\Logger::write('crontab_addCutompage', 
							array('CreateCustompageFile',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
						// 设置状态为成功
						if($val->Reg == $domainReg['notinename'][0])
						{
							\core\Logger::write('crontab_addCutompage', 
								array('notinenamesetstatus',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
							$this->cusLib->setPageDomainStatusBNot($cuspageStatus['del'][0], $val->EnameId, 
								$val->DomainName);
						}
						$statusRs = $this->cusLib->setPageDomainStatus($val->CustompageDId, 
							$cuspageStatus['success'][0]);
						if($statusRs)
						{
							// 添加积分
							$s = new \logic\common\Common();
							$s::addScore($val->EnameId, 1, '添加域名展示页成功');
							\core\Logger::write('crontab_addCutompage', 
								array('SetStatusToSuccesstrue',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
						}
						else
						{
							\core\Logger::write('crontab_addCutompage', 
								array('SetStatusToSuccessfalse',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
						}
					}
					else
					{
						\core\Logger::write('crontab_addCutompage', 
							array('CreateCustompageFilefalse',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
					}
				}
				else
				{
					\core\Logger::write('crontab_addCutompage', 
						array('CheckCnamefalse',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
					// Cname不通过，状态为Cname,并且更新时间大于3天则删除
					if(($val->UpdateTime && time() > $val->UpdateTime + 2 * 86400) ||(!$val->UpdateTime && time() > $val->CreateTime + 2 * 86400))
					{
						$delRs = true;
						// 我司
						if($val->Reg == $domainReg['inename'][0])
						{
							// 检查域名是否属于用户
							// if($this->cusLib->getDomainForUser($val->DomainName,
							// $val->EnameId))
							// {
							$delRs = $this->domainInEname($val->EnameId, $val->DomainName, $val->HoldStatus, 
								$cnameServer);
							// }
						}
						if(! $delRs)
							continue;
							
							// 设置为删除状态
						$rsSetDel = $this->cusLib->setPageDomainStatus($val->CustompageDId, $cuspageStatus['del'][0]);
						if($rsSetDel)
							\core\Logger::write('crontab_addCutompage', 
								array('SetStatusToDeltrue',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
						else
							\core\Logger::write('crontab_addCutompage', 
								array('SetStatusToDelfalse',$val->DomainName,$val->EnameId,__FILE__,__LINE__));
					}
				}
			}
			break;
		}
	}

	public function checkCname($data, $domainReg)
	{
		// 检测是否有cname
		$content = array();
		foreach($data as $val)
		{
			$regenameid = ($val->Reg == $domainReg['inename'][0]? '': $val->EnameId);
			$content[] = array($val->DomainName,$regenameid);
		}
		// print_r($param);
		$param['content'] = $content;
		$param = array_merge($param, array('function'=> 'checkCname'));
		$socket = new \core\Socket();
		$socket->write(json_encode($param));
		$rs = $socket->getDataArr();
		$socket->close();
		if($rs)
		{
			return $rs[0];
		}
		return false;
	}

	public function mergeCheckName($data, $rs)
	{
		\core\Logger::write('crontab_addCutompage', array('socket_check_cname_num',count($rs->CheckDs)));
		foreach($data as $k => $v)
		{
			foreach($rs->CheckDs as $val)
			{
				if($v->DomainName == $val->DomainName)
				{
					$data[$k]->Flag = $val->Flag;
					break;
				}
			}
		}
		return $data;
	}

	/**
	 * 获取模板信息
	 */
	public function getCusTempData($domain, $enameId, $templateId, $description, $transInfo, $pageTemStatus)
	{
		$CSTsdk = new ModelBase('template_style');
		$temInfo = $this->getPageDataTem($templateId, $pageTemStatus['del'][0]);
		if(! $temInfo)
		{
			\core\Logger::write('crontab_addCutompage', array('GeTemplatetData',$domain,$enameId,__FILE__,__LINE__));
			return false;
		}
		\core\Logger::write('crontab_addCutompage', array('GeTemplatetData',$domain,$enameId,__FILE__,__LINE__));
		
		// 获取出售页style模板信息
		$styInfo = $CSTsdk->getData('TemplateId, EnameId, TemplateName, Html, Css, CreateTime, UpdateTime, Status', 
			array('TemplateId'=> $temInfo['StyleId'],'Status'=> array('!=',$pageTemStatus['del'][0])));
		if(! $styInfo)
		{
			\core\Logger::write('crontab_addCutompage', array('GetTemplateStyle',$domain,$enameId,__FILE__,__LINE__));
			return false;
		}
		\core\Logger::write('crontab_addCutompage', array('GetTemplateStyle',$domain,$enameId,__FILE__,__LINE__));
		
		$html = htmlspecialchars_decode($this->cusLib->composeHtml($styInfo['Html']));
		
		$dataInfo = array('action'=> 'update','enameid'=> $enameId,'domain'=> $domain,'email'=> $temInfo['Email'],
				'qq'=> $temInfo['QQ'],'tel'=> $temInfo['Phone'],'statType'=> $temInfo['StatType'],
				'statId'=> $temInfo['StatId'],'adType'=> $temInfo['AdType'],'adId'=> $temInfo['AdId'],
				'title'=> $temInfo['Title'],'keywords'=> $temInfo['KeyWords'],'description'=> $temInfo['Description'],
				'html'=> $html,'css'=> $styInfo['Css'],'domaindesc'=> $description,'transinfo'=> $transInfo,
				'templateType'=> $temInfo['TemplateType'],'styleId'=> $temInfo['StyleId'],
				'templateType'=> $temInfo['TemplateType'],'enameType'=> $temInfo['enameType'],
				'enameAdSolt'=> $temInfo['enameAdSolt'],'UserName'=> $temInfo['linkname'],
				'Description'=> $temInfo['linkdesc'],'Imgurl'=> $temInfo['avatarlinkurl']);
		return $dataInfo;
	}

	/**
	 * 获取系统模板数据
	 */
	public function getPageDataTem($templateId, $status)
	{
		$CDTsdk = new ModelBase('template_data');
		$dataInfo = $CDTsdk->getData(
			'TemplateDId, TemplateName, StyleId, TemplateType, Status, Ucid, StatType, StatId, AdType, AdId, Seoid,enameAdSolt,enameType', 
			array('TemplateDId'=> $templateId,'Status'=> array('!=',$status)), $CDTsdk::FETCH_ROW);
		if($dataInfo)
		{
			$dataInfo = (array)$dataInfo;
			$CSTsdk = new ModelBase('template_style');
			$usdk = new ModelBase('user_contact');
			$ssdk = new ModelBase('seo');
			if(empty($dataInfo['StyleId']))
			{
				return false;
			}
			if($dataInfo['Ucid'])
			{
				$ucinfo = $usdk->getData('*', array('UserCId'=> $dataInfo['Ucid']), $usdk::FETCH_ROW);
				if($ucinfo)
				{
					$ucinfo = (array)$ucinfo;
					$dataInfo['Email'] = $ucinfo['Email'];
					$dataInfo['QQ'] = $ucinfo['QQ'];
					$dataInfo['Phone'] = $ucinfo['Phone'];
					$dataInfo['linkname'] = $ucinfo['UserName'];
					$dataInfo['linkdesc'] = $ucinfo['Description'];
					$dataInfo['avatarlinkurl'] = $ucinfo['Imgurl'];
				}
				else
				{
					$dataInfo['Email'] = $dataInfo['QQ'] = $dataInfo['Phone'] = '';
				}
			}
			else
			{
				$dataInfo['Email'] = $dataInfo['QQ'] = $dataInfo['Phone'] = '';
			}
			if($dataInfo['Seoid'])
			{
				$sinfo = $ssdk->getData('*', array('SEOId'=> $dataInfo['Seoid']), $ssdk::FETCH_ROW);
				if($sinfo)
				{
					$sinfo = (array)$sinfo;
					$dataInfo['Title'] = $sinfo['Title'];
					$dataInfo['KeyWords'] = $sinfo['Keywords'];
					$dataInfo['Description'] = $sinfo['Description'];
				}
				else
				{
					$dataInfo['Title'] = $dataInfo['KeyWords'] = $dataInfo['Description'] = '';
				}
			}
			else
			{
				$dataInfo['Title'] = $dataInfo['KeyWords'] = $dataInfo['Description'] = '';
			}
			$styleInfo = $CSTsdk->getData('TemplateId, EnameId, TemplateName, Html, Css', 
				array('TemplateId'=> $dataInfo['StyleId']), $CSTsdk::FETCH_ROW);
			if($styleInfo)
			{
				$styleInfo = (array)$styleInfo;
				$dataInfo['Html'] = urldecode($styleInfo['Html']);
				$dataInfo['Css'] = $styleInfo['Css'];
				$dataInfo['StyleTemplateName'] = $styleInfo['TemplateName'];
				return $dataInfo;
			}
		}
		return false;
	}

	/**
	 * 若为我司域名，则需取消展示页状态，同时删除IIDNS
	 */
	public function domainInEname($EnameId, $DomainName, $holdStatus, $cnameServer)
	{
		$timesTamp = time();
		
		// 管理平台，取消展示页状态
		$closeRs = $this->cusLib->closePageHoldStatus($EnameId, $DomainName);
		if($closeRs['flag'])
		{
			\core\Logger::write('crontab_addCutompage', 
				array('ClosePageHoldStatus',$DomainName,$EnameId,__FILE__,__LINE__));
			// 删除IIDNS解析记录
			// $holdStatus = $this->cusLib->convertIIDNSHoldStatus($holdStatus);
			// $cnameRs = $this->cusLib->setCnameRecord($DomainName , $EnameId);
			// if($cnameRs['flag'])
			// {
			// Logger::log('addCustompage', array('DelCnameRecord', $DomainName,
			// $EnameId, __FILE__, __LINE__));
			// return true;
			// }
			// else
			// {
			// Logger::log('addCustompage', array('DelCnameRecord',
			// $cnameRs['msg'], $DomainName, $EnameId, __FILE__, __LINE__), 2);
			// return false;
			// }
			\core\Logger::write('crontab_addCutompage', 
				array('ClosePageHoldStatussuccess',$closeRs['msg'],$DomainName,$EnameId,__FILE__,__LINE__));
			return true;
		}
		\core\Logger::write('crontab_addCustompagetofalse', 
			array('ClosePageHoldStatuserror',$DomainName,$EnameId,__FILE__,__LINE__));
		\core\Logger::write('crontab_addCutompage', 
			array('ClosePageHoldStatuserror',$closeRs['msg'],$DomainName,$EnameId,__FILE__,__LINE__));
		return false;
	}

	/**
	 * 获取分销过该域名的分销enameId
	 *
	 * @param int $enameId
	 */
	public function agentEnameId($domainAgentId)
	{
		$data = $new = array();
		$agentPos = new ModelBase('agent_pos');
		$adPos = new ModelBase('ad_pos');
		$user = new \core\ModelBase('user_list');
		$position = $agentPos->getData('PosId', array('AgentId'=> $domainAgentId,'AgentType'=> 1));
		if(! array_filter($position))
		{
			return array();
		}
		
		foreach($position as $pos)
		{
			$data[] = $adPos->getData('EnameId', array('PosId'=> $pos->PosId), $adPos::FETCH_COLUMN);
		}
		
		$data = array_unique(array_filter($data));
		foreach($data as $val)
		{
			if(! $val)
			{
				continue;
			}
			$unsubscribe = $user->getData('isAgree', array('EnameId'=> $val), $user::FETCH_COLUMN);
			if($unsubscribe && (2 == $unsubscribe || 3 == $unsubscribe))
			{
				continue;
			}
			$new[] = $val;
		}
		
		return $new;
	}

	public function checkIsSell(ModelBase $model, $domain, $seller)
	{
		$model = new ModelBase('domain_agent');
		$where = array();
		$where['FinishTime'] = array('>',time());
		$where['CreateTime'] = array('<=',time() - \core\Config::item('edittime'));
		$where['DomainName'] = $domain;
		$where['EnameId'] = $seller;
		return $model->getData('*', $where);
	}

	public function updateAgent(ModelBase $model, $domain, $seller, $finishTime, $percent, $transId, $price)
	{
		$model = new ModelBase('domain_agent');
		$where = array();
		$where['FinishTime'] = array('>',time());
		$where['CreateTime'] = array('<=',time() - \core\Config::item('edittime'));
		$where['DomainName'] = $domain;
		$where['EnameId'] = $seller;
		return $model->update(
			array('FinishTime'=> $finishTime,'Percent'=> $percent,'TransId'=> $transId,'Price'=> $price,'Topic'=> 8), 
			$where);
	}
}
