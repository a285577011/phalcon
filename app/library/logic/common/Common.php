<?php
namespace logic\common;
use core\ModelBase;
use core\EnameApi;
class Common
{

	public static function addScore($enameId, $score, $remark)
	{
		$userM = new ModelBase('user_list');
		$model = new ModelBase('score_record');
		try
		{
			$userM->begin();
			$insert = array('EnameId'=> $enameId,'Score'=> $score,'Remark'=> $remark,'CreateTime'=> time());
			if(! $model->insert($insert))
			{
				throw new \PDOException('插入积分记录失败');
			}
			$userM->exec("UPDATE user_list SET Score=Score+{$score} WHERE EnameId={$enameId}");
			$userM->commit();
		}
		catch(\PDOException $e)
		{
			$userM->rollback(); // 执行失败，事务回滚
			\core\Logger::write('USER_SCORE', $e->getMessage());
			exit();
		}
	}

	public static function checkIsAgree($enameId)
	{
		if(! isset($_COOKIE['isAgree'][$enameId]))
		{
			$model = new \core\ModelBase('user_list');
			setcookie('isAgree[' .$enameId. ']', 
				$model->getData('isAgree', array('EnameId'=> $enameId), $model::FETCH_COLUMN, false, 
					array(0,1)), time() + 3600 * 24, '/');
		}
		if(! isset($_COOKIE['isAgree'][$enameId]))
		{
			return $model->getData('isAgree', array('EnameId'=>$enameId), $model::FETCH_COLUMN, 
				false, array(0,1));
		}
		return $_COOKIE['isAgree'][$enameId];
	}
	public static function initUserInfo($enameid)
	{
		$model = new \core\ModelBase('user_list');
		if(! $model->getData('UserId', array('EnameId'=> $enameid), $model::FETCH_COLUMN, false, array(0,1)))
		{
			$insert = array('EnameId'=> $enameid,'IncomeMoney'=> 0,'PayMoney'=> 0,'Money'=> 0,'Score'=> 0,'CreateTime'=>time());
				
			$model->insert($insert);
			$adminApi = new EnameApi(\core\Config::item('apiTrans'));
			$rs=$adminApi->sendCmd('member/addsitemessage',
				array(
						'data'=> array('enameid'=> $enameid,'title'=> '域名联盟欢迎您',
								'content'=> '亲爱的' . $enameid .
						'用户：您好！感谢您选择了域名联盟平台，希望您能够在<a href="http://www.ename.com.cn/index/topic"> 域名联盟</a>收获颇丰。'),
						'enameId'=> $enameid,'templateId'=> '','type'=> 99));
			\core\Logger::write('crontab_first_login', array($rs));
			// 发送邮箱
			// 发送消息
			$adminApi->sendCmd('member/sendemail',
				array(
						'tplData'=> array('enameid'=> $enameid,'title'=> '域名联盟欢迎您',
								'content'=> '亲爱的' . $enameid .
						'用户：您好！感谢您选择了域名联盟平台，希望您能够在<a href="http://www.ename.com.cn/index/topic"> 域名联盟</a>收获颇丰。'),
						'enameId'=> $enameid,'templateId'=> '','type'=> 99,'email'=> ''));
		}
	}
	/**
	 * 是否移动端访问访问
	 *
	 * @return bool
	 */
	public static function isMobile()
	{
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
		{
			return true;
		}
		// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if (isset ($_SERVER['HTTP_VIA']))
		{
			// 找不到为flase,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		// 脑残法，判断手机发送的客户端标志,兼容性有待提高
		if (isset ($_SERVER['HTTP_USER_AGENT']))
		{
			$clientkeywords = array ('nokia',
					'sony',
					'ericsson',
					'mot',
					'samsung',
					'htc',
					'sgh',
					'lg',
					'sharp',
					'sie-',
					'philips',
					'panasonic',
					'alcatel',
					'lenovo',
					'iphone',
					'ipod',
					'blackberry',
					'meizu',
					'android',
					'netfront',
					'symbian',
					'ucweb',
					'windowsce',
					'palm',
					'operamini',
					'operamobi',
					'openwave',
					'nexusone',
					'cldc',
					'midp',
					'wap',
					'mobile'
			);
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
			{
				return true;
			}
		}
		// 协议法，因为有可能不准确，放到最后判断
		if (isset ($_SERVER['HTTP_ACCEPT']))
		{
			// 如果只支持wml并且不支持html那一定是移动设备
			// 如果支持wml和html但是wml在html之前则是移动设备
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
			{
				return true;
			}
		}
		return false;
	}
}