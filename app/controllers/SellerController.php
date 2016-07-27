<?php
use core\Config;
use logic\agent\AgentManager;
use core\Page;

class SellerController extends ControllerBase
{

	/**
	 * 逻辑层
	 *
	 * @var logic\agent\AgentManager
	 */
	protected $logic;

	/**
	 * 分页大小
	 *
	 * @var int
	 */
	private $pageSize;

	/**
	 * 登录用户id
	 *
	 * @var int
	 */
	protected $enameId;

	public function initialize()
	{
		parent::initialize();
		$this->enameId = WebVisitor::checkLogin();
		if(! \lib\user\UserLib::getUserStatus($this->enameId, 2))
		{
			echo '<script language="javascript">parent.location.href = "' . $this->url->get('user/guideOne/2') .
				 '";</script>';
			exit();
		}
		$this->logic = new AgentManager($this->enameId);
		$this->pageSize = Config::item('pagesize');
	}

	/**
	 * 淘域名搜索
	 *
	 * @throws Exception
	 */
	public function searchAction()
	{
		try
		{
			if($this->isGet() == TRUE)
			{
				// 搜索条件
				$tld = intval($this->getQuery('domaintld')); // 返回类似"24,26"这样的字符串
				$offset = intval($this->getQuery('limit_start'));
				$transType = intval($this->getQuery('transtype'));
				$finishTime = intval($this->getQuery('finishtime'));
				$priceStart = intval($this->getQuery('pricestart'));
				$priceEnd = intval($this->getQuery('priceend'));
				$domainGroup = intval($this->getQuery('domaingroup'));
				$sort = $this->input->filterXss($this->getQuery('sort'));
				$domain = $this->input->filterXss($this->getQuery('domainname'));
				
				list($isEmpty, $domainList, $count, $orderList, $error) = $this->logic->getSolrData($domain, $sort, $tld, 
					$transType, $finishTime, $domainGroup, $priceStart, $priceEnd, $offset, $this->pageSize);
				// 分页链接
				$page = new Page($count, $this->pageSize);
				$pageLink = $page->show();
				
				// 渲染视图
				$this->view->setVars(
					array('domainList'=> $domainList,'pageLink'=> $pageLink,'isEmpty'=> $isEmpty,'order'=> $orderList,
							'title'=> '所有域名', 'error' => $error));
				$this->view->render('seller', 'search');
			}
			else
			{
				throw new Exception('非法请求！');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 设置域名分销
	 */
	public function agentAction()
	{
		try
		{
			if($this->isAjax() == TRUE)
			{
				$domainName = (array)$this->getPost('param');
				$isAgree = intval($this->getPost('agreement'));
				$domain = $this->input->filterXss($domainName);
				$percent = floatval($this->getPost('percent'));
				$data = $this->logic->setAgent($domain, $percent, $isAgree);
				$this->ajaxReturn($data);
			}
			else
			{
				throw new Exception('非法请求！');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 显示店铺信息
	 */
	public function shopAction()
	{
		try
		{
			$data = $this->logic->getShopData();
			$data['title'] = '发店铺';
			$this->view->setVars($data);
			$this->view->render('seller', 'shop');
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 设置|修改店铺分销
	 */
	public function shopAgentAction()
	{
		try
		{
			if($this->isAjax() == true)
			{
				$flag = $this->logic->isOpen();
				$this->ajaxReturn(array('flag'=> $flag));
			}
			
			$percent = intval($this->getPost('percent'));
			$finishDate = $this->input->filterXss($this->getPost('endDate'));
			$id = intval($this->getPost('id'));
			$isAgree = intval($this->getPost('agreement'));
			$flag = $this->logic->setShopAgent($percent, $finishDate, $isAgree, $id);
			if($flag == 1)
			{
				$this->response->redirect('seller/shop');
			}
			elseif($flag == 2)
			{
				$this->view->setVar('status', 2);
				$this->view->render('seller', 'shop');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 修改店铺
	 *
	 * @throws Exception
	 */
	public function editShopAction()
	{
		try
		{
			$shopAgentId = intval($this->getPost('id'));
			if($this->isAjax() == true)
			{
				$shopInfo = $this->logic->getShopInfo($shopAgentId);
				$this->ajaxReturn($shopInfo);
			}
			else
			{
				$percent = intval($this->getPost('percent'));
				$finishDate = $this->input->filterXss($this->getPost('endDate'));
				$flag = $this->logic->editShop($shopAgentId, $percent, $finishDate);
				if(! $flag)
				{
					throw new Exception('超过规定时间，不可以修改/删除！');
				}
				else
				{
					return $this->response->redirect('seller/shop');
				}
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 检测店铺是否推广
	 */
	public function checkAction()
	{
		try
		{
			if($this->isAjax() == true)
			{
				$id = $this->getPost('id');
				$data = $this->logic->checkShop($id);
				$this->ajaxReturn($data);
			}
			else
			{
				throw new Exception('非法请求！');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 删店铺分销
	 */
	public function deleteShopAction()
	{
		try
		{
			if($this->isAjax() == TRUE)
			{
				$shopAgentId = intval($this->getPost('agentId'));
				$data = $this->logic->deleteShop($shopAgentId);
				$this->ajaxReturn($data);
			}
			else
			{
				throw new Exception('非法请求！');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 已设置分销列表
	 */
	public function agentedAction()
	{
		try
		{
			if($this->isGet() == true)
			{
				$param = $this->getQuery();
				$param['sort'] = $this->input->filterXss($this->getQuery('sort'));
				$param['domainname'] = $this->input->filterXss($this->getQuery('domainname'));
				list($data, $isEmpty, $count, $order) = $this->logic->getAgentedList(array_filter($param), 
					$this->pageSize);
				$page = new Page($count, $this->pageSize);
				$isLast = $page->pageCount == $page->nowPage? 1: 0;
				$pageLink = $page->show();
				$this->view->setVars(
					array('domainList'=> $data,'isEmpty'=> $isEmpty,'pageLink'=> $pageLink,'order'=> $order,
							'title'=> '已设置分销','isLast'=> $isLast));
				$this->view->render('seller', 'agented');
			}
			else
			{
				throw new Exception('非法请求');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 域名详情页
	 *
	 * @param string $domainName
	 * @throws Exception
	 */
	public function detailAction($domainName, $domainAgentId)
	{
		try
		{
			$details = $this->logic->getDomainDetail($domainName, $domainAgentId);
			$details['domainName'] = $domainName;
			$details['title'] = '域名详情';
			$this->view->setVars($details);
			$this->view->render('seller', 'details');
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 已售出域名列表页
	 *
	 * @throws Exception
	 */
	public function soldAction()
	{
		try
		{
			if($this->isGet() == true)
			{
				$param = $this->getQuery();
				$param['sort'] = $this->input->filterXss($this->getQuery('sort'));
				$param['domainname'] = $this->input->filterXss($this->getQuery('domainname'));
				list($data, $isEmpty, $count, $order) = $this->logic->getSoldDomain(array_filter($param), 
					$this->pageSize);
				$page = new Page($count, $this->pageSize);
				$pageLink = $page->show();
				$this->view->setVars(
					array('domainList'=> $data,'isEmpty'=> $isEmpty,'pageLink'=> $pageLink,'order'=> $order,
							'title'=> '已售出'));
				$this->view->render('seller', 'sold');
			}
			else
			{
				throw new Exception('非法请求');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 修改分销比例
	 *
	 * @throws Exception
	 */
	public function editAction()
	{
		try
		{
			if($this->isAjax() == TRUE)
			{
				$msg = '';
				$percent = floatval($this->getPost('percent'));
				$domainAgentId = $this->getPost('param');
				list($flag, $data) = $this->logic->editPercent($domainAgentId, $percent);
				if($flag == 2)
				{
					isset($data[1]) && $msg .= implode(', ', $data[1]) . '已生效，无法修改或删除！';
					isset($data[2]) && $msg .= implode(', ', $data[2]) . '修改失败！';
				}
				$this->ajaxReturn(array('flag'=> $flag,'msg'=> $msg));
			}
			else
			{
				throw new Exception('非法请求');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 删除域名分销
	 *
	 * @throws Exception
	 */
	public function deleteAction()
	{
		try
		{
			if($this->isAjax() == TRUE)
			{
				$domainAgentId = $this->getPost('domainAgentId');
				$msg = '';
				list($flag, $data) = $this->logic->deletePercent($domainAgentId);
				if($flag == 2)
				{
					isset($data[1]) && $msg .= implode(', ', $data[1]) . '已生效，无法修改或删除！';
					isset($data[2]) && $msg .= implode(', ', $data[2]) . '删除失败！';
				}
				$this->ajaxReturn(array('flag'=> $flag,'msg'=> $msg));
			}
			else
			{
				throw new Exception('非法请求');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 未设置分销列表
	 */
	public function unsetedAction()
	{
		try
		{
			if($this->isGet() == true)
			{
				$param = $this->getQuery();
				$param['domainname'] = $this->input->filterXss($this->getQuery('domainname'));
				$param['sort'] = $this->input->filterXss($this->getQuery('sort'));
				list($data, $isEmpty, $count, $order,$error) = $this->logic->getUnset(array_filter($param), $this->pageSize);
				$page = new Page($count, $this->pageSize);
				$pageLink = $page->show();
				$this->view->setVars(
					array('domainList'=> $data,'isEmpty'=> $isEmpty,'pageLink'=> $pageLink,'order'=> $order,
							'title'=> '未设置分销', 'error' => $error));
				$this->view->render('seller', 'unseted');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 更新已设置分销记录
	 *
	 * @throws Exception
	 */
	public function updateAction()
	{
		try
		{
			if($this->isAjax() == true)
			{
				$id = intval($this->getPost('id'));
				$data = $this->logic->getInfoById($id);
				$this->ajaxReturn($data);
			}
			else
			{
				throw new Exception('非法请求！');
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}