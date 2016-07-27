<?php

/**
 * 退出登录控制器
 */
class LogoutController extends ControllerBase
{

	public function indexAction()
	{
		$backUrl = empty($_GET['backurl'])? $this->appConfig->develop->basePath: $_GET['backurl'];
		$logoutCode = WebVisitor::logout(urlencode($backUrl));
		exit();
	}

	public function callAction()
	{
		$logoutCode = \WebVisitor::callLogout();
		if($logoutCode === TRUE)
		{
			echo json_encode(array('status'=> '1'));
			exit();
		}
		echo json_encode(array('status'=> '0','errcode'=> $logoutCode));
		exit();
	}
}