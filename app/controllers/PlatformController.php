<?php
use \logic\agent\Platform;

class PlatformController extends ControllerBase
{

	protected $logic;

	protected $enameId;

	public function initialize()
	{
		parent::initialize();
		$this->enameId = WebVisitor::checkLogin();
		$this->logic = new Platform($this->enameId);
	}

	/**
	 * 添加网站(自有网站)
	 *
	 * @throws \Exception
	 */
	public function addSiteAction()
	{
		try
		{
			if($this->isPost() == true)
			{
				$siteName = $this->input->filterXss($this->request->getPost('siteName'));
				if($this->logic->checkName($siteName,1))
				{
					throw new \Exception('已经添加过该平台名字');
				}
				$site = $this->input->filterUrl($this->request->getPost('site'));
				$siteType = intval($this->request->getPost('siteType'));
				$decr = $this->input->filterXss($this->request->getPost('decr'));
				$id = $this->logic->addPlatform($siteName, $site, $siteType, $decr, 1);
				if(! $id)
				{
					throw new \Exception('系统繁忙');
				}
				$this->response->redirect('Platform/siteList');
			}
			else
			{
				$this->view->setVar('siteType', \core\Config::item('site_type'));
				$this->view->render('platform', 'addSite');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 用户自有网站列表
	 *
	 * @throws \Exception
	 */
	public function siteListAction()
	{
		try
		{
			$siteName = $this->input->filterXss($this->request->getQuery('siteName'));
			$start = intval($this->request->getQuery('limit_start'));
			$data = $this->logic->getPlatformList($siteName, $start, 1);
			$this->view->setVar('page', $data['page']);
			$this->view->setVar('list', $data['list']);
			$this->view->setVar('isLastPage', $data['isLastPage']);
			$this->view->render('platform', 'siteList');
		}
		
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 修改自有网站信息页
	 *
	 * @throws \Exception
	 */
	public function updateIndexAction()
	{
		try
		{
			$PlatformId = intval($this->request->getQuery('PlatformId'));
			$data = $this->logic->getSiteById($PlatformId);
			$this->view->setVar('siteType', \core\Config::item('site_type'));
			$this->view->setVar('data', $data);
			$this->view->render('platform', 'updateIndex');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 修改自有网站信息
	 *
	 * @throws \Exception
	 */
	public function updateSiteInfoAction()
	{
		try
		{
			$siteName = $this->input->filterXss($this->request->getPost('siteName'));
			$site = $this->input->filterXss($this->request->getPost('site'));
			$siteType = intval($this->request->getPost('siteType'));
			$decr = $this->input->filterXss($this->request->getPost('decr'));
			$PlatformId = intval($this->request->getPost('PlatformId'));
			$res = $this->logic->updatePlatform($siteName, $site, $siteType, $decr, $PlatformId);
			if(! $res)
			{
				throw new \Exception('请选择');
			}
			$this->response->redirect('Platform/siteList');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 添加其他平台
	 *
	 * @throws \Exception
	 */
	public function addOtherAction()
	{
		try
		{
			$Name = $this->input->filterXss($this->request->getPost('Name'));
			if($this->logic->checkName($Name,3))
			{
				throw new \Exception('已经添加过该平台名字');
			}
			$decr = $this->input->filterXss($this->request->getPost('decr'));
			$id = $this->logic->addPlatform($Name, '', '', $decr, 3);
			if(! $id)
			{
				throw new \Exception('系统繁忙');
			}
			$this->ajaxReturn($id);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 其他平台列表
	 *
	 * @throws \Exception
	 */
	public function otherListAction()
	{
		try
		{
			$Name = $this->input->filterXss($this->request->getQuery('siteName'));
			$start = intval($this->request->getQuery('limit_start'));
			$data = $this->logic->getPlatformList($Name, $start, 3);
			$this->view->setVar('page', $data['page']);
			$this->view->setVar('list', $data['list']);
			$this->view->setVar('isLastPage', $data['isLastPage']);
			$this->view->render('platform', 'otherList');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 更新其他平台
	 *
	 * @throws \Exception
	 */
	public function updateOtherInfoAction()
	{
		try
		{
			$Name = $this->input->filterXss($this->request->getPost('Name'));
			$decr = $this->input->filterXss($this->request->getPost('decr'));
			$PlatformId = intval($this->request->getPost('PlatformId'));
			$res = $this->logic->updatePlatform($Name, '', '', $decr, $PlatformId);
			$this->ajaxReturn($res);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 删除平台
	 *
	 * @throws \Exception
	 */
	public function deletePlatformAction()
	{
		try
		{
			$PlatformId = $this->request->get('PlatformId');
			$res = $this->logic->deletePlatformById($PlatformId);
			if(is_array($res))
			{
				$msg = implode(',', $res);
				$this->ajaxReturn(array('status'=> 1,'msg'=> $msg));
				// throw new \Exception($msg);
				// error
			}
			elseif($res)
			{
				$this->ajaxReturn(array('status'=> 1,'msg'=> '删除成功!'));
			}
			else
			{
				$this->ajaxReturn(array('status'=> - 1));
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 通过类型获取用户网站列表 ajax
	 *
	 * @throws \Exception
	 */
	public function getSiteByTypeAction()
	{
		try
		{
			$type = intval($this->request->getPost('PlatformType'));
			$data = $this->logic->getSite($type);
			$this->ajaxReturn($data);
		}
		
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 检查平台名字
	 */
	public function checkNameAction()
	{
		try
		{
			$Name = $this->input->filterXss($this->request->getPost('Name'));
			$type = intval($this->request->getPost('type'));
			$data = $this->logic->checkName($Name,$type);
			if($data)
			{
				$this->ajaxReturn(array('status'=> 1));
			}
			else
			{
				$this->ajaxReturn(array('status'=> - 1));
			}
		}
		
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}