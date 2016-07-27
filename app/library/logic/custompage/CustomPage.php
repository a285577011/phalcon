<?php
namespace logic\custompage;
use core\ModelBase;
use lib\custompage\CustomPageLib;
use lib\user\UserLib;
use logic\agent;
use core\UploadFile;
use core\driver\Redis;
use core\Page;
use core\Logger;

class CustomPage
{

	protected $lib;

	protected $enameId;

	public function __construct($enameId = '')
	{
		$this->enameId = $enameId;
		$this->lib = new CustomPageLib();
	}

	public function getPageDomainList($data)
	{
		$templateId = $data['templateId'];
		$domainName = trim($data['domainName']);
		$status = $data['status'];
		$transInfo = $data['transInfo'];
		$errowInfo = $data['errowInfo'];
		$reg = $data['reger'];
		$holdStatus = $data['holdStatus'];
		$perPage = $data['per_page'];
		$perNum = \core\Config::item('cpagesize');
		$perPage = $perPage? : 0;
		$limit = $perPage . ', ' . $perNum;
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$getItems = array_diff_key($data, array('per_page'=> ''));
		$where = array('EnameId'=> $this->enameId);
		if($domainName)
		{
			$where['DomainName'] = '%' . $domainName . '%';
		}
		if($templateId)
		{
			$where['TemplateDId'] = $templateId;
		}
		if($status)
		{
			$where['Status'] = $status;
		}
		else
		{
			$where['Status'] = array('<',$statusConf['del'][0]);
		}
		if($transInfo)
		{
			$where['TransInfo'] = $transInfo;
		}
		if($errowInfo)
		{
			$where['errowInfo'] = $errowInfo;
		}
		if($reg)
		{
			$where['Reg'] = $reg;
		}
		if($holdStatus)
		{
			$where['HoldStatus'] = $holdStatus;
		}
		$CDsdk = new ModelBase('custompage_domain');
		$pageStatusConf = \core\Config::item('page_domain_status')->toArray();
		$totalCnt = $CDsdk->count($where, 'CustompageDId');
		$page = new Page($totalCnt, $perNum);
		$limit = array($perPage,\core\Config::item('cpagesize'));
		$pages = $page->show();
		$list = $CDsdk->getData('*', $where, $CDsdk::FETCH_ALL, 'CustompageDId desc', $limit);
		// 获取用户未删除的模板
		$templates = $this->lib->getValidDataTemplateForList($this->enameId);
		$statusConfLite = $this->KeyToValue($pageStatusConf, array('del'));
		return array('list'=> $list,'pages'=> $pages,
				'transInfoConf'=> $this->KeyToValue(\core\Config::item('page_domain_transinfo')->toArray()),
				'regConf'=> $this->KeyToValue(\core\Config::item('page_domain_reg')->toArray()),
				'holdStatusConf'=> $this->KeyToValue(\core\Config::item('page_domain_holdstatus')->toArray()),
				'statusConfLite'=> $statusConfLite,'statusConf'=> $pageStatusConf,'getItems'=> $getItems,
				'templates'=> $templates);
	}

	/**
	 * 删除域名展示页
	 *
	 * @param MY_Controller $ctrl
	 * @throws Exception
	 * @return multitype:NULL
	 */
	public function delPageDomain($ctrl, $ids)
	{
		$result = array('success'=> array(),'failed'=> array(),'url'=> array());
		if(! $ids)
		{
			throw new \Exception('请选择需要删除的展示页');
		}
		
		// 防并发请求，往redis里写入个正在发布交易的Flag
		$TAsdk = Redis::getInstance();
		$keyName = 'trans:cumstompage:' . $this->enameId;
		if($TAsdk->get($keyName))
		{
			throw new \Exception('请耐心等待勿重复操作');
		}
		else
		{
			$TAsdk->set($keyName, 30, 1);
		}
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		foreach($ids as $id)
		{
			$info = $this->lib->getPageDomainInfoForDel($id, $this->enameId);
			if(! $info)
			{
				$result['failed'][] = array("删除失败,无此记录");
				continue;
			}
			// 先删除本地数据库记录，再调用接口，根据接口返回值，进行后续处理
			if(! $this->lib->delPageDomain($id, $this->enameId))
			{
				$result['failed'][] = array($info['DomainName'],"删除失败,请重试或联系客服！");
				continue;
			}
			if($info['Reg'] == $regConf['inename'][0])
			{
				if($info['Status'] == $statusConf['auditfalse'][0])
				{
					// 审核未通过状态直接删除
					$delFlagStatus = 0;
				}
				elseif($info['Status'] == $statusConf['hold'][0])
				{
					// hold状态直接删除 ，并删除展示页状态
					// $result['failed'][] =
					// array($info['DomainName'],'审核时不能删除');
					// $this->lib->setPageDomainStatus($id, $info['Status'],
					// $this->enameId);
					// continue;
					$this->lib->closePageHoldStatus($this->enameId, $info['DomainName']);
					$delFlagStatus = 0;
				}
				else
				{
					$delFlagStatus = $this->lib->doDelInEname($info, $this->enameId);
					// 如果设置hold失败时
					if($delFlagStatus == $statusConf['hold'][0])
					{
						$result['failed'][] = array($info['DomainName'],"删除失败,设置展示页状态失败");
						$this->lib->setPageDomainStatus($id, $info['Status'], $this->enameId);
						continue;
					}
				}
			}
			else
			{
				$delFlagStatus = $this->lib->doDelNotInEname($info, $this->enameId);
			}
			
			if($delFlagStatus)
			{
				if(! $this->lib->delPageDomain($id, $this->enameId, $delFlagStatus))
				{
					$result['failed'][] = array($info['DomainName'],'删除失败');
					continue;
				}
			}
			$result['success'][] = array($info['DomainName'],'删除展示页成功');
		}
		$result['url'][] = array($ctrl->url->get('custompage/index'),'我的展示页列表');
		if(trim($_GET['a']) == 'del' && count($ids) == 1)
		{
			if(isset($result['success'][0][1]))
			{
				echo '删除成功';
			}
			elseif(isset($result['failed'][0][1]))
			{
				echo $result['failed'][0][1];
			}
			else
			{
				echo '删除失败';
			}
			exit();
		}
		
		$TAsdk->del($keyName); // 返回前确认释放发布交易锁
		return $result;
	}

	/**
	 * 展示页预览
	 */
	public function custompagePreView($id)
	{
		$templateType = \core\Config::item('page_template_style')->toArray();
		$page_stat_type = \core\Config::item('page_stat_type')->toArray();
		
		$info = $this->lib->getPageDomainInfoById($id);
		if(! $info)
		{
			throw new \Exception('无法获取展示页信息');
		}
		if($info['EnameId'] != $this->enameId)
		{
			throw new \Exception('操作错误');
		}
		$temInfo = $this->lib->getOldSystemTemInfo($info['TemplateDId'], $this->enameId);
		if(! $temInfo)
		{
			throw new \Exception('无法获取展示页模板');
		}
		
		$temInfo['domain'] = $info['DomainName'];
		$temInfo['TransInfo'] = $info['TransInfo'];
		$temInfo['errowInfo'] = $info['errowInfo'];
		$temInfo['domaindesc'] = $info['Description'];
		$result = $this->lib->doHtmlParam($temInfo);
		return $result;
	}

