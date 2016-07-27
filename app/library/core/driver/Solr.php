<?php

/**
 * solr基类
 */
namespace core\driver;

class Solr
{

	/**
	 * solr客户端对象
	 * 
	 * @var object
	 */
	private $solr = NULL;

	/**
	 * url请求地址
	 * 
	 * @var string
	 */
	private $url;

	/**
	 * 配置对象
	 * 
	 * @var object
	 */
	private $config;

	/**
	 * 构造函数
	 * 
	 * @param object 配置对象
	 */
	public function __construct($config)
	{
		// 配置对象
		$this->config = $config;
		
		// url地址
		$this->url = $this->getUrl();
		
		// 连接solr服务器
		$this->connect();
	}

	/**
	 * solr服务器连接
	 * 
	 * @return void
	 */
	public final function connect()
	{
		// 创建solr客户端
		$this->solr = new \SolrClient(
			array('hostname'=> $this->config->host,'port'=> $this->config->port,'path'=> $this->config->path));
		try
		{
			// 检查是否连接
			$this->solr->ping();
		}
		catch(\Exception $e)
		{
			$this->solr = NULL;
		}
	}

	/**
	 * 获取url地址
	 * 
	 * @return string url地址
	 */
	protected function getUrl()
	{
		return "http://{$this->config->host}:{$this->config->port}{$this->config->path}";
	}

	/**
	 * 更新字段
	 * 
	 * @param array 字段数组
	 * @return boolean 是否更新成功
	 */
	public function updateFieldsById($fields = array())
	{
		if(! is_array($fields) or empty($fields))
		{
			return FALSE;
		}
		
		// 提交地址
		$updateUrl = $this->url . '/update';
		$data = '[' . json_encode($fields) . ']';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $updateUrl);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:application/json","Content-length: " . strlen($data)));
		$res = curl_exec($ch);
		if(! $res)
		{
			throw new Exception(curl_error($ch));
		}
		curl_close($ch);
		
		$result = json_decode($r);
		return ($result->responseHeader->status == 0)? TRUE: FALSE;
	}

	/**
	 * 移除表达式字符
	 * 
	 * @param string 表达式字符
	 * @return string,boolean 如果整个关键字都是表达式字符返回FALSE
	 */
	public function removeCharacter($str)
	{
		$str2 = trim(preg_replace("/[^0-9a-zA-Z\x{4e00}-\x{9fa5}\.@_\-', ]/u", "", $str));
		if('' === $str2 and trim($str) !== '')
		{
			return FALSE;
		}
		
		return $str2;
	}

	/**
	 * 转为solr时间查询格式
	 * 
	 * @param string Y-m-d H:i:s
	 * @return string,boolean
	 */
	public function dateFormat($date)
	{
		if($date)
		{
			return date('Y-m-d\TH:i:s\Z', $date);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * 看solr服务器能否ping通
	 * 
	 * @return boolean
	 */
	public function ping()
	{
		return is_null($this->solr)? FALSE: TRUE;
	}

	/**
	 * 删除索引
	 * 
	 * @param string 索引
	 * @return object SolrUpdateResponse对象
	 */
	public function deleteByQuery($query)
	{
		return $this->solr->deleteByQuery($query);
	}

	/**
	 * 提交更改
	 * 
	 * @return object SolrUpdateResponse对象
	 */
	public function commit()
	{
		return $this->solr->commit();
	}

	/**
	 * 执行solr查询
	 * 
	 * @param object SolrQuery对象
	 * @return object SolrQueryResponse对象
	 */
	public function query($query)
	{
		return $this->solr->query($query);
	}

	/**
	 * 添加、更新索引(已存在主键相同的则覆盖更新)
	 * 
	 * @param object SolrInputDocument对象
	 * @return object SolrUpdateResponse对象
	 */
	public function addDocument($doc)
	{
		return $this->solr->addDocument($doc);
	}

	/**
	 * 关闭连接
	 * 
	 * @return void
	 */
	public final function close()
	{
		$this->solr = NULL;
	}

	/**
	 * 析构函数
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
	}
}
