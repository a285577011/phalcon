<?php
use \core\ModelBase;
use \solr\DomainAuctionSolr;
use \core\driver\Redis;
use logic\task\TaskLogic;
use core\EnameApi;
use \core\Logger;
class cronPageTask extends \Phalcon\CLI\Task
{
	const MSCENTER_DOMAIN_KEY = 'centerdomain';
	const UPDATE_NUM = 100; // 每次更新数量
	public function mainAction()
	{
		echo '一个定时任务';
	}

	/**
	 * 分销域名价格更新任务(竞价)
	 */
	public function insertstyleAction() // 定时更新域名
	{
		$custompageStyleTemplate = new ModelBase('custompage_style_template', 'ename_trans');
		$template_style = new ModelBase('template_style');
		$count = $custompageStyleTemplate->count(array(), 'Id');
		$size = ceil($count / self::UPDATE_NUM);
		for($i = 0; $i < $size; $i++)
		{
			$data = $custompageStyleTemplate->getData('*', array('EnameId'=> array('!=',1000)), 
				$custompageStyleTemplate::FETCH_ALL, false, array($i * self::UPDATE_NUM,self::UPDATE_NUM));
			/*
			 * $newData = $template_style->getData('TemplateId', array(),
			 * $template_style::FETCH_ALL); if($newData) { foreach($newData as
			 * $val) { foreach($data as $key => $vals) { if($val->TemplateId ==
			 * $vals->Id) { unset($data[$key]); } } } }
			 */
			foreach($data as $val)
			{
				$insert = array('TemplateId'=> $val->Id,'EnameId'=> $val->EnameId,'Css'=> $val->Css,'Html'=> $val->Html,
						'CreateTime'=> strtotime(strtotime($val->CreateTime)),
						'UpdateTime'=> strtotime(strtotime($val->UpdateTime)),'Status'=> $val->Status,
						'TemplateName'=> $val->TemplateName);
				if(! $template_style->insert($insert))
				{
					\core\Logger::write('insertstyle', 'ID:' . $val->Id);
				}
			}
		}
		echo 'sucess';
	}

	/**
	 * 分销域名价格更新任务(竞价)
	 */
	public function insertdataAction() // 定时更新域名
	{
		$custompageDataTemplate = new ModelBase('custompage_data_template', 'ename_trans');
		$template_data = new ModelBase('template_data');
		$userC = new ModelBase('user_contact');
		$seo = new ModelBase('seo');
		$count = $custompageDataTemplate->count(array(), 'Id');
		$size = ceil($count / self::UPDATE_NUM);
		$j=1;
		for($i = 0; $i < $size; $i++)
		{
			$data = $custompageDataTemplate->getData('*',  array(),
				$custompageDataTemplate::FETCH_ALL, false, array($i * self::UPDATE_NUM,self::UPDATE_NUM));
			$newData = $template_data->getData('TemplateDId', array(), $template_data::FETCH_ALL);
			if($newData)
			{
				foreach($newData as $val)
				{
					foreach($data as $key => $vals)
					{
						if($val->TemplateDId == $vals->Id)
						{
							unset($data[$key]);
						}
					}
				}
			}
			foreach($data as $val)
			{
				$ucont = $scont = 0;
				$styleId = $val->TemplateType == 2?$val->StyleId :  mt_rand(1, 6);
				$insertU = array('Email'=> $val->Email,'QQ'=> $val->QQ,'Phone'=> $val->Phone,'EnameId'=> $val->EnameId,
						'CreateTime'=> strtotime($val->CreateTime),'CardName'=> $val->TemplateName);
				$ucont = $userC->count(array('EnameId'=> $val->EnameId), 'UserCId') ;
				if(! $uid = $userC->getData('UserCId', 
					array('Email'=> $val->Email,'QQ'=> $val->QQ,'Phone'=> $val->Phone,'EnameId'=> $val->EnameId), 
					$userC::FETCH_COLUMN))
				{
					if($ucont <10)
					{
						if(! $uid = $userC->insert($insertU))
						{
							\core\Logger::write('insertdata', 'ID:' . $val->Id);
							continue;
						}
					}else {
						$newcinfo = $userC->getData('UserCId', array('EnameId'=> $val->EnameId), $userC::FETCH_ROW);
						$uid = $newcinfo->UserCId;
					}
				}
				$insertS = array('Title'=> $val->Title,'Keywords'=> $val->KeyWords,'Description'=> $val->Description,
						'EnameId'=> $val->EnameId,'CreateTime'=> strtotime(strtotime($val->CreateTime)),
						'CardName'=> 'C' . $j);
				$scont = $seo->count(array('EnameId'=> $val->EnameId), 'SEOId') ;
				if(! $sid = $seo->getData('SEOId', 
					array('Title'=> $val->Title,'Keywords'=> $val->KeyWords,'Description'=> $val->Description,
							'EnameId'=> $val->EnameId), $seo::FETCH_COLUMN))
				{
					if($scont <10)
					{
						if(! $sid = $seo->insert($insertS))
						{
							\core\Logger::write('insertdata', 'ID:' . $val->Id);
							continue;
						}
					}else 
					{
						if(! $sid = $seo->insert(array('Title'=> '{%domain%}域名出售，{%domain%}可以转让，this domain is for sale','Keywords'=> '{%domain%}域名出售，{%domain%}可以转让，this domain is for sale','Description'=> '{%domain%}域名出售，{%domain%}可以转让，this domain is for sale',
						'EnameId'=> $val->EnameId,'CreateTime'=> time(),'CardName'=> 'C' . $j)))
						{
							\core\Logger::write('insertdata', 'ID:' . $val->Id);
							continue;
						}
					}
				}
				$insertT = array('TemplateDId'=> $val->Id,'TemplateName'=> $val->TemplateName,
						'TemplateType'=> $val->TemplateType,'EnameId'=> $val->EnameId,
						'CreateTime'=> strtotime($val->CreateTime),'Status'=> $val->Status,'StatType'=> $val->StatType,
						'StatId'=> $val->StatId,'AdType'=> $val->AdType,'UpdateTime'=> strtotime($val->UpdateTime),
						'StyleId'=> $styleId,'LinkId'=> $val->LinkId,'DomainCount'=> 0,
						'enameAdSolt'=> '','enameType'=> '','Ucid'=> $uid,'Seoid'=> $sid);
				if(! $template_data->insert($insertT))
				{
					\core\Logger::write('insertdata', 'ID:' . $val->Id);
				}
				$j++;
				echo $j;
				echo "\n";
			}
		}
		echo 'sucess';
	}
	/**
	 * 分销域名价格更新任务(竞价)
	 */
	public function updateTempdataAction() // 定时更新域名
	{
		$cusLib=new \lib\custompage\CustomPageLib();
		$template_data = new ModelBase('template_data');
		$Domain = new ModelBase('custompage_domain');
		$count = $template_data->count(array('AdType'=>array('>',0),'TemplateDId'=>array('<',52342)), 'TemplateDId');
		$size = ceil($count / self::UPDATE_NUM);
		$j = 0;
		for($i = 0; $i < $size; $i++)
		{
		$data = $template_data->getData('*',  array('AdType'=>array('>',0),'TemplateDId'=>array('<',52342)),
				$template_data::FETCH_ALL, false, array($i * self::UPDATE_NUM,self::UPDATE_NUM));
		
				foreach($data as $val)
				{
							 	$cusdata = $Domain->getData('CustompageDId,DomainName,EnameId,TemplateDId,Reg', array('Status'=>1,'TemplateDId'=>$val->TemplateDId), $Domain::FETCH_ALL);
							 	if($cusdata)
							 	{
							 		foreach($cusdata as $vals)
							 		{
							 			$j++;
							 			$vals = (array)$vals;
							 			echo $vals['DomainName'];
							 			echo "\n";
							 			// 检查域名是否还属于用户
							 			if($vals['Reg'] == 1)
							 			{
							 				if(!$cusLib->getDomainForUser($vals['DomainName'],$vals['EnameId']))
							 				{
							 					echo 'notusername'. '---'.$vals['DomainName'];
							 					echo "\n";
							 					Logger::write( 'newTemplateBywe_error',
							 					array('FALSE','cronreatePage','NotUserDomain',$vals['CustompageDId'],$vals['EnameId'],$vals['DomainName'],
							 					__FILE__,__LINE__),'custompage');
							 					continue;
							 				}
							 			}
							 			echo 'pageretry'. '---'.$vals['DomainName'];
							 			echo "\n";
							 			$result = $cusLib->pageRetry($vals['CustompageDId'], $vals['EnameId'], $vals['DomainName']);
							 			if($result['result'])
							 			{
							 				Logger::write('newTemplateBywe_success',
							 				array( 'TRUE' ,'pageretry', $vals['TemplateDId'],$vals['EnameId'],
							 				$vals['DomainName'],$vals['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
							 			}
							 			else
							 			{
							 				Logger::write('newTemplateBywe_error',
							 				array('FALSE','pageretry', $vals['TemplateDId'],$vals['EnameId'],
							 				$vals['DomainName'],$vals['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
							 			}
							 	
							 		}
							 	}
				}
				echo $j;
				echo 'sucess';
				}
	}			
	/**
	 * 用户推广的代码生成有BUG的修复
	 */
	public function updateTempdataByadAction() // 
	{
		$cusLib=new \lib\custompage\CustomPageLib();
		$template_data = new ModelBase('template_data');
		$Domain = new ModelBase('custompage_domain');
		$count = $template_data->count(array('enameType'=>2,'Status'=>1), 'TemplateDId');
		echo $count;
		$size = ceil($count / self::UPDATE_NUM);
		$j = 0;
		for($i = 0; $i < $size; $i++)
		{
		$data = $template_data->getData('*',  array('enameType'=>2,'Status'=>1),
				$template_data::FETCH_ALL, false, array($i * self::UPDATE_NUM,self::UPDATE_NUM));
	
				foreach($data as $val)
				{
				$cusdata = $Domain->getData('CustompageDId,DomainName,EnameId,TemplateDId', array('Status'=>1,'TemplateDId'=>$val->TemplateDId), $Domain::FETCH_ALL);
				if($cusdata)
				{
				foreach($cusdata as $vals)
					{
					$j++;
					$vals = (array)$vals;
					echo $vals['DomainName'];
					echo "\n";

	 					$result = $cusLib->pageRetry($vals['CustompageDId'], $vals['EnameId'], $vals['DomainName']);
	 							if($result['result'])
	 							{
	 							Logger::write('updateTempdataByad_success',
	 							array( 'TRUE' ,'pageretry', $vals['TemplateDId'],$vals['EnameId'],
	 							$vals['DomainName'],$vals['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
	 							}
	 							else
	 							{
	 									Logger::write('updateTempdataByad_error',
	 											array('FALSE','pageretry', $vals['TemplateDId'],$vals['EnameId'],
 				$vals['DomainName'],$vals['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
	 							}
	 										
	 							}
				}
					}
					echo $j;
					echo 'sucess';
				}
					}		
	/**
	 * 分销域名价格更新任务(竞价)
	 */
	public function insertdomainAction() // 定时更新域名
	{
		$custompageDomain = new ModelBase('custompage_domain', 'ename_trans');
		$Domain = new ModelBase('custompage_domain');
		$count = $custompageDomain->count(array(), 'Id');
		$size = ceil($count / self::UPDATE_NUM);
		for($i = 0; $i < $size; $i++)
		{
			$data = $custompageDomain->getData('*', array(), $custompageDomain::FETCH_ALL, false, 
				array($i * self::UPDATE_NUM,self::UPDATE_NUM));
			foreach($data as $val)
			{
				$insert = array('CustompageDId'=> $val->Id,'TemplateDId'=> $val->TemplateId,'EnameId'=> $val->EnameId,
						'DomainName'=> $val->DomainName,'Status'=> $val->Status,'TransInfo'=> $val->TransInfo,
						'Reg'=> $val->Reg,'HoldStatus'=> $val->HoldStatus,'Description'=> $val->Description,
						'CreateTime'=> strtotime($val->CreateTime),'UpdateTime'=> strtotime($val->UpdateTime),
						'DeleteTime'=> strtotime($val->DeleteTime),'DelFlag'=> $val->DelFlag,'errowInfo'=> 1);
				if(! $Domain->insert($insert))
				{
					\core\Logger::write('insertdomain', 'ID:' . $val->Id);
				}
			}
		}
		echo 'sucess';
	}
	/**
	 * 
	 */
	public function CreatePageAction() // 定时更新域名
	{
		$t1 = microtime(true);
		$cusLib=new \lib\custompage\CustomPageLib();
		$custompageDomain = new ModelBase('custompage_domain');
		$Domain = new ModelBase('custompage_domain');
		$count = $custompageDomain->count(array('Status'=>1), 'CustompageDId');
		echo $count;
		$size = ceil($count / self::UPDATE_NUM);
		for($i = 0; $i < $size; $i++)
		{
		$data = $custompageDomain->getData('CustompageDId,DomainName,EnameId,TemplateDId,Reg', array('Status'=>1), $custompageDomain::FETCH_ALL, false,
			array($i * self::UPDATE_NUM,self::UPDATE_NUM));
		if($data)
		{
			foreach($data as $val)
			{
				$val = (array)$val;
				echo $val['DomainName'];
				echo "\n";
				// 检查域名是否还属于用户
				if($val['Reg'] == 1)
				{
					if(!$cusLib->getDomainForUser($val['DomainName'],$val['EnameId']))
					{
						echo 'notusername'. '---'.$val['DomainName'];
						echo "\n";
						Logger::write( 'newTemplateBywe_error',
								array('FALSE','cronreatePage','NotUserDomain',$val['CustompageDId'],$val['EnameId'],$val['DomainName'],
										__FILE__,__LINE__),'custompage');
						continue;
					}
				}
				echo 'pageretry'. '---'.$val['DomainName'];
				echo "\n";
				$result = $cusLib->pageRetry($val['CustompageDId'], $val['EnameId'], $val['DomainName']);
				if($result['result'])
				{
					Logger::write('newTemplateBywe_success',
					array( 'TRUE' ,'pageretry', $val['TemplateDId'],$val['EnameId'],
					$val['DomainName'],$val['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
				}
				else
				{
					Logger::write('newTemplateBywe_error',
					array('FALSE','pageretry', $val['TemplateDId'],$val['EnameId'],
					$val['DomainName'],$val['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
				}
						
			}
		}
		}
			$t2 = microtime(true);
			echo '耗时'.round($t2-$t1,3).'秒';
			echo 'sucess';
		}
		/**
		 *
		 */
		public function UpdateTplCreatePageAction() // 用户修改模板重新生成展示页
		{
			$t1 = microtime(true);
			$cusLib=new \lib\custompage\CustomPageLib();
			$custompageDomain = new ModelBase('custompage_domain');
			$Domain = new ModelBase('custompage_domain');
			$count = $custompageDomain->count(array('Status'=>array('IN',array('1')),'TemplateDId'=>'72457'), 'CustompageDId');
			echo $count;
			echo "\n";
			$size = ceil($count / self::UPDATE_NUM);
			for($i = 0; $i < $size; $i++)
			{
			$data = $custompageDomain->getData('CustompageDId,DomainName,EnameId,TemplateDId,Reg,Status', array('Status'=>array('IN',array('1','2')),'TemplateDId'=>'72457'), $custompageDomain::FETCH_ALL, false,
					array($i * self::UPDATE_NUM,self::UPDATE_NUM));
					if($data)
					{
			foreach($data as $val)
			{
			$val = (array)$val;
			echo $val['DomainName'];
					echo "\n";
					// 检查域名是否还属于用户
					if($val['Reg'] == 1)
					{
					if(!$cusLib->getDomainForUser($val['DomainName'],$val['EnameId']))
					{
							echo 'notusername'. '---'.$val['DomainName'];
						echo "\n";
					Logger::write( 'UpdateTplCreatePage_error',
								array('FALSE','cronreatePage','NotUserDomain',$val['CustompageDId'],$val['EnameId'],$val['DomainName'],
										__FILE__,__LINE__),'custompage');
										continue;
					}
					}
					echo 'pageretry'. '---'.$val['DomainName'];
					echo "\n";
							$result = $cusLib->pageRetry($val['CustompageDId'], $val['EnameId'], $val['DomainName']);
							if($result['result'])
					{
						$rs = 1;
						if($val['Status'] == 2)
						{
							$rs = $cusLib->setPageDomainStatus($val['CustompageDId'], 1);
						}
					Logger::write('UpdateTplCreatePage_success',
					array( 'TRUE' ,'pageretry', $val['TemplateDId'],$val['EnameId'],
									$val['DomainName'],$val['CustompageDId'],$rs?'udpatestatussuccess':'udpatestatuserror',json_encode($result),__FILE__,__LINE__),'custompage');
					}
				else
						{
						Logger::write('UpdateTplCreatePage_error',
					array('FALSE','pageretry', $val['TemplateDId'],$val['EnameId'],
									$val['DomainName'],$val['CustompageDId'],json_encode($result),__FILE__,__LINE__),'custompage');
						}
		
					}
					}
					}
					$t2 = microtime(true);
					echo '耗时'.round($t2-$t1,3).'秒';
					echo 'sucess';
		}
}
?>