<?php
namespace rule\validator;

use Phalcon\Validation\Validator,
	Phalcon\Validation\ValidatorInterface,
	Phalcon\Validation\Validator\StringLength as StringLength,
	Phalcon\Validation\Message;

class CheckDate extends Validator implements ValidatorInterface
{
	/**
	 * 
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
		$message = $this->getOption('message');
		$flag = false;
		if($value)
		{
			if(preg_match('/^[0-9]{4}[-\/](0[1-9]|1[12])[-\/](0[1-9]|[12][0-9]|3[01])$/', $value))
			{
				$flag = true;
			}
		}
		if(!$flag)
		{
			$validator->appendMessage(new Message($message, $attribute, 'checkDate'));
			return false;
		}
		return true;
	}
}