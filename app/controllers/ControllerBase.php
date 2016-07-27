<?php
use core\RuleBase;

class ControllerBase extends \Phalcon\Mvc\Controller
{

	private $ctrlName;

	private $actName;

	protected $formCheck = true;

	protected $formMsg = array();

	protected $appConfig;

	/**
	 * 要显示的语言名称
	 */
	private $lang = false;

	/**
	 *
	 * @var \common\filter\Input
	 */
	protected $input;

	/**
	 * URL参数 index/index/2014/11/11 里面的2014/11/11
	 *
	 * @var array
	 */
	protected $urlParams;

	public function initialize()
	{
		// 配置初始化
		\core\Config::init(DEBUG);
		$this->input = new \common\filter\Input();
		include_once ROOT_PATH . 'app/library/core/cas/WebVisitor.php';
		$this->setLang();
		// 登录后头部显示
		if(WebVisitor::getEnameId())
		{
			$logic = new \logic\user\User(WebVisitor::getEnameId());
			$messageInfo = $logic->getUserMessage();
			$this->view->setVars(array('enameId'=> WebVisitor::getEnameId(),'messageNum'=> $messageInfo[2]));
		}
	}

	public function beforeExecuteRoute()
	{
		try
		{
			$this->appConfig = new \Phalcon\Config\Adapter\Ini(ROOT_PATH . "/app/config/app.ini");
			$this->ctrlName = $this->dispatcher->getControllerName();
			$this->actName = $this->dispatcher->getActionName();
			defined('CONTROLLER_NAME') or define('CONTROLLER_NAME', $this->ctrlName);
			defined('ACTION_NAME') or define('ACTION_NAME', $this->actName);
			$ruleClassName = '\rule\Rule' . ucfirst($this->ctrlName);
			if(class_exists($ruleClassName) === true && method_exists($ruleClassName, $this->actName) === true)
			{
				$this->runFormRule($ruleClassName);
			}
		}
		catch(\Exception $e)
		{
			echo 'Exception:' . $e->getMessage() . ',Code:' . $e->getCode();
			exit();
		}
	}

	/**
	 * 自动检测表单并执行检测函数
	 *
	 * @param string $className
	 */
	private function runFormRule($className)
	{
		$this->urlParams = $this->dispatcher->getParams();
		$method = RuleBase::$methodGet;
		$request = new \Phalcon\Http\Request();
		if($request->isPost())
		{
			$method = ruleBase::$methodPost;
		}
		$classNameObj = new $className();
		$fun = $this->actName;
		$FormParse = new \common\form\FormParse($method, $request);
		$validation = new \Phalcon\Validation();
		$messages = $FormParse->validator($validation, $classNameObj->$fun(), $this->urlParams);
		if($messages !== true && count($messages))
		{
			$this->formCheck = false;
			$this->formMsg = $messages;
			$this->showError($this->formatFormErr());
		}
		unset($messages);
	}

	/**
	 * 显示错误信息方法 默认返回首页
	 *
	 * @param string $errMsg
	 * @param string $url
	 */
	protected function showError($errMsg, $url = '')
	{
		$this->showMsg('common', 'error', $errMsg, $url);
	}

	/**
	 * 显示成功信息方法 默认返回首页
	 *
	 * @param string $msg
	 * @param string $url
	 */
	protected function showSuccess($msg, $url = '')
	{
		$this->showMsg('common', 'success', $msg, $url);
	}

	private function showMsg($ctrl, $fun, $msg, $url)
	{
		$url or $url = 'javascript:history.back(-1);';
		$this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
		$this->view->setVars(array('msg'=> $msg,'url'=> $url));
		$this->dispatcher->forward(array('controller'=> $ctrl,'action'=> $fun));
	}

	/**
	 * 设置浏览器的语言
	 *
	 * @param string $lang //用户选择的语言
	 */
	private function setLang($lang = false)
	{
		if(false == $this->lang)
		{
			$this->lang = \core\Lang::getLangName();
		}
	}

	/**
	 * 显示多语言 如果有变量请在$value里面传值 array('name'=>'value')
	 *
	 * @param string $key
	 * @param array $value
	 */
	protected function e($key, array $value = array())
	{
		return \core\Lang::e($key, $value);
	}

	/**
	 * 获取错误信息
	 *
	 * @param unknown $messgae
	 * @return string
	 */
	protected function formatFormErr()
	{
		$msg = array();
		foreach($this->formMsg as $v)
		{
			$msg[] = $v->getMessage();
		}
		return implode(',', $msg);
	}

	/**
	 * Gets a variable from the $_POST superglobal applying filters if
	 * needed
	 *
	 * @param string $name
	 * @param string|array $filters
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	protected function getPost($name = null, $filters = null, $defaultValue = null)
	{
		return $this->request->getPost($name, $filters, $defaultValue);
	}

	/**
	 * Gets variable from $_GET superglobal applying filters if needed
	 *
	 * @param string $name
	 * @param string|array $filters
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	protected function getQuery($name = null, $filters = null, $defaultValue = null)
	{
		return $this->request->getQuery($name, $filters, $defaultValue);
	}

	/**
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 * Checks whether HTTP method is GET. if
	 * $_SERVER['REQUEST_METHOD']=='GET'
	 *
	 * @return boolean
	 */
	protected function isGet()
	{
		return $this->request->isGet();
	}

	/**
	 * Checks whether HTTP method is POST.
	 * if $_SERVER['REQUEST_METHOD']=='POST'
	 *
	 * @return boolean
	 */
	protected function isPost()
	{
		return $this->request->isPost();
	}

	/**
	 * Checks whether request has been made using ajax.
	 * Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
	 *
	 * @return boolean
	 */
	protected function isAjax()
	{
		return $this->request->isAjax();
	}

	/**
	 * Ajax方式返回数据到客户端
	 *
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type AJAX返回数据格式
	 * @return void
	 */
	protected function ajaxReturn($data, $type = '')
	{
		$type or $type = 'JSON';
		switch(strtoupper($type))
		{
			case 'JSON':
				// 返回JSON数据格式到客户端 包含状态信息
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode($data));
			case 'XML':
				// 返回xml格式数据
				header('Content-Type:text/xml; charset=utf-8');
				exit(xml_encode($data));
			case 'JSONP':
				// 返回JSON数据格式到客户端 包含状态信息
				header('Content-Type:application/json; charset=utf-8');
				$handler = isset($_GET['callback'])? $_GET['callback']: '';
				exit($handler . '(' . json_encode($data) . ');');
			case 'EVAL':
				// 返回可执行的js脚本
				header('Content-Type:text/html; charset=utf-8');
				exit($data);
			default:
				// 返回JSON数据格式到客户端 包含状态信息
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode($data));
		}
	}
}