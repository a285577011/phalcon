<?php
namespace es;

class DomainElasticSearch
{

	protected $client;

	protected $config;

	function __construct()
	{
		$this->config = \core\Config::item('elasticSearch');
		$this->client = \Elasticsearch\ClientBuilder::create()->setHosts(array($this->config['server']))
			->build();
	}

	/**
	 * ES淘域名搜索
	 *
	 * @param array $dn 域名 array(域名，后缀)
	 * @param int $enameId
	 * @param array|int $transType 交易类型 1：一口价 4：竞价(包含4,6,7,8) 5：竞价(预订竞价)
	 * 6：竞价(专题拍卖) 7：竞价(易拍易卖) 2:一口价(sedo) 8:竞价拍卖会
	 * @param array $class 分类 array(一级分类,二级分类,三级分类)
	 * @param array $length 域名长度（最小值， 最大值）
	 * @param array $price 域名价格
	 * @param array $endTime 结束时间
	 * @param array $sort 排序
	 * @param array $page(开始页，每页多少条)
	 * @return boolean multitype:NULL |multitype:number multitype:
	 */
	public function taoDomainData($dn, $enameId, $transType, $class, $length, $price, $endTime, $sort, $page)
	{
		$must = $notMust = $should = array();
		$must[] = array('term'=> array('t_status'=> 1));
		$enameId && $must[] = array('term'=> array('t_enameId'=> $enameId));
		if(is_array($dn) && array_filter($dn))
		{
			$dn[0] && $should[] = array("query_string"=> array("query"=> "t_body:*{$dn[0]}*"));
			$dn[1] && $should[] = array("term"=> array('t_tld'=> $dn[1]));
		}
		
		if(is_array($transType))
		{
			$must[] = array('terms'=> array("t_type"=> $transType));
		}
		elseif($transType)
		{
			$must[] = array("term"=> array("t_type"=> $type));
		}
		
		if(is_array($class) && (isset($class[0]) || isset($class[1]) || isset($class[2])))
		{
			$one = isset($class[0])? intval($class[0]): false;
			$two = isset($class[1])? intval($class[1]): false;
			$three = isset($class[2])? intval($class[2]): false;
			if($one)
			{
				$must[] = array('term'=> array('t_class_name'=> $class[0]));
			}
			if($two)
			{
				if(10 == $two)
				{
					$must[] = array("terms"=> array("t_two_class"=> array(10,12)));
				}
				elseif(2 == $two)
				{
					$must[] = array("terms"=> array("t_two_class"=> array(2,12)));
				}
				else
				{
					$two = 9999 ==$two ? 0 :$two;
					$must[] = array('term'=> array('t_two_class'=> $two));
				}
			}
			//特殊处理三级分类  新加了声母，非声母，CVCV
			if(is_array($three) && count($three))
			{
				foreach ($three as $k=>$v)
				{
					switch ($v)
					{
						case 5001:
							$exclude[] = array('a,i,o,v,u',0,0);
							unset($three[$k]);
							break;
						case 5002:
							$two = 9999;//非全声母 搜索条件 大类：2字母，长度3，，2级分类=0  因为在其他情况下t_two_class也是=0
							unset($three[$k]);
							break;
						case 5010:
							$two = 10;
							unset($three[$k]);
							break;
					}
				}
				if(!empty($three))
				{
					$must[] = array('terms'=> array('t_three_class'=> $three));
				}
			}
		}
		
		if(is_array($length))
		{
			$lenGte = isset($length[0]) && intval($length[0]) > 0? intval($length[0]): 1;
			$lenRange = array("range"=> array("t_len"=> array("gte"=> $lenGte)));
			if(intval($length[1]))
			{
				$lenRange['range']['t_len']['lte'] = $length[1];
			}
			$must[] = $lenRange;
		}
		
		if(is_array($price))
		{
			$range = array();
			intval($price[0]) && $range['gte'] = intval($price[0]);
			intval($price[1]) && $range['lte'] = intval($price[1]);
			! empty($range) && $must[] = array('range'=> array('t_now_price'=> $range));
		}
		
		if(is_array($endTime))
		{
			$endMust['range']['t_complate_time']['gte'] = $endTime[0]? $endTime[0]: time() - 61; // ES的数据1分钟更新一次，防止遗漏往前推61s
			$endTime[1] && $endMust['range']['t_complate_time']['lte'] = $endTime[1];
			$must[] = $endMust;
		}
		
		$arrayData = array("from"=> intval($page[0]),"size"=> intval($page[1]),
				"query"=> array(
						"filtered"=> array(
								"filter"=> array(
										"bool"=> array("must"=> $must,"must_not"=> $notMust,"should"=> $should)))));
		
		$order = isset($sort[1]) && $sort[1]? 'desc': 'asc';
		$orderField = isset($sort[0]) && $sort[0]? $sort[0]: 0;
		switch($orderField)
		{
			case 1:
				$sortMust = array('t_now_price'=> $order,'t_len'=> 'asc');
				break;
			case 2:
				$sortMust = array('t_complate_time'=> $order,'t_len'=> 'asc');
				break;
			default:
				$sortMust = array('t_complate_time'=> $order,'t_len'=> 'asc');
		}
		$arrayData['sort'] = $sortMust;
		$params = ['index'=> $this->config['index'],'type'=> $this->config['type'],'body'=> json_encode($arrayData)];
		$result = $this->client->search($params);
		if(isset($result['hits']))
		{
			$total = isset($result['hits']['total'])? $result['hits']['total']: false;
			if(false === $total)
			{
				return false;
			}
			else
			{
				return array('total'=> $total,'data'=> $result['hits']['hits']);
			}
		}
		return array('total'=> 0,'data'=> array());
	}

	public function getInfoByUser($enameId, $domain = '', $from = 0, $size = 1)
	{
		$must = $should = $notmust = array();
		$must[] = array('term'=> array('t_status'=> 1));
		$enameId && $must[] = array('term'=> array('t_enameId'=> $enameId));
		$must[] = array('terms'=>array('t_type'=>array(1,4,6,7,8)));
		if(is_array($domain) && isset($domain[0]) && isset($domain[1]))
		{
			$must[] = array('term'=> array("t_body"=>$domain[0]));
			$must[] = array('term'=>array('t_tld'=>$domain[1]));
		}
		$endMust['range']['t_complate_time']['gte'] = time() + \core\Config::item('lefttime') - 61; // ES的数据1分钟更新一次
		
		$arrayData = array("from"=> $from,"size"=> 333,
				"query"=> array(
						"filtered"=> array(
								"filter"=> array("bool"=> array("must"=> $must,"notmust"=> $notmust,"should"=> $should)))));
		$params = ['index'=> $this->config['index'],'type'=> $this->config['type'],'body'=> json_encode($arrayData)];
		$result = $this->client->search($params);
		if(isset($result['hits']))
		{
			$total = isset($result['hits']['total'])? $result['hits']['total']: false;
			if(false === $total)
			{
				return false;
			}
			else
			{
				return array('total'=> $total,'data'=> $result['hits']['hits']);
			}
		}
		return array('total'=> 0,'data'=> array());
	}

	public function getInfoById($id)
	{
		if(! $id)
		{
			return false;
		}
		$must[] = array("term"=> array('t_id'=> $id));
		$arrayData = array("from"=> 0,"size"=> 1,
				"query"=> array("filtered"=> array("filter"=> array("bool"=> array("must"=> $must)))));
		$params = ['index'=> $this->config['index'],'type'=> $this->config['type'],'body'=> json_encode($arrayData)];
		$result = $this->client->search($params);
		if(isset($result['hits']))
		{
			$total = isset($result['hits']['total'])? $result['hits']['total']: false;
			if(false === $total)
			{
				return false;
			}
			else
			{
				return array('total'=> $total,'data'=> $result['hits']['hits']);
			}
		}
		return array('total'=> 0,'data'=> array());
	}

	public function getInfoByTopic($topic, $time = 0, $from = 0, $size = 100)
	{
		$must = array();
		$must[] = array('term'=> array('t_topic'=> $topic));
		$must[] = array('term'=> array('t_status'=> 1));
		$must[] = array('range'=> array('t_complate_time'=> array('gte'=> $time)));
		
		$arrayData = array("from"=> $from,"size"=> $size,
				"query"=> array("filtered"=> array("filter"=> array("bool"=> array("must"=> $must)))));
		$params = ['index'=> $this->config['index'],'type'=> $this->config['type'],'body'=> json_encode($arrayData)];
		$result = $this->client->search($params);
		if(isset($result['hits']))
		{
			$total = isset($result['hits']['total'])? $result['hits']['total']: false;
			if(false === $total)
			{
				return false;
			}
			else
			{
				return array('total'=> $total,'data'=> $result['hits']['hits']);
			}
		}
		return array('total'=> 0,'data'=> array());
	}
}