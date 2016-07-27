<?php

use logic\index\Index;
class IndexController extends ControllerBase
{
	
	/**
	 * 首页
	 */
	public function indexAction()
	{
		$logic = new Index();
		$data = $logic->TopList();
		$this->view->setVars($data);
		$this->view->render('index', 'index');
	}
	
	/**
	 * 专题页一
	 */
	public function topicAction()
	{
		$this->view->render('index', 'topic');
	}
	
	/**
	 * 专题页二
	 */	
	public function topic2Action()
	{
		$this->view->render('index', 'topic2');
	}
}