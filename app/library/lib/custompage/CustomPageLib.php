<?php
namespace lib\custompage;
use table\TemplateStyleTable;
use logic\common;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use core\Logger;
use common\domain\Domain;
use core\EnameApi;
use core\driver\Redis;
use core\ModelBase;
use core\CustomPageIDNALib;

class CustomPageLib
{

	/**
	 * 系统模板预览
	 */
	public function doSystemHtml($data, $id = 0)
	{
		$html = $this->composeHtmlCss($data['Html'], $data['Css']);
		$brokertel = \core\Config::item('brokertel');
		$whoisurl = \core\Config::item('whois_link_url');
		$data['domain'] = '展示页.CN';
		$data['css'] = '';
		$data['QQ'] = "400-0044-400";
		$data['Phone'] = "400-0044-400";
		$data['Email'] = "1001@ename.com";
		$data['Description'] = '';
		$data['transtype'] = '竞价';
		$data['transurl'] = '#';
		$data['price'] = 10000;
		$data['whoisurl'] = $whoisurl;
		$data['errows'] = '';
		$data['lefttime'] = '3天6时30分20秒';
		$data['codeStat'] = '';
		$data['codeAd'] = "<img src='/upload/images/common/zcool-recommend.png'>";
		$data['Title'] = "展示页模板预览  " . $data['StyleTemplateName'];
		$data['KeyWords'] = '';
		$data['domaindesc'] = "域名联盟（ename.com.cn）带您畅游域名的世界";
		$data['linkdesc'] = "易小二为您提供真诚、可靠的域名服务。";
		$data['avatarlinkurl'] = "<img src='/upload/images/common/common-avatar.png'>";
		$data['linkname'] = '王小二';
		$data['trans'] = '';
		$styleData = \core\Config::item('ad_zhanshiye_style')->toArray();
		$adHtml = $styleData[$id];
		$data['enamecode'] = $adHtml['html']['head'];
		for($i = 1; $i <= 3; $i++)
		{
			if($i == 1)
			{
				$data['enamecode'] .= str_replace(
					array('{Url}','{Name}','{SimpleDec}','{Price}','{FinishTime}','{First}'), 
					array('javascript:void(0);','ename.com' . $i,'企鹅企鹅全文请','19000','6时15分',' domain-item-first'), 
					$adHtml['html']['content']);
			}
			else
			{
				$data['enamecode'] .= str_replace(
					array('{Url}','{Name}','{SimpleDec}','{Price}','{FinishTime}','{First}'), 
					array('javascript:void(0);','ename.com' . $i,'企鹅企鹅全文请','19000','6时15分',''), 
					$adHtml['html']['content']);
			}
		}
		$data['enamecode'] .= $adHtml['html']['end'];
		// 将html中的变量替换
		$data['enamedis'] = '';
		$data['qqdis'] = $data['teldis'] = $data['emaildis'] = $data['descdis'] = '';
		$html = $this->replaceParams($html, $data);
		return $html;
	}

	/**
	 * yonhu模板预览
	 */
	public function doUserHtml($data)
	{
		$statCode = \core\Config::item('page_stat_type')->toArray();
		$adCodeType = \core\Config::item('page_ad_type')->toArray();
		$adCode = \core\Config::item('page_ad')->toArray();
		// 统计代码
		$param['codeStat'] = $this->getStatCode($data['StatType'], $data['StatId'], $statCode);
		
		// 广告代码
		if($data['AdType'] == 2)
		{
			$param['codeAd'] = sprintf($adCode[$data['AdType']][0] . $adCodeType[$data['AdType']][0], $data['AdId']) .
				 $adCodeType[$data['AdType']][0];
		}
		elseif($data['AdType'] == 1 && ! empty($data['AdId']))
		{
			$adId = json_decode($data['AdId'], true);
			$param['codeAd'] = sprintf($adCode[$data['AdType']][0], $adId['pubid'], $adId['slotid'], $adId['adwidth'], 
				$adId['adheight']) . $adCodeType[$data['AdType']][0];
		}
		else
		{
			$param['codeAd'] = '';
		}
		$param['enamedis'] = '';
		$baseurl = 'http://' . $_SERVER['HTTP_HOST'];
		if(! empty($data['enameType']) && ! empty($data['enameCode']))
		{
			if($data['enameType'] == 2)
			{
				$param['enamecode'] = '<script>var adInfo = {ename_ad_solt:' . $data['enameCode'] .
					 '}</script><script type="text/javascript" src="' . $baseurl . '/js/show_o.js"></script>';
			}
			else
			{
				$param['enamecode'] = $data['enameCode'];
			}
		}
		else
		{
			$param['enamecode'] = '';
			$param['enamedis'] = 'style="display:none"';
		}
		$html = $this->composeHtmlCss($data['Html'], $data['Css']);
		$brokertel = \core\Config::item('brokertel');
		$whoisurl = \core\Config::item('whois_link_url');
		$param['QQ'] = $data['QQ'];
		$param['Phone'] = $data['Phone'];
		$param['Email'] = $data['Email'];
		$param['Title'] = $data['Title'];
		$param['Description'] = $data['Description'];
		$param['KeyWords'] = $data['KeyWords'];
		$param['domain'] = '展示页.CN';
		$param['css'] = '';
		$param['transtype'] = '竞价';
		$param['transurl'] = '#';
		$param['price'] = 10000;
		$param['whoisurl'] = $whoisurl;
		$param['lefttime'] = '3天6时30分20秒';
		$param['domaindesc'] = '域名联盟（ename.com.cn）带您畅游域名的世界';
		$param['linkdesc'] = $data['linkdesc'];
		$param['avatarlinkurl'] = $data['avatarlinkurl'] == ''? "<img src='/upload/images/common/common-avatar.png'>": "<img src='/avatar/" .
			 $data['avatarlinkurl'] . "'>";
		$param['linkname'] = $data['linkname'];
		$param['trans'] = '';
		$param['errows'] = '';
		$param['teldis'] = $param['Phone']? '': 'style="display:none"';
		$param['emaildis'] = $param['Email']? '': 'style="display:none"';
		$param['qqdis'] = $param['QQ']? '': 'style="display:none"';
		$param['descdis'] = $param['linkdesc']? '': 'style="display:none"';
		$html = $this->replaceParams($html, $param);
		return $html;
	}

	/**
	 * 将css加载到html中
	 */
	public function composeHtmlCss($html, $css)
	{
		$html = htmlspecialchars_decode($this->composeHtml($html));
		
		// 过滤<link>标签
		$html = preg_replace("/<(\/?link.*?)>/si", "", $html);
		
		$pos = strpos($html, '</head>');
		$part1 = substr($html, 0, $pos);
		$part2 = substr($html, $pos);
		$html = $part1 . '<style>' . $css . '</style>' . $part2;
		return $html;
	}

	public function composeHtml($html)
	{
		$code = \core\Config::item('page_template_code')->toArray();
		return $code['header'] . $html . $code['footer'];
	}

	/**
	 * 替换html中的变量
	 */
	public function replaceParams($html, $data)
	{
		// 替换html中的参数
		$param = array('domain'=> $data['domain'],'css'=> $data['css'],'qq'=> $data['QQ'],'tel'=> $data['Phone'],
				'email'=> $data['Email'],'information'=> $data['Description'],'transtype'=> $data['transtype'],
				'errows'=> $data['errows'],'enamedis'=> $data['enamedis'],'transurl'=> $data['transurl'],
				'price'=> $data['price'],'lefttime'=> $data['lefttime'],'whoisurl'=> $data['whoisurl'],
				'codestat'=> $data['codeStat'],'codead'=> $data['codeAd'],'title'=> $data['Title'],
				'linkname'=> $data['linkname'],'enamecode'=> $data['enamecode'],'keywords'=> $data['KeyWords'],
				'domaindesc'=> $data['domaindesc'],'linkdesc'=> $data['linkdesc'],
				'avatarlinkurl'=> $data['avatarlinkurl'],'trans'=> $data['trans'],'teldis'=> $data['teldis'],
				'emaildis'=> $data['emaildis'],'descdis'=> $data['descdis'],'qqdis'=> $data['qqdis']);
		$i = 1;
		while(preg_match('{%(\w+)%}', $html, $regs))
		{
			if(100 == $i)
			{
				break;
			}
			$found = $regs[1];
			if(! isset($param[$found]))
				$param[$found] = '';
			$html = preg_replace("/\{%" . $found . "%\}/", $param[$found], $html);
			$i++;
		}
		return $html;
	}

	/**
	 * 列表中可以取出编辑状态的展示页
	 */
	public function getValidDataTemplateForList($EnameId)
	{
		$CDTsdk = new ModelBase('template_data');
		$statusConf = \core\Config::item('page_template_status')->toArray();
		$list = $CDTsdk->getData('*', 
			array('EnameId'=> $EnameId,'Status'=> array($statusConf['normal'][0],$statusConf['edit'][0])));
		if($list && is_array($list))
		{
			foreach($list as $v)
			{
				$v = (array)$v;
				$newList[$v['TemplateDId']] = $v['TemplateName'];
			}
			return $newList;
		}
		return false;
	}

	/**
	 * 添加或修改出售页不可以取老系统模板
	 */
	public function getValidDataNewTemplate($EnameId)
	{
		$CDTsdk = new ModelBase('template_data');
		$statusConf = \core\Config::item('page_template_status')->toArray();
		$templateStyleConf = \core\Config::item('page_template_style')->toArray();
		$status = $statusConf['normal'][0];
		$list = $CDTsdk->getData(' TemplateDId, TemplateName, TemplateType', 
			array('EnameId'=> $EnameId,'Status'=> $status));
		if($list && is_array($list))
		{
			$newList = array();
			foreach($list as $v)
			{
				$v = (array)$v;
				$newList[$v['TemplateDId']] = $v['TemplateName'];
			}
			return $newList;
		}
		else
		{
			return array('result'=> false);
		}
	}

	public function checkValidDataTemplate($EnameId, $templateId)
	{
		$CDTsdk = new ModelBase('template_data');
		$statusConf = \core\Config::item('page_template_status')->toArray();
		$res = $CDTsdk->count(
			array('EnameId'=> $EnameId,'TemplateDId'=> $templateId,'Status'=> $statusConf['normal'][0]), 'TemplateDId');
		return $res;
	}

