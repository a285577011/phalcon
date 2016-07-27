<?php
use \logic\agent\AgentGuests;
use core\Logger;
use core\driver\Redis;
use core\ModelBase;

class AgentguestsController extends ControllerBase
{

	protected $logic;

	protected $enameId;

	public function initialize()
	{
		parent::initialize();
		$this->enameId = WebVisitor::checkLogin();
		if(! \lib\user\UserLib::getUserStatus($this->enameId, 3))
		{
			echo '<script language="javascript">parent.location.href = "' . $this->url->get('user/guideOne/3') .
				 '";</script>';
			exit();
		}
		$this->logic = new AgentGuests($this->enameId);
	}

	/**
	 * 分销域名列表
	 */
	public function domainAgentAction()
	{
		if($this->isGet() == true)
		{
			$domainName = $this->input->filterXss($this->getQuery('DomainName'));
			$tld = $this->getQuery('Tld');
			$topic = intval($this->getQuery('topic'));
			$finishTime = intval($this->getQuery('FinishTime'));
			$startPrice = floatval($this->getQuery('StartPrice'));
			$endPrice = floatval($this->getQuery('EndPrice'));
			$group = intval($this->getQuery('Group'));
			$transType = intval($this->getQuery('transType'));
			$startCommission = floatval($this->getQuery('StartCommission'));
			$endCommission = floatval($this->getQuery('EndCommission'));
			$start = intval($this->getQuery('limit_start'));
			$sort = $this->input->filterXss($this->getQuery('sort'));
			$data = $this->logic->getDomainAgentList($domainName, $tld, $finishTime, $startPrice, $endPrice, $group, 
				$transType, $startCommission, $endCommission, $start, $sort, $topic);
			$this->view->setVar('data', $data);
			$this->view->render('agentguests', 'domainAgent');
		}
		else
		{
			throw new \Exception('非法请求');
		}
	}
	/**
	 * 店铺分销列表
	 *
	 * @throws \Exception
	 */
	public function shopAgentAction()
	{
		try
		{
			if($this->isGet() == true)
			{
				$shopName = $this->input->filterXss($this->getQuery('ShopName'));
				$startCredit = floatval($this->getQuery('startCredit'));
				$endCredit = floatval($this->getQuery('endCredit'));
				$starGoodRating = floatval($this->getQuery('startGoodRating'));
				$endGoodRating = floatval($this->getQuery('endGoodRating'));
				$startCommission = floatval($this->getQuery('StartCommission'));
				$endCommission = floatval($this->getQuery('EndCommission'));
				$sort = $this->input->filterXss($this->getQuery('sort'));
				$start = intval($this->getQuery('limit_start'));
				$data = $this->logic->getShopAgentList($shopName, $startCredit, $endCredit, $starGoodRating, 
					$endGoodRating, $startCommission, $endCommission, $start, $sort);
				$this->view->setVar('data', $data);
				$this->view->render('agentguests', 'shopAgent');
			}
			else
			{
				throw new \Exception('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 自动分销
	 *
	 * @throws \Exception
	 */
	public function autoAgentIndexAction()
	{
		try
		{
			$tld = \core\Config::item('tld')->toArray();
			$finishTime = \core\Config::item('finishtime')->toArray();
			$group = \core\Config::item('domaingroup')->toArray();
			$this->view->setVar('tld', $tld);
			$this->view->setVar('finishTime', $finishTime);
			$this->view->setVar('group', $group);
			$this->view->render('agentguests', 'autoAgentIndex');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 自动分销筛选域名
	 *
	 * @throws \Exception
	 */
	public function autoAgentFirstAction()
	{
		try
		{
			if($this->isPost() == true)
			{
				$tld = $this->getPost('Tld');
				$finishTime = intval($this->getPost('FinishTime'));
				$startPrice = floatval($this->getPost('StartPrice'));
				$endPrice = floatval($this->getPost('EndPrice'));
				$group = intval($this->getPost('Group'));
				$transType = intval($this->getPost('transType'));
				$startCommission = intval($this->getPost('StartCommission'));
				$endCommission = intval($this->getPost('EndCommission'));
				$res = $this->logic->checkDomain($tld, $finishTime, $startPrice, $endPrice, $group, $transType, 
					$startCommission, $endCommission, array(0,5));
				if(! $res)
				{
					if($this->isAjax())
					{
						$this->ajaxReturn(array('status'=> - 1));
					}
					throw new \Exception('无筛选结果,请重新筛选');
				}
				if($this->isAjax())
				{
					$this->ajaxReturn(array('status'=> 1));
				}
				$this->view->setVar('tld', @implode(',', $tld));
				$this->view->setVar('finishTime', $finishTime);
				$this->view->setVar('startPrice', $startPrice);
				$this->view->setVar('endPrice', $endPrice);
				$this->view->setVar('transType', $transType);
				$this->view->setVar('group', $group);
				$this->view->setVar('endCommission', $endCommission);
				$this->view->setVar('startCommission', $startCommission);
				$this->view->render('agentguests', 'autoAgentFirst');
			}
			else
			{
				throw new \Exception('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 自动分选择平台并生成JS代码
	 *
	 * @throws \Exception
	 */
	public function autoAgentSecondAction()
	{
		try
		{
			if($this->isPost() == true)
			{
				$tld = $this->input->filterXss($this->getPost('Tld'));
				$finishTime = intval($this->getPost('FinishTime'));
				$startPrice = floatval($this->getPost('StartPrice'));
				$endPrice = floatval($this->getPost('EndPrice'));
				$group = intval($this->getPost('Group'));
				$transType = intval($this->getPost('transType'));
				$startCommission = intval($this->getPost('StartCommission'));
				$endCommission = intval($this->getPost('EndCommission'));
				$PlatformId = intval($this->getPost('PlatformId'));
				$PlatformType = intval($this->getPost('PlatformType'));
				$StyleId = intval($this->getPost('StyleId'));
				$TemplateDId = intval($this->getPost('TemplateDId'));
				$Agreement = intval($this->getPost('Agreement'));
				$data = $this->logic->getAutoAgentList($Agreement, $tld, $finishTime, $startPrice, $endPrice, $group, 
					$transType, $startCommission, $endCommission, $PlatformId, $PlatformType, $StyleId, $TemplateDId);
				if($this->request->isAjax())
				{
					$this->ajaxReturn($data);
				}
				else
				{
					$this->view->setVar('jscode', $data);
					$this->view->render('agentguests', 'autoAgentSecond');
				}
			}
			else
			{
				throw new \Exception('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 推广域名,店铺
	 *
	 * @throws \Exception
	 */
	public function spreadAgentAction()
	{
		try
		{
			if($this->isPost() == true)
			{
				$AgentId = $this->request->getPost('AgentId');
				$AgentType = intval($this->getPost('AgentType'));
				$PlatformId = intval($this->getPost('PlatformId'));
				$PlatformType = intval($this->getPost('PlatformType'));
				$StyleId = intval($this->getPost('StyleId'));
				$Agreement = intval($this->getPost('Agreement'));
				$data = $this->logic->spreadAgent($Agreement, $AgentId, $AgentType, $PlatformId, $PlatformType, 
					$StyleId);
				$this->ajaxReturn($data);
			}
			elseif($this->isGet() == true)
			{
				$AgentId = $this->getQuery('AgentId');
				$AgentType = intval($this->getQuery('AgentType'));
				$PlatformId = intval($this->getQuery('PlatformId'));
				$PlatformType = intval($this->getQuery('PlatformType'));
				$StyleId = intval($this->getQuery('StyleId'));
				$Agreement = intval($this->getQuery('Agreement'));
				$data = $this->logic->spreadAgent($Agreement, $AgentId, $AgentType, $PlatformId, $PlatformType, 
					$StyleId, true);
				$this->ajaxReturn($data);
			}
			else
			{
				throw new \Exception('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * ajax获取广告数据
	 */
	public function getAdInfoAction()
	{
		try
		{
			$AgentId = $this->request->get('AgentId');
			$AgentType = intval($this->getPost('AgentType'));
			$StyleId = intval($this->getPost('StyleId'));
			$PlatformType = intval($this->getPost('PlatformType'));
			$sort = $this->input->filterXss($this->getQuery('sort'));
			$data = $this->logic->getAdInfo($AgentId, $AgentType, $StyleId, $PlatformType);
			$this->ajaxReturn($data);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 推广域名历史
	 */
	public function spreadDomainAction()
	{
		try
		{
			
			$start = intval($this->getQuery('limit_start'));
			$startDate = $this->input->filterXss($this->getQuery('startDate'));
			$endDate = $this->input->filterXss($this->getQuery('endDate'));
			$startCommission = floatval($this->getQuery('StartCommission'));
			$endCommission = floatval($this->getQuery('EndCommission'));
			$domainName = $this->input->filterXss($this->getQuery('DomainName'));
			$sort = $this->input->filterXss($this->getQuery('sort'));
			$status = intval($this->getQuery('status'));
			$topic = intval($this->getQuery('topic'));
			$PlatformType = intval($this->getQuery('PlatformType'));
			$data = $this->logic->spreadDomain($start, $startDate, $endDate, $startCommission, $endCommission, 
				$domainName, $sort, $status, $PlatformType, $topic);
			$this->view->setVar('data', $data);
			$this->view->render('agentguests', 'spreadDomain');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 推广详情历史
	 */
	public function spreadDetailAction()
	{
		try
		{
			if($this->isAjax())
			{
				// $start = intval($this->getQuery('limit_start'));
				$startDate = $this->input->filterXss($this->getPost('startDate'));
				$endDate = $this->input->filterXss($this->getPost('endDate'));
				$startCommission = floatval($this->getPost('StartCommission'));
				$endCommission = floatval($this->getPost('EndCommission'));
				$domainName = $this->input->filterXss($this->getPost('Name'));
				$sort = $this->input->filterXss($this->getPost('sort'));
				$status = intval($this->getPost('status'));
				$PlatformType = intval($this->getPost('PlatformType'));
				$agentType = intval($this->getPost('agentType'));
				$topic = intval($this->getPost('topic'));
				$data = $this->logic->spreadDetail($startDate, $endDate, $startCommission, $endCommission, $domainName, 
					$sort, $status, $PlatformType, $agentType, $topic);
				$this->ajaxReturn($data);
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 推广店铺历史
	 */
	public function spreadShopAction()
	{
		try
		{
			$start = intval($this->getQuery('limit_start'));
			$startDate = $this->input->filterXss($this->getQuery('startDate'));
			$endDate = $this->input->filterXss($this->getQuery('endDate'));
			$startCommission = floatval($this->getQuery('StartCommission'));
			$endCommission = floatval($this->getQuery('EndCommission'));
			$Name = $this->input->filterXss($this->getQuery('Name'));
			$sort = $this->input->filterXss($this->getQuery('sort'));
			$status = intval($this->getQuery('status'));
			$PlatformType = intval($this->getQuery('PlatformType'));
			$data = $this->logic->spreadShop($start, $startDate, $endDate, $startCommission, $endCommission, $Name, 
				$sort, $status, $PlatformType);
			$this->view->setVar('data', $data);
			$this->view->render('agentguests', 'spreadShop');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 渠道统计
	 */
	public function platformStatisticsAction()
	{
		try
		{
			$start = intval($this->getQuery('limit_start'));
			$startDate = $this->input->filterXss($this->getQuery('startDate'));
			$endDate = $this->input->filterXss($this->getQuery('endDate'));
			$type = intval($this->getQuery('type'));
			$name = $this->input->filterXss($this->getQuery('name'));
			// $sort = $this->input->filterXss($this->getQuery('sort'));
			$pageSize = intval($this->getQuery('pageSize'));
			$data = $this->logic->platformStatistics($start, $startDate, $endDate, $type, $name);
			$this->view->setVar('data', $data);
			$this->view->render('agentguests', 'platformStatistics');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}