	public function setPageDomain($ids)
	{
		$list = array();
		if($ids && is_array($ids))
		{
			foreach($ids as $id)
			{
				$info = $this->lib->getPageDomainInfoForSet($id, $this->enameId);
				if($info)
					$list[$info['CustompageDId']] = $info;
			}
		}
		$templates = $this->lib->getValidDataNewTemplate($this->enameId);
		if(isset($templates['result']))
		{
			if($templates['flag'])
			{
				throw new \Exception('您暂无有效的展示页模板');
			}
			else
			{
				throw new \Exception('原展示页系统模板已不可使用');
			}
		}
		return array('list'=> $list,'templates'=> $templates,'sid'=> \core\Config::item('page_edit_sid'));
	}

	public function doSetPageDomain($ctrl, $domains)
	{
		$result = array('success'=> array(),'failed'=> array(),'url'=> array());
		if(! $domains)
		{
			throw new \Exception('请选择需要修改展示页的域名');
		}
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		$result = array('url'=> array(),'false'=> array(),'success'=> array());
		foreach($domains as $v)
		{
			$domainTag = str_replace('.', '_', $v);
			// 将数字强制转为数值型
			$_POST[$domainTag . '_id'] = intval($_POST[$domainTag . '_id']);
			$_POST[$domainTag . '_templateId'] = intval($_POST[$domainTag . '_templateId']);
			$_POST[$domainTag . '_transInfo'] = isset($_POST[$domainTag . '_transInfo'])? intval(
				$_POST[$domainTag . '_transInfo']): 0;
			$_POST[$domainTag . '_errowInfo'] = isset($_POST[$domainTag . '_errowInfo'])? intval(
				$_POST[$domainTag . '_errowInfo']): 0;
			
			$checkRes = $this->checkSetPageDomainPostData($domainTag, $v);
			if($checkRes === false)
			{
				$result['failed'][] = array($v,'非法操作');
				continue;
			}
			
			if(! $this->lib->checkValidDataTemplate($this->enameId, $_POST[$domainTag . '_templateId']))
			{
				$result['failed'][] = array($v,'非法模板');
				continue;
			}
			
			$transInfoConf = \core\Config::item('page_domain_transinfo')->toArray();
			$errowInfoConf = \core\Config::item('page_domain_errowinfo')->toArray();
			$transInfo = (isset($_POST[$domainTag . '_transInfo']) && $_POST[$domainTag . '_transInfo'])? $transInfoConf['show'][0]: $transInfoConf['hide'][0];
			$errowInfo = (isset($_POST[$domainTag . '_errowInfo']) && $_POST[$domainTag . '_errowInfo'])? $errowInfoConf['show'][0]: $errowInfoConf['hide'][0];
			if(! $this->lib->setPageDomain($_POST[$domainTag . '_id'], $this->enameId, 
				$_POST[$domainTag . '_description'], $_POST[$domainTag . '_templateId'], $transInfo, $errowInfo))
			{
				$result['failed'][] = array($v,'修改失败');
				continue;
			}
			
			// 对相应的展示页状态进行操作
			$info = $this->lib->getPageDomainInfoById($_POST[$domainTag . '_id'], $this->enameId);
			if(! $info)
			{
				$result['failed'][] = array($v,'非法操作');
				continue;
			}
			// 检查域名是否属于用户
			if(in_array($info['Status'], 
				array($statusConf['cname'][0],$statusConf['page'][0],$statusConf['success'][0])))
			{
				if($info['Reg'] == $regConf['inename'][0])
				{
					if(! $this->lib->getDomainForUser($info['DomainName'], $this->enameId))
					{
						$result['failed'][] = array($v,'域名不属于您');
						continue;
					}
				}
			}
			switch($info['Status'])
			{
				case $statusConf['hold'][0]:
					// 防止展示页正在审核中，所以不让hold状态进行重试或删除
					$rs = array("result"=> true,"msg"=> '审核中','pageStatus'=> $statusConf['hold'][0]);
					break;
				case $statusConf['cname'][0]:
					// 重新添加CNAME记录和生成展示页
					$rs = $this->lib->cnameRetry($info['CustompageDId'], $this->enameId, $info['DomainName'], 
						$info['HoldStatus'], $info['Reg']);
					break;
				case $statusConf['page'][0]:
					// 重新生成展示页
					$rs = $this->lib->pageRetry($info['CustompageDId'], $this->enameId, $info['DomainName']);
					break;
				case $statusConf['success'][0]:
					// 重新生成展示页
					$rs = $this->lib->pageRetry($info['CustompageDId'], $this->enameId, $info['DomainName']);
					break;
				default:
					$result['failed'][] = array($v,'非法操作');
					break;
			}
			if(! isset($rs) || ! $rs)
			{
				continue;
			}
			if(! $this->lib->setPageDomainStatus($_POST[$domainTag . '_id'], $rs['pageStatus']))
			{
				Logger::write('custompage_SetPageDomain', 
					array('msgType'=> 350000,'resultFlag'=> 2,'domain'=> $info['DomainName'],
							'note'=> array(__METHOD__,'SetPageDomain',$info['CustompageDId'],$this->enameId,
									$info['DomainName'],$info['HoldStatus'],$info['Status'],$rs['pageStatus'])), 
					'custompage');
			}
			$result['success'][] = array($v,'修改成功');
		}
		$result['url'][] = array($ctrl->url->get('custompage/index'),'我的展示页列表');
		return $result;
	}

	private function checkSetPageDomainPostData($domainTag, $domainName)
	{
		$sid = \core\Config::item('page_edit_sid');
		if(isset($_POST[$domainTag . '_md5']) && $_POST[$domainTag . '_md5'] == md5($domainName . $sid))
		{
			if(isset($_POST[$domainTag . '_id']) && $_POST[$domainTag . '_id'] && is_numeric($_POST[$domainTag . '_id']))
			{
				if(isset($_POST[$domainTag . '_templateId']) && $_POST[$domainTag . '_templateId'] &&
					 is_numeric($_POST[$domainTag . '_templateId']))
				{
					if(isset($_POST[$domainTag . '_description']))
					{
						$_POST[$domainTag . '_description'] = $this->stripTags($_POST[$domainTag . '_description']);
						return true;
					}
				}
			}
		}
		return false;
	}

	public function stripTags($str)
	{
		return htmlspecialchars(strip_tags($str), ENT_QUOTES);
	}

	public function retryPageDomain($id)
	{
		$info = $this->lib->getPageDomainInfoById($id, $this->enameId);
		if(! $info)
		{
			throw new \Exception('无此展示页记录');
		}
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		
		// 检查域名是否属于用户
		if($info['Reg'] == $regConf['inename'][0])
		{
			if(! $this->lib->getDomainForUser($info['DomainName'], $this->enameId))
			{
				throw new \Exception('域名不属于您');
			}
		}
		switch($info['Status'])
		{
			case $statusConf['cname'][0]:
				$result = $this->lib->cnameRetry($info['CustompageDId'], $this->enameId, $info['DomainName'], 
					$info['HoldStatus'], $info['Reg']);
				break;
			case $statusConf['page'][0]:
				$result = $this->lib->pageRetry($info['CustompageDId'], $this->enameId, $info['DomainName']);
				break;
			default:
				throw new \Exception('非法操作');
		}
		if($result['pageStatus'] == $statusConf['success'][0] && $info['Reg'] == 2)
		{
			$this->lib->setPageDomainStatusBNot($statusConf['del'][0], $this->enameId, $info['DomainName']);
		}
		if($this->lib->setPageDomainStatus($id, $result['pageStatus'], $this->enameId))
		{
			if($result['result'])
			{
				return $result['msg'];
			}
		}
		throw new \Exception($result['msg']);
	}