	public function isDelInTenMin($domain, $EnameId)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$delDateLine = time() - 600;
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		if($CDsdk->count(
			array('EnameId'=> $EnameId,'DomainName'=> $domain,'DeleteTime'=> array('>',$delDateLine),
					'Status'=> $statusConf['del'][0]), 'CustompageDId'))
		{
			return true;
		}
		return false;
	}

	public function checkPageDomain($domainName, $EnameId)
	{
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$CDsdk = new ModelBase('custompage_domain');
		return $CDsdk->getData('CustompageDId, EnameId, DomainName', 
			array('DomainName'=> $domainName,'EnameId'=> $EnameId,'Status'=> array('<',$statusConf['del'][0])));
	}

	/**
	 * 过滤HTML
	 */
	public function filterHtml($html)
	{
		$html = preg_replace("/<(\!.*?)>/si", "", $html); // 过滤DOCTYPE
		$html = preg_replace("/&lt;(\/?html.*?)&gt;/si", "", $html); // 过滤html标签
		$html = preg_replace("/&lt;(\/?head.*?)&gt;/si", "", $html); // 过滤head标签
		$html = preg_replace("/&lt;(\/?link.*?)&gt;/si", "", $html); // 过滤link标签
		$html = preg_replace("/&lt;(\/?meta.*?)&gt;/si", "", $html); // 过滤meta标签
		$html = preg_replace("/&lt;(\/?body.*?)&gt;/si", "", $html); // 过滤body标签
		$html = preg_replace("/&lt;(title.*?)&gt;(.*?)&lt;(\/title.*?)&gt;/si", "", $html); // 过滤title标签
		$html = preg_replace("/&lt;(\/?title.*?)&gt;/si", "", $html); // 过滤title标签
		$html = preg_replace("/&lt;(noframes.*?)&gt;(.*?)&lt;(\/noframes.*?)&gt;/si", "", $html); // 过滤noframes标签
		$html = preg_replace("/&lt;(\/?noframes.*?)&gt;/si", "", $html); // 过滤noframes标签
		$html = preg_replace("/&lt;(i?frame.*?)&gt;(.*?)&lt;(\/i?frame.*?)&gt;/si", "", $html); // 过滤frame标签
		$html = preg_replace("/&lt;(\/?i?frame.*?)&gt;/si", "", $html); // 过滤frame标签
		$html = htmlspecialchars_decode($html);
		return trim($html);
	}

	/**
	 * 格式话域名，将[域名,简介]格式转换
	 */
	public function formatDomain($domains, $flag)
	{
		$domainArr = $decArr = array();
		$domains = str_replace("，", ",", $domains);
		$arr = explode("\n", $domains);
		foreach($arr as $k => $v)
		{
			if($v)
			{
				$pos = strpos($v, $flag);
				if($pos)
				{
					$domainname = trim(substr($v, 0, $pos));
					$simpledec = trim(substr($v, $pos + 1));
				}
				else
				{
					$domainname = trim($v);
					$simpledec = '';
				}
				if(Domain::checkDomain($domainname))
				{
					$domainname = strtolower($domainname);
					if(in_array($domainname, $domainArr) === false)
					{
						$domainArr[] = $domainname;
						$decArr[$domainname] = $simpledec;
					}
				}
			}
		}
		$data = array($domainArr,$decArr);
		return $data;
	}

	public function getPageDomainInfoById($id, $EnameId = '', $status = '')
	{
		$CDsdk = new ModelBase('custompage_domain');
		$where = array('CustompageDId'=> $id);
		if($EnameId)
		{
			$where['EnameId'] = $EnameId;
		}
		if($status)
		{
			$where['Status'] = $status;
		}
		$return = $CDsdk->getData(
			'DomainName, EnameId, TemplateDId, Description,errowInfo, TransInfo, Status, CustompageDId, HoldStatus, Reg', 
			$where, $CDsdk::FETCH_ROW);
		$return = (array)$return;
		return $return;
	}

	/**
	 * 获取系统模板数据
	 */
	public function getOldSystemTemInfo($templateId, $EnameId)
	{
		$CDTsdk = new ModelBase('template_data');
		$dataInfo = $CDTsdk->getData(
			'TemplateDId, TemplateName, StyleId, TemplateType, Status, Ucid, StatType, StatId, AdType, AdId, Seoid,enameAdSolt,enameType', 
			array('TemplateDId'=> $templateId,'EnameId'=> $EnameId), $CDTsdk::FETCH_ROW);
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
					$dataInfo['Email'] = $dataInfo['QQ'] = $dataInfo['Phone'] = $dataInfo['avatarlinkurl'] = $dataInfo['linkname'] = $dataInfo['linkdesc'] = '';
				}
			}
			else
			{
				$dataInfo['Email'] = $dataInfo['QQ'] = $dataInfo['Phone'] = $dataInfo['avatarlinkurl'] = $dataInfo['linkname'] = $dataInfo['linkdesc'] = '';
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
				$dataInfo['Html'] = $styleInfo['Html'];
				$dataInfo['Css'] = $styleInfo['Css'];
				$dataInfo['StyleTemplateName'] = $styleInfo['TemplateName'];
				return $dataInfo;
			}
		}
		return false;
	}

	/**
	 * 处理预览页的html
	 */
	public function doHtmlParam($data)
	{
		// 将css加载到html中
		$html = $this->composeHtmlCss($data['Html'], $data['Css']);
		
		$transInfo = \core\Config::item('page_domain_transinfo')->toArray();
		$errowInfo = \core\Config::item('page_domain_errowinfo')->toArray();
		$transKey = \core\Config::item('interfaceKey')->toArray();
		$statCode = \core\Config::item('page_stat_type')->toArray();
		$adCodeType = \core\Config::item('page_ad_type')->toArray();
		$adCode = \core\Config::item('page_ad')->toArray();
		$data['StatType'] = isset($data['StatType'])? $data['StatType']: '';
		$data['AdType'] = isset($data['AdType'])? $data['AdType']: '';
		$data['QQ'] = isset($data['QQ'])? $data['QQ']: '';
		$data['Phone'] = isset($data['Phone'])? $data['Phone']: '';
		$data['Email'] = isset($data['Email'])? $data['Email']: '';
		$data['Description'] = isset($data['Description'])? $data['Description']: '';
		$data['Title'] = isset($data['Title'])? $data['Title']: '';
		$data['KeyWords'] = isset($data['KeyWords'])? $data['KeyWords']: '';
		$data['domaindesc'] = isset($data['domaindesc'])? $data['domaindesc']: '';
		
		// 交易信息
		if($data['TransInfo'] == $transInfo['show'][0])
			$transInfo = $this->getTransInfo($data['domain'], $transKey['getDomainForBBSLogic']);
		else
			$transInfo = "";
		
		$data['transtype'] = isset($transInfo['transtype'])? $transInfo['transtype']: '';
		$data['transurl'] = isset($transInfo['transurl'])? $transInfo['transurl']: '';
		$data['price'] = isset($transInfo['price'])? $transInfo['price']: '';
		$data['lefttime'] = isset($transInfo['lefttime'])? $transInfo['lefttime']: '';
		$data['errows'] = '';
		if($data['errowInfo'] == $errowInfo['hide'][0])
		{
			$data['errows'] = "style='display:none'";
		}
		// 统计代码
		$data['codeStat'] = $this->getStatCode($data['StatType'], $data['StatId'], $statCode);
		
		// 广告代码
		if($data['AdType'] == 2)
		{
			$data['codeAd'] = sprintf($adCode[$data['AdType']][0] . $adCodeType[$data['AdType']][0], $data['AdId']) .
				 $adCodeType[$data['AdType']][0];
		}
		elseif($data['AdType'] == 1 && ! empty($data['AdId']))
		{
			$adId = json_decode($data['AdId'], true);
			$data['codeAd'] = sprintf($adCode[$data['AdType']][0], $adId['pubid'], $adId['slotid'], $adId['adwidth'], 
				$adId['adheight']) . $adCodeType[$data['AdType']][0];
		}
		else
		{
			$data['codeAd'] = '';
		}
		
		// 若无交易信息，则隐藏
		if(empty($data['transtype']))
			$data['trans'] = "style='display:none'";
		else
			$data['trans'] = '';
		
		$data['css'] = '';
		$data['whoisurl'] = \core\Config::item('whois_link_url');
		$baseurl = 'http://' . $_SERVER['HTTP_HOST'];
		$data['enamedis'] = '';
		if(! empty($data['enameType']) && ! empty($data['enameAdSolt']))
		{
			if($data['enameType'] == 2)
			{
				$data['enamecode'] = '<script>var adInfo = {ename_ad_solt:' . $data['enameAdSolt'] .
					 '}</script><script type="text/javascript" src="' . $baseurl . '/js/show_o.js"></script>';
			}
			else
			{
				$data['enamecode'] = $data['enameAdSolt'];
			}
		}
		else
		{
			$data['enamecode'] = '';
			$data['enamedis'] = "style='display:none'";
		}
		$data['avatarlinkurl'] = empty($data['avatarlinkurl'])? "<img src='/upload/images/common/common-avatar.png'>": "<img src='/avatar/" .
			 $data['avatarlinkurl'] . "'>";
		// 替换html中的参数
		$data['teldis'] = $data['Phone']? '': 'style="display:none"';
		$data['emaildis'] = $data['Email']? '': 'style="display:none"';
		$data['qqdis'] = $data['QQ']? '': 'style="display:none"';
		$data['descdis'] = $data['linkdesc']? '': 'style="display:none"';
		$html = $this->replaceParams($html, $data);
		return $html;
	}

	/**
	 * 获取预览页交易信息
	 */
	public function getTransInfo($domain, $key)
	{
		$result = array();
		$times = time();
		$sid = md5($key . $times);
		$url = 'http://www.ename.com/auctioninterface/getdomainforbbs?domain=' . $domain . '&sid=' . $sid . '&times=' .
			 $times;
		$data = json_decode(file_get_contents($url), true);
		if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
		{
			$times = strtotime($data['FinishDate']) - time();
			$data['leftTimes'] = $this->NewTimeToDHIS($times);
			if($data['leftTimes'] != '交易已结束')
			{
				if($data['TransType'] == '询价')
				{
					$result['transtype'] = $data['TransType'];
					$result['lefttime'] = $data['leftTimes'];
					$result['price'] = '';
					$result['transurl'] = "http://www.ename.com/auction/inquiry/" . $data['ActionId'];
				}
				else
				{
					$result['transtype'] = $data['TransType'];
					$result['price'] = $data['BidPrice'] . '元';
					$result['lefttime'] = $data['leftTimes'];
					$result['transurl'] = "http://www.ename.com/auction/domain/" . $data['ActionId'];
				}
				return $result;
			}
		}
		return false;
	}

	public function NewTimeToDHIS($time)
	{
		if($time <= 0)
		{
			return '交易已结束';
		}
		$timeStr = '';
		$nY = intval($time / 60 / 60 / 24 / 365);
		$nD = ($time / 60 / 60 / 24) % 365;
		$nH = ($time / 60 / 60) % 24;
		$nI = ($time / 60) % 60;
		$nS = ($time % 60);
		$nD = $nY * 365 + $nD;
		if($nD >= 7)
		{
			$timeStr .= $nD? $nD . '天': '';
		}
		elseif($nD >= 1)
		{
			$timeStr .= $nD? $nD . '天': '';
			$timeStr .= $nH? $nH . '时': '';
		}
		elseif($nH >= 1)
		{
			$timeStr .= $nH? $nH . '时': '';
			$timeStr .= $nI? $nI . '分': '';
		}
		else
		{
			$timeStr .= $nI? $nI . '分': '';
			$timeStr .= $nS? $nS . '秒': '';
		}
		return $timeStr;
	}

	/**
	 * 整理统计代码
	 */
	public function getStatCode($statType, $statId, $statProvide)
	{
		switch($statType)
		{
			case 1:
				// 51la
				$statcode = sprintf($statProvide[1][0], $statId);
				break;
			case 2:
				// cnzz 若以1000开头则是新版本
				if(preg_match('/^1000\d+/', $statId))
				{
					$statcode = sprintf($statProvide[2][0][2], $statId);
				}
				else
				{
					$statcode = sprintf($statProvide[2][0][1], $statId);
				}
				break;
			default:
				$statcode = '';
		}
		return $statcode;
	}

	public function getPageDomainInfoForSet($id, $EnameId)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$return = $CDsdk->getData('*', array('CustompageDId'=> $id,'EnameId'=> $EnameId,'Status'=> array('<','5')), 
			$CDsdk::FETCH_ROW);
		$return = (array)$return;
		return $return;
	}

	/**
	 * 判断我司域名是否是此人(先判断是我司域名，在用此方法判断是否属于用户)
	 *
	 * @param unknown_type $EnameId
	 * @param unknown_type $domainName
	 * @author Qxh
	 */
	public function getDomainForUser($domainName, $EnameId = '')
	{
		$api = new EnameApi();
		$where['domain'] = $domainName;
		if($EnameId)
		{
			$where['enameId'] = $EnameId;
		}
		$shopJson = $api->sendCmd('sellpage/checkDomain ', $where);
		Logger::write('custompage_interface', array("getDomainForUser",$domainName,$shopJson,__LINE__,__FILE__), 
			'custompage');
		$data = json_decode($shopJson, true);
		if($data['code'] == 100000)
		{
			return $data['flag'];
		}
		return false;
	}

	/**
	 * 添加展示页，我司域名处理方式
	 */
	public function doInEname($domains, $EnameId)
	{
		$result = array('url'=> array(),'false'=> array(),'success'=> array());
		if($domains && is_array($domains))
		{
			$statusConf = \core\Config::item('page_domain_status')->toArray();
			$regConf = \core\Config::item('page_domain_reg')->toArray();
			$holdStatusConf = \core\Config::item('page_domain_holdstatus')->toArray();
			$transInfoConf = \core\Config::item('page_domain_transinfo')->toArray();
			$errowInfoConf = \core\Config::item('page_domain_errowinfo')->toArray();
			// 先添加记录，防止批量发布展示页时，接口先执行完，而记录却未写入到数据库，导致接口无法找到相关记录进行设置
			$addedDomains = $addedDomainIds = array();
			$count = count($domains);
			foreach($domains as $domain)
			{
				$domainTag = str_replace('.', '_', $domain);
				$transInfo = (isset($_POST[$domainTag . '_transInfo']) && $_POST[$domainTag . '_transInfo'])? $transInfoConf['show'][0]: $transInfoConf['hide'][0];
				$errowInfo = (isset($_POST[$domainTag . '_errowInfo']) && $_POST[$domainTag . '_errowInfo'])? $errowInfoConf['show'][0]: $errowInfoConf['hide'][0];
				$pageId = $this->addPageDomain($EnameId, $domain, $_POST[$domainTag . '_templateId'], 
					$statusConf['hold'][0], $transInfo, $regConf['inename'][0], $holdStatusConf['unkown'][0], 
					$_POST[$domainTag . '_description'], $errowInfo);
				Logger::write('custompage_addpagedomain', 
					array($pageId? 'TRUE': 'FALSE','addPageDomain','InEname','addPageDomain',$pageId,$domain,$EnameId,
							__FILE__,__LINE__), 'custompage');
				if($pageId)
				{
					$addedDomains[] = $domain;
					$addedDomainIds[$domain] = $pageId;
				}
				else
				{
					$result['false']['addPageDomainFalse'][] = $domain;
				}
			}
			// 调用管理平台接口
			$holdRs = $this->setPageHoldStatus($EnameId, implode(',', $addedDomains), true);
			if($holdRs['flag'])
			{
				foreach($holdRs['msg'] as $v)
				{
					$domainName = $v['domain'];
					$domainTag = str_replace('.', '_', $domainName);
					// 交易信息
					$transInfo = (isset($_POST[$domainTag . '_transInfo']) && $_POST[$domainTag . '_transInfo'])? $transInfoConf['show'][0]: $transInfoConf['hide'][0];
					$errowInfo = (isset($_POST[$domainTag . '_errowInfo']) && $_POST[$domainTag . '_errowInfo'])? $errowInfoConf['show'][0]: $errowInfoConf['hide'][0];
					// 设置HoldStatus
					$v['domainStatus'] = isset($v['domainStatus'])? $v['domainStatus']: $holdStatusConf['unkown'][0]; // 未知的holdstatus
					if($this->setHoldStatusWithHold($addedDomainIds[$domainName], $v['domainStatus'], $EnameId))
					{
						if($v['code'] == 100000)
						{
							// 设置状态为CNAME
							$setPage = $this->setPageDomainStatus($addedDomainIds[$domainName], $statusConf['cname'][0], 
								$EnameId);
							Logger::write('custompage_addpagedomain', 
								array($setPage? 'TRUE': 'FALSE','addPageDomain','InEname','setPageDomainStatus','CNAME',
										$addedDomainIds[$domainName],$domainName,$EnameId,__FILE__,__LINE__), 
								'custompage');
							if($count > 10)
							{
								if(Redis::getInstance()->rPush('setcnamerecord_addpage', array('domainname'=> $domainName,'EnameId'=>$EnameId ,'pageid'=>$addedDomainIds[$domainName])))
								{
									Logger::write('custompage_addpagedomain',
									array('batchaddPageDomain','true','addPageDomain',$addedDomainIds[$domainName],
									$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
									$result['success']['batchset'][] = $domainName;
								}
								else
								{
									Logger::write('custompage_addpagedomain',
									array('batchaddPageDomain','redisfalse','addPageDomain',$addedDomainIds[$domainName],
									$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
									$result['success']['setCnameError'][] = array('domain'=> $domainName,'msg'=> $cnameRs['msg']);
								}
							}
							else
							{
								// 去IIDNS接口设置CNAME记录
								$cnameRs = $this->setCnameRecord($domainName, $EnameId);
								if($cnameRs['flag'] && $cnameRs['code'] == '100000')
								{
									if($this->checkCname($domainName))
									{
										// 设置状态为PAGE
										$setPage = $this->setPageDomainStatus($addedDomainIds[$domainName], 
											$statusConf['page'][0], $EnameId);
										Logger::write('custompage_addpagedomain', 
											array($setPage? 'TRUE': 'FALSE','addPageDomain','InEname',
													'setPageDomainStatus','PAGE',$addedDomainIds[$domainName],
													$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
										
										if($this->createPageDomain($domainName, $_POST[$domainTag . '_templateId'], 
											$EnameId, $_POST[$domainTag . '_description'], $transInfo, $errowInfo))
										{
											// 设置状态为SUCCESS
											$setSuccess = $this->setPageDomainStatus($addedDomainIds[$domainName], 
												$statusConf['success'][0], $EnameId);
											// 添加积分
											$s = new \logic\common\Common();
											$s::addScore($EnameId, 1, '添加域名展示页成功');
											$templateinfo = $this->getTemplateById($_POST[$domainTag . '_templateId']);
											if($templateinfo && $templateinfo->TemplateType == 1)
											{
												$s::addScore($EnameId, 1, '添加域名展示页使用系统模板');
											}
											Logger::write('custompage_addpagedomain', 
												array($setSuccess? 'TRUE': 'FALSE','addPageDomain','InEname',
														'setPageDomainStatus','SUCCESS',$addedDomainIds[$domainName],
														$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
											
											$result['success']['addPageDomainSuccess'][] = $domainName;
										}
										else
										{
											$result['success']['createPageDomainFalse'][] = $domainName;
										}
									}
									else
									{
											$result['success']['cnameNotEffectInEname'][] = $domainName;
									}
								}
								else
								{
									$result['success']['setCnameError'][] = array('domain'=> $domainName,
											'msg'=> $cnameRs['msg']);
								}
							}
						}
						else
						{
							// 无法设置展示页的域名，设置状态为DEL
							$setSuccess = $this->setPageDomainStatus($addedDomainIds[$domainName], 
								$statusConf['del'][0], $EnameId);
							$result['false']['forbidAddPageDomain'][] = array('domain'=> $domainName,'msg'=> $v['msg']);
							continue;
						}
					}
					else
					{
						$result['false']['addPageDomainFalse'][] = $domain;
						Logger::write("custompage_addpagedomain", 
							array('FALSE','addPageDomain','InEname','setHoldStatus',$domainName,$EnameId,
									$addedDomainIds[$domainName],$v['domainStatus']), 'custompage');
						continue;
					}
				}
			}
			else
			{
				Logger::write('custompage_addpagedomain', 
					array('FALSE','setPageHoldStatus',$domains,$EnameId,json_encode($holdRs)), 'custompage');
				return 'error';
			}
		}
		return $result;
	}

	public function addPageDomain($EnameId, $domainName, $templateId, $pageStatus, $transInfo, $reg, $holdStatus, 
		$description, $errowInfo)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$createTime = time();
		$insert = array('EnameId'=> $EnameId,'TemplateDId'=> $templateId,'DomainName'=> $domainName,
				'CreateTime'=> time(),'Status'=> $pageStatus,'TransInfo'=> $transInfo,'Reg'=> $reg,
				'HoldStatus'=> $holdStatus,'errowInfo'=> $errowInfo,'Description'=> $description);
		return $CDsdk->insert($insert);
	}

	public function createPageDomain($domainName, $templateId, $EnameId, $domaindesc, $transInfo, $errowInfo)
	{
		// 获取展示页模版
		Logger::write('custompage_CreateCustompageFile', 
			array('CreateCustompageFiledata',$templateId,$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
		$templateInfo = $this->getOldSystemTemInfo($templateId, $EnameId);
		if(! $templateInfo)
		{
			return false;
		}
		$html = $this->composeHtml($templateInfo['Html']);
		// 先生成本地文件，失败后通知服务器更新展示页
		$rs = $this->createPageFile($EnameId, $domainName, $html, $transInfo, $domaindesc, $templateInfo, $errowInfo);
		if(! $rs)
			return false;
		return true;
	}

	/**
	 * 设置展示页生成文件
	 * 2013-03-12 Wlx
	 */
	public function createPageFile($EnameId, $domainName, $html, $transInfo, $domaindesc, $templateInfo, $errowInfo)
	{
		// 通知服务器更新展示页
		$data = $this->createCustompageFile($EnameId, $domainName, $html, $templateInfo['Css'], $transInfo, 
			$templateInfo['QQ'], $templateInfo['Phone'], $templateInfo['Email'], $templateInfo['StatType'], 
			$templateInfo['StatId'], $templateInfo['AdType'], $templateInfo['AdId'], $templateInfo['KeyWords'], 
			$templateInfo['Description'], $domaindesc, $templateInfo['Title'], $templateInfo['TemplateType'], 
			$templateInfo['StyleId'], $errowInfo, $templateInfo['enameType'], $templateInfo['enameAdSolt'], 
			$templateInfo['linkname'], $templateInfo['linkdesc'], $templateInfo['avatarlinkurl']);
		if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
		{
			Logger::write('custompage_CreateCustompageFile', 
				array('TRUE','CreateCustompageFile',$data['msg'],$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
			return true;
		}
		else
		{
			Logger::write('custompage_CreateCustompageFile', 
				array('FALSE','CreateCustompageFile',$data['msg'],$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
			return false;
		}
		return false;
	}

	/**
	 * 生成展示页文件
	 */
	public function createCustompageFile($enameid, $domain, $html, $css, $transinfo, $qq, $tel, $email, $statType, 
		$statId, $adType, $adId, $keywords, $description, $domaindesc, $title, $templateType, $styleId, $errowInfo, 
		$enametype, $enameCode, $linkname, $linkdesc, $avatarurl)
	{
		$statProvide = \core\Config::item('page_stat_type')->toArray();
		$adProvidType = \core\Config::item('page_ad_type')->toArray();
		$adProvide = \core\Config::item('page_ad')->toArray();
		$broker = \core\Config::item('brokertel');
		$pageTemStyle = \core\Config::item('page_template_style')->toArray();
		$pageTransinfo = \core\Config::item('page_domain_transinfo')->toArray();
		$pageErrowinfo = \core\Config::item('page_domain_errowinfo')->toArray();
		$baseurl = 'http://' . (isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST']: 'www.ename.com.cn');
		if($avatarurl)
		{
			$avatarurl = '<img src="' . $baseurl . '/avatar/' . $avatarurl . '">';
		}
		else
		{
			$avatarurl = '<img src="' . $baseurl . '/upload/images/common/common-avatar.png' . '">';
		}
		// 构建基本目录
		$md5_domain = md5($domain);
		$base_dir = $this->makeDir($domain);
		$file_full_path = \core\Config::item('CUSTOMPAGE_DATA_PATH') . $base_dir . '/';
		// 判断目录是否已经存在
		if(! file_exists($file_full_path))
		{
			$a = mkdir($file_full_path, 0777, true); // 递归创建目录
			if(! $a)
				return array('ServiceCode'=> 4000,'msg'=> '生成目录文件失败');
		}
		
		// 删除缓存图片及html、css文件
		$file[] = $file_full_path . $md5_domain . '_email.png';
		$file[] = $file_full_path . $md5_domain . '_qq.png';
		$file[] = $file_full_path . $md5_domain . '_tel.png';
		$file[] = $file_full_path . $md5_domain . '_white.png';
		$file[] = $file_full_path . $md5_domain . '_black.png';
		$file[] = $file_full_path . $md5_domain . '.html';
		$file[] = $file_full_path . $md5_domain . '.css';
		if(! $this->delFile($file))
		{
			return array('ServiceCode'=> 5000,'msg'=> '删除缓存文件失败');
		}
		// 创建图片
		$this->checkCacheImage($file_full_path, $domain, $email, $qq, $tel, $styleId);
		
		$dataDir = '/data' . $base_dir . '/' . $md5_domain;
		$telImg = $tel == ''? '': "<img style='vertical-align:sub;' src='" . $dataDir . "_tel.png' />";
		$emailImg = $email == ''? '': "<img style='vertical-align:sub' src='" . $dataDir . "_email.png' />";
		$qqImg = $qq == ''? '': "<a href='http://wpa.qq.com/msgrd?v=3&uin=" .
		$qq . "&site=" . $domain .
		"&menu=no' title='点击这里给我留言' target='_blank' ><img src='" . $dataDir ."_qq.png' /></a>";
		
		// 将transinfo等参数写入文件
		$parameter = "<?php\n" . "\$transInfo = '$transinfo';\n" . "\$errowInfo = '$errowInfo';\n" .
			 "\$enameId = '$enameid';\n" . "\$domain = '$domain';\n" . "\$qq = '$qq';\n" . "\$tel = '$tel';\n" .
			 "\$email = '$email';\n" . "\$linkname = '$linkname';\n" . "\$statType = '$statType';\n" .
			 "\$statId = '$statId';\n" . "\$adType = '$adType';\n" . "\$linkdesc= '$linkdesc';\n" .
			 "\$avatarlinkurl = '$avatarurl';\n" . "\$adId = '$adId';\n" . "\$keywords = '$keywords';\n" .
			 "\$information = '$description';\n" . "\$domaindesc = '$domaindesc';\n" . "\$enameCode = '$enameCode';\n" .
			 "\$enametype='$enametype';\n" . "\$title = '$title';\n" . "\$templateType = '$templateType';\n" .
			 "\$styleId = '$styleId';\n";
		
		// 生成参数文件
		$createParaFile = $this->writeFile($parameter, $file_full_path . $md5_domain);
		if(! $createParaFile)
		{
			return array('ServiceCode'=> 4000,'msg'=> '生成参数文件失败');
		}
		
		// 若是系统模板则返回
		if($templateType == $pageTemStyle['system'][0])
		{
			return array('ServiceCode'=> 1000,'msg'=> '生成展示页文件成功');
		}
		$enamedis = '';
		if($enametype && $enameCode)
		{
			if($enametype == 2)
			{
				$enameCode = '<script>var adInfo = {ename_ad_solt:' . $enameCode .
					 '}</script><script type="text/javascript" src="' . $baseurl . '/js/show_o.js"></script>';
			}
		}
		else
		{
			$enamedis = 'style="display:none"';
		}
		
		// 统计ID
		$statcode = $this->doStatCode($statId, $statType, $statProvide);
		
		// 广告ID
		$adcode = $this->doAdCode($adId, $adType, $adProvidType, $adProvide);
		
		// 是否设置显示交易信息
		if($transinfo != $pageTransinfo['show'][0])
		{
			$transtype = '';
			$lefttime = '';
			$price = '';
			$transurl = '';
		}
		else
		{
			$transtype = "<?php echo \$transtype; ?>";
			$lefttime = "<?php echo \$lefttime; ?>";
			$price = "<?php echo \$price; ?>";
			$transurl = "<?php echo \$transurl;?>";
		}
		// 交易信息显示
		
		// 写入css文件
		$cssfile = $file_full_path . $md5_domain . '.css';
		$writeCssFile = $this->writeFile($css, $cssfile);
		if(! $writeCssFile)
		{
			return array('ServiceCode'=> 4000,'msg'=> '生成CSS文件失败');
		}
		
		$errows = "<?php echo \$errows; ?>";
		$trans = "<?php echo \$trans; ?>";
		$cssfile = $dataDir . '.css';
		if($avatarurl == '<img src="' . $baseurl . '/upload/images/common/common-avatar.png' . '">')
		{
			$avatarurl = '<img src="/upload/images/common/common-avatar.png' . '">';
		}
		$param['teldis'] = $tel? '': 'style="display:none"';
		$param['emaildis'] = $email? '': 'style="display:none"';
		$param['qqdis'] = $qq? '': 'style="display:none"';
		$param['descdis'] = $linkdesc? '': 'style="display:none"';
		// 替换html中的参数
		$param = array('enameid'=> $enameid,'domain'=> $domain,'qq'=> $qqImg,'css'=> $cssfile,'keywords'=> $keywords,
				'tel'=> $telImg,'email'=> $emailImg,'information'=> $description,'transtype'=> $transtype,
				'whoisurl'=> \core\Config::item('whois_link_url'),'linkname'=> $linkname,'linkdesc'=> $linkdesc,
				'avatarlinkurl'=> $avatarurl,'lefttime'=> $lefttime,'price'=> $price,'transurl'=> $transurl,
				'codestat'=> $statcode,'codead'=> $adcode,'domaindesc'=> $domaindesc,'enamecode'=> $enameCode,
				'title'=> $title,'trans'=> $trans,'enamedis'=> $enamedis,'errows'=> $errows);
		$html = $this->doHtml($html, $param);
		
		// 数据写入html文件
		$htmlFile = $file_full_path . $md5_domain . '.html';
		$writeHtmlFile = $this->writeFile($html, $htmlFile);
		if(! $writeHtmlFile)
		{
			return array('ServiceCode'=> 4000,'msg'=> '生成Html文件失败');
		}
		return array('ServiceCode'=> 1000,'msg'=> '生成展示页文件成功');
	}

	/**
	 * 处理统计代码
	 */
	public function doStatCode($statId, $statType, $statProvide)
	{
		$statcode = '';
		if(! empty($statId))
		{
			switch($statType)
			{
				case 1:
					// 51la
					$statcode = sprintf($statProvide[1][0], $statId);
					break;
				case 2:
					// cnzz 若以1000开头则是新版本
					if(preg_match('/^1000\d+/', $statId))
					{
						$statcode = sprintf($statProvide[2][0][2], $statId);
					}
					else
					{
						$statcode = sprintf($statProvide[2][0][1], $statId);
					}
					break;
				default:
					$statcode = '';
			}
		}
		return $statcode;
	}

	/**
	 * 处理广告代码
	 */
	public function doAdCode($adId, $adType, $adProvidType, $adProvide)
	{
		if($adId && $adType == 2)
			$adcode = sprintf($adProvide[$adType][0] . $adProvidType[$adType][0], $adId);
		elseif($adType == 1 && ! empty($adId))
		{
			$adId = json_decode($adId, true);
			$adcode = sprintf($adProvide[$adType][0] . $adProvidType[$adType][0], $adId['pubid'], $adId['slotid'], 
				$adId['adwidth'], $adId['adheight']);
		}
		else
			$adcode = '';
		return $adcode;
	}

	/**
	 * 替换html中的参数
	 */
	public function doHtml($html, array $param)
	{
		$i = 1;
		while(preg_match('{%(\w+)%}', $html, $regs))
		{
			if(100 == $i)
			{
				break;
			}
			$found = $regs[1];
			if(! isset($param[$found]))
				$param[$found] = '';
			$html = preg_replace("/\{%" . $found . "%\}/", $param[$found], $html);
			$i++;
		}
		return $html;
	}

	/**
	 * 写入文件
	 */
	public function writeFile($data, $file)
	{
		$fp = fopen($file, 'w');
		if($fp)
		{
			if(fwrite($fp, $data) === FALSE)
			{
				fclose($fp);
				return false;
			}
			fclose($fp);
			// chmod($file, 0777);
			if(! @chmod($file, 0777))
			{
				error_log(date('Y-m-d H:i:s') . $file, 3, '/tmp/xf_' . date('Y-m-d') . '.log');
			}
			return true;
		}
		return false;
	}

	/**
	 * 定义图片大小
	 */
	public function checkCacheImage($dataMenuPath, $domain, $email, $qq, $tel, $styleId = false)
	{
		$md5_domain = md5($domain);
		if(file_exists($dataMenuPath))
		{
			$this->mkdirs($dataMenuPath, 0700, true);
		}
		
		$lenEmail = strlen($email);
		$lenQq = strlen($qq);
		$lenTel = strlen($tel);
		
		$emailPath = $dataMenuPath . $md5_domain . '_email.png';
		$qqPath = $dataMenuPath . $md5_domain . '_qq.png';
		$telPath = $dataMenuPath . $md5_domain . '_tel.png';
		$height = 16;
		switch($styleId)
		{
			case 2:
			case 4:
			case 10:
			case 11:
				$flag = true;
				break;
			default:
				$flag = false;
		}
		// 生成email图片
		if(! empty($email))
			$this->createImage($lenEmail * 8, $height, $email, $emailPath, $flag);
			// 生成qq图片
			if(! empty($qq))
			$this->createImage($lenQq * 8, $height, $qq, $qqPath);
			// 生成tel图片
		if(! empty($tel))
			$this->createImage($lenTel * 8, $height, $tel, $telPath, $flag);
		return true;
	}

	public function mkdirs($dir, $mode = 0777)
	{
		if(is_dir($dir) || mkdir($dir, $mode))
			return TRUE;
		if(! $this->mkdirs(dirname($dir), $mode))
			return FALSE;
		return mkdir($dir, $mode);
	}

	/**
	 * 生成透明背景图
	 */
	public function createImage($width, $height, $string, $path, $flag = false)
	{
		// 创建图像,宽度为width,高度为height
		$im = imagecreatetruecolor($width, $height); // (创建基于调色板的图像)
		                                             
		// 创建透明背景
		imagealphablending($im, false);
		imagesavealpha($im, true);
		$white = imagecolorallocatealpha($im, 255, 255, 255, 127);
		imagefill($im, 0, 0, $white);
		
		if(false == $flag)
			$color = imagecolorallocate($im, 0, 0, 0); // 设置字体颜色
		else
			$color = imagecolorallocate($im, 255, 255, 255); // 设置字体颜色
				                                                
		// 画上字符串
		imagestring($im, 4, 0, 0, $string, $color);
		imagepng($im, $path);
		if(is_resource($im))
		{
			imagedestroy($im);
		}
	}

	/**
	 * 删除文件
	 */
	public function delFile($file)
	{
		if(is_array($file))
		{
			foreach($file as $val)
			{
				if(file_exists($val))
				{
					if(! unlink($val))
						return false;
				}
			}
		}
		elseif(file_exists($file))
			return unlink($file);
		
		return true;
	}

	/**
	 * 构建基本目录
	 */
	public function makeDir($domain)
	{
		// MD5传入的域名，分配目录
		// www.dnspod.com => b/4/a/c/b4ac8ab8e88d02698dea760dcdbccf87
		$md5_domain = md5($domain);
		$base_dir = '';
		for($i = 0; $i < 4; $i++)
		{
			$base_dir .= "/" . $md5_domain{$i};
		}
		return $base_dir;
	}

	public function removePageDomain($domainName, $EnameId)
	{
		// 删除本地展示页文件页
		if(! $this->removePageFile($domainName, $EnameId))
			return false;
		return true;
	}

	/**
	 * 删除展示页文件
	 * 2013-03-12 Wlx
	 */
	public function removePageFile($domainName, $EnameId)
	{
		// 通知服务器更新展示页
		$data = $this->removeCustompageFile($domainName, $EnameId);
		if(isset($data['ServiceCode']) && $data['ServiceCode'] == 1000)
		{
			Logger::write('custompage_RemoveCustompageFile', 
				array('TRUE','RemoveCustompageFile',$data['msg'],$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
			return true;
		}
		else
		{
			Logger::write('custompage_RemoveCustompageFile', 
				array('FALSE','RemoveCustompageFile',$data['msg'],$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
			return false;
		}
		return false;
	}

	/**
	 * 移除展示页文件
	 */
	public function removeCustompageFile($domain, $enameid)
	{
		// 构建目录
		$md5_domain = md5($domain);
		$base_dir = $this->makeDir($domain);
		
		$oldPath = \core\Config::item('CUSTOMPAGE_DATA_PATH') . $base_dir . '/';
		$newPath = \core\Config::item('CUSTOMPAGE_DATA_PATH') . '/removedata/' . $enameid . '/' . $md5_domain . '/';
		
		// 创建存放删除出售页的文件夹
		if(! file_exists($newPath))
		{
			$a = mkdir($newPath, 0777, true);
			if(! $a)
				return array('ServiceCode'=> 4000,'msg'=> '生成目录文件失败');
		}
		
		// 移除参数文件
		if(file_exists($oldPath . $md5_domain))
		{
			if(! rename($oldPath . $md5_domain, $newPath . $md5_domain))
				return array('ServiceCode'=> 4000,'msg'=> '移除参数文件失败');
		}
		else
			return array('ServiceCode'=> 1000,'msg'=> '展示页文件已移除');
			
			// 移除CSS文件
		if(file_exists($oldPath . $md5_domain . '.css'))
		{
			if(! rename($oldPath . $md5_domain . '.css', $newPath . $md5_domain . '.css'))
				return array('ServiceCode'=> 4000,'msg'=> '移除css文件失败');
		}
		
		// 移除html文件
		if(file_exists($oldPath . $md5_domain . '.html'))
		{
			if(! rename($oldPath . $md5_domain . '.html', $newPath . $md5_domain . '.html'))
				return array('ServiceCode'=> 4000,'msg'=> '移除html文件失败');
		}
		
		// 删除缓存图片
		$emailImg = $oldPath . $md5_domain . '_email.png';
		$telImg = $oldPath . $md5_domain . '_tel.png';
		$qqImg = $oldPath . $md5_domain . '_qq.png';
		$white_image = $oldPath . $md5_domain . '_white.png';
		$black_image = $oldPath . $md5_domain . '_black.png';
		if(file_exists($emailImg))
			rename($emailImg, $newPath . $md5_domain . '_email.png');
		
		if(file_exists($telImg))
			rename($telImg, $newPath . $md5_domain . '_tel.png');
		
		if(file_exists($qqImg))
			rename($qqImg, $newPath . $md5_domain . '_qq.png');
		
		if(file_exists($white_image))
			rename($white_image, $newPath . $md5_domain . '_white.png');
		
		if(file_exists($black_image))
			rename($black_image, $newPath . $md5_domain . '_black.png');
		
		return array('ServiceCode'=> 1000,'msg'=> '移除展示页文件成功');
	}

	/**
	 * 添加展示页，非我司域名处理方式
	 */
	public function doNotInEname($domains, $EnameId)
	{
		$result = array('url'=> array(),'false'=> array(),'success'=> array());
		if($domains && is_array($domains))
		{
			$statusConf = \core\Config::item('page_domain_status')->toArray();
			$regConf = \core\Config::item('page_domain_reg')->toArray();
			$holdStatusConf = \core\Config::item('page_domain_holdstatus')->toArray();
			$count = count($domains);
			foreach($domains as $domainName)
			{
				$domainTag = str_replace('.', '_', $domainName);
				// 交易信息
				$transInfoConf = \core\Config::item('page_domain_transinfo')->toArray();
				$transInfo = (isset($_POST[$domainTag . '_transInfo']) && $_POST[$domainTag . '_transInfo'])? $transInfoConf['show'][0]: $transInfoConf['hide'][0];
				$errowInfoConf = \core\Config::item('page_domain_errowinfo')->toArray();
				$errowInfo = (isset($_POST[$domainTag . '_errowInfo']) && $_POST[$domainTag . '_errowInfo'])? $errowInfoConf['show'][0]: $errowInfoConf['hide'][0];
				// 先添加记录，防止HTTP中断，导致接口请求了，但数据库没写入数据
				$pageId = $this->addPageDomain($EnameId, $domainName, $_POST[$domainTag . '_templateId'], 
					$statusConf['cname'][0], $transInfo, $regConf['notinename'][0], $holdStatusConf['hold'][0], 
					$_POST[$domainTag . '_description'], $errowInfo);
				Logger::write('custompage_addpagedomain', 
					array($pageId? 'TRUE': 'FALSE','addPageDomain','NotInEname','addPageDomain',$pageId,$domainName,
							$EnameId,__FILE__,__LINE__), 'custompage');
				
				if(! $pageId)
				{
					$result['false']['addPageDomainFalse'][] = $domainName;
					continue;
				}
				if($count > 10)
				{
					Logger::write('custompage_addpagedomain', 
						array('batchaddPageDomain','batch','addPageDomain',$pageId,$domainName,$EnameId,__FILE__,
								__LINE__), 'custompage');
					$result['success']['batchset'][] = $domainName;
				}
				else
				{
					if($this->checkCname($domainName, $EnameId))
					{
						$setSuccess = $this->setPageDomainStatus($pageId, $statusConf['page'][0], $EnameId);
						Logger::write('custompage_addpagedomain', 
							array($setSuccess? 'TRUE': 'FALSE','addPageDomain','NotInEname','setPageDomainStatus',
									'PAGE',$pageId,$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
						
						if($this->createPageDomain($domainName, $_POST[$domainTag . '_templateId'], $EnameId, 
							$_POST[$domainTag . '_description'], $transInfo, $errowInfo))
						{
							// 非我司域名生效的话，其他已经生效的域名删除
							$this->setPageDomainStatusBNot($statusConf['del'][0], $EnameId, $domainName);
							$setSuccess = $this->setPageDomainStatus($pageId, $statusConf['success'][0], $EnameId);
							// 添加积分
							$s = new \logic\common\Common();
							$s::addScore($EnameId, 1, '添加域名展示页成功');
							$templateinfo = $this->getTemplateById($_POST[$domainTag . '_templateId']);
							if($templateinfo && $templateinfo->TemplateType == 1)
							{
								$s::addScore($EnameId, 1, '添加域名展示页使用系统模板');
							}
							Logger::write('custompage_addpagedomain', 
								array($setSuccess? 'TRUE': 'FALSE','addPageDomain','NotInEname','setPageDomainStatus',
										'SUCCESS',$pageId,$domainName,$EnameId,__FILE__,__LINE__), 'custompage');
							
							$result['success']['addPageDomainSuccess'][] = $domainName;
						}
						else
							$result['success']['createPageDomainFalse'][] = $domainName;
					}
					else
						$result['success']['cnameNotEffectNotInEname'][] = $domainName;
				}
			}
		}
		return $result;
	}

	public function checkCname($domainName, $EnameId = '')
	{
		if(preg_match("/[\x80-\xff]./", $domainName))
		{
			$idna = new CustomPageIDNALib();
			$domainName = $idna->encode($domainName);
		}
		// 若EnameId为空，则是我司域名，只查询域名是否解析成功
		// 若不为空，则非我司域名，则要查询EnameId+domain，用来验证域名是否属于用户
		$server = \core\Config::item('page_cname_server');
		if($EnameId)
		{
			$record3 = dns_get_record($EnameId . '.' . $domainName, DNS_CNAME);
			if($record3 && isset($record3[0]['target']) && strtolower($record3[0]['target']) == $server)
			{
				$record1 = dns_get_record($domainName, DNS_CNAME);
				if($record1 && isset($record1[0]['target']) && strtolower($record1[0]['target']) == $server)
				{
					return true;
				}
				$record2 = dns_get_record('www.' . $domainName, DNS_CNAME);
				if($record2 && isset($record2[0]['target']) && strtolower($record2[0]['target']) == $server)
				{
					return true;
				}
			}
		}
		else
		{
			$record1 = dns_get_record($domainName, DNS_CNAME);
			if($record1 && isset($record1[0]['target']) && strtolower($record1[0]['target']) == $server)
			{
				return true;
			}
			$record2 = dns_get_record('www.' . $domainName, DNS_CNAME);
			if($record2 && isset($record2[0]['target']) && strtolower($record2[0]['target']) == $server)
			{
				return true;
			}
		}
		return false;
	}
	public function  checkCnameTask($domainName, $EnameId = '')
	{
		$server = \core\Config::item('page_cname_server');
		$host = '';
		if(empty($EnameId))
		{
			$host = $domainName;
		}
		else {
			$host = $EnameId. '.' .$domainName;
		
		}
		$content = $this->dig_get_dns_record("CNAME", $host);
		Logger::write('crontab_dnstest',array('checkcname',$host,$domainName,$EnameId,$content));
		$RELLAG = FALSE;
		if(stripos($content, $server))
		{
			if($EnameId)
			{
				$ncontent = $this->dig_get_dns_record("CNAME", $domainName);
				Logger::write('crontab_dnstest',array('notinename','checkcname',$domainName,$EnameId,$ncontent));
				if(stripos($ncontent, $server))
				{
					$RELLAG = TRUE;
				}
			}
			else {
				$RELLAG = TRUE;
			}
		}
		Logger::write('crontab_dnstest',array("result",$EnameId,$domainName,$val['CustompageDId'],$RELLAG ? "true" : "false"));
		return $RELLAG;
	}
	public function dig_get_dns_record($type, $host)
	{
		$cleaned_host = escapeshellcmd($host);
		ob_start();
		// Note: for this to work on Windows/XAMPP "dig" has to be installed and the search path
		passthru("dig $type $cleaned_host");
		$lookup = ob_get_contents();
		ob_end_clean();
		//                                              //echo "<pre>" . $lookup . "</pre>";  // Remove comment to see dig output
		return $lookup;
	}

	/**
	 * 设置展示页（管理平台）
	 */
	public function setPageHoldStatus($enameid, $domains, $batch = false)
	{
		$api = new EnameApi();
		$result = $api->sendCmd('sellpage/setSellPage', array('domains'=> $domains,'enameId'=> $enameid));
		Logger::write("custompage_interface", array("setPageHoldStatus",$result,__LINE__,__FILE__), 'custompage');
		$rs = json_decode($result, true);
		if($rs['flag'])
		{
			if($batch === false)
			{
				// 单个域名删除展示页
				if($rs['code'] == 100000)
					return array('flag'=> true,'domainStatus'=> $rs['msg']['domainStatus'],'msg'=> '设置展示页成功');
				else
					return array('flag'=> false,'msg'=> $rs['msg']);
			}
			else
			{
				return $rs;
			}
		}
		return array('flag'=> false,'msg'=> '展示页接口请求失败');
	}

	/**
	 * 关闭展示页（管理平台）
	 */
	public function closePageHoldStatus($enameid, $domains)
	{
		$api = new EnameApi();
		$result = $api->sendCmd('sellpage/closeSellPage ', array('domains'=> $domains,'enameId'=> $enameid));
		Logger::write("custompage_interface", array("closePageHoldStatus",$domains,$result,__LINE__,__FILE__), 
			'custompage');
		$rs = json_decode($result, true);
		if($rs['flag'] && $rs['code'] == '100000')
		{
			return array('flag'=> true,'msg'=> '关闭展示页成功');
		}
		return array('flag'=> false,'msg'=> '关闭展示页接口请求失败');
	}

	/**
	 * 设置展示页添加cname
	 */
	public function setCnameRecord($domain, $enameid)
	{
		$api = new EnameApi();
		$result = $api->sendCmd('sellpage/domainCustompage ', array('domain'=> $domain,'enameId'=> $enameid));
		Logger::write("custompage_interface", array("setCnameRecord",$domain,$result,__LINE__,__FILE__), 'custompage');
		$rs = json_decode($result, true);
		if($rs['flag'])
		{
			return $rs;
		}
		return array('flag'=> false,'msg'=> '设置展示页添加cname接口请求失败');
	}

	/**
	 * 设置展示页域名HoldStatus(带接口返回HoldStatus)
	 * 2013-03-11 Qxh Add
	 */
	public function setHoldStatusWithHold($pageId, $holdStatus, $EnameId = '')
	{
		// 将接口得到的holdstatus转为平台的holdstatus直
		$pageHoldStatus = $this->convertHoldStatus($holdStatus);
		// 设置展示页域名HoldStatus
		return $this->setHoldStatus($pageId, $pageHoldStatus, $EnameId);
	}

	/**
	 * 设置展示页域名HoldStatus
	 * 2013-03-11 Qxh Add
	 */
	public function setHoldStatus($pageId, $holdStatus, $EnameId = '')
	{
		$CDsdk = new ModelBase('custompage_domain');
		$where = array('CustompageDId'=> $pageId);
		if($EnameId)
		{
			$where['EnameId'] = $EnameId;
		}
		return $CDsdk->update(array('HoldStatus'=> $holdStatus,'UpdateTime'=> time()), $where);
	}

	public function setPageDomainStatus($id, $status, $EnameId = '')
	{
		$CDsdk = new ModelBase('custompage_domain');
		$where = array('CustompageDId'=> $id);
		if($EnameId)
		{
			$where['EnameId'] = $EnameId;
		}
		return $CDsdk->update(array('Status'=> $status,'UpdateTime'=> time()), $where);
	}

	public function setPageDomainStatusBNot($status, $EnameId = '', $domain = '')
	{
		$CDsdk = new ModelBase('custompage_domain');
		if(empty($domain))
		{
			return false;
		}
		$where = array('Status'=> array('<>',$status),'DomainName'=> $domain);
		if($EnameId)
		{
			$where['EnameId'] = array('<>',$EnameId);
		}
		return $CDsdk->update(array('Status'=> $status,'UpdateTime'=> time()), $where);
	}

	public function convertHoldStatus($getHoldStatus)
	{
		$holdStatusConf = \core\Config::item('page_domain_holdstatus')->toArray();
		switch($getHoldStatus)
		{
			case 0:
				$status = $holdStatusConf['normal'][0];
				break;
			case 1:
				$status = $holdStatusConf['clienthold'][0];
				break;
			case 2:
				$status = $holdStatusConf['serverhold'][0];
				break;
			default:
				$status = $holdStatusConf['unkown'][0];
		}
		return $status;
	}

	public function getPageDomainInfoForDel($id, $EnameId)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$where = array('CustompageDId'=> $id,'EnameId'=> $EnameId,'Status'=> array('<>',$statusConf['del'][0]));
		$data = $CDsdk->getData(
			'CustompageDId, EnameId, DomainName, TemplateDId, Description, TransInfo, HoldStatus, Reg, Status', $where, 
			$CDsdk::FETCH_ROW);
		return (array)$data;
	}

	public function delPageDomain($id, $EnameId = '', $delFlagStatus = 0)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$where = array('CustompageDId'=> $id);
		if($EnameId)
		{
			$where['EnameId'] = $EnameId;
		}
		return $CDsdk->update(array('Status'=> $statusConf['del'][0],'DeleteTime'=> time(),'DelFlag'=> $delFlagStatus), 
			$where);
	}

	/**
	 * 我司域名删除展示页
	 * 2013-01-28 Qxh Add
	 */
	public function doDelInEname($info, $EnameId)
	{
		$cs = \core\Config::item('page_cname_server');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		// 如果域名不属于用户，为了兼容老版本出售页
		$checkUser = $this->getDomainForUser($info['DomainName'], $EnameId);
		if(! $checkUser)
		{
			Logger::write('custompage_DelPageDomain', 
				array('FALSE','DelPageDomain','NotUserDomain',json_encode($checkUser),$info['DomainName'],$EnameId,
						__FILE__,__LINE__), 'custompage');
			return 0;
		}
		// 管理平台，取消展示页状态
		$closeRs = $this->closePageHoldStatus($EnameId, $info['DomainName']);
		if($closeRs['flag'])
		{
			if($this->removePageDomain($info['DomainName'], $EnameId))
				$delFlagStatus = 0;
			else
				$delFlagStatus = $statusConf['page'][0];
		}
		else
		{
			Logger::write('custompage_DelPageDomain', 
				array('FALSE','DelPageDomain','closePageHoldStatus',$closeRs['msg'],$info['DomainName'],$EnameId), 
				'custompage');
			$delFlagStatus = $statusConf['hold'][0];
		}
		return $delFlagStatus;
	}

	/**
	 * 非我司域名删除展示页
	 * 2013-01-28 Qxh Add
	 */
	public function doDelNotInEname($info, $EnameId)
	{
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$delFlagStatus = 0;
		if($info['Status'] == $statusConf['success'][0])
		{
			if($this->removePageDomain($info['DomainName'], $EnameId))
				$delFlagStatus = 0;
			else
				$delFlagStatus = $statusConf['page'][0];
		}
		return $delFlagStatus;
	}

	public function convertIIDNSHoldStatus($getHoldStatus)
	{
		$holdStatusConf = \core\Config::item('page_domain_holdstatus')->toArray();
		$status = '';
		switch($getHoldStatus)
		{
			case $holdStatusConf['normal'][0]:
				$status = 0;
				break;
			case $holdStatusConf['clienthold'][0]:
				$status = 1;
				break;
			case $holdStatusConf['serverhold'][0]:
				$status = 2;
				break;
		}
		return $status;
	}

	public function setPageDomain($id, $EnameId, $description, $templateId, $transInfo, $errowInfo)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$where = array('CustompageDId'=> $id);
		if($EnameId)
		{
			$where['EnameId'] = $EnameId;
		}
		return $CDsdk->update(
			array('Description'=> $description,'TemplateDId'=> $templateId,'TransInfo'=> $transInfo,
					'errowInfo'=> $errowInfo,'UpdateTime'=> time()), $where);
	}

	/**
	 * 发布展示页未成功时
	 * 处于cname状态的重试
	 */
	public function cnameRetry($id, $EnameId, $domain, $holdStatus, $reg)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$cs = \core\Config::item('page_cname_server');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		$setCname = true;
		$regFlag = $EnameId;
		if($reg == $regConf['inename'][0])
		{
			$cnameRs = $this->setCnameRecord($domain, $EnameId);
			$setCname = $cnameRs['flag'] && $cnameRs['code'] == '100000';
			$regFlag = '';
		}
		if($setCname)
		{
			$info = $this->getPageDomainInfoById($id, $EnameId);
			if($this->checkCname($domain, $regFlag))
			{
				if($this->createPageDomain($domain, $info['TemplateDId'], $EnameId, $info['Description'], 
					$info['TransInfo'], $info['errowInfo']))
					$result = array("result"=> true,"msg"=> '生成展示页成功','pageStatus'=> $statusConf['success'][0]);
				else
					$result = array("result"=> false,"msg"=> '生成展示页失败','pageStatus'=> $statusConf['page'][0]);
			}
			else
			{
				$result = array("result"=> false,"msg"=> 'CNAME记录未解析生效','pageStatus'=> $statusConf['cname'][0]);
			}
		}
		else
		{
			$result = array("result"=> false,"msg"=> "设置CNAME失败",'pageStatus'=> $statusConf['cname'][0]);
		}
		return $result;
	}

	/**
	 * 发布展示页未成功时
	 * 处于cname状态的重试
	 */
	public function pageRetry($id, $EnameId, $domain)
	{
		$info = $this->getPageDomainInfoById($id, $EnameId);
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		if($this->createPageDomain($domain, $info['TemplateDId'], $EnameId, $info['Description'], $info['TransInfo'], 
			$info['errowInfo']))
			$result = array("result"=> true,"msg"=> '生成展示页成功','pageStatus'=> $statusConf['success'][0]);
		else
			$result = array("result"=> false,"msg"=> '生成展示页失败','pageStatus'=> $statusConf['page'][0]);
		
		return $result;
	}

	public function isExistSameTemplate($templateName, $EnameId)
	{
		$CDTsdk = new ModelBase('template_data');
		$statusConf = \core\Config::item('page_template_status')->toArray();
		return $CDTsdk->count(
			array('TemplateName'=> $templateName,'EnameId'=> $EnameId,'Status'=> array('<>',$statusConf['del'][0])), 
			'TemplateDId');
	}

	public function getTemplateById($tempid)
	{
		$CDTsdk = new ModelBase('template_data');
		return $CDTsdk->getdata('*', array('TemplateDId'=> $tempid), $CDTsdk::FETCH_ROW);
	}

	public function isExistSameTemplateForSet($templateName, $EnameId, $templateId)
	{
		$CDTsdk = new ModelBase('template_data');
		$statusConf = \core\Config::item('page_template_status')->toArray();
		$data = $CDTsdk->getData('*', 
			array('TemplateName'=> $templateName,'EnameId'=> $EnameId,'Status'=> $statusConf['normal'][0],
					'TemplateDId'=> array('<>',$templateId)));
		return $data;
	}

	public function getPageDomainByTemplateId($templateId, $status = array())
	{
		$CDTsdk = new ModelBase('custompage_domain');
		$where = array('TemplateDId'=> $templateId);
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		if($status)
		{
			$where['Status'] = array("IN",$status);
		}
		else
		{
			$where['Status'] = array('<',$statusConf['del'][0]);
		}
		return $CDTsdk->getData('*', $where);
	}

	public function setTemplateWithoutPage($templateId, $styleId, $templateType, $templateName, $ucid, $seoid, $statType, 
		$statId, $adType, $adId, $htmlCode, $cssCode, $oldHtmlCode, $oldCssCode, $oldStatus, $enamecode, $enametype)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$templateStyleConf = \core\Config::item('page_template_style')->toArray();
		$status = $oldStatus;
		// 若是自定义模板，且模板HTML或CSS修改过，则要进行重新审核
		if($templateType == $templateStyleConf['diy'][0] && ($htmlCode != $oldHtmlCode || $cssCode != $oldCssCode))
			$status = $templateStatusConf['waitaudit'][0];
		if($this->setTemplatedata($templateId, $status, $templateName, $ucid, $seoid, $statType, $statId, $adType, 
			$adId, $enamecode, $enametype))
		{
			// 如果是自定义风格模板，则同时要修改样式模板
			if($templateType == $templateStyleConf['diy'][0])
			{
				
				if(! $this->setTemplate($styleId, $status, $htmlCode, $cssCode))
					return false;
			}
			switch($status)
			{
				case $templateStatusConf['normal'][0]:
					return array('update'=> true,'msg'=> '模板修改成功！');
					break;
				case $templateStatusConf['waitaudit'][0]:
					return array('update'=> false,'msg'=> '模板修改成功,请等待审核！');
					break;
				default:
					return false;
			}
		}
		else
			return false;
	}

	public function setTemplateWithPage($EnameId, $templateId, $styleId, $templateType, $templateName, $ucid, $seoid, 
		$statType, $statId, $adType, $adId, $htmlCode, $cssCode, $oldHtmlCode, $oldCssCode, $oldStatus, $enamecode, 
		$enametype)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$templateStyleConf = \core\Config::item('page_template_style')->toArray();
		$status = $oldStatus;
		
		$CDTsdk = new ModelBase('template_data');
		$CSTsdk = new ModelBase('template_style');
		$usdk = new ModelBase('user_contact');
		$ssdk = new ModelBase('seo');
		// 若是自定义模板
		if($templateType == $templateStyleConf['diy'][0])
		{
			Logger::write('custompage_setTemplateWithPage', 
				array("TRUE",'setTemplateWithPage','diy',$EnameId,$templateId,$styleId,__FILE__,__LINE__), 'custompage');
			
			// 模板HTML或CSS修改过，则要进行重新审核
			if($htmlCode != $oldHtmlCode || $cssCode != $oldCssCode)
			{
				Logger::write('custompage_setTemplateWithPage', 
					array("TRUE",'setTemplateWithPage','diy','edit html or css',$EnameId,$templateId,$styleId,__FILE__,
							__LINE__), 'custompage');
				
				// 检查是不是临时模板
				$dataTemplateId = 0;
				if(! $this->checkIsTempTemplate($templateId))
				{
					// 不是临时模板
					Logger::write('custompage_setTemplateWithPage', 
						array("TRUE",'setTemplateWithPage','diy','edit html or css','not temp template',$EnameId,
								$templateId,$styleId,__FILE__,__LINE__), 'custompage');
					// 添加新的样式模板
					$styleTemplateId = $this->addStyleTemplate($EnameId, $templateName, $htmlCode, $cssCode);
					Logger::write('custompage_setTemplateWithPage', 
						array($styleTemplateId? "TRUE": "FALSE",'setTemplateWithPage','diy','edit html or css',
								'not temp template','add style template',$EnameId,$templateId,$styleId,__FILE__,__LINE__), 
						'custompage');
					if(! $styleTemplateId)
						return false;
						// 添加新的数据模板
					$dataTemplateId = $this->addDataTemplate($EnameId, $templateName, $styleTemplateId, $templateType, 
						$ucid, $statType, $statId, $adType, $adId, $seoid, $enamecode, $enametype);
					Logger::write('custompage_setTemplateWithPage', 
						array($dataTemplateId? "TRUE": "FALSE",'setTemplateWithPage','diy','edit html or css',
								'not temp template','add data template',$EnameId,$templateId,$styleId,$styleTemplateId,
								$dataTemplateId,__FILE__,__LINE__), 'custompage');
					if(! $dataTemplateId)
						return false;
						// 设置老数据模板：状态为正在编辑状态、LinkId为新模板Id
					if(! $this->setOldTemplatedata($templateId, $dataTemplateId, $templateStatusConf['edit'][0]))
						return false;
						// 设置老样式模板：状态为正在编辑状态
					if(! $this->setOldTemplate($styleId, $templateStatusConf['edit'][0]))
						return false;
				}
				else
				{
					// 是临时模板
					Logger::write('custompage_setTemplateWithPage', 
						array("TRUE",'setTemplateWithPage','diy','edit html or css',$EnameId,$templateId,$styleId,
								__FILE__,__LINE__), 'custompage');
					if(! $this->setTemplatedata($templateId, $templateStatusConf['waitaudit'][0], $templateName, $ucid, 
						$seoid, $statType, $statId, $adType, $adId, $enamecode, $enametype))
						return false;
					if(! $this->setTemplate($styleId, $templateStatusConf['waitaudit'][0], $htmlCode, $cssCode))
						return false;
				}
				
				return array('update'=> false,'msg'=> '修改模板成功,请等待审核！');
			}
			else
			{
				Logger::write('custompage_setTemplateWithPage', 
					array("TRUE",'setTemplateWithPage','diy','not edit html or css',$EnameId,$templateId,$styleId,
							__FILE__,__LINE__), 'custompage');
				
				// 只改数据模板，未改样式模板，则不需要审核
				if(! $this->setTemplatedata($templateId, $status, $templateName, $ucid, $seoid, $statType, $statId, 
					$adType, $adId, $enamecode, $enametype))
					return false;
					
					// 重新生成海外出售页
					// $this->reCreatePageByTemplate( $templateId, $EnameId);
				
				return array('update'=> true,'msg'=> '修改模板成功');
			}
		}
		else
		{
			Logger::write('custompage_setTemplateWithPage', 
				array("TRUE",'setTemplateWithPage','system',$EnameId,$templateId,$styleId,__FILE__,__LINE__), 
				'custompage');
			// 系统模板，未改样式模板，则不需要审核
			if(! $this->setTemplatedata($templateId, $status, $templateName, $ucid, $seoid, $statType, $statId, $adType, 
				$adId, $enamecode, $enametype))
				return false;
				
				// 重新生成海外出售页
				// $this->reCreatePageByTemplate($templateId, $EnameId);
			
			return array('update'=> true,'msg'=> '修改模板成功');
		}
	}

	/**
	 * 修改模板时，判断这模板是不是临时模板
	 * 是的话就直接编辑，不生成新模板
	 */
	public function checkIsTempTemplate($linkId)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$CSTsdk = new ModelBase('template_data');
		return $CSTsdk->count(array('Status'=> $templateStatusConf['edit'][0],'LinkId'=> $linkId), 'TemplateDId');
	}

	public function addStyleTemplate($EnameId, $templateName, $htmlCode, $cssCode)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		$CSTsdk = new ModelBase('template_style');
		$templateId = $CSTsdk->insert(
			array('EnameId'=> $EnameId,'TemplateName'=> $templateName,'Html'=> $htmlCode,'Css'=> $cssCode,
					'CreateTime'=> time(),'Status'=> $templateStatusConf['waitaudit'][0]));
		return $templateId;
	}

	public function addDataTemplate($EnameId, $templateName, $styleTemplateId, $templateType, $ucid, $statType, $statId, 
		$adType, $adId, $seoid, $enameCode, $enameType)
	{
		$CSTsdk = new ModelBase('template_data');
		$statusConf = \core\Config::item('page_template_status')->toArray();
		$styleConf = \core\Config::item('page_template_style')->toArray();
		if($templateType == $styleConf['diy'][0])
		{
			$status = $statusConf['waitaudit'][0];
		}
		else
		{
			$status = $statusConf['normal'][0];
		}
		$insert = array('EnameId'=> $EnameId,'StyleId'=> $styleTemplateId,'TemplateName'=> $templateName,
				'TemplateType'=> $templateType,'StatType'=> $statType,'StatId'=> $statId,'AdType'=> $adType,
				'AdId'=> $adId,'CreateTime'=> time(),'Ucid'=> $ucid,'Seoid'=> $seoid,'Status'=> $status,
				'enameAdSolt'=> $enameCode,'enameType'=> $enameType);
		Logger::write('custompage_addTemplate', array('addTemplate',json_encode($insert),__FILE__,__LINE__), 
			'custompage');
		return $CSTsdk->insert($insert);
	}

	public function setOldTemplatedata($templateId, $linkId, $status)
	{
		$CSTsdk = new ModelBase('template_data');
		$update = array('LinkId'=> $linkId,'Status'=> $status,'UpdateTime'=> time());
		return $CSTsdk->update($update, array('TemplateDId'=> $templateId));
	}

	public function setOldTemplate($templateId, $status)
	{
		$CSTsdk = new ModelBase('template_style');
		$update = array('Status'=> $status,'UpdateTime'=> time());
		return $CSTsdk->update($update, array('TemplateId'=> $templateId));
	}

	public function setTemplatedata($templateId, $status, $templateName, $ucid, $seoid, $statType, $statId, $adType, 
		$adId, $enamecode, $enametype, $linkId = 0)
	{
		$CSTsdk = new ModelBase('template_data');
		$update = array('Status'=> $status,'TemplateName'=> $templateName,'Ucid'=> $ucid,'Seoid'=> $seoid,
				'StatType'=> $statType,'StatId'=> $statId,'AdType'=> $adType,'AdId'=> $adId,'UpdateTime'=> time(),
				'enameType'=> $enametype,'enameAdSolt'=> $enamecode,'LinkId'=> $linkId);
		$res = $CSTsdk->update($update, array('TemplateDId'=> $templateId));
		return $res;
	}
	// 自动推广选择展示页更新展示页
	public function setTemplatedataEcode($templateId, $enamecode, $enametype = 2)
	{
		$CSTsdk = new ModelBase('template_data');
		$update = array('UpdateTime'=> time(),'enameType'=> $enametype,'enameAdSolt'=> $enamecode);
		return $CSTsdk->update($update, array('TemplateDId'=> $templateId));
	}

	public function setTemplate($styleId, $status, $htmlcode, $cssCode)
	{
		$CSTsdk = new ModelBase('template_style');
		$update = array('Status'=> $status,'Css'=> $cssCode,'Html'=> $htmlcode,'UpdateTime'=> time());
		return $CSTsdk->update($update, array('TemplateId'=> $styleId));
	}

	public function reCreatePageByTemplate($templateId, $EnameId)
	{
		$CDsdk = new ModelBase('custompage_domain');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		$regConf = \core\Config::item('page_domain_reg')->toArray();
		$domains = $this->getPageDomainByTemplateId($templateId, array($statusConf['success'][0]));
		if($domains)
		{
			foreach($domains as $domain)
			{
				$domain = (array)$domain;
				// 检查域名是否还属于用户
				if($domain['Reg'] == $regConf['inename'][0])
				{
					if($this->getDomainForUser($domain['DomainName'], $EnameId))
					{
						$result = $this->pageRetry($domain['CustompageDId'], $EnameId, $domain['DomainName']);
						Logger::write('custompage_reCreatePageByTemplate', 
							array($result['result']? 'TRUE': 'FALSE','setTemplate',$templateId,$EnameId,
									$domain['DomainName'],$domain['CustompageDId'],json_encode($result),__FILE__,
									__LINE__), 'custompage');
					}
					else
					{
						Logger::write('custompage_reCreatePageByTemplate', 
							array('FALSE','setTemplate','NotUserDomain',$templateId,$EnameId,$domain['DomainName'],
									__FILE__,__LINE__), 'custompage');
					}
				}
			}
		}
		return true;
	}

	public function delTemplate($templateId, $styleId, $templateType)
	{
		$templateStatusConf = \core\Config::item('page_template_status')->toArray();
		if($this->setTemplatedataStatus($templateId, $templateStatusConf['del'][0]))
		{
			$styleConf = \core\Config::item('page_template_style')->toArray();
			// 如果不系统风格模板，则同时要修改样式模板
			if($templateType != $styleConf['system'][0])
			{
				if(! $this->setTemplateStatus($styleId, $templateStatusConf['del'][0]))
					return false;
					// 如果是临时模板，则要把原模板改回正常状态
				$oldInfo = $this->getTemplateByLinkId($templateId);
				if($oldInfo)
				{
					if(! $this->setTemplatedataStatus($oldInfo['TemplateDId'], $templateStatusConf['normal'][0]))
						return false;
					if(! $this->setTemplateStatus($oldInfo['StyleId'], $templateStatusConf['normal'][0]))
						return false;
				}
			}
			return true;
		}
		else
			return false;
	}

	public function setTemplatedataStatus($templateId, $status)
	{
		$CSTsdk = new ModelBase('template_data');
		$update = array('Status'=> $status,'UpdateTime'=> time());
		return $CSTsdk->update($update, array('TemplateDId'=> $templateId));
	}

	public function setTemplateStatus($templateId, $status)
	{
		$CSTsdk = new ModelBase('template_style');
		$update = array('Status'=> $status,'UpdateTime'=> time());
		return $CSTsdk->update($update, array('TemplateId'=> $templateId));
	}

	public function getTemplateByLinkId($linkId)
	{
		$CSTsdk = new ModelBase('template_data');
		$data = $CSTsdk->getData('TemplateDId,StyleId', array('LinkId'=> $linkId,'Status'=> 3), $CSTsdk::FETCH_ROW);
		if($data)
		{
			return (array)$data;
		}
		return false;
	}

	public function getPageDomainCountByTemplateId($templateId)
	{
		$CSTsdk = new ModelBase('custompage_domain');
		$statusConf = \core\Config::item('page_domain_status')->toArray();
		return $CSTsdk->count(array('TemplateDId'=> $templateId,'Status'=> array('<',$statusConf['del'][0])), 
			'CustompageDId');
	}
}