<?php
use logic\custompage\CustomPage;

class CustompageRpc extends Base
{

	public function __construct($di)
	{
		$this->logic = new CustomPage();
		parent::__construct($di);
	}

	/**
	 * rpc demo for domain
	 */
	public function index()
	{}

	public function countUnAudit()
	{
		try
		{
			$data = $this->logic->countUnAudit();
			return $this->output($data);
		}
		catch(\Exception $e)
		{
			return $this->output($e->getMessage(), $e->getCode());
		}
	}

	public function countAuditById($auditTime = false)
	{
		try
		{
			$data = $this->logic->countAuditById($auditTime);
			return $this->output($data);
		}
		catch(\Exception $e)
		{
			return $this->output($e->getMessage(), $e->getCode());
		}
	}

	public function setTemplate($cusDomainId)
	{
		try
		{
			$data = $this->logic->singleSetTemplate($cusDomainId);
			return $this->output($data);
		}
		catch(\Exception $e)
		{
			return $this->output($e->getMessage(), $e->getCode());
		}
	}
}