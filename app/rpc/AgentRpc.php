<?php
use logic\agent\AgentGuests;

class AgentRpc extends Base
{

	public function __construct($di)
	{
		$this->logic = new AgentGuests();
		parent::__construct($di);
	}

	/**
	 * rpc demo for domain
	 */
	public function index()
	{}

	public function makeAgentUrl($transId,$enameId)
	{
		try
		{
			$data = $this->logic->makeAgentUrlById($transId,$enameId);
			return $this->output($data);
		}
		catch(\Exception $e)
		{
			return $this->output($e->getMessage(), $e->getCode());
		}
	}
}