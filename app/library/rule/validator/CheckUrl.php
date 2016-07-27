<?php
namespace rule\validator;

use Phalcon\Validation\Validator,
	Phalcon\Validation\ValidatorInterface,
	Phalcon\Validation\Validator\StringLength as StringLength,
	Phalcon\Validation\Message;

class CheckUrl extends Validator implements ValidatorInterface
{
	/**
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
		$flag = false;
		if($value)
		{
			if(filter_var($value,FILTER_VALIDATE_URL))
			{
				$flag = true;
			}
		}
		if(!$flag)
		{
			$validator->appendMessage(new Message($message, $attribute, 'checkPhone'));
			return false;
		}
		return true;
	}
}