<?php
namespace Test;
use \core\ModelBase;

class PlatformTest extends \UnitTestCase
{

	private $logic;

	private static $id;

	private static $otherId;

	protected $enameId = 505863;

	function __construct()
	{
		$this->logic = new \logic\agent\Platform($this->enameId);
	}

	public function testAddPlatform()
	{
		$pftable = new \table\PlatformTable();
		$pftable->ClassType = 1;
		$pftable->Description = 'aaaaa';
		$pftable->EnameId = $this->enameId;
		$pftable->Message = '';
		$pftable->Name = '奇葩网';
		$pftable->Url = 'www.ename.com';
		unset($pftable->PlatformId);
		self::$id = $this->logic->addPlatform($pftable->Name,$pftable->Url,$pftable->ClassType,$pftable->Description,1);
		$this->assertTrue((boolean)self::$id, '插入一条自有网站失败');
		self::$otherId = $this->logic->addPlatform($pftable->Name,'',$pftable->ClassType,$pftable->Description,2);
		$this->assertTrue((boolean)self::$otherId, '插入一条第三方平台失败');
	}
	public function testgetPlatformList(){
		$data = $this->logic->getPlatformList('奇葩', 0, 1);
		$this->assertNotEmpty($data, '获取自有网站失败');
		$res = $this->logic->getPlatformList('奇葩', 0, 2);
		$this->assertNotEmpty($res, '获取自有网站失败');
	}
	public function testgetUpdatePlatform(){
		$data = $this->logic->updatePlatform('aaa','www.huang.com',2,'bbbb',self::$id);
		$this->assertTrue((boolean)$data, '更新平台失败');
	}
	public function testGetSiteById(){
		$data = $this->logic->getSiteById(self::$id);
		$this->assertNotEmpty((boolean)$data, '$data');
	}
	public function testCheckName()
	{
		$res=$this->logic->checkName('奇葩网');
		$this->assertTrue((boolean)$res, '不存在相同名字');
	}
	public function testDeletePlatformById()
	{
		 $id=$this->logic->deletePlatformById(self::$id);
		 $other=$this->logic->deletePlatformById(self::$otherId);
		 $this->assertTrue((boolean)$id, '删除网站失败');
		 $this->assertTrue((boolean)$other, '删除其他平台失败');
	}
	
}