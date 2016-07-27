<?php
class CommonController extends ControllerBase
{

	public function errorAction()
	{}

	public function successAction()
	{}
	public function headerAction()
	{}
	public function headerNavAction()
	{
		
	}
	public function footerAction()
	{
		
	}
	public function show404Action(){
		$this->view->render('common', '404');
	}
	public function urlFailAction(){
		$this->view->render('common', 'urlFail');
	}
}