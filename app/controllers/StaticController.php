<?php
use logic\datastatic\DataStatic;


class StaticController extends ControllerBase
{

	protected $logic;
	
	protected $enameId;
	
	public function initialize()
	{
		parent::initialize();
		$this->enameId = WebVisitor::checkLogin();
		$this->logic = new  DataStatic($this->enameId);
	}
	
	/**
	 * 
	 *
	 * @throws \Exception
	 */
	public function farmerStaticAction()
	{
		if($this->request->isGet() == true)
		{
			$dgroup = intval($this->getQuery('dgroup'));
			$ttype = intval($this->getQuery('ttype'));
			$starttime = $this->input->filterXss($this->getQuery('starttime'));
			$endtime = $this->input->filterXss($this->getQuery('endtime'));
			$data = $this->logic->farmerStatic($dgroup,$ttype,$starttime,$endtime);
			$this->view->setVar('html', $data);
		}
		else
		{
			throw new \Exception('非法请求');
		}
	}
	public function dofarmStaticAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$dgroup = intval($this->getQuery('dgroup'));
				$ttype = intval($this->getQuery('ttype'));
				$starttime = $this->input->filterXss($this->getQuery('starttime'));
				$endtime = $this->input->filterXss($this->getQuery('endtime'));
				$data = $this->logic->ajaxFarmStatic($dgroup,$ttype,$starttime,$endtime);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=>false,'error'=>'非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=>false,'error'=>$e->getMessage()));
		}
	}
	public function exportFarmerAction()
	{
		try
		{
			$dgroup = intval($this->getQuery('dgroup'));
			$ttype = intval($this->getQuery('ttype'));
			$starttime = $this->input->filterXss($this->getQuery('starttime'));
			$endtime = $this->input->filterXss($this->getQuery('endtime'));
			$this->logic->exprotFarm($this,$dgroup,$ttype,$starttime,$endtime);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
		
	}
	/**
	 *
	 *
	 * @throws \Exception
	 */
	public function guestStaticAction()
	{
		if($this->request->isGet() == true)
		{
			$ptype = intval($this->getQuery('ptype'));
			$stype = intval($this->getQuery('stype'));
			$starttime = $this->input->filterXss($this->getQuery('starttime'));
			$endtime = $this->input->filterXss($this->getQuery('endtime'));
			$data = $this->logic->guestStatic($starttime ,$endtime ,$ptype ,$stype);
			$this->view->setVar('html', $data);
		}
		else
		{
			throw new \Exception('非法请求');
		}
	}
	public function doGuestStaticAction()
	{
		try
		{
			if($this->request->isGet() == true)
			{
				$ptype = intval($this->getQuery('ptype'));
				$stype = intval($this->getQuery('stype'));
				$starttime = $this->input->filterXss($this->getQuery('starttime'));
				$endtime = $this->input->filterXss($this->getQuery('endtime'));
				$data = $this->logic->ajaxGuestStatic($stype,$ptype,$starttime,$endtime);
				$this->ajaxReturn($data);
			}
			else
			{
				$this->ajaxReturn(array('flag'=>false,'error'=>'非法请求'));
			}
		}
		catch(\Exception $e)
		{
			$this->ajaxReturn(array('flag'=>false,'error'=>$e->getMessage()));
		}
	}
	public function exportGuestAction()
	{
		try
		{
			$dgroup = intval($this->getQuery('dgroup'));
			$stype = intval($this->getQuery('stype'));
			$ttype = intval($this->getQuery('ptype'));
			$starttime = $this->input->filterXss($this->getQuery('starttime'));
			$endtime = $this->input->filterXss($this->getQuery('endtime'));
			$this->logic->exprotGuest($this ,$stype,$dgroup,$ttype,$starttime,$endtime);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	
	}

}
?>