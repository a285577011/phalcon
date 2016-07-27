<?php
use logic\custompage\CustomPage;
use core\Logger;
class CustompageController extends ControllerBase
{

	protected $logic;

	protected $enameId;

	public function initialize()
	{
		parent::initialize();
		$this->enameId =  WebVisitor::checkLogin();
		$this->logic = new CustomPage($this->enameId);
	}

	/**
	 * 展示页域名列表
	 */
	public function indexAction()
	{
		if(!\lib\user\UserLib::getUserStatus($this->enameId,1))
		{
			echo '<script language="javascript">parent.location.href = "'.$this->url->get('user/guideOne/1').'";</script>';
			exit();
		}
		try
		{
			if($this->request->isGet() == true)
			{
				$data = array();
				$data['templateId'] = intval($this->getQuery('templateId'));
				$data['domainName'] = $this->input->filterXss($this->getQuery('domainName'));
				$data['status'] = intval($this->getQuery('status'));
				$data['transInfo'] = $this->input->filterXss($this->getQuery('transInfo'));
				$data['errowInfo'] = intval($this->getQuery('errowInfo'));
				$data['reger'] = intval($this->getQuery('reger'));
				$data['holdStatus'] = intval($this->getQuery('holdStatus'));
				$data['per_page'] = intval($this->getQuery('limit_start'));
				$return = $this->logic->getPageDomainList($data);
				$this->view->setVar('title', '展示页域名列表');
				$this->view->setVar('list', $return);
				$this->view->render('custompage', 'index');
			}
			else
			{
				$this->showError($e->getMessage('非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 展示页预览
	 */
	public function previewAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$id = intval($this->getQuery('id'));
				$data = $this->logic->custompagePreView($id);
				$this->view->setVar('html', $data);
				$this->view->render('custompage', 'pageview');
			}
		else
			{
				$this->showError($e->getMessage('非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 修改展示页
	 */
	public function setPageDomainAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$id = $this->input->filterXss($this->getQuery('id'));
				$data = $this->logic->setPageDomain($id);
				$this->view->setVar('list', $data['list']);
				$this->view->setVar('title', '修改展示页');
				$this->view->setVar('templates', $data['templates']);
				$this->view->setVar('sid', $data['sid']);
			}
			else
			{
				$this->showError($e->getMessage('非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 系統模板列表
	 *
	 * @throws \Exception
	 */
	public function customPageStyleAction()
	{
		if($this->request->isGet() == true)
		{
			$data = $this->logic->getCustomStyleList();
			$this->view->setVar('list', $data['list']);
		}
		else
		{
			throw new \Exception('非法请求');
		}
	}

	/**
	 * 默认展示页模板预览
	 */
	public function pageViewAction()
	{
		try
		{
			$templateid = intval($this->getQuery('templateid'));
			$data = $this->logic->pageView($templateid);
			$this->view->setVar('html', $data['html']);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function addTemplateAction()
	{
		try
		{
			$templateid = intval($this->getQuery('templateid'));
			$type = intval($this->getQuery('type'));
			$data = $this->logic->addTemplate($type, $templateid);
			$this->view->setVar('title', '添加模板');
			$this->view->setVar('html', $data);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function doAddtemplateAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$data = array();
				$data['ename_csrf'] = $this->input->filterXss($this->getPost('ename_csrf'));
				$data['templateName'] = $this->input->filterXss($this->getPost('templateName'));
				$data['adType'] = intval($this->getPost('adType'));
				$data['pubid'] = $this->input->filterXss($this->getPost('pubid'));
				$data['adId'] = intval($this->getPost('adId'));
				$data['slotid'] = $this->input->filterXss($this->getPost('slotid'));
				$data['ucid'] = intval($this->getPost('ucid'));
				$data['seoid'] = intval($this->getPost('seoid'));
				$data['statType'] = intval($this->getPost('statType'));
				$data['statId'] = intval($this->getPost('statId'));
				$data['enameType'] = intval($this->getPost('enameType'));
				$data['enameCode'] = intval($this->getPost('enameCode'));
				$data['adheight'] = intval($this->getPost('adheight'));
				$data['adwidth'] = intval($this->getPost('adwidth'));
				$data['type'] = intval($this->getPost('type'));
				$data['templateId'] = intval($this->getPost('templateId'));
				$htmlcode = $this->getPost('htmlCode') != ''? $this->getPost('htmlCode'): '';
				$csscode = $this->getPost('cssCode') !=''? $this->getPost('cssCode'): '';
				$msg = $this->logic->doAddTemplate($data, $htmlcode, $csscode);
				echo json_encode(array('result'=> true,'msg'=> $msg));
			}
			else
			{
				echo json_encode(array('result'=> false,'msg'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			echo json_encode(array('result'=> false,'msg'=> str_replace("<br/>", "\n", $e->getMessage())));
		}
	}
	/*
	 * 添加编辑模板浏览
	 */
	public function modelViewAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$data = array();
				$data['templateName'] = $this->input->filterXss($this->getPost('templateName'));
				$data['adType'] = intval($this->getPost('adType'));
				$data['pubid'] = $this->input->filterXss($this->getPost('pubid'));
				$data['adId'] = intval($this->getPost('adId'));
				$data['slotid'] = $this->input->filterXss($this->getPost('slotid'));
				$data['ucid'] = intval($this->getPost('Ucid'));
				$data['seoid'] = intval($this->getPost('Seoid'));
				$data['statType'] = intval($this->getPost('statType'));
				$data['statId'] = intval($this->getPost('statId'));
				$data['enameType'] = intval($this->getPost('enameType'));
				$data['enameCode'] = $this->input->filterXss($this->getPost('enameCode'));
				$data['adheight'] = intval($this->getPost('adheight'));
				$data['adwidth'] = intval($this->getPost('adwidth'));
				$data['type'] = intval($this->getPost('typeid'));
				$data['templateId'] = intval($this->getPost('templateid'));
				$data['dataid'] = intval($this->getPost('dataid'));
				$htmlcode = $this->getPost('htmlCode') !='' ? $this->getPost('htmlCode'): '';
				$csscode = $this->getPost('cssCode') != ''? $this->getPost('cssCode'): '';
				$data = $this->logic->viewTemplate($data, $htmlcode, $csscode);
				$this->view->setVar('html', $data);
			}
		else
			{
				$this->showError('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
	public function settemplateAction()
	{
		try
		{
			if($this->request->isget() == true)
			{
				$id = intval($this->getQuery('id'));
				$data = $this->logic->setTemplate($id);
				$this->view->setVar('html', $data);
			}
		else
			{
				$this->showError('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
	
	public function dosettemplateAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$data = array();
				$data['ename_csrf'] = $this->input->filterXss($this->getPost('ename_csrf'));
				$data['templateName'] = $this->input->filterXss($this->getPost('templateName'));
				$data['adType'] = intval($this->getPost('adType'));
				$data['pubid'] = $this->input->filterXss($this->getPost('pubid'));
				$data['adId'] = intval($this->getPost('adId'));
				$data['slotid'] = $this->input->filterXss($this->getPost('slotid'));
				$data['ucid'] = intval($this->getPost('ucid'));
				$data['seoid'] = intval($this->getPost('seoid'));
				$data['statType'] = intval($this->getPost('statType'));
				$data['statId'] = intval($this->getPost('statId'));
				$data['adheight'] = intval($this->getPost('adheight'));
				$data['adwidth'] = intval($this->getPost('adwidth'));
				$data['type'] = intval($this->getPost('type'));
				$data['templateId'] = intval($this->getPost('templateId'));
				$data['dataid'] = intval($this->getPost('dataid'));
				$htmlcode = $this->getPost('htmlCode') != ''? $this->getPost('htmlCode'): '';
				$csscode = $this->getPost('cssCode') !='' ? $this->getPost('cssCode'): '';
				$data['enameCode'] = $this->input->filterXss($this->getPost('enameCode'));
				$data['enameType'] = intval($this->getPost('enameType'));
				$msg = $this->logic->doSetTemplate($data, $htmlcode, $csscode,2);
				echo json_encode(array('result'=> true,'msg'=> $msg));
			}
			else
			{
				echo json_encode(array('result'=> false,'msg'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			echo json_encode(array('result'=> false,'msg'=> str_replace("<br/>", "\n", $e->getMessage())));
		}
	}
	
	// update to ajax 2014-6-11
	public function deltemplateAction()
	{
		try
		{
			if($this->request->isget() == true)
			{
				$id = intval($this->getQuery('id'));
				$data = $this->logic->delTemplate($id);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=>false,'error'=>'非法请求'));
			}
		}
		catch(Exception $e)
		{
			$this->ajaxReturn(array('flag'=>false,'error'=>$e->getMessage()));
		}

	}

	/**
	 * 展示页模板列表
	 */
	public function templateListAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$perPage = intval($this->getQuery('limit_start'));
				$templateName = $this->input->filterXss($this->getQuery('templateName'));
				$templateType = intval($this->getQuery('templateType'));
				$status = intval($this->getQuery('status'));
				$data = $this->logic->getTemplateList($templateName, $templateType, $status, $perPage);
				$this->view->setVar('html', $data);
			}
			
		else
			{
				$this->showError('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 获取用户系统模板foragent  ONLEY SID
	 */
	public function getSystemTemplateAction()
	{
		if($this->request->isGet() == true)
		{
			$data = $this->logic->getSystemTemplateList();
			$this->ajaxReturn($data);
		}
		else
		{
			echo json_encode(array('flag'=> false,'msg'=> '非法请求'));
		}
	}
	/**
	 * 根据系统模板ID 获取用户对应模板
	 */
	public function getTemplateBySidAction()
	{
		if($this->request->isGet() == true)
		{
			$sid = intval($this->getQuery('styleid'));
			$data = $this->logic->getSystemTemplateListBysid($sid);
			$this->ajaxReturn($data);
		}
		else
		{
			echo json_encode(array('flag'=> false,'msg'=> '非法请求'));
		}
	}
	//用户选择展示页自动推广
	public function autoAgentForUserAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$templateid = intval($this->getQuery('templateid'));
				$codeid = intval($this->getQuery('codeid'));
				$data = $this->logic->autoAgent($codeid, $templateid);
				$this->ajaxReturn($data);
			}
			else
			{
				 $this->ajaxReturn(array('flag'=>false,'msg'=>'添加推广成功'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=>false,'msg'=>$e->getMessage()));
		}
	}

	/**
	 * 添加域名展示页
	 */
	public function addShowpageAction()
	{}

	public function addPagedomainAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$domainname = $this->input->filterXss($this->getPost('domainName'));
 				Logger::write("custompage_addpagedomain",
				array('AddPageDomain',"================Start=================="),'custompage');
				$data = $this->logic->addPageDomain($domainname);
								Logger::write( "custompage_addpagedomain",
				array('AddPageDomain',"================End=================="),'custompage');
				if(isset($data['msg']))
				{
					$this->showError($data['msg'], $data['url']);
				}
				$this->view->setVar('domains', $data['domains']);
				$this->view->setVar('decs', $data['decs']);
				$this->view->setVar('templates', $data['templates']);
				$this->view->setVar('sid', $data['sid']);
				$this->view->setVar('messageConf', $data['messageConf']);
			}
			else
			{
				$this->showError('非法请求');
			}
		}
		catch(\Exception $e)
		{
			Logger::write('custompage_addpagedomain', array('AddPageDomain',"================End=================="),'custompage');
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 添加展示页(操作)
	 */
	public function doaddpagedomainAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$domainname = $this->input->filterXss($this->getPost('domainName'));
				$data = $this->logic->doAddPageDomain($this,$domainname);
				$this->view->setVar('success', $data['success']);
				$this->view->setVar('false', $data['false']);
				$this->view->setVar('url', $data['url']);
				$this->view->setVar('messageConfig', $data['messageConfig']);
				$this->view->render('custompage', 'addresult');
			}
		else
			{
				$this->showError('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage(),$this->url->get('custompage/addshowpage'));
		}
	}

	public function dosetpagedomainAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$domainname = $this->input->filterXss($this->getPost('domainName'));
				$data = $this->logic->doSetPageDomain($this,$domainname);
				$this->view->setVar('success', $data['success']);
				if(isset($data['failed']))
				{
					$this->view->setVar('false', $data['failed']);
				}
				else
				{
					$this->view->setVar('false', array());
				}
				$this->view->setVar('url', $data['url']);
				$this->view->render('custompage', 'result');
			}
		else
			{
				$this->showError($e->getMessage('非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function delPagedomainAction()
	{
		if($this->request->isGet() == true)
		{
			$id = $this->input->filterXss($this->getQuery('id'));
			$data = $this->logic->delPageDomain($this,$id);
			$this->view->setVar('success', $data['success']);
			$this->view->setVar('failed', $data['failed']);
			$this->view->render('custompage', 'delresult');
		}
		else
		{
			echo json_encode(array('flag'=> false,'error'=> '非法请求'));
		}
	}

	public function doretryAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$id = intval($this->getQuery('id'));
				$msg = $this->logic->retryPageDomain($id);
				$this->showSuccess($msg);
			}
		else
			{
				$this->showError('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function getContactAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$cid = intval($this->getPost('cid'));
				$data = $this->logic->getContactInfo($cid);
				echo json_encode($data);
			}
			else
			{
				echo json_encode(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			echo json_encode(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	public function addContactAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$username = $this->input->filterXss($this->getPost('username'));
				$email = $this->input->filterXss($this->getPost('email'));
				$qq = $this->input->filterXss($this->getPost('qq'));
				$desc = $this->input->filterXss($this->getPost('desc'));
				$avatar = $this->input->filterXss($this->getPost('avatar'));
				$cardname = $this->input->filterXss($this->getPost('cardname'));
				$phone = $this->input->filterXss($this->getPost('phone'));
				$data = $this->logic->addContact($username, $email, $qq, $desc, $avatar, $phone, $cardname);
				$this->ajaxReturn($data);
			}
			else
			{
				echo json_encode(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			echo json_encode(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	public function editContactAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$cid = intval($this->getPost('cid'));
				$username = $this->input->filterXss($this->getPost('username'));
				$email = $this->input->filterXss($this->getPost('email'));
				$qq = $this->input->filterXss($this->getPost('qq'));
				$desc = $this->input->filterXss($this->getPost('desc'));
				$avatar = $this->input->filterXss($this->getPost('avatar'));
				$cardname = $this->input->filterXss($this->getPost('cardname'));
				$phone = $this->input->filterXss($this->getPost('phone'));
				$data = $this->logic->editContact($cid, $username, $email, $qq, $desc, $avatar, $phone, $cardname);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	public function delContactAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$cid = intval($this->getQuery('cid'));
				$data = $this->logic->delContact($cid);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	/**
	 * 上传用户名片图片
	 */
	public function uploadAction()
	{
		if($this->request->hasFiles() == true)
		{
			$files = $this->request->getUploadedFiles();
			$data = $this->logic->upload($files);
			echo json_encode($data);
		}
		else
		{
			echo json_encode(array('flag'=> false,'error'=> '请选择上传的文件'));
		}
	}

	/**
	 * 获取用户SEO信息
	 * 
	 * @throws \Exception
	 */
	public function getSeoAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$sid = intval($this->getPost('sid'));
				$data = $this->logic->getSeoInfo($sid);
				echo json_encode($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			echo json_encode(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	public function addSeoAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$seoname = $this->input->filterXss($this->getPost('seoname'));
				$kword = $this->input->filterXss($this->getPost('kword'));
				$title = $this->input->filterXss($this->getPost('title'));
				$desc = $this->input->filterXss($this->getPost('desc'));
				$data = $this->logic->addSeo($seoname, $kword, $title, $desc);
				$this->ajaxReturn($data);
			}
			else
			{
				echo json_encode(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			echo json_encode(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	public function editSeoAction()
	{
		try
		{
			if($this->request->isPost() == true)
			{
				$sid = intval($this->getPost('sid'));
				$seoname = $this->input->filterXss($this->getPost('seoname'));
				$kword = $this->input->filterXss($this->getPost('kword'));
				$title = $this->input->filterXss($this->getPost('title'));
				$desc = $this->input->filterXss($this->getPost('desc'));
				$data = $this->logic->editSeo($sid, $seoname, $kword, $title, $desc);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}

	public function delSeoAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$sid = intval($this->getQuery('sid'));
				$data = $this->logic->delSeo($sid);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=> false,'error'=> '非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=> false,'error'=> $e->getMessage()));
		}
	}
	public function dataViewAction()
	{
		try
		{
			if($this->request->get() == true)
			{
				$id = intval($this->getQuery('id'));
				$data = $this->logic->dataview($id);
				$this->view->setVar('html', $data);
				$this->view->render('custompage', 'pageview');
			}
		else
			{
				$this->showError($e->getMessage('非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}
?>