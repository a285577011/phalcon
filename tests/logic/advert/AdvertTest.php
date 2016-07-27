<?php
namespace Test;
use \core\ModelBase;

class Advert extends \UnitTestCase
{

	private $logic;

	private static $id;

	private static $shopId;

	protected $enameId = 505863;

	function __construct()
	{
		$this->logic = new \logic\ad\Advert($this->enameId);
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
		unset($agtable->DomainAgentId);
		$model = new ModelBase('domain_agent');
		self::$id = $model->insert((array)$agtable);
	}


	public function testmakePosAdUrl()
	{
		self::add();
		$data = $this->logic->creatSpreadCode(62);
		$this->assertNotEmpty($data, '无记录');
	}
	public function testGetAdInfo(){
		$data = $this->logic->getAdInfo(62);
		print_r($data);
		$this->assertNotEmpty($data, '无记录');
	}
	public function __destruct()
	{
	}
}
