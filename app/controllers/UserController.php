<?php
use \logic\user\User;
use core\Page;
use core\Config;

class UserController extends ControllerBase
{

	private $logic;

	private $enameId;

	public function initialize()
	{
		parent::initialize();
		$this->enameId = \WebVisitor::checkLogin();
		$this->logic = new User($this->enameId);
	}

	public function financeAction()
	{
		try
		{
			$start = intval($this->getQuery('limit_start'));
			$data = $this->logic->getFinance($start);
			$this->view->setVar('data', $data);
			$this->view->render('user', 'finance');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function orderDetailAction()
	{
		try
		{
			$startDate = $this->input->filterXss($this->getQuery('startDate'));
			$endDate = $this->input->filterXss($this->getQuery('endDate'));
			$orderType = intval($this->getQuery('OrderType'));
			$start = intval($this->getQuery('limit_start'));
			$topic = intval($this->getQuery('topic'));
			$data = $this->logic->getOrderDetail($startDate, $endDate, $orderType, $start, $topic);
			$this->view->setVar('data', $data);
			$this->view->setVar('OrderType', \core\Config::item('OrderType')->toArray());
			$this->view->render('user', 'orderDetail');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 申请转出列表
	 */
	public function turnOutAction()
	{
		try
		{
			$startDate = $this->input->filterXss($this->getQuery('startDate'));
			$endDate = $this->input->filterXss($this->getQuery('endDate'));
			$start = intval($this->getQuery('limit_start'));
			$data = $this->logic->getFinanceRecord($startDate, $endDate, $start);
			$this->view->setVar('data', $data);
			$this->view->render('user', 'turnOut');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 域名分销转出
	 */
	public function doTurnOutAction()
	{
		try
		{
			$price = floatval($this->getPost('price'));
			$data = $this->logic->doTurnOut($price);
			$this->ajaxReturn($data);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 信息列表
	 */
	public function messageAction()
	{
		try
		{
			$status = $this->getQuery('status');
			$pageNum = intval($this->getQuery('limit_start'));
			list($isEmpty, $messageList, $count) = $this->logic->getUserMessage($status, $pageNum, 
				Config::item('cpagesize'));
			$page = new Page($count, Config::item('cpagesize'));
			$isLast = $page->nowPage == $page->pageCount? 1: 0;
			$pageLink = $page->show();
			$this->view->setVars(
				array('isEmpty'=> $isEmpty,'messageList'=> $messageList,'pageLink'=> $pageLink,'title'=> '消息通知',
						'isLast'=> $isLast));
			$this->view->render('user', 'message');
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 修改站内信状态 标记为已读|回收站
	 */
	public function editMsgAction()
	{
		try
		{
			if($this->isPost() == TRUE)
			{
				$messageId = $this->getPost('messageId');
				$messageId = is_array($messageId)? $messageId: (array)$messageId;
				$status = $this->getPost('status');
				$flag = $this->logic->setMsgStatus($messageId, $status);
				
				$this->ajaxReturn(array('status'=> $flag));
			}
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function exportOrderDetailAction()
	{
		try
		{
			$startDate = $this->input->filterXss($this->getQuery('startDate'));
			$endDate = $this->input->filterXss($this->getQuery('endDate'));
			$orderType = intval($this->getQuery('OrderType'));
			$topic = intval($this->getQuery('topic'));
			$this->logic->exportOrderDetail($this, $startDate, $endDate, $orderType, $topic);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	public function changeIsAgreeAction()
	{
		if($this->isAjax())
		{
			$status = intval($this->getPost('status'));
			$rs = $this->logic->changeIsAgree($status);
			if($rs)
			{
				$this->ajaxReturn(array('status'=> 1));
			}
		}
	}

	public function guideOneAction($status)
	{
		// $status = intval($this->getQuery('status'));
		$status = intval($status);
		if(empty($status) || $status > 3)
		{
			$this->showError('非法请求');
		}
		$flag = $this->logic->setUserGuideStatus($status);
		if($status == 1)
		{
			$this->view->render('user', 'guideone');
		}
		elseif($status == 2)
		{
			$this->view->render('user', 'guidetwo');
		}
		else
		{
			$this->view->render('user', 'guidethree');
		}
	}

	/**
	 * 邮件退订
	 */
	public function unsubscribeAction()
	{
		try
		{
			if(TRUE == $this->isPost())
			{
				$type = $this->getPost('type');
				if(!$type)
				{
					$this->view->pick('common/404');
				}
				$unsubscribe = $this->getPost('unsubscribe');
				$this->logic->unsubscribeMsg($unsubscribe, $type);
			}
			$type = $this->getQuery('type');
			if(empty($type))
			{
				$this->view->pick('common/404');
			}
			$this->view->setVars(array('type'=>$type));
		}
		catch(Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}