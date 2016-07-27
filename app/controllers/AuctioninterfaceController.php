<?php
use logic\auctioninterface\AuctionInterface;
use core\Logger;
class AuctioninterfaceController extends ControllerBase
{
	protected $logic;
	
	protected $ip;
	
	public function initialize()
	{
		parent::initialize();
		$this->logic = new AuctionInterface();
		$this->ip = \common\Client::getClientIp(0);
	}
	/**
	 * 修改或审核模板时，更新海外展示页
	 */
	public function updatePageDomainByTplAction()
	{
		try
		{
			Logger::write("custompage_INTERFACE", array("updatePageDomainByTpl", $this->ip , __LINE__, __FILE__));
			if(in_array($this->ip, array('127.0.0.1','192.168.10.234','192.168.20.75','117.25.143.37')) === false)
			{
				$this->ajaxReturn(array('ServiceCode' => '1001','msg' => '非法访问'));
			}
				$templateid = intval($this->getQuery('templateid'));
				$templatetype = intval($this->getQuery('templatetype'));
				$this->logic->updatePageDomainByTpl($templateid,$templatetype);
				$success = array('ServiceCode' => '1000','msg' => '更新展示页成功');
				$this->ajaxReturn($success);
		}
		catch(\Exception $e)
		{
			$error = array('ServiceCode' => $e->getCode(),'msg' => $e->getMessage());
			$this->ajaxReturn($error);
		}
	}
}