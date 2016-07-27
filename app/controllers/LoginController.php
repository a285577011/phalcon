<?php
use core\ModelBase;
use \logic\common\Common;

/**
 * 登录控制器
 */
class LoginController extends ControllerBase
{

	public function initialize()
	{
		parent::initialize();
	}

	public function indexAction()
	{
		// 验证st，并跳回访问页面
		$backUrl = empty($_GET['backurl'])? '': $_GET['backurl'];
		$backUrl = urldecode($backUrl);
		$basurl = 'http://' . $_SERVER['HTTP_HOST'];
		$backUrl = empty($backUrl)? $basurl: $backUrl;
		$enameid = WebVisitor::forceLogin();
		if($enameid)
		{
			Common::initUserInfo($enameid);
			// 如果是iframe，则进行js处理
			if(isset($_GET['lg']) && $_GET['lg'] == 'iframe' || isset($_GET['ql']) && $_GET['ql'] == '1')
			{
				echo '<script language="javascript">parent.cas.reload();</script>';
				exit();
			}
			if($backUrl == $basurl)
			{
				header('Location: ' . $basurl);
				exit();
			}
			header('Location: ' . $backUrl);
		}
		exit();
	}
}