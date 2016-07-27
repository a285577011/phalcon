<?php
namespace common\form;

class FormException extends \Exception
{

	function __construct($message, $code)
	{
		parent::__construct($message, $code);
	}
}
