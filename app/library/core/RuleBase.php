<?php
namespace core;
use Phalcon\Validation, Phalcon\Validation\Validator\Email as EmailValidator, Phalcon\Validation\Validator\StringLength as StringLength, Phalcon\Validation\Validator\Between;

class RuleBase
{

	public static $checkStr = 1;

	public static $checkNum = 2;

	public static $checkEmail = 3;

	public static $checkStrArr = 4;

	public static $checkNumArr = 5;
	
	public static $checkDate = 6;

	public static $methodPost = 'POST';

	public static $methodGet = 'GET';

	public static $methodUrl = 'URL';

	private static $checkType;

	public static function setRules($checkType, $message, array $rang)
	{
		self::$checkType = strtolower($checkType);
		if(count($rang) != 2 || $rang[0] === null || $rang[1] === null)
		{
			throw new \common\form\FormException("form check rule rang error must array", 11002);
			return;
		}
		$min = $rang[0];
		$max = false === $rang[1]? PHP_INT_MAX: $rang[1];
		return self::componseCheckObj($message, $min, $max);
	}

	private static function componseCheckObj($message, $min, $max)
	{
		$obj = null;
		switch(self::$checkType)
		{
			case self::$checkStr:
				$obj = new StringLength(
					array('max'=> $max,'min'=> $min,'messageMaximum'=> $message,'messageMinimum'=> $message));
				break;
			case self::$checkNum:
				$obj = new \rule\validator\CheckNum(array('max'=> $max,'min'=> $min,'message'=> $message));
				break;
			case self::$checkEmail:
				$obj = new EmailValidator(array('message'=> $message));
				break;
			case self::$checkStrArr:
				$obj = new \rule\validator\CheckboxStr(array('max'=> $max,'min'=> $min,'message'=> $message));
				break;
			case self::$checkNumArr:
				$obj = new \rule\validator\CheckboxNum(array('max'=> $max,'min'=> $min,'message'=> $message));
				break;
			case self::$checkDate:
				$obj = new \rule\validator\CheckDate(array('message'=>$message));
				break;
			default:
				$objName = "\\rule\\validator\\Check" . ucfirst(self::$checkType);
				if(class_exists($objName))
				{
					$obj = new $objName(array('max'=> $max,'min'=> $min,'message'=> $message));
				}
				else
				{
					throw new \common\form\FormException("unknow form check type", 11001);
				}
				break;
		}
		return $obj;
	}
}
?>