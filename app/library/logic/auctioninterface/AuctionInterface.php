<?php
namespace logic\auctioninterface;
use core\ModelBase;
use core\driver\Redis;
use lib\custompage\CustomPageLib;
use core\Logger;

class AuctionInterface
{
	protected $lib;
	
	
	public function __construct()
	{
		$this->lib = new CustomPageLib();
	}
	/**
	 * 修改或审核模板时，更新海外展示页
	 */
	public function updatePageDomainByTpl($templateId,$templateType)
	{
		Logger::write("custompage_INTERFACE", array("updatePageDomainByTpl", $templateId, $templateType, __LINE__, __FILE__));
		$templateTypeConf = \core\Config::item('page_template_style');
		if (in_array($templateType, array($templateTypeConf['diy'][0], $templateTypeConf['system'][0])))
		{
			$domainStatusConf = \core\Config::item('page_domain_status');
			$domainReg = \core\Config::item('page_domain_reg');
			$list = $this->lib->getPageDomainByTemplateId($templateId, array($domainStatusConf['success'][0],$domainStatusConf['page'][0]));
			if ($list)
			{
				$idArr = array();
				foreach($list as $v)
				{
					$idArr[] = $v->CustompageDId;
				}
				if(count($idArr) > 100)
				{					
					Redis::getInstance()->rPush('setTemplate_id', array('tId'=> $templateId));
				}
				else 
				{
					foreach ($list AS $info)
					{
						$info = (array)$info;
						//检查域名是否属用户，若不是则不更新展示页
						if($info['Reg'] == $domainReg['inename'][0])
						{
							if(!$this->lib->getDomainForUser( $info['DomainName'],$info['EnameId']))
							{
								Logger::write("custompage_NotUserDomain",array('msgType'=>350000 ,'domain'=>$info['DomainName'],'resultFlag'=>2,'note'=>array('UpdatePageDomainByTpl', 'DomainIsNotTheUser', $info['CustompageDId'], $info['DomainName'], $info['EnameId'],__METHOD__)),'custompage');
								continue;
							}
						}
					
						if($this->lib->createPageDomain($info['DomainName'], $templateId, $info['EnameId'], $info['Description'], $info['TransInfo'], $info['errowInfo']))
						{
							if($info['Status']==$domainStatusConf['page'][0])
							{
								//如果是page状态，则设置展示页状态生效(success)
								$rs = $this->lib->setPageDomainStatus($info['CustompageDId'], $domainStatusConf['success'][0]);
								Logger::write("custompage_SetPageDomain",array('msgType'=>350000,'domain'=>$info['DomainName'],'resultFlag'=>$rs?1:2,'note'=>array('UpdatePageDomainByTpl', 'SetPageToSuccess', $info['CustompageDId'], $info['DomainName'], $info['EnameId'],__METHOD__)),'custompage');
							}
						}
					}
				}
			}
		}
		return true;
	}
}