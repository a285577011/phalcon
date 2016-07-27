<?php
namespace core;

class Socket
{

	public $ip;

	public $port;

	public $socket;

	function __construct($config = null)
	{
		try
		{
			$config or $config = \core\Config::item('socket');
			$this->ip = $config->ip;
			$this->port = $config->port;
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if($this->socket < 0)
			{
				\core\Logger::write('socket_error', 
					array("socket_create() failed: reason: " . socket_strerror($socket) . "\n"));
				throw new \Exception('ocket_create() failed');
			}
			if($rs = socket_connect($this->socket, $this->ip, $this->port) < 0)
			{
				\core\Logger::write('socket_error', 
					array("socket_connect() failed.\nReason: ($rs) " . socket_strerror($rs) . "\n"));
				throw new \Exception('socket_connect() failed');
			}
		}
		catch(\Exception $e)
		{
			echo $e->getMessage();
			exit();
		}
	}

	public function write($content)
	{
		try
		{
			if(! socket_write($this->socket, $content, strlen($content)))
			{
				\core\Logger::write('socket_error', 
					array("socket_write() failed: reason: " . socket_strerror($this->socket) . "\n"));
				throw new \Exception('socket_write() failed');
			}
		}
		catch(\Exception $e)
		{
			echo $e->getMessage();
			exit();
		}
		return true;
	}

	public function getDataArr()
	{
		$data = array();
		while($out = socket_read($this->socket, 88888))
		{
			$data[] = $out;
		}
		return $data;
	}

	public function close()
	{
		socket_close($this->socket);
	}
}