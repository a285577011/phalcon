<?php
namespace Test;
use table\DomainAgentTable;
use logic\agent\AgentManager;

class AgentManagerTest extends \UnitTestCase
{

	const PAGE_SIZE = 30;

	/**
	 *
	 * @var AgentManager
	 */
	private $logic;

	/**
	 * 当前登录用户
	 *
	 * @var int
	 */
	private $enameId;

	public function __construct()
	{
		$this->enameId = 505863;
		$this->logic = new AgentManager($this->enameId);
	}

	/**
	 * 测试solr数据查询逻辑
	 */
// 	public function testGetSolrData()
// 	{
// 		$tld = 0; // 如果后缀为24,26类似这样的，返回字符串拼接的数组
// 		$offset = 0;
// 		$transType = 0; // 1竞价4一口价
// 		$finishTime = 0;
// 		$priceStart = 0;
// 		$priceEnd = 0;
// 		$domainGroup = 0;
// 		$sort = '';
// 		$domain = '';
// 		$pageSize = 30;
		
// 		list($isEmpty, $domainList, $domainData['numFound'], $orderList) = $this->logic->getSolrData($domain, $sort, 
// 			$tld, $transType, $finishTime, $domainGroup, $priceStart, $priceEnd, $offset, self::PAGE_SIZE);
// 		$this->assertFalse($isEmpty, '获取solr数据有误');
// 	}

	/**
	 * 设置域名分销逻辑测试用例
	 */
// 	public function testSetAgent()
// 	{
// 		$domain = array('orweb.com.cn','test.cn');
// 		$percent = 5;
// 		$data = $this->logic->setAgent($domain, $percent);
// 		$this->assertEquals(3, $data['flag'], '域名设置分销失败');
// 		return array($data['id'],$domain);
// 	}

	/**
	 * 修改分销比例测试用例
	 *
	 * @param array $data 分销id数组
	 * @depends testSetAgent
	 */
// 	public function testEditPercent($data)
// 	{
// 		$percent = 23;
// 		$agentId = $data[0];
// 		if(is_array($agentId))
// 		{
// 			foreach($agentId as $id)
// 			{
// 				$data = $this->logic->editPercent($id, $percent);
// 				$this->assertEquals(1, $data['status'], '修改分销比例失败');
// 			}
// 		}
// 		return $agentId;
// 	}

	/**
	 * 删除域名分销逻辑测试用例
	 *
	 * @param array $agentId 分销id数组
	 * @depends testEditPercent
	 */
// 	public function testDeletePercent($agentId)
// 	{
// 		if(is_array($agentId))
// 		{
// 			foreach($agentId as $id)
// 			{
// 				$data = $this->logic->deletePercent($id);
// 				$this->assertTrue($data['status'], '删除域名分销失败');
// 			}
// 		}
// 	}

	/**
	 * 测试店铺详细信息
	 */
	public function testGetShopData()
	{
		$shopData = $this->logic->getShopData();
		$this->assertEquals(1, $shopData['status'], '获取店铺信息失败');
	}

	/**
	 * 测试设置店铺分销比例
	 */
	public function testSetShopAgent()
	{
		$param = array('enameid'=> 505863,'percent'=> 5);
		$finishDate = date('Y-m-d', strtotime('+16 day'));
		list($flag, $id) = $this->logic->setShopAgent($param, $finishDate);
		$this->assertTrue($flag, '店铺设置分销失败');
		return $id;
	}
	
	/**
	 * 修改店铺分销测试用例
	 * 
	 * @param int $id
	 * @depends testSetShopAgent
	 */
	public function testEditShop($id)
	{
		$percent = 20;
		$flag = $this->logic->editShop($id, $percent);
	}
	
	/**
	 * 删除店铺分销测试用例
	 * 
	 * @param int $id
	 * @depends testSetShopAgent
	 */
	public function testDeleteShop($id)
	{
		$flag = $this->logic->deleteShop($id);
		$this->assertTrue($flag, '删除店铺分销失败');
	}

	/**
	 * 获取已设置分销列表测试用例
	 */
// 	public function testGetAgentedList()
// 	{
// 		$param = $this->searchProvider();
		
// 		list($data, $isEmpty, $count, $orderList) = $this->logic->getAgentedList($param, self::PAGE_SIZE);
		
// 		$this->assertFalse($isEmpty, '获取已分销列表失败');
// 	}

	/**
	 * 域名详情页测试用例
	 *
	 * @param array $data 域名数组
	 * @depends testSetAgent
	 * @depends testDeletePercent
	 */
// 	public function testGetDomainDetail($data)
// 	{
// 		$domainName = array_pop($data[1]);
// 		$data = $this->logic->getDomainDetail($domainName);
// 		$this->assertEquals(1, $data['status'], '查询域名详情失败');
// 	}

	/**
	 * 已售出域名列表逻辑层测试用例
	 */
// 	public function testGetSoldDomain()
// 	{
// 		$param = $this->searchProvider();
		
// 		list($data, $isEmpty, $count) = $this->logic->getSoldDomain($param, self::PAGE_SIZE);
// 		$this->assertFalse($isEmpty, '获取已售出域名失败');
// 	}

	/**
	 * 未设置分销域名列表逻辑层测试用例
	 */
// 	public function testGetUnset()
// 	{
// 		$param = $this->searchProvider();
		
// 		list($unset, $isEmpty, $num, $orderList) = $this->logic->getUnset($param, self::PAGE_SIZE);
// 		$this->assertFalse($isEmpty, '获取未设置分销列表失败');
// 	}

// 	public function searchProvider()
// 	{
// 		$param['domainname'] = '';
// 		$param['domaingroup'] = 0;
// 		$param['domaintld'] = 0;
// 		$param['transtype'] = 0;
// 		$param['pricestart'] = 0;
// 		$param['priceend'] = 0;
// 		$param['finishtime'] = 0;
// 		$param['percentstart'] = 0;
// 		$param['percentend'] = 0;
// 		$param['limit_start'] = 0;
		
// 		return array($param);
// 	}
}