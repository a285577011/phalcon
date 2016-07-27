<?php
use \core\ModelBase;
use \solr\DomainAuctionSolr;
use \core\driver\Redis;
use logic\task\TaskLogic;
use core\EnameApi;
use \core\Logger;

class PageTask extends \Phalcon\CLI\Task
{

	const MSCENTER_DOMAIN_KEY = 'centerdomain';

	const UPDATE_NUM = 100; // 每次更新数量
	public function mainAction()
	{
		echo '一个定时任务';
	}

	/**
	 * 添加展示页
	 */
	public function addCutompageAction() // 定时更新域名
	{
		\core\Logger::write('crontab_addCutompage', array(__FUNCTION__,'start',date('Y-m-d H:i:s')));
		set_time_limit(0);
		$logic = new TaskLogic();
		$logic->addCustompage();
		\core\Logger::write('crontab_addCutompage', array(__FUNCTION__,'end',date('Y-m-d H:i:s')));
	}

	/**
	 */
	public function CreatePageByholdAction() // 更新展示页状态为4的等待审核
	{
		$t1 = microtime(true);
		$cusLib = new \lib\custompage\CustomPageLib();
		$holdStatusConf = \core\Config::item('page_domain_holdstatus')->toArray();
		$custompageDomain = new ModelBase('custompage_domain');
		$Domain = new ModelBase('custompage_domain');
		$count = $custompageDomain->count(array('Status'=> 4), 'CustompageDId');
		echo $count;
		$size = ceil($count / self::UPDATE_NUM);
		for($i = 0; $i < $size; $i++)
		{
			$data = $custompageDomain->getData(
				'CustompageDId,DomainName,EnameId,TemplateDId,Reg,CreateTime,TransInfo,errowInfo ,Description', 
				array('Status'=> 4), $custompageDomain::FETCH_ALL, false, array($i * self::UPDATE_NUM,self::UPDATE_NUM));
			if($data)
			{
				foreach($data as $val)
				{
					$val = (array)$val;
					echo $val['DomainName'];
					echo "\n";
					// 检查域名是否还属于用户
					if($val['CreateTime'] != '' && time() < $val['CreateTime'] + 3 * 86400)
					{
						if($val['Reg'] == 1 && $val['CreateTime'] < time() - 7200)
						{
							$holdRs = $cusLib->setPageHoldStatus($val['EnameId'], $val['DomainName'], true);
							if($holdRs['flag'])
							{
								foreach($holdRs['msg'] as $v)
								{
									// 设置HoldStatus
									$v['domainStatus'] = isset($v['domainStatus'])? $v['domainStatus']: $holdStatusConf['unkown'][0]; // 未知的holdstatus
									if($cusLib->setHoldStatusWithHold($val['CustompageDId'], $v['domainStatus'], 
										$val['EnameId']))
									{
										if($v['code'] == 100000 || $v['code'] == '360015')
										{
											
											// 设置状态为CNAME
											$setPage = $cusLib->setPageDomainStatus($val['CustompageDId'], 3, 
												$val['EnameId']);
											Logger::write('CreatePageByhold_inename', 
												array($setPage? 'TRUE': 'FALSE','addPageDomain','InEname',
														'setPageDomainStatus','CNAME',$val['CustompageDId'],
														$val['DomainName'],$val['EnameId'],json_encode($v),__FILE__,
														__LINE__), 'custompage');
											$cnameRs = $cusLib->setCnameRecord($val['DomainName'], $val['EnameId']);
											if($cnameRs['flag'] && $cnameRs['code'] == '100000')
											{
												Logger::write('CreatePageByhold_inename',
												array('TRUE','setCnameRecord',$val['CustompageDId'],$val['DomainName'],$val['EnameId'],json_encode($v),__FILE__,__LINE__), 'custompage');
											}
											else 
											{
												Logger::write('CreatePageByhold_inename',
												array( 'FALSE','setCnameRecord',$val['CustompageDId'],$val['DomainName'],$val['EnameId'],json_encode($v),__FILE__,__LINE__), 'custompage');
											}
										}
										else
										{
											// 无法设置展示页的域名，设置状态为DEL
											$setSuccess = $cusLib->setPageDomainStatus($val['CustompageDId'], 5, 
												$val['EnameId']);
											Logger::write('CreatePageByhold_inename', 
												array($setSuccess? 'TRUE': 'FALSE','addPageDomain','InEname',
														'forbidAddPageDomain','error',$val['CustompageDId'],
														$val['DomainName'],$val['EnameId'],__FILE__,__LINE__), 
												'custompage');
											continue;
										}
									}
									else
									{
										$result['false']['addPageDomainFalse'][] = $domain;
										Logger::write("custompage_addpagedomain", 
											array('FALSE','addPageDomain','InEname','setHoldStatus',$val['DomainName'],
													$val['EnameId'],$val['CustompageDId'],$v['domainStatus']), 
											'custompage');
										continue;
									}
								}
							}
							else
							{
								Logger::write('CreatePageByhold_inename', 
									array('FALSE','setPageHoldStatus',$val['DomainName'],$val['EnameId'],
											json_encode($holdRs)), 'custompage');
							}
						}
					}
					else
					{
						$res = $cusLib->closePageHoldStatus($val['EnameId'], $val['DomainName']);
						$cusLib->setPageDomainStatus($val['CustompageDId'], 5, $val['EnameId']);
						Logger::write('CreatePageByhold', 
							array('FALSE','timeout',$val['TemplateDId'],$val['EnameId'],$val['DomainName'],
									$val['CustompageDId'],json_encode($res),__FILE__,__LINE__), 'custompage');
					}
				}
			}
		}
		$t2 = microtime(true);
		echo '耗时' . round($t2 - $t1, 3) . '秒';
		echo 'sucess';
	}

