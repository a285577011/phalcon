<?php
if(!class_exists('SolrClient'))
{
	trigger_error('没装SolrClient扩展！');
	class SolrClient
	{

		public function ping()
		{
			throw new Exception('没装SolrClient扩展！');
		}
	}
}
class SolrBase
{

	private static $connections = array();

	private $solr;

	private $host;

	private $port;

	private $path;

	private $configClassName;

	public function __construct($configClassName, $isConnet = TRUE)
	{
		$this->configClassName = $configClassName;
		$this->solr = NULL;
		if($isConnet)
		{
			$this->connect();
		}
	}

	public final function connect()
	{
		if(isset(self::$connections[$this->configClassName]))
		{
			$this->solr = self::$connections[$this->configClassName];
			return;
		}
		
		$conf = new $this->configClassName();
		$this->path = $conf->geturl();
		$this->solr = new SolrClient(
			array('hostname' => $conf->gethost(),'port' => $conf->getport(),'path' => $conf->getpath()));
		try
		{
			$this->solr->ping();
		}
		catch(Exception $e)
		{
			$this->solr = NULL;
		}
		
		self::$connections[$this->configClassName] = $this->solr;
	}
	
	// 查询
	public function query($query)
	{
		return $this->solr->query($query);
	}
	// 添加、更新索引(已存在主键相同的则覆盖更新)
	public function addDocument($doc)
	{
		return $this->solr->addDocument($doc);
	}
	
	// 更新字段 $fields=array('id'=>'value','field'=>array('set'=>'value'))
	public function updateFieldsById($fields = array())
	{
		if(!is_array($fields) or empty($fields))
			return FALSE;
		
		$updateUrl = $this->path . '/update'; // 提交地址
		$data = '[' . json_encode($fields) . ']';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $updateUrl);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:application/json","Content-length: " . strlen($data)));
		$r = curl_exec($ch);
		if(!$r)
		{
			throw new Exception(curl_error($ch));
		}
		;
		curl_close($ch);
		$result = json_decode($r);
		if(0 == $result->responseHeader->status)
			return TRUE;
		else
			return FALSE;
	}
	
	// 删除索引
	public function deleteByQuery($query)
	{
		return $this->solr->deleteByQuery($query);
	}
	
	// 提交更改
	public function commit()
	{
		return $this->solr->commit();
	}
	
	// 移除表达式字符 如果整个关键字都是表达式字符返回FALSE
	public function removeCharacter($str)
	{
		$str2 = trim(preg_replace("/[^0-9a-zA-Z\x{4e00}-\x{9fa5}\.@_\-', ]/u", "", $str));
		if('' === $str2 and trim($str) !== '')
			return FALSE;
		return $str2;
	}
	
	// 转为solr时间查询格式
	public function dateFormat($date)
	{
		if($t = strtotime($date))
		{
			return date('Y-m-d\TH:i:s\Z', $t);
		}
		else
		{
			return FALSE;
		}
	}
	
	// 看solr服务器能否ping通
	public function ping()
	{
		if(is_null($this->solr))
			return FALSE;
		else
			return TRUE;
	}

	public final function close()
	{
		$this->solr = NULL;
	}

	public function __destruct()
	{
		$this->close();
	}
}
