<?php
use \logic\ad\Advert;

class AdvertController extends ControllerBase
{

	protected $logic;

	public function initialize()
	{
		parent::initialize();
		$this->logic = new Advert();
	}

	/**
	 * 广告统计次数
	 */
	public function clickAction()
	{
		try
		{
			$str = $this->input->filterXss($this->request->getQuery('c'));
			$this->logic->click($str,$this->dispatcher);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 获取广告数据 ajax
	 */
	public function getAdInfoAction()
	{
		try
		{
			$posId = intval($this->request->getQuery('posId'));
			$data = $this->logic->getAdInfo($posId);
			header('Access-Control-Allow-Origin:*');
			$this->ajaxReturn($data);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 预览广告数据 ajax
	 */
	public function reviewAdInfoAction()
	{
		try
		{
			$AgentId = $this->request->get('AgentId');
			$AgentType = intval($this->getPost('AgentType'));
			$StyleId = intval($this->getPost('StyleId'));
			$data = $this->logic->reviewAd($AgentId, $AgentType, $StyleId);
			$this->ajaxReturn($data);
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}

	/**
	 * 获取广告数据 PHP方式
	 */
	public function getAdInfoPhpAction()
	{
		try
		{
			$posId = intval($this->request->getQuery('posId'));
			$data = $this->logic->getAdInfo($posId);
			$this->view->setVar('data', $data);
			$this->view->setVar('posId', $posId);
			$this->view->render('advert', 'adByCss');
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}