	/**
	 * 定时任务更新消息中心数据
	 */
	public function msgPageDomainAction()
	{
		$cusLib = new \lib\custompage\CustomPageLib();
		\core\Logger::write('crontab_delpagedomain_mscenter', array('delpagedomain','start',date('Y-m-d H:i:s')));
		$Mod = new ModelBase('custompage_domain');
		$count = Redis::getInstance()->lSize(self::MSCENTER_DOMAIN_KEY);
		\core\Logger::write('crontab_delpagedomain_mscenter', array('delpagedomain','总数量' . $count,date('Y-m-d H:i:s')));
		for($i = 0; $i < $count; $i++)
		{
			if(! $redisdata = Redis::getInstance()->rPop(self::MSCENTER_DOMAIN_KEY))
			{
				continue;
			}
			$redisdata = json_decode($redisdata, true);
			$params = json_decode($redisdata['p'], true);
			$domain = $params['domain'];
			\core\Logger::write('crontab_delpagedomain_mscenter', array($domain,'start',date('Y-m-d H:i:s')));
			if($redisdata['c'] == '1003')
			{
				$this->delPageDomain($domain);
			}
			elseif($redisdata['c'] == '1001')
			{
				$t = 0;
				if(isset($redisdata['t']) && $redisdata['t'])
				{
					$t = $redisdata['t'];
				}
				$this->pushPageDomain($domain, $params ,$t);
			}
			elseif($redisdata['c'] == '1004')
			{
				$this->inPageDomain($domain, $params);
			}
			elseif($redisdata['c'] == '1002')
			{
				$t = 0;
				if(isset($redisdata['t']) && $redisdata['t'])
				{
					$t = $redisdata['t'];
				}
				$this->regPageDomain($domain, $params, $t);
			}
			else
			{
				\core\Logger::write('crontab_delpagedomain_mscenter', 
					array('domain' . $domain,'数据有误',date('Y-m-d H:i:s')));
			}
			\core\Logger::write('crontab_delpagedomain_mscenter', array($domain,'end',date('Y-m-d H:i:s')));
		}
	}

	/**
	 * 定时任务更新消息中心数据
	 */
	public function delPageDomain($domain)
	{
		\core\Logger::write('crontab_delpagedomain_mscenter', 'delPageDomain' . $domain . 'start');
		$cusLib = new \lib\custompage\CustomPageLib();
		$Mod = new ModelBase('custompage_domain');
		$info = $Mod->getData('DomainName, EnameId, CustompageDId, Reg', 
			array('DomainName'=> $domain,'Status'=> array('<',5)));
		if(! $info)
		{
			\core\Logger::write('crontab_delpagedomain_mscenter', $domain . '展示页记录不存在');
			return '';
		}
		foreach($info as $k => $v)
		{
			
			if($v->Reg != 2)
			{
				if($Mod->update(array('Status'=> 5,'DeleteTime'=> time()), array('CustompageDId'=> $v->CustompageDId)))
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态成功，记录id:' . $v->CustompageDId);
					$data = $cusLib->removeCustompageFile($v->DomainName, $v->EnameId);
					if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
					{
						\core\Logger::write('crontab_delpagedomain_mscenter', 
							array('TRUE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,
									__LINE__));
					}
					else
					{
						Logger::write('crontab_delpagedomain_mscenter', 
							array('FALSE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,
									__LINE__));
					}
				}
				else
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态失败，记录id:' . $v->CustompageDId);
				}
			}
		}
		return '';
	}

	/**
	 * 定时任务更新消息中心数据
	 */
	public function regPageDomain($domain, $params, $t = 0)
	{
		\core\Logger::write('crontab_delpagedomain_mscenter', 'regPageDomain' . $domain . 'START');
		$cusLib = new \lib\custompage\CustomPageLib();
		$Mod = new ModelBase('custompage_domain');
		$info = $Mod->getData('DomainName, EnameId, CustompageDId, Reg , CreateTime', 
			array('DomainName'=> $domain,'Status'=> array('<',5)));
		if(! $info)
		{
			\core\Logger::write('crontab_delpagedomain_mscenter', $domain . '展示页记录不存在');
			return '';
		}
		foreach($info as $k => $v)
		{
			if($v->EnameId == $params['enameid'] || $t < $v->CreateTime)
			{
				if($v->Reg == 1)
				{
					continue;
				}
			}
			if($Mod->update(array('Status'=> 5,'DeleteTime'=> time()), array('CustompageDId'=> $v->CustompageDId)))
			{
				\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态成功，记录id:' . $v->CustompageDId);
				$data = $cusLib->removeCustompageFile($v->DomainName, $v->EnameId);
				if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', 
						array('TRUE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,__LINE__));
				}
				else
				{
					Logger::write('crontab_delpagedomain_mscenter', 
						array('FALSE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,__LINE__));
				}
			}
			else
			{
				\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态失败，记录id:' . $v->CustompageDId);
			}
		}
		return '';
	}

	/**
	 * 定时任务更新消息中心数据
	 */
	public function pushPageDomain($domain, $params , $t = 0)
	{
		\core\Logger::write('crontab_delpagedomain_mscenter', 'pushPageDomain' . $domain . 'START');
		$cusLib = new \lib\custompage\CustomPageLib();
		$Mod = new ModelBase('custompage_domain');
		$info = $Mod->getData('DomainName, EnameId, CustompageDId, Reg', 
			array('DomainName'=> $domain,'Status'=> array('<',5)));
		if(! $info)
		{
			\core\Logger::write('crontab_delpagedomain_mscenter', $domain . '展示页记录不存在');
			return '';
		}
		
		foreach($info as $k => $v)
		{
			if($v->EnameId == $params['enameid'])
			{
				if($Mod->update(array('Status'=> 5,'DeleteTime'=> time()), array('CustompageDId'=> $v->CustompageDId)))
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态成功，记录id:' . $v->CustompageDId);
					$otherdata = array();
					if($t)
					{
						$otherdata = $Mod->getData('CustompageDId' , array('DomainName'=>$v->DomainName,'EnameId'=>array('<>', $v->EnameId),'Status'=>array('<' , 5) , 'CreateTime'=>array('>' ,$t)));
					}
					if(!$otherdata)
					{
						$data = $cusLib->removeCustompageFile($v->DomainName, $v->EnameId);
						if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
						{
							\core\Logger::write('crontab_delpagedomain_mscenter', 
								array('TRUE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,
										__LINE__));
						}
						else
						{
							Logger::write('crontab_delpagedomain_mscenter', 
								array('FALSE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,
										__LINE__));
						}
				 	}
				}
				else
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态失败，记录id:' . $v->CustompageDId);
				}
			}
		}
		return '';
	}

	/**
	 * 定时任务更新消息中心数据
	 */
	public function inPageDomain($domain, $params)
	{
		\core\Logger::write('crontab_delpagedomain_mscenter', 'inPageDomain' . $domain . 'START');
		$cusLib = new \lib\custompage\CustomPageLib();
		$Mod = new ModelBase('custompage_domain');
		$info = $Mod->getData('DomainName, EnameId, CustompageDId, Reg', 
			array('DomainName'=> $domain,'Status'=> array('<',5)));
		if(! $info)
		{
			\core\Logger::write('crontab_delpagedomain_mscenter', $domain . '展示页记录不存在');
			return '';
		}
		foreach($info as $k => $v)
		{
			if($v->EnameId == $params['enameid'])
			{
				// 非我司域名
				$res1 = $cusLib->setPageHoldStatus($params['enameid'], $params['domain']); // 设置域名展示页状态
				$res2 = $cusLib->setCnameRecord($params['domain'], $params['enameid']); // 设置CNAME记录
				if($res1['flag'] && $res1['msg'][0]['code'] == 100000 && $res2['flag'])
				{
					if($v->Reg == 2)
					{
						$Mod->update(array('Reg'=> 1), array('CustompageDId'=> $v->CustompageDId));
					}
				}
				else
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新展示页状态或CNAME失败,记录id:' . $v->CustompageDId);
				}			
				continue;
			}
			else
			{
				
				// 关闭展示状态（情景：域名转入，用户在消息中心执行前发布了展示页，导致无脑删除了展示页记录，而没修改展示页状态）
				$res = $cusLib->closePageHoldStatus($v->EnameId, $v->DomainName);
				\core\Logger::write('crontab_delpagedomain_mscenter', $res['msg'] . ',记录id:' . $v->CustompageDId);
				if($Mod->update(array('Status'=> 5,'DeleteTime'=> time()), array('CustompageDId'=> $v->CustompageDId)))
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态成功，记录id:' . $v->CustompageDId);
					$data = $cusLib->removeCustompageFile($v->DomainName, $v->EnameId);
					if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
					{
						\core\Logger::write('crontab_delpagedomain_mscenter', 
							array('TRUE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,
									__LINE__));
					}
					else
					{
						Logger::write('crontab_delpagedomain_mscenter', 
							array('FALSE','RemoveCustompageFile',$data['msg'],$v->DomainName,$v->EnameId,__FILE__,
									__LINE__));
					}
				}
				else
				{
					\core\Logger::write('crontab_delpagedomain_mscenter', '更新记录为删除状态失败，记录id:' . $v->CustompageDId);
				}
			}
		}
		return '';
	}

	/**
	 * socket更新展示页域名
	 */
	public function setTemplateAction($data = array())
	{
		
		$logic = new \logic\custompage\CustomPage();
		if($count = Redis::getInstance()->lSize('setTemplate_id'))
		{
			\core\Logger::write('socket_SetTemplate', 'START');
			$lib = new lib\custompage\CustomPageLib();
			for($i = 0; $i < $count && $count < 100; $i++)
			{
				if(! $content = Redis::getInstance()->lPop('setTemplate_id'))
				{
					continue;
				}
				\core\Logger::write('socket_SetTemplate', json_encode($content));
				$temId = $content['tId'];
				if(! $domainarr = $lib->getPageDomainByTemplateId($temId))
				{
					\core\Logger::write('socket_SetTemplate', array('temp is not exit', $temId));
					continue;
				}
				foreach($domainarr as $v)
				{ 
					$v = (array)$v;
					$res = $logic->singleSetTemplate($v);
				}
			}
			\core\Logger::write('socket_SetTemplate', 'END');
		}
		elseif($data)
		{
			$cusDomainId = $data[0];
			\core\Logger::write('socket_SetTemplate', 'START');
			$logic->singleSetTemplate($cusDomainId);
			\core\Logger::write('socket_SetTemplate', 'END');
		}
	}
	/**
	 * 批量添加展示页去IIDNS接口设置CNAME记录，值针对我司域名
	 */
	public function setCnameRecordTaskAction()
	{
		\core\Logger::write('setCnameRecordTask', 'START');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		if($count = Redis::getInstance()->lSize('setcnamerecord_addpage'))
		{
			\core\Logger::write('setCnameRecordTask', 'count,'.$count);
			$lib = new lib\custompage\CustomPageLib();
			for($i = 0; $i < $count && $i < 100; $i++)
			{
				if(! $content = Redis::getInstance()->lPop('setcnamerecord_addpage'))
				{
					\core\Logger::write('setCnameRecordTask', 'redis error');
						continue;
				}
				\core\Logger::write('setCnameRecordTask', json_encode($content));
				$domainName = $content['domainname'];
				$EnameId = $content['EnameId'];
				$Id = $content['pageid'];
				$cnameRs = $lib->setCnameRecord($domainName, $EnameId);				
				if($cnameRs['flag'] && $cnameRs['code'] == '100000')
				{
					
						\core\Logger::write('setCnameRecordTask', array('setCNAME true', $domainName,$EnameId));
				}
				else 
				{
					\core\Logger::write('setCnameRecordTask', array('setCNAME false', $domainName,$EnameId));
				}
			}
		}
		\core\Logger::write('setCnameRecordTask', 'END');
	}
}
?>