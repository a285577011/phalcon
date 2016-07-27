<?php
use \logic\user\Faq;
use core\Page;
class FaqController extends ControllerBase
{

	private $logic;

	public function initialize()
	{
		parent::initialize();
		$this->logic = new Faq();
		$list = $this->logic->recommendList();
		$this->view->setVar('recommendList', $list);
	}

	public function indexAction()
	{
		try
		{
			if($this->isGet() == true)
			{
				$offset = intval($this->getQuery('limit_start'));
				$type = intval($this->getQuery('type'));
				$keyWord = $this->input->filterXss($this->getQuery('keyWord'));
				
				list($isEmpty, $data, $count) = $this->logic->getFaq($type, $keyWord, $offset, \core\Config::item('pagesize'));
				$page = new Page($count, \core\Config::item('pagesize'));
				$pageLink = $page->show();
				$pageLink = $count <= \core\Config::item('pagesize') ? '':$pageLink;
				
				$this->view->setVars(array('isEmpty'=> $isEmpty,'faqList'=> $data, 'pageLink' => $pageLink, 'title' => '帮助中心'));
				$this->view->render('faq', 'index');
			}
			else 
			{
				throw new Exception('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
	
	public function detailAction($id)
	{
		try
		{
			if($this->isGet() == true)
			{
				$details = $this->logic->detail($id);
				$this->view->setVars(array('detail' => $details, 'title' => '帮助中心'));
				$this->view->render('faq', 'detail');
			}
			else
			{
				throw new Exception('非法请求');
			}
		}
		catch(\Exception $e)
		{
			$this->showError($e->getMessage());
		}
	}
}