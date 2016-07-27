<?php
namespace Test;

/**
 * Class UnitTest
 */
class UnitTest extends \UnitTestCase
{

	public function testTestCase()
	{
		$this->assertEquals('works', 'works', 'This is OK');
		
		$this->assertEquals('works', 'works1', 'This wil fail');
	}
}