<?php
namespace rule\validator;
use Phalcon\Validation\Validator, Phalcon\Validation\ValidatorInterface, Phalcon\Validation\Validator\StringLength as StringLength, Phalcon\Validation\Message;

class CheckboxNum extends Validator implements ValidatorInterface
{

	/**
	 *
	 *
	 * array('min'=>0,'max'=>100,'message'=>errorMsg)
	 *
	 * @param array $option
	 */
	function __construct($option)
	{
		parent::__construct($option);
	}

	/**
	 * 执行验证
	 *
	 * @param \Phalcon\Validation $validator
	 * @param string $attribute
	 * @return boolean
	 */
	public function validate(\Phalcon\Validation $validator, $attribute)
	{
		$value = $validator->getValue($attribute);
		$min = $this->getOption('min');
		$max = $this->getOption('max');
		$message = $this->getOption('message');
		$flag = true;
		if($value)
		{
			foreach($value as $v)
			{
				if(false == is_numeric($v) || $max < $v || $min > $v)
				{
					$flag = false;
					break;
				}
			}
		}
		else
		{
			$flag = false;
		}
		if(! $flag)
		{
			$validator->appendMessage(new Message($message, $attribute, 'checkboxNum'));
			return false;
		}
		return true;
	}
}