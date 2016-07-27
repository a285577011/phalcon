<?php
namespace Test;
use \core\ModelBase;

class AgentguestsTest extends \UnitTestCase
{

	private $logic;

	private static $id;

	private static $shopId;

	protected $enameId = 505863;

	function __construct()
	{
		$this->logic = new \logic\agent\AgentGuests($this->enameId);
	}

	public static function add()
	{
		$agtable = new \table\DomainAgentTable();
		$agtable->ClickNum = 1;
		$agtable->CreateTime = time();
		$agtable->DomainLen = 1;
		$agtable->DomainName = 'huangyy.com';
		$agtable->EnameId = 505863;
		$agtable->FinishTime = strtotime("+1 week");
		$agtable->GroupOne = 1;
		$agtable->GroupTwo = 100;
		$agtable->Percent = 90;
		$agtable->Price = 1000;
		$agtable->TLD = 6;
		$agtable->UpdateTime = time();
		$agtable->TransType = 1;
		$agtable->TransId = 0;
		$agtable->SimpleDec = '';
		unset($agtable->DomainAgentId);
		$model = new ModelBase('domain_agent');
		self::$id = $model->insert((array)$agtable);
	}

	public static function addShop()
	{
		$sgtable = new \table\ShopAgentTable();
		$sgtable->CreateTime = time();
		$sgtable->Credit = 99;
		$sgtable->DomainNum = 11;
		$sgtable->EnameId = 505863;
		$sgtable->GoodRating = 99;
		$sgtable->Logo = '';
		$sgtable->Name = 'huang';
		$sgtable->Notice = 'a';
		$sgtable->Percent = 12;
		$sgtable->UpdateTime = time()-86400;
		unset($sgtable->ShopAgeId);
		$model = new ModelBase('shop_agent');
		self::$shopId = $model->insert((array)$sgtable);
	}

	public function testGetDomainAgentList()
	{
		self::add();
		$data = $this->logic->getDomainAgentList("huang", 2, 12, 1, 1001, 2, 1, 1, 100, 0, 'DomainAgentId-desc');
		$this->assertNotEmpty($data['list'], '无记录');
	}

	public function testGetShopAgentList()
	{
		self::addShop();
		$data = $this->logic->getShopAgentList("huang", 1, 101, 1, 1001, 1, 20, 0, 'DomainAgentId-desc');
		$this->assertNotEmpty($data['list'], '无记录');
	}

	public function testCheckDomain()
	{
		$data = $this->logic->checkDomain(2, 12, 1, 1001, 2, 1, 1, 100, 0, array(0,4));
		$this->assertTrue((boolean)$data, '无筛选域名');
	}

	public function testGetAutoAgentList()
	{
		$data = $this->logic->getAutoAgentList(6, 12, 1, 1001, 2, 1, 1, 100, 0, 1, 1, array(0,4));
		$this->assertNotEmpty($data, '自动分销失败');
	}

	public function testSpreadAgent()
	{
		$data = $this->logic->spreadAgent(array(self::$id), 1, 10, 3, 1);
		$this->assertNotEmpty($data, '推广分销失败');
	}

	public function testUpdatePosById()
	{
		$data = $this->logic->updatePosById(1, 1, 1);z
		$this->assertNotEmpty($data, '推广分销失败');
	}

	public function __destruct()
	{
		$shopmodel = new ModelBase('shop_agent');
		$model = new ModelBase('domain_agent');
		$shopmodel->delete(array('ShopAgeId'=> self::$shopId));
	$model->delete(array('DomainAgentId'=> self::$id));
	}
}
