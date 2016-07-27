<?php
namespace common\form;

class FormParse
{

	private $method = 'POST';

	private $rules;

	private $formData = array();

	/**
	 *
	 * @var \Phalcon\Http\Request
	 */
	private $request;

	function __construct($method,\Phalcon\Http\Request $request)
	{
		$this->method = strtoupper($method);
		$this->request = $request;
	}

	public function validator(\Phalcon\Validation $validation, array $rules, array $urlParams)
	{
		$conut = 0;
		$this->composeCheckData();
		$newRule = array();
		if(isset($rules['url']))
		{
			$this->setUrlParToData($rules['url'], $urlParams);
			$newRule[] = $rules['url'];
		}
		if(isset($rules['post']) && $this->method == 'POST')
		{
			$newRule[] = $rules['post'];
		}
		if(isset($rules['get']) && $this->method == 'GET')
		{
			$newRule[] = $rules['get'];
		}
		if(count($newRule) < 1)
		{
			return true;
		}
		foreach($newRule as $value)
		{
			foreach($value as $subKey => $subValue)
			{
				if(4 > count($subValue))
				{
					throw new \common\form\FormException("the rules config error about key:" . $subKey, 11003);
					return;
				}
				$must = isset($subValue[4])? $subValue[4]: true;
				$isset = isset($this->formData[$subKey]);
				if(false == $must && (false == $isset || (true == $isset && empty($this->formData[$subKey]))))
				{
					continue;
				}
				$conut++;
				$validation->add($subKey, 
					\core\RuleBase::setRules($subValue[0], $subValue[1], array($subValue[2],$subValue[3])));
				$validation->setFilters($subKey, 'trim');
			}
		}
		if($conut)
		{
			return $validation->validate($this->formData);
		}
		return true;
	}

	/**
	 * 把URL上的参数p1/p2/p3 添加到formData里面
	 * 
	 * @param array $urlRules
	 * @param array $urlParams
	 */
	private function setUrlParToData($urlRules, array $urlParams)
	{
		$newKey = array_keys($urlRules);
		$newValue = array();
		foreach($newKey as $k => $v)
		{
			$newValue[$k] = isset($urlParams[$k])? $urlParams[$k]: '';
		}
		if(count($newKey))
		{
			$this->formData = array_merge($this->formData, (array)array_combine($newKey, $newValue));
		}
	}

	private function composeCheckData()
	{
		$data = array();
		if($this->method == \core\RuleBase::$methodPost)
		{
			$data = $this->request->getPost();
		}
		else
		{
			$data = $this->request->getQuery();
		}
		$this->formData = $data;
	}
}