<?php
use Phalcon\CLI\Task;
use core\driver\Redis;
use core\ModelBase;
use core\EnameApi;
use core\Logger;
use logic\task\TaskLogic;
use \lib\custompage\CustomPageLib;

class SocketTask extends Task
{

	public function mainAction()
	{
		echo '一个socket任务';
	}

	/**
	 * 分销域名价格更新任务(竞价)
	 */
	public function domainJinJiaAction() // 定时更新域名
	{
		$this->updateAgentDomain(1);
		exit();
	}
	public function checkCnameAction($array)
	{
		\core\Logger::write('agent_socket_checkCname', array('start',date('Y-m-d H:i:s')));
		$lib = new CustomPageLib();
		\core\Logger::write('agent_socket_checkCname', array('start',$array[0],$array[1]));
		echo $lib->checkCname($array[0], $array[1]);
	}
}
?>