	/**
	 * 獲取系統風格模板列表
	 */
	public function getCustomStyleList()
	{
		$model = new ModelBase('template_style');
		$field = '*';
		$order = ' TemplateId ASC ';
		$data['list'] = $model->getData($field, 
			array('EnameId'=> \core\Config::item('page_systemplate_enameid'),'Status'=> 1), $model::FETCH_ALL, $order);
		return $data;
	}

	/**
	 * 系统模板预览
	 */
	public function pageView($tempid)
	{
		$data = $this->getTemplateInfo($tempid);
		$result['html'] = $this->lib->doSystemHtml($data, $tempid);
		return $result;
	}

	public function addTemplate($type, $templateid)
	{
		$cmodel = new ModelBase('user_contact');
		$smodel = new ModelBase('seo');
		$seo = $smodel->getData('*', array('EnameId'=> $this->enameId));
		$contact = $cmodel->getData('*', array('EnameId'=> $this->enameId));
		return array('type'=> $type,'templateId'=> $templateid,'seos'=> $seo,'contacts'=> $contact,
				'defaultdomain'=> \core\Config::item('defalut_html_domain'),
				'defaultwhois'=> \core\Config::item('defalut_html_whois'),
				'defaulterrow'=> \core\Config::item('defalut_html_errow'),
				'defaultdomaindesc'=> \core\Config::item('defalut_html_domaindesc'),
				'defaulttrans'=> \core\Config::item('defalut_html_trans'),
				'cssdomain'=> \core\Config::item('defalut_css_domain'),
				'csswhois'=> \core\Config::item('defalut_css_whois'),
				'csserrow'=> \core\Config::item('defalut_css_errow'),
				'cssdomaindesc'=> \core\Config::item('defalut_css_domaindesc'),
				'csstrans'=> \core\Config::item('defalut_css_trans'),
				'statType'=> \core\Config::item('page_stat_type')->toArray(),
				'adType'=> \core\Config::item('page_ad_type')->toArray());
	}

	/**
	 * 根据系统模板ID获取展示页信息
	 *
	 * @param unknown_type $tempid
	 * @throws \Exception
	 * @return array
	 */
	private function getTemplateInfo($tempid)
	{
		$model = new ModelBase('template_style');
		$field = '*';
		$data = $model->getData($field, array('TemplateId'=> $tempid), $model::FETCH_ROW);
		if(! $data)
		{
			throw new \Exception('无法获取展示页模板');
		}
		$data = (array)$data;
		if($data)
		{
			$dataInfo['Html'] = $data['Html'];
			$dataInfo['Css'] = $data['Css'];
			$dataInfo['StyleTemplateName'] = $data['TemplateName'];
			return $dataInfo;
		}
	}

	public function doAddTemplate($data, $htmlCode, $cssCode)
	{
		$mod = new ModelBase('template_data');
		$templateName = $data['templateName'];
		$statType = $data['statType'];
		$statId = $data['statId'];
		$adType = $data['adType'];
		$adId = $data['adId'];
		$templateId = $data['templateId'];
		$ucid = $data['ucid'];
		$seoid = $data['seoid'];
		$type = $data['type'];
		$enameType = $data['enameType'];
		$enameCode = $data['enameCode'];
		$pubid = $data['pubid'];
		$slotid = $data['slotid'];
		$adwidth = $data['adwidth'];
		$adheight = $data['adheight'];
		$systmeenamid = \core\Config::item('page_systemplate_enameid');
		Logger::write('custompage_addTemplate', array('addTemplate','start',json_encode($data),__FILE__,__LINE__), 
			'custompage');
		// google广告参数
		if($adType == 1)
		{
			$adId = array('pubid'=> $pubid,'slotid'=> $slotid,'adwidth'=> $adwidth,'adheight'=> $adheight);
			$adId = json_encode($adId);
		}
		if($htmlCode)
		{
			// 过滤相关标签
			$htmlCode = $this->lib->filterHtml($htmlCode);
		}
		// 检查是否有同名模板
		$res = $this->lib->isExistSameTemplate($templateName, $this->enameId);
		if($res)
		{
			throw new \Exception('该同名模板已经存在');
		}
		$tmod = new ModelBase('template_style');
		// 有templateId说明是系统模板
		$styleConf = \core\Config::item('page_template_style')->toArray();
		if($templateId && $type == 1)
		{
			$templateType = $styleConf['system'][0];
			$res = $tmod->getData('*', array('EnameId'=> $systmeenamid,'TemplateId'=> $templateId));
			if(! $res)
			{
				throw new \Exception('无此模板信息');
			}
			$msg = '添加展示页模板成功！';
		}
		else
		{
			$templateType = $styleConf['diy'][0];
			if(! $htmlCode)
			{
				throw new \Exception('模板内容不能为空');
			}
			$templateId = $this->lib->addStyleTemplate($this->enameId, $templateName, $htmlCode, $cssCode);
			if(! $templateId)
			{
				throw new \Exception('系统出错,请联系管理员');
			}
			$msg = '模板添加成功,请等待管理员审核！';
		}
		$dataTempId = $this->lib->addDataTemplate($this->enameId, $templateName, $templateId, $templateType, $ucid, 
			$statType, $statId, $adType, $adId, $seoid, $enameCode, $enameType);
		if($enameType == 2 && $enameCode)
		{
			$agentLogic = new \logic\agent\AgentGuests($this->enameId);
			$agentLogic->updatePosById($enameCode, $dataTempId);
		}
		if(! $dataTempId)
		{
			throw new \Exception('系统出错,请联系管理员');
		}
		
		return $msg;
	}

	public function viewTemplate($data, $htmlCode, $cssCode)
	{
		$mod = new ModelBase('template_data');
		$templateName = $data['templateName'];
		$type = $data['type'];
		$data['StatType'] = $data['statType'];
		$data['StatId'] = $data['statId'];
		$data['AdType'] = $data['adType'];
		$data['AdId'] = $data['adId'];
		$templateId = $data['templateId'];
		$seoid = $data['seoid'];
		$ucid = $data['ucid'];
		$dataid = $data['dataid'];
		$enameType = $data['enameType'];
		$enameCode = $data['enameCode'];
		$pubid = $data['pubid'];
		$slotid = $data['slotid'];
		$adwidth = $data['adwidth'];
		$adheight = $data['adheight'];
		$usdk = new ModelBase('user_contact');
		$ssdk = new ModelBase('seo');
		$ucinfo = $usdk->getData('*', array('UserCId'=> $ucid), $usdk::FETCH_ROW);
		if($ucinfo)
		{
			$ucinfo = (array)$ucinfo;
			$data['Email'] = $ucinfo['Email'];
			$data['QQ'] = $ucinfo['QQ'];
			$data['Phone'] = $ucinfo['Phone'];
			$data['avatarlinkurl'] = $ucinfo['Imgurl'];
			$data['linkname'] = $ucinfo['UserName'];
			$data['linkdesc'] = $ucinfo['Description'];
		}
		else
		{
			$data['Email'] = $data['QQ'] = $data['Phone'] = $data['avatarlinkurl'] = $data['linkname'] = $data['linkdesc'] = '';
		}
		$sinfo = $ssdk->getData('*', array('SEOId'=> $seoid), $ssdk::FETCH_ROW);
		if($sinfo)
		{
			$sinfo = (array)$sinfo;
			$data['Title'] = $sinfo['Title'];
			$data['KeyWords'] = $sinfo['Keywords'];
			$data['Description'] = $sinfo['Description'];
		}
		else
		{
			$data['Title'] = $data['KeyWords'] = $data['Description'] = '';
		}
		// google广告参数
		if($data['AdType'] == 1)
		{
			$adId = array('pubid'=> $pubid,'slotid'=> $slotid,'adwidth'=> $adwidth,'adheight'=> $adheight);
			$data['AdId'] = json_encode($adId);
		}
		if($htmlCode)
		{
			// 过滤相关标签
			$htmlCode = $this->lib->filterHtml($htmlCode);
		}
		$dmod = new ModelBase('template_data');
		$tmod = new ModelBase('template_style');
		// 有templateId说明是系统模板
		$styleConf = \core\Config::item('page_template_style')->toArray();
		if($templateId && $type == 1)
		{
			$templateType = $styleConf['system'][0];
			if($dataid)
			{
				$res = $dmod->getData('*', 
					array('EnameId'=> $this->enameId,'TemplateDId'=> $dataid,'TemplateType'=> $templateType), 
					$dmod::FETCH_ROW);
				if(! $res)
				{
					throw new \Exception('无此模板信息');
				}
			}
			$tinfo = $tmod->getData('*', 
				array('EnameId'=> \core\Config::item('page_systemplate_enameid'),'TemplateId'=> $templateId), 
				$dmod::FETCH_ROW);
			if(! $tinfo)
			{
				throw new \Exception('无此模板信息');
			}
			$htmlCode = $tinfo->Html;
			$cssCode = $tinfo->Css;
		}
		$data['Html'] = $htmlCode;
		$data['Css'] = $cssCode;
		$msg = $this->lib->doUserHtml($data);
		return $msg;
	}

	public function dataview($templateId)
	{
		$data = $this->lib->getOldSystemTemInfo($templateId, $this->enameId);
		if($data && $data['enameAdSolt'])
		{
			$data['enameCode'] = $data['enameAdSolt'];
		}
		if($data && $data['Html'] && $data['TemplateType'] != 1)
		{
			$data['Html'] = stripslashes($data['Html']);
			$data['Css'] = stripslashes($data['Css']);
		}
		$msg = $this->lib->doUserHtml($data);
		return $msg;
	}

	public function setTemplate($templateId)
	{
		$cmodel = new ModelBase('user_contact');
		$smodel = new ModelBase('seo');
		$seo = $smodel->getData('*', array('EnameId'=> $this->enameId));
		$contact = $cmodel->getData('*', array('EnameId'=> $this->enameId));
		$model = new ModelBase('template_data');
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$field = '*';
		$info = $model->getData($field, 
			array('TemplateDId'=> $templateId,'EnameId'=> $this->enameId,
					'Status'=> array(' <',$templateStatusConf['del'][0])), $model::FETCH_ROW);
		if($info)
		{
			$info = (array)$info;
			if($info['AdType'] == 1 && ! empty($info['AdId']))
			{
				$info['AdId'] = json_decode($info['AdId'], true);
			}
			$tempinfo = $this->getTemplateInfo($info['StyleId']);
			return array('templateInfo'=> $tempinfo,'info'=> $info,
					'statType'=> \core\Config::item('page_stat_type')->toArray(),'seos'=> $seo,'contacts'=> $contact,
					'defaultdomain'=> \core\Config::item('defalut_html_domain'),
					'defaultwhois'=> \core\Config::item('defalut_html_whois'),
					'defaulterrow'=> \core\Config::item('defalut_html_errow'),
					'defaultdomaindesc'=> \core\Config::item('defalut_html_domaindesc'),
					'defaulttrans'=> \core\Config::item('defalut_html_trans'),
					'cssdomain'=> \core\Config::item('defalut_css_domain'),
					'csswhois'=> \core\Config::item('defalut_css_whois'),
					'csserrow'=> \core\Config::item('defalut_css_errow'),
					'cssdomaindesc'=> \core\Config::item('defalut_css_domaindesc'),
					'csstrans'=> \core\Config::item('defalut_css_trans'),
					'statType'=> \core\Config::item('page_stat_type')->toArray());
		}
		throw new \Exception('找不到相关模板数据');
	}

	public function doSetTemplate($data, $htmlCode, $cssCode, $type = 1)
	{
		$templateName = $data['templateName'];
		$statType = $data['statType'];
		$statId = $data['statId'];
		$adType = $data['adType'];
		$dataid = $data['dataid'];
		$adId = $data['adId'];
		$templateId = $data['templateId'];
		$ucid = $data['ucid'];
		$seoid = $data['seoid'];
		$enameCode = $data['enameCode'];
		$enameType = $data['enameType'];
		
		// google广告参数
		$pubid = $data['pubid'];
		$slotid = $data['slotid'];
		$adwidth = $data['adwidth'];
		$adheight = $data['adheight'];
		
		if($adType == 1)
		{
			$adId = array('pubid'=> $pubid,'slotid'=> $slotid,'adwidth'=> $adwidth,'adheight'=> $adheight);
			$adId = json_encode($adId);
		}
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$model = new ModelBase('template_data');
		$field = '*';
		$info = $model->getData($field, 
			array('TemplateDId'=> $dataid,'EnameId'=> $this->enameId,
					'Status'=> array(' <',$templateStatusConf['del'][0])), $model::FETCH_ROW);
		if(! $info)
		{
			throw new \Exception('展示页模板不存在');
		}
		$info = (array)$info;
		// 如果非系统风格模板，则一定要填写模板代码
		if(! $htmlCode && $info['TemplateType'] != 1)
		{
			throw new \Exception('模板内容不能为空');
		}
		if($htmlCode)
		{
			// 过滤相关标签
			$htmlCode = $this->lib->filterHtml($htmlCode);
		}
		if(empty($ucid) && $info['TemplateType'] == 1)
		{
			throw new \Exception('联系人名片不能为空');
		}
		// 检查是否有同名模板
		if($this->lib->isExistSameTemplateForSet($templateName, $this->enameId, $dataid))
		{
			throw new \Exception('该同名模板已经存在');
		}
		
		$tempinfo = $this->getTemplateInfo($info['StyleId']);
		// 若该模板下存在展示页，添加临时模板
		$domain = $this->lib->getPageDomainByTemplateId($dataid);
		if($domain)
		{
			$rs = $this->lib->setTemplateWithPage($this->enameId, $dataid, $info['StyleId'], $info['TemplateType'], 
				$templateName, $ucid, $seoid, $statType, $statId, $adType, $adId, $htmlCode, $cssCode, $tempinfo['Html'], 
				$tempinfo['Css'], $info['Status'], $enameCode, $enameType);
			if($rs === false)
				throw new \Exception("更新模板信息失败，请联系客服！");
				// 更新
			if($rs['update'])
			{
				$statusConf = \core\Config::item('page_domain_status')->toArray();
				$regConf = \core\Config::item('page_domain_reg')->toArray();
				$idArr = array();
				foreach($domain as $v)
				{
					$v = (array)$v;
					if(in_array($v['Status'], array($statusConf['success'][0],$statusConf['page'][0])))
					{
						$idArr[] = $v['CustompageDId'];
					}
				}
				if(count($idArr) > 100)
				{
					Redis::getInstance()->rPush('setTemplate_id', array('tId'=> $dataid));
				}
				else
				{
					foreach($domain as $v)
					{
						$v = (array)$v;
						// 展示页状态为成功或者等待生成展示页时，则自动更新展示页
						if(in_array($v['Status'], array($statusConf['success'][0],$statusConf['page'][0])))
						{
							// 我司域名但不属于用户则不更新展示页
							if($v['Reg'] == $regConf['inename'][0])
							{
								if(! $this->lib->getDomainForUser($v['DomainName'], $v['EnameId']))
								{
									
									Logger::write('custompage_NotUserDomain', 
										array('msgType'=> 350000,'resultFlag'=> 2,'domain'=> $v['DomainName'],
												'note'=> array(__METHOD__,'setTemplateWithPage','NotUserDomain',
														$v['Status'],$v['DomainName'],$v['TemplateDId'],$v['EnameId'])), 
										'custompage');
									continue;
								}
							}
							$createRs = $this->lib->createPageDomain($v['DomainName'], $v['TemplateDId'], $v['EnameId'], 
								$v['Description'], $v['TransInfo'], $v['errowInfo']);
							Logger::write('custompage_createPageDomain', 
								array('msgType'=> 350000,'resultFlag'=> $createRs? 1: 2,'domain'=> $v['DomainName'],
										'note'=> array(__METHOD__,'setTemplateWithPage','createPageDomain',$v['Status'],
												$v['DomainName'],$v['TemplateDId'],$v['EnameId'],$v['Description'],
												$v['TransInfo'])), 'custompage');
							// 更新展示页不成功，则设置状态
							if(! $createRs)
							{
								$this->lib->setPageDomainStatus($v['CustompageDId'], $statusConf['page'][0], 
									$v['EnameId']);
							}
							elseif($v['Status'] == $statusConf['page'][0])
							{
								$rst = $this->lib->setPageDomainStatus($v['CustompageDId'], $statusConf['success'][0], 
									$v['EnameId']);
								Logger::write('custompage_createPageDomain', 
									array('resultFlag'=> $rst? 1: 2,'domain'=> $v['DomainName'],
											'note'=> array(__METHOD__,'setTemplateWithPage','setDomainStatusTs',
													$v['Status'],$v['DomainName'],$v['TemplateDId'],$v['EnameId'])), 
									'custompage');
							}
						}
					}
				}
			}
			return $rs['msg'];
		}
		else
		{
			// 修改模板
			$rs = $this->lib->setTemplateWithoutPage($dataid, $info['StyleId'], $info['TemplateType'], $templateName, 
				$ucid, $seoid, $statType, $statId, $adType, $adId, $htmlCode, $cssCode, $tempinfo['Html'], 
				$tempinfo['Css'], $info['Status'], $enameCode, $enameType);
			if($rs === false)
				throw new \Exception("更新模板失败，请联系客服处理！");
			
			return $rs['msg'];
		}
	}

	public static function sentSocketCut($num)
	{
		static $i;
		if($num >= 5000)
		{
			$num = ceil($num / 10);
			$i += 10;
			self::sentSocketCut($num);
		}
		return array($num,$i);
	}

	public function singleSetTemplate($v)
	{
		Logger::write('custompage_NotUserDomain_Socket', array('start',"id:" . $v['CustompageDId']));
		$CDTsdk = new ModelBase('custompage_domain');
		// $v = (array)$CDTsdk->getData('*', array('CustompageDId'=>
		// $cusDomainId), $CDTsdk::FETCH_ROW);
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		// 展示页状态为成功或者等待生成展示页时，则自动更新展示页
		if(in_array($v['Status'], array($statusConf['success'][0],$statusConf['page'][0])))
		{
			// 我司域名但不属于用户则不更新展示页
			if($v['Reg'] == $regConf['inename'][0])
			{
				if(! $this->lib->getDomainForUser($v['DomainName'], $v['EnameId']))
				{
					
					Logger::write('custompage_NotUserDomain_Socket', 
						array('msgType'=> 350000,'resultFlag'=> 2,'domain'=> $v['DomainName'],
								'note'=> array(__METHOD__,'setTemplateWithPage','NotUserDomain',$v['Status'],
										$v['DomainName'],$v['TemplateDId'],$v['EnameId'])), 'custompage');
					return false;
				}
			}
			$createRs = $this->lib->createPageDomain($v['DomainName'], $v['TemplateDId'], $v['EnameId'], 
				$v['Description'], $v['TransInfo'], $v['errowInfo']);
			Logger::write('custompage_createPageDomain_Socket', 
				array('msgType'=> 350000,'resultFlag'=> $createRs? 1: 2,'domain'=> $v['DomainName'],
						'note'=> array(__METHOD__,'setTemplateWithPage','createPageDomain',$v['Status'],
								$v['DomainName'],$v['TemplateDId'],$v['EnameId'],$v['Description'],$v['TransInfo'])), 
				'custompage');
			// 更新展示页不成功，则设置状态
			if(! $createRs)
			{
				$this->lib->setPageDomainStatus($v['CustompageDId'], $statusConf['page'][0], $v['EnameId']);
			}
			elseif($v['Status'] == $statusConf['page'][0])
			{
				$rst = $this->lib->setPageDomainStatus($v['CustompageDId'], $statusConf['success'][0], $v['EnameId']);
				Logger::write('custompage_createPageDomain_Socket', 
					array('resultFlag'=> $rst? 1: 2,'domain'=> $v['DomainName'],
							'note'=> array(__METHOD__,'setTemplateWithPage','setDomainStatusTs',$v['Status'],
									$v['DomainName'],$v['TemplateDId'],$v['EnameId'])), 'custompage');
			}
			Logger::write('custompage_createPageDomain_Socket', 
				array('true','createpagedomain',$v['DomainName'],$v['CustompageDId']), 'custompage');
			return true;
		}
		Logger::write('custompage_createPageDomain_Socket', 
			array('false','not success or page domain',$v['DomainName'],$v['CustompageDId']), 'custompage');
		return false;
	}

	public function autoAgent($enameCode, $templateId)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$model = new ModelBase('template_data');
		$field = '*';
		$info = $model->getData($field, 
			array('TemplateDId'=> $templateId,'EnameId'=> $this->enameId,
					'Status'=> array(' <',$templateStatusConf['del'][0])), $model::FETCH_ROW);
		if(! $info)
		{
			throw new \Exception('展示页模板不存在');
		}
		$info = (array)$info;
		
		$tempinfo = $this->getTemplateInfo($info['StyleId']);
		// 若该模板下存在展示页，添加临时模板
		$domain = $this->lib->getPageDomainByTemplateId($templateId);
		if($domain)
		{
			Logger::write('custompage_setTemplateWithPage', 
				array("TRUE",'setTemplateWithPageforautoagent','system',$this->enameId,$templateId,$info['StyleId'],
						__FILE__,__LINE__), 'custompage');
			
			// 系统模板，未改样式模板，则不需要审核
			if(! $this->lib->setTemplatedataEcode($templateId, $enameCode))
			{
				throw new \Exception("更新模板信息失败，请联系客服！");
			}
			
			// 重新生成海外出售页
			$this->lib->reCreatePageByTemplate($templateId, $this->enameId);
			// 更新
			$statusConf = \core\Config::item('page_domain_status')->toArray();
			$regConf = \core\Config::item('page_domain_reg')->toArray();
			foreach($domain as $v)
			{
				$v = (array)$v;
				// 展示页状态为成功或者等待生成展示页时，则自动更新展示页
				if(in_array($v['Status'], array($statusConf['success'][0],$statusConf['page'][0])))
				{
					// 我司域名但不属于用户则不更新展示页
					if($v['Reg'] == $regConf['inename'][0])
					{
						if(! $this->lib->getDomainForUser($v['DomainName'], $v['EnameId']))
						{
							
							Logger::write('custompage_NotUserDomain', 
								array('msgType'=> 350000,'resultFlag'=> 2,'domain'=> $v['DomainName'],
										'note'=> array(__METHOD__,'setTemplateWithPage','NotUserDomain',$v['Status'],
												$v['DomainName'],$v['TemplateDId'],$v['EnameId'])), 'custompage');
							continue;
						}
					}
					$createRs = $this->lib->createPageDomain($v['DomainName'], $v['TemplateDId'], $v['EnameId'], 
						$v['Description'], $v['TransInfo'], $v['errowInfo']);
					Logger::write('custompage_createPageDomain', 
						array('msgType'=> 350000,'resultFlag'=> $createRs? 1: 2,'domain'=> $v['DomainName'],
								'note'=> array(__METHOD__,'setTemplateWithPage','createPageDomain',$v['Status'],
										$v['DomainName'],$v['TemplateDId'],$v['EnameId'],$v['Description'],
										$v['TransInfo'])), 'custompage');
					// 更新展示页不成功，则设置状态
					if(! $createRs)
						$this->lib->setPageDomainStatus($v['TemplateDId'], $statusConf['page'][0], $v['EnameId']);
				}
			}
			return array('flag'=> 'true','msg'=> '添加推广成功');
		}
		else
		{
			// 修改模板
			$rs = $this->lib->setTemplatedataEcode($templateId, $enameCode);
			if($rs === false)
			{
				throw new \Exception("更新模板失败，请联系客服处理！");
			}
			return array('flag'=> 'true','msg'=> '修改模板成功');
		}
	}

	public function delTemplate($templateId)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$model = new ModelBase('template_data');
		$field = '*';
		$info = $model->getData($field, 
			array('TemplateDId'=> $templateId,'EnameId'=> $this->enameId,
					'Status'=> array(' <',$templateStatusConf['del'][0])), $model::FETCH_ROW);
		if(! $info)
		{
			throw new \Exception("获取模板信息失败！");
		}
		$info = (array)$info;
		// 查看模板下是否有展示页
		if($this->lib->getPageDomainCountByTemplateId($templateId))
		{
			throw new \Exception("已经存在展示页域名使用了该模板,不能删除！");
		}
		if(! $this->lib->delTemplate($templateId, $info['StyleId'], $info['TemplateType']))
		{
			throw new \Exception("删除失败,请重试！");
		}
		
		return array('flag'=> true,'error'=> '模板删除成功');
	}

	/**
	 * 获取用户系统风格模板
	 */
	public function getSystemTemplateList()
	{
		$data = array();
		
		$model = new ModelBase('template_data');
		$list = $model->getData('StyleId', array('EnameId'=> $this->enameId,'Status'=> 1,'TemplateType'=> 1), 
			$model::FETCH_ALL, false, false, ' StyleId');
		if($list)
		{
			$list = (array)$list;
			foreach($list as $key => $val)
			{
				$val = (array)$val;
				$data[$key]['StyleId'] = $val['StyleId'];
			}
			foreach($data as $k => $v)
			{
				$smodel = new ModelBase('template_style');
				$field = '*';
				$styledata = $smodel->getData('TemplateName', array('TemplateId'=> $v['StyleId']), $smodel::FETCH_ROW);
				$data[$k]['tempname'] = $styledata->TemplateName;
			}
		}
		return $data;
	}

	/**
	 * 获取用户系统风格模板
	 */
	public function getSystemTemplateListBysid($sid)
	{
		$data = array();
		$data['style'] = $this->getSystemTemplateList();
		if($sid)
		{
			$model = new ModelBase('template_data');
			$list = $model->getData('TemplateDId,TemplateName', 
				array('EnameId'=> $this->enameId,'Status'=> 1,'TemplateType'=> 1,'StyleId'=> $sid), $model::FETCH_ALL);
			if($list)
			{
				$list = (array)$list;
				foreach($list as $key => $val)
				{
					$val = (array)$val;
					$data['data'][$key] = $val;
				}
			}
		}
		return array('flag'=> true,'data'=> $data);
	}

	/**
	 * 用户自定义数据模板列表
	 */
	public function getTemplateList($templateName, $templateType, $status, $perPage)
	{
		if($status && ($status == 3 || $status == 5))
		{
			throw new \Exception('审核状态有误');
		}
		$perNum = \core\Config::item('cpagesize');
		$perPage = $perPage? : 0;
		$limit = $perPage . ', ' . $perNum;
		$template_status = \core\Config::item('page_template_status')->toArray();
		$template_style = \core\Config::item('page_template_style')->toArray();
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$list = array();
		$where = array('EnameId'=> $this->enameId);
		if($templateName)
		{
			$where['TemplateName'] = '%' . $templateName . '%';
		}
		if($templateType)
		{
			$where['TemplateType'] = $templateType;
		}
		if($status)
		{
			$where['Status'] = $status;
		}
		else
		{
			$where['Status'] = array('NOT IN',array('3','5'));
		}
		$model = new ModelBase('template_data');
		$field = '*';
		$totalCnt = $model->count($where, 'TemplateDId');
		$page = new Page($totalCnt, \core\Config::item('cpagesize'));
		$pages = $page->show();
		$list = $model->getData($field, $where, $model::FETCH_ALL, 'TemplateDId desc', $limit);
		if($list)
		{
			$list = (array)$list;
			$dmodel = new ModelBase('custompage_domain');
			foreach($list as $k => $v)
			{
				$v = (array)$v;
				$list[$k] = $v;
				$list[$k]['DomainCount'] = $dmodel->count(
					array('TemplateDId'=> $v['TemplateDId'],'Status'=> array('<',$statusConf['del'][0]),
							'EnameId'=> $this->enameId), 'CustompageDId');
			}
		}
		$templateStatusConf = $this->KeyToValue($template_status, array('edit','del'));
		$templateTypeConf = $this->KeyToValue($template_style);
		return array('list'=> $list,'page'=> $pages,
				'getItems'=> array('templateName'=> $templateName,'status'=> $status,'templateType'=> $templateType),
				'templateTypeConf'=> $templateTypeConf,'templateStatusConf'=> $templateStatusConf,
				'templatestatus'=> $template_status);
	}

	/**
	 * KeyToValue
	 * 把2维配置文件数组转成想要的1维数组
	 */
	public function KeyToValue($config, $skipkeys = array())
	{
		assert(is_array($config));
		$temp = array();
		foreach($config as $k => $v)
		{
			if(in_array($k, $skipkeys))
				continue;
			$temp[$v[0]] = $v[1];
		}
		return $temp;
	}

	/**
	 *
	 * @throws Exception
	 * @return
	 *
	 *
	 *
	 *
	 */
	public function addPageDomain($domainNames)
	{
		if(! $domainNames)
		{
			throw new \Exception('请选择需要设置展示页的域名');
		}
		$maxNum = \core\Config::item('page_domains_maxnum');
		$messageConf = \core\Config::item('page_domain_message')->toArray();
		$doDomains = array('url'=> array(),'false'=> array(),'success'=> array());
		// 检测是否存在有效模板
		$templates = $this->lib->getValidDataNewTemplate($this->enameId);
		if(isset($templates['result']))
		{
			throw new \Exception('您暂无有效的展示页模板');
		}
		list($domainNames, $decs) = $this->lib->formatDomain($domainNames, ',');
		if(count($domainNames) <= $maxNum)
		{
			foreach($domainNames as $k => $domainName)
			{
				
				// 判断域名10分钟内是否被删除过.
				if($this->lib->isDelInTenMin($domainName, $this->enameId))
				{
					Logger::write('custompage_addpagedomain', 
						array('msgType'=> 350000,'domain'=> $domainName,'resultFlag'=> 2,
								'note'=> array('IsDelInTenMin',$domainName,$this->enameId,__METHOD__)), 'custompage');
					$doDomains['false']['delInTenMin'][] = array($domainName,$messageConf['delInTenMin']);
					continue;
				}
				// 判断是否已经添加过展示页，且属于该用户
				$info = $this->lib->checkPageDomain($domainName, $this->enameId);
				if($info)
				{
					Logger::write('custompage_addpagedomain', 
						array('msgType'=> 350000,'domain'=> $domainName,'resultFlag'=> 2,
								'note'=> array('alreadyInPageDomain',$domainName,$this->enameId,__METHOD__)), 
						'custompage');
					$doDomains['false']['alreadyInPageDomain'][] = array($domainName,
							$messageConf['alreadyInPageDomain']);
					continue;
				}
				// 检查是否在我司且属于用户
				if($this->lib->getDomainForUser($domainName) &&
					 ! $this->lib->getDomainForUser($domainName, $this->enameId))
				{
					$doDomains['false']['notUserDomain'][] = array($domainName,$messageConf['notUserDomain']);
					continue;
				}
				$doDomains['success'][] = $domainName;
			}
		}
		return array('domains'=> $doDomains,'decs'=> $decs,'templates'=> $templates,
				'sid'=> \core\Config::item('page_add_sid'),'messageConf'=> $messageConf);
	}

	/**
	 * 添加域名展示页
	 */
	public function doAddPageDomain($ctrl, $domainNames)
	{
		Logger::write('custompage_addpagedomain', 
			array('DOMAIN','AddPageDomain',"================Start==================",__METHOD__), 'custompage');
		
		if(! $domainNames)
		{
			throw new \Exception('请选择需要修改展示页的域名');
		}
		$messageConf = \core\Config::item('page_domain_message')->toArray();
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		$holdStatusConf = \core\Config::item('page_domain_holdstatus')->toArray();
		$result = array('url'=> array(),'false'=> array(),'success'=> array());
		$notInEname = $inEname = array();
		foreach($domainNames as $domainName)
		{
			$domainTag = str_replace('.', '_', $domainName);
			// 将数字强制转为数值型
			$_POST[$domainTag . '_templateId'] = intval($_POST[$domainTag . '_templateId']);
			$_POST[$domainTag . '_transInfo'] = isset($_POST[$domainTag . '_transInfo'])? intval(
				$_POST[$domainTag . '_transInfo']): 0;
			$_POST[$domainTag . '_errowInfo'] = isset($_POST[$domainTag . '_errowInfo'])? intval(
				$_POST[$domainTag . '_errowInfo']): 0;
			// 检查POST数据
			if(! $this->checkAddPageDomainPostData($domainTag, $domainName))
			{
				$result['false']['unValidPostData'][] = $domainName;
				continue;
			}
			// 检查模板是否有效
			if(! $this->lib->checkValidDataTemplate($this->enameId, $_POST[$domainTag . '_templateId']))
			{
				$result['false']['unValidTemplate'][] = $domainName;
				continue;
			}
			// 判断域名10分钟内是否被删除过.
			if($this->lib->isDelInTenMin($domainName, $this->enameId))
			{
				Logger::write('custompage_IsDelInTenMin', 
					array('msgType'=> 350000,'domain'=> $domainName,'resultFlag'=> 2,
							'note'=> array('IsDelInTenMin',$domainName,$this->EnameId,__METHOD__)), 'custompage');
				
				$result['false']['delInTenMin'][] = $domainName;
				continue;
			}
			// 判断是否已经添加过展示页
			$info = $this->lib->checkPageDomain($domainName, $this->enameId);
			if($info)
			{
				// 域名存在，且属于该用户
				Logger::write('custompage_alreadyInPageDomain', 
					array('msgType'=> 350000,'domain'=> $domainName,'resultFlag'=> 2,
							'note'=> array('alreadyInPageDomain',$domainName,$this->enameId,__METHOD__)), 'custompage');
				$result['false']['alreadyInPageDomain'][] = $domainName;
				continue;
			}
			// 域名我司和非我司分类
			if($this->lib->getDomainForUser($domainName))
			{
				// 检查是否属于用户
				if(! $this->lib->getDomainForUser($domainName, $this->enameId))
				{
					$result['false']['notUserDomain'][] = $domainName;
					continue;
				}
				$inEname[] = $domainName;
			}
			else
				$notInEname[] = $domainName;
		}
		// 我司处理
		$result1 = $inEname? $this->lib->doInEname($inEname, $this->enameId): array();
		if($result1 && $result1 == 'error')
		{
			throw new \Exception('设置展示页状态失败，请等待系统处理，如24小时后状态依然是等待审核请联系客服处理');
		}
		// 非我司处理
		$result2 = $notInEname? $this->lib->doNotInEname($notInEname, $this->enameId): array();
		
		$result = array_merge_recursive($result, $result1, $result2);
		$result['url'] = array(array($ctrl->url->get('custompage/index'),'我的展示页列表'),
				array($ctrl->url->get('custompage/addshowpage'),'继续添加'));
		$result['messageConfig'] = $messageConf;
		return $result;
	}

	private function checkAddPageDomainPostData($domainTag, $domainName)
	{
		$sid = \core\Config::item('page_add_sid');
		if(isset($_POST[$domainTag . '_md5']) && $_POST[$domainTag . '_md5'] == md5($domainName . $sid))
		{
			if(isset($_POST[$domainTag . '_templateId']) && $_POST[$domainTag . '_templateId'])
			{
				if(isset($_POST[$domainTag . '_description']))
				{
					$_POST[$domainTag . '_description'] = $this->stripTags($_POST[$domainTag . '_description']);
					return true;
				}
			}
		}
		return false;
	}

	public function getContactInfo($cid)
	{
		$contact = array('result'=> 'error');
		$cmodel = new ModelBase('user_contact');
		$contacts = $cmodel->getData('*', array('EnameId'=> $this->enameId,'UserCId'=> $cid));
		if($contacts)
		{
			$contact = $contacts[0];
			return array('flag'=> true,'username'=> $contact->UserName,'cardname'=> $contact->CardName,
					'qq'=> $contact->QQ,'email'=> $contact->Email,'description'=> $contact->Description,
					'phone'=> $contact->Phone,'imgurl'=> $contact->Imgurl);
		}
		else
		{
			return array('flag'=> false,'error'=> '获取数据有误请重试');
		}
		return $contact;
	}

	/**
	 * 添加模板联系人名片
	 *
	 * @param unknown_type $username
	 * @param unknown_type $email
	 * @param unknown_type $qq
	 * @param unknown_type $desc
	 * @param unknown_type $avatar
	 */
	public function addContact($username, $email, $qq, $desc, $avatar, $phone, $cardname)
	{
		$mod = new ModelBase('user_contact');
		$count = $mod->count(array('EnameId'=> $this->enameId), 'UserCId');
		$limitnum = \core\Config::item('card_limit_num');
		if($count >= $limitnum)
		{
			return array('flag'=> false,'error'=> '名片数量超过10个不可以在添加');
		}
		$rename = $mod->getData('CardName', array('EnameId'=> $this->enameId,'CardName'=> $cardname));
		if($rename)
		{
			return array('flag'=> false,'error'=> '名片名称不能重复，请重新修改');
		}
		$insert = array('EnameId'=> $this->enameId,'Email'=> $email,'QQ'=> $qq,'CreateTime'=> time(),'Phone'=> $phone,
				'CardName'=> $cardname,'Imgurl'=> $avatar,'UserName'=> $username,'Description'=> $desc);
		$rds = $mod->insert($insert);
		if($rds)
		{
			return array('flag'=> true,'error'=> '保存成功','lastid'=> $rds);
		}
		else
		{
			return array('flag'=> false,'error'=> '保存失败');
		}
	}

	/**
	 * 修改模板联系人名片
	 *
	 * @param unknown_type $username
	 * @param unknown_type $email
	 * @param unknown_type $qq
	 * @param unknown_type $desc
	 * @param unknown_type $avatar
	 */
	public function editContact($cid, $username, $email, $qq, $desc, $avatar, $phone, $cardname)
	{
		$mod = new ModelBase('user_contact');
		$rename = $mod->getData('CardName', 
			array('EnameId'=> $this->enameId,'CardName'=> $cardname,'UserCId' > '<> ' . $cid));
		if($rename)
		{
			return array('flag'=> false,'error'=> '名片名称不能重复，请重新修改');
		}
		$insert = array('EnameId'=> $this->enameId,'Email'=> $email,'QQ'=> $qq,'Phone'=> $phone,'CardName'=> $cardname,
				'Imgurl'=> $avatar,'UserName'=> $username,'Description'=> $desc);
		$rds = $mod->update($insert, array('UserCId'=> $cid));
		if($rds)
		{
			$this->setTemplateByeditInfo($cid, 1);
			return array('flag'=> true,'error'=> '保存成功','lastid'=> $rds);
		}
		else
		{
			return array('flag'=> false,'error'=> '未做修改');
		}
	}

	public function setTemplateByeditInfo($id, $type)
	{
		$model = new ModelBase('template_data');
		switch($type)
		{
			case 1:
				$tId = $model->getData('TemplateDId', array('Ucid'=> $id));
				if($tId)
				{
					foreach($tId as $v)
					{
						Redis::getInstance()->rPush('setTemplate_id', array('tId'=> $v->TemplateDId));
					}
				}
				break;
			case 2:
				$tId = $model->getData('TemplateDId', array('Seoid'=> $id));
				if($tId)
				{
					foreach($tId as $v)
					{
						Redis::getInstance()->rPush('setTemplate_id', array('tId'=> $v->TemplateDId));
					}
				}
				break;
			default:
				break;
		}
	}

	public function delContact($cid)
	{
		$mod = new ModelBase('user_contact');
		$csdsdk = new ModelBase('template_data');
		$cout = $csdsdk->count(array('Ucid'=> $cid,'Status'=> 1), 'TemplateDId');
		if($cout)
		{
			return array('flag'=> false,'error'=> '有模板使用该数据，不能删除');
		}
		$res = $mod->delete(array('UserCId'=> $cid,'EnameId'=> $this->enameId));
		if($res)
		{
			return array('flag'=> true,'error'=> '数据删除成功');
		}
		return array('flag'=> false,'error'=> '删除失败');
	}

	public function upload($files)
	{
		$uplodlib = new UploadFile($this->enameId);
		$results = array('flag'=> false,'error'=> '上传文件有误，请重新上传！');
		if(count($files) > 0)
		{
			$results = $uplodlib->UploadFileImg($files[0]);
		}
		return $results;
	}

	public function getSeoInfo($cid)
	{
		$cmodel = new ModelBase('seo');
		$contacts = $cmodel->getData('*', array('EnameId'=> $this->enameId,'SEOId'=> $cid));
		if($contacts)
		{
			$contact = $contacts[0];
			return array('flag'=> true,'Title'=> $contact->Title,'CardName'=> $contact->CardName,
					'Keywords'=> $contact->Keywords,'description'=> $contact->Description);
		}
		else
		{
			return array('flag'=> false,'error'=> '获取数据有误请重试');
		}
	}

	public function addSeo($seoname, $kword, $title, $desc)
	{
		$mod = new ModelBase('seo');
		$count = $mod->count(array('EnameId'=> $this->enameId), 'SEOId');
		$limitnum = \core\Config::item('card_limit_num');
		if($count >= $limitnum)
		{
			return array('flag'=> false,'error'=> '名片数量超过10个不可以在添加');
		}
		$rename = $mod->getData('CardName', array('EnameId'=> $this->enameId,'CardName'=> $seoname));
		if($rename)
		{
			return array('flag'=> false,'error'=> '名片名称不能重复，请重新修改');
		}
		$insert = array('CreateTime'=> time(),'EnameId'=> $this->enameId,'Title'=> $title,'CardName'=> $seoname,
				'Keywords'=> $kword,'Description'=> $desc);
		$rds = $mod->insert($insert);
		if($rds)
		{
			return array('flag'=> true,'error'=> '保存成功','lastid'=> $rds);
		}
		else
		{
			return array('flag'=> false,'error'=> '保存失败');
		}
	}

	public function editSeo($sid, $seoname, $kword, $title, $desc)
	{
		$mod = new ModelBase('seo');
		$rename = $mod->getData('CardName', 
			array('EnameId'=> $this->enameId,'CardName'=> $seoname,'SEOId' > '<> ' . $sid));
		if($rename)
		{
			return array('flag'=> false,'error'=> '名片名称不能重复，请重新修改');
		}
		$insert = array('EnameId'=> $this->enameId,'Title'=> $title,'CardName'=> $seoname,'Keywords'=> $kword,
				'Description'=> $desc);
		$rds = $mod->update($insert, array('SEOId'=> $sid));
		if($rds)
		{
			$this->setTemplateByeditInfo($sid, 2);
			return array('flag'=> true,'error'=> '保存成功');
		}
		else
		{
			return array('flag'=> false,'error'=> '未做修改');
		}
	}

	public function delSeo($cid)
	{
		$mod = new ModelBase('seo');
		$csdsdk = new ModelBase('template_data');
		$cout = $csdsdk->count(array('Seoid'=> $cid,'Status'=> 1), 'TemplateDId');
		if($cout)
		{
			return array('flag'=> false,'error'=> '有模板使用该数据，不能删除');
		}
		$res = $mod->delete(array('SEOId'=> $cid,'EnameId'=> $this->enameId));
		if($res)
		{
			return array('flag'=> true,'error'=> '数据删除成功');
		}
		return array('flag'=> false,'error'=> '删除失败');
	}

	public function countUnAudit()
	{
		$mod = new ModelBase('template_data');
		return $mod->count(array('Status'=> 2), 'TemplateDId');
	}

	public function countAuditById($auditTime)
	{
		$mod = new ModelBase('check_template');
		$time = strtotime($auditTime)? strtotime($auditTime): strtotime(date('Y-m-d', strtotime('-1 days')));
		return $mod->getData('COUNT(CheckTId) AS Num,Auditor', 
			array('AuditTime'=> array('between',array($time,$time + 86399))), $mod::FETCH_ALL, false, false, 'Auditor');
	}

	/**
	 * 根据指定日期获取所在月的起始时间和结束时间
	 */
	public static function getMonthinfoByDate($date)
	{
		$ret = array();
		$timestamp = strtotime($date);
		$mdays = date('t', $timestamp);
		return array(strtotime(date('Y-m-1', $timestamp)),strtotime(date('Y-m-' . $mdays, $timestamp)) + 86399);
	}
}
