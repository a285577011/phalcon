<?php
namespace Test;
use \core\ModelBase;
use \logic\common\Common;
class CommonTest extends \UnitTestCase
{


	public function testAddScore()
	{
		Common::addScore(505863, 100, '卖菜赚的');
	}	
	
}
