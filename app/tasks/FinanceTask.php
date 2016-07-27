<?php
use \core\ModelBase;
use \core\driver\Redis;
use \lib\ad\AdvertLib;
use logic\task\TaskLogic;
use logic\common\Common;
use core\EnameApi;
use common\domain\Domain;

class FinanceTask extends \Phalcon\CLI\Task
{

	const ORDER_SUCESS_DOMAIN_KEY = 'agent:domain';

	const ORDER_SUCESS_SHOP_KEY = 'agent:shop';

	const MAX_NUM = 100;

	const CHECK_OUT_MONEY_KEY = 'agent:checkout';

	const LOG_NAME = 'crontab_finance';

	public function mainAction()
	{
		echo '一个定时任务';
	}

	/**
	 * 添加域名订单
	 *
	 * @throws \PDOException
	 */
	public function addOrderDomainAction()
	{
		\core\Logger::write('crontab_add_order', array('add_order','start',date('Y-m-d H:i:s')));
		$adminApi = new EnameApi(\core\Config::item('apiTrans'));
		$backupM = new ModelBase('agent_backup');
		$model = new ModelBase();
		$domainM = new ModelBase('domain_agent');
		$posM = new ModelBase('ad_pos');
		$spreadDomain = new ModelBase('spread_domain_record');
		$orderRecordM = new ModelBase('order_record');
		$AdvertLib = new AdvertLib();
		if($count = Redis::getInstance()->lSize(self::ORDER_SUCESS_DOMAIN_KEY))
		{
			\core\Logger::write('crontab_add_order', array('get_order_redis','reids:' . $count));
			for($i = 0; $i < $count && $i < self::MAX_NUM; $i++)
			{
				if(! $data = Redis::getInstance()->lPop(self::ORDER_SUCESS_DOMAIN_KEY))
				{
					continue;
				}
				if(isset($data['TransType']) && $data['TransType'] == 8)
				{
					$topic = 8;
				}
				else
				{
					$topic = 0;
				}
				$buyer = $data['buyer'];
				$price = $data['price'];
				$crypt = $data['str'];
				\core\Logger::write('crontab_add_order', array('get_order_data',$buyer,$price,$crypt));
				$str = $AdvertLib->decrypt($crypt);
				if(! $str || count(explode('_', $str)) != 6)
				{
					continue;
				}
				list($type, $agentEnameId, $transId, $domainName, $agentId, $posId) = explode('_', $str);
				\core\Logger::write('crontab_add_order', array('add_order_data',$buyer,$price,$crypt,$domainName));
				$transId = $data['AuditListId'];
				if($orderRecordM->count(array('TransId'=> $transId), 'OrderId'))
				{
					continue;
				}
				try
				{
					$model->begin();
					$domainData = $domainM->getData(
						'DomainAgentId,EnameId,DomainName,TransType,Percent,GroupTwo,DomainLen,GroupOne,TLD,ClickNum,SimpleDec,GroupThree', 
						array('DomainAgentId'=> $agentId), $model::FETCH_ROW);
					$adPosData = $posM->getData('PlatformType,PlatformId', array('PosId'=> $posId), $posM::FETCH_ROW);
					$income = $domainData->Percent * $price / 100;
					if(! $domainData)
					{
						throw new \PDOException('域名表无数据');
					}
					elseif(! $adPosData)
					{
						throw new \PDOException('1');
					}
					$model->exec(
						"INSERT INTO order_record (EnameId,AgentEnameId,DomainName,TransType,Price,Percent,CreateTime,CheckoutTime,Status,GroupTwo,DomainLen,GroupOne,Income,AgentType,PlatformType,PlatformId,TransId,Topic,Buyer,GroupThree) 
						VALUES({$domainData->EnameId},{$agentEnameId},'{$domainData->DomainName}',{$domainData->TransType},{$price},{$domainData->Percent}," .
							 time() .
							 ",0,1,{$domainData->GroupTwo},{$domainData->DomainLen},{$domainData->GroupOne},{$income},{$type},{$adPosData->PlatformType},{$adPosData->PlatformId},{$transId},{$topic},{$buyer},{$domainData->GroupThree})");
					if($topic == 0)
					{
						$model->exec(
							"UPDATE user_list SET IncomeMoney=IncomeMoney+{$income} WHERE EnameId={$agentEnameId}");
					}
					if($domainM->delete(array('DomainAgentId'=> $domainData->DomainAgentId)) === false)
					{
						throw new \PDOException('删除域名分销失败');
					}
					if($spreadDomain->update(array('IsOrder'=> 1), array('DomainAgentId'=> $agentId,'PosId'=> $posId)) ===
						 false)
					{
						throw new \PDOException('更新历史记录表订单状态失败');
					}
					if($topic == 0)
					{
						$rs = $adminApi->sendCmd('finance/charge', 
							array('enameId'=> $domainData->EnameId,'domain'=> $domainName,'remark'=> '推广域名扣款',
									'price'=> $income,'type'=> 99));
						$rs = json_decode($rs);
						if($rs->code != 100000 || ! $rs->flag)
						{
							throw new \PDOException('扣款失败');
						}
					}
					$model->commit();
				}
				catch(\PDOException $e)
				{
					\core\Logger::write('crontab_add_order', array('add_order_error',$e->getMessage()));
					\core\Logger::write('crontab_add_order', array('add_order_error',array($buyer,$price,$crypt)));
					$model->rollback(); // 执行失败，事务回滚
					continue;
				}
				Redis::getInstance()->rPush('agent_domain_trans_success', 
					array($agentEnameId,$domainName,$price,$income,$topic));
				Common::addScore($agentEnameId, 3, '分销域名：' . $domainName . '成功');
				if($backupM->count(array('DomainAgentId'=> $agentId), 'AgentBackupId'))
				{
					if(! $backupM->update(array('BackupType'=> 3), array('DomainAgentId'=> $agentId)))
					{
						\core\Logger::write('crontab_add_order', 
							array('crontab_add_order','update_status_fail:agentId' . $agentId));
					}
				}
				else
				{
					if(! $backupM->insert(
						array('DomainAgentId'=> $agentId,'EnameId'=> $domainData->EnameId,
								'DomainName'=> $domainData->DomainName,'Price'=> $price,
								'TransType'=> $domainData->TransType,'Percent'=> $domainData->Percent,
								'TLD'=> $domainData->TLD,'GroupOne'=> $domainData->GroupOne,
								'GroupTwo'=> $domainData->GroupTwo,'DomainLen'=> $domainData->DomainLen,
								'ClickNum'=> $domainData->ClickNum,'CreateTime'=> time(),'BackupType'=> 3,
								'SimpleDec'=> $domainData->SimpleDec,'Topic'=> $topic,
								'GroupThree'=> $domainData->GroupThree)))
					{
						\core\Logger::write('crontab_add_order', 
							array('crontab_add_order','insert_status_fail:agentId' . $agentId));
					}
				}
				if(! $spreadDomain->update(array('Status'=> - 1), array('DomainAgentId'=> $agentId)))
				{
					\core\Logger::write('crontab_add_order', 
						array('crontab_add_order','update_spreadDomain_status_fail:agentId' . $agentId));
				}
			}
		}
		\core\Logger::write('crontab_add_order', array('add_order','sucess',date('Y-m-d H:i:s')));
	}

	/**
	 * 定时结算
	 *
	 * @author zhujp
	 */
	public function autoCheckMoneyAction()
	{
		$logName = self::LOG_NAME . '_autoCheck';
		\core\Logger::write($logName, ': Start.');
		$newCheck = array();
		$day = date('d');
		$hour = date('H');
		$minute = date('i');
		$second = date('s');
		$days = date('t', strtotime('-1 month'));
		$firstDay = strtotime("-1 month -" . ($day - 1) . " day -{$hour} hour -{$minute} minute -{$second} second"); // 上一月开头的第一天
		$lastDay = strtotime('+' . $days . ' day -1 second', $firstDay); // 上一月的最后一天
		
		$model = new ModelBase();
		$orderRecord = new OrderRecord();
		$user = new ModelBase('user_list');
		$fields = 'AgentEnameId,SUM(Income) AS Incomes';
		$condition['Status'] = 1;
		$condition['Topic'] = 0;
		$condition['CreateTime'] = array('BETWEEN',array($firstDay,$lastDay));
		$groupBy = 'AgentEnameId';
		$check = $orderRecord->getData($fields, $condition, $orderRecord::FETCH_ALL, '', '', $groupBy);
		\core\Logger::write($logName, ': Get ename_aliiance.order_record ' . count($check) . ' row(s) data success.');
		
		while(! empty($check))
		{
			$newCheck = array();
			foreach($check as $v)
			{
				if(! $v->Incomes)
				{
					continue;
				}
				$isExist = $user->count(array('EnameId'=> $v->AgentEnameId)) > 0? : FALSE;
				if(! $isExist)
				{
					continue;
				}
				\core\Logger::write($logName, ': Check whether exist or not success.');
				try
				{
					$model->begin();
					// 获取用户余额
					$model->query("SELECT Money FROM `user_list` WHERE EnameId=:EnameId", 
						array(':EnameId'=> $v->AgentEnameId));
					$balance = $model->getOne();
					$balance += $v->Incomes;
					
					// 更新用户余额
					$model->exec(
						"UPDATE user_list SET Money = Money + {$v->Incomes} WHERE EnameId = {$v->AgentEnameId}");
					
					// 添加财务流水
					$model->exec(
						"INSERT INTO `finance_record` (EnameId, Money, CreateTime, MoneyType, FinanceType, Balance) VALUES ({$v->AgentEnameId}, {$v->Incomes}, " .
							 time() . ", 2, 3, {$balance})");
					
					// 更新订单表状态未2（结算）
					$orderRecord->exec(
						"UPDATE order_record SET Status=2 WHERE Status=1 AND CreateTime BETWEEN {$firstDay} AND {$lastDay} AND AgentEnameId={$v->AgentEnameId} AND Topic=0");
					
					// 技术服务费
					if(\core\Config::item('techFee') > 0)
					{
						$techFee = $v->Incomes * \core\Config::item('techFee') * 0.01;
						$newBalance = $balance - $techFee;
						$model->exec(
							"UPDATE user_list SET Money = Money - {$techFee} WHERE EnameId = {$v->AgentEnameId}");
						$model->exec(
							"INSERT INTO `finance_record` (EnameId, Money, CreateTime, MoneyType, FinanceType, Balance) VALUES ({$v->AgentEnameId}, {$techFee}, " .
								 time() . ", 1, 1, {$newBalance})");
					}
					$model->commit();
					\core\Logger::write($logName, ': Checkout success.');
					
					// 写入结算消息队列
					Redis::getInstance()->rPush(self::CHECK_OUT_MONEY_KEY, $v->AgentEnameId);
					\core\Logger::write($logName, ': Push redis key(' . self::CHECK_OUT_MONEY_KEY . ') success.');
				}
				catch(\PDOException $e)
				{
					$model->rollback(); // 事务回滚
					\core\Logger::write($logName, $e->getMessage());
					$newCheck[] = $v; // 插入失败的重新拼接数组
					continue;
				}
			}
			$check = $newCheck;
		}
		
		echo 'Auto turn out user\'s order record success.';
		\core\Logger::write($logName, ': Auto turn out user\'s order record success.');
	}

	/**
	 * 定时发送20号结算消息
	 */
	public function sendCheckoutAction()
	{
		$logName = self::LOG_NAME . '_sendcheckout';
		\core\Logger::write($logName, ': Start.');
		$redis = Redis::getInstance();
		$adminApi = new EnameApi(\core\Config::item('apiTrans'));
		while($redis->lSize(self::CHECK_OUT_MONEY_KEY))
		{
			$eNameId = $redis->lPop(self::CHECK_OUT_MONEY_KEY);
			if(! $eNameId)
			{
				continue;
			}
			\core\Logger::write($logName, ': Get redis "' . self::CHECK_OUT_MONEY_KEY . '" key success.');
			$month = date('n', strtotime('-1 month'));
			$days = date('t', strtotime('-1 month'));
			$title = "{$month}月份结算通知";
			$content = "亲爱的{$eNameId}用户：<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！又到了收获的时节了，您{$month}.1-{$month}.{$days}的佣金收入已经结算，快去<a href='http://www.ename.com.cn/user/finance'>财务中心</a>体会丰收的喜悦吧~感谢您对域名联盟平台的支持！";
			
			// 发送消息
			$msgJson = $adminApi->sendCmd('member/addsitemessage', 
				array('data'=> array('enameid'=> $eNameId,'title'=> $title,'content'=> $content),'enameId'=> $eNameId,
						'templateId'=> '','type'=> 99));
			$msgData = json_decode($msgJson, TRUE);
			$msg = $msgData['code'] == 100000 && $msgData['flag'] == true? 'Send the msg to ' . $eNameId . ' success.': $msgData;
			\core\Logger::write($logName, ": {$msg}");
			
			// 发送邮箱
			$msgEmail = $adminApi->sendCmd('member/sendemail', 
				array('tplData'=> array('enameid'=> $eNameId,'title'=> $title,'content'=> $content),
						'enameId'=> $eNameId,'templateId'=> '','type'=> 99,'email'=> ''));
			$emailData = json_decode($msgEmail, TRUE);
			$email = $emailData['code'] == 100000 && $emailData['flag'] == true? 'Send the email to ' . $eNameId .
				 ' success.': $emailData;
			\core\Logger::write($logName, ": {$email}");
		}
		echo "Auto send message when checkout every month";
		\core\Logger::write($logName, ": Auto send message when checkout every month");
	}

	/**
	 * 添加店铺订单
	 *
	 * @throws \PDOException
	 */
	public function addOrderShopAction()
	{
		\core\Logger::write('crontab_add_order_shop', array('add_order_shop','start',date('Y-m-d H:i:s')));
		$adminApi = new EnameApi(\core\Config::item('apiTrans'));
		$model = new ModelBase();
		$backupM = new ModelBase('agent_backup');
		$domainM = new ModelBase('domain_agent');
		$shopM = new ModelBase('shop_agent');
		$posM = new ModelBase('ad_pos');
		$orderRecordM = new ModelBase('order_record');
		$spreadShopM = new ModelBase('spread_shop_record');
		$AdvertLib = new AdvertLib();
		$isAgentDomain = false;
		if($count = Redis::getInstance()->lSize(self::ORDER_SUCESS_SHOP_KEY))
		{
			for($i = 0; $i < $count && $i < self::MAX_NUM; $i++)
			{
				if(! $data = Redis::getInstance()->lPop(self::ORDER_SUCESS_SHOP_KEY))
				{
					continue;
				}
				$buyer = $data['buyer'];
				$price = $data['price'];
				$crypt = $data['str'];
				$transId = $data['AuditListId'];
				$domainName = $data['domain'];
				$TransType = $data['TransType'];
				\core\Logger::write('crontab_add_order_shop', 
					array('add_order_shop_redis_data',$buyer,$price,$crypt,$domainName,$TransType));
				$str = $AdvertLib->decrypt($crypt);
				if(! $str || count(explode('_', $str)) != 5)
				{
					continue;
				}
				if($orderRecordM->count(array('TransId'=> $transId), 'OrderId'))
				{
					continue;
				}
				list($type, $enameId, $agentEnameId, $agentId, $posId) = explode('_', $str);
				try
				{
					$model->begin();
					$percent = $shopM->getData('Percent', 
						array('ShopAgeId'=> $agentId,'CreateTime'=> array('<=',time() - \core\Config::item('edittime')),
								'FinishTime'=> array('>=',time())), $shopM::FETCH_COLUMN);
					if(! $percent)
					{
						throw new \PDOException('店铺状态不正确');
					}
					$adPosData = $posM->getData('PlatformType,PlatformId', array('PosId'=> $posId), $posM::FETCH_ROW);
					if(! $adPosData)
					{
						throw new \PDOException('1');
					}
					$domainData = $domainM->getData(
						'DomainAgentId,EnameId,DomainName,TransType,GroupTwo,DomainLen,GroupOne,TLD,ClickNum,SimpleDec,GroupThree', 
						array('EnameId'=> $enameId,'DomainName'=> $domainName), $model::FETCH_ROW);
					$income = $percent * $price / 100;
					if(! $domainData)
					{
						list($domainSysOne, $domainSysTwo, $groupThree, $domainLength) = Domain::getDomainGroupAll(
							$domainName);
						$domainData = new \stdClass();
						$domainData->DomainAgentId = 0;
						$domainData->EnameId = $enameId;
						$domainData->DomainName = $domainName;
						switch($TransType)
						{
							case 4:
							case 6:
							case 7:
							case 8:
								$domainData->TransType = 1;
								break;
							case 1:
								$domainData->TransType = 4;
						}
						$domainData->GroupTwo = $domainSysTwo;
						$domainData->DomainLen = $domainLength;
						$domainData->GroupOne = $domainSysOne;
						$domainData->GroupThree = $groupThree;
						$domainData->ClickNum = 0;
						$domainData->SimpleDec = "";
						$domainData->TLD = Domain::tldValue($domainName);
						\core\Logger::write('crontab_add_order_shop', array('此域名没在联盟做分销'));
					}
					else
					{
						$isAgentDomain = true;
					}
					$model->exec(
						"INSERT INTO order_record (EnameId,AgentEnameId,DomainName,TransType,Price,Percent,CreateTime,CheckoutTime,Status,GroupTwo,DomainLen,GroupOne,Income,AgentType,PlatformType,PlatformId,TransId,Buyer)
					VALUES({$domainData->EnameId},{$agentEnameId},'{$domainData->DomainName}',{$domainData->TransType},{$price},{$percent}," .
							 time() .
							 ",0,1,{$domainData->GroupTwo},{$domainData->DomainLen},{$domainData->GroupOne},{$income},{$type},{$adPosData->PlatformType},{$adPosData->PlatformId},{$transId},{$buyer})");
					$model->exec("UPDATE user_list SET IncomeMoney=IncomeMoney+{$income} WHERE EnameId={$agentEnameId}");
					if($isAgentDomain)
					{
						if($domainM->delete(array('DomainAgentId'=> $domainData->DomainAgentId)) === false)
						{
							throw new \PDOException('删除域名分销失败');
						}
					}
					if($spreadShopM->update(array('IsOrder'=> 1), array('ShopAgentId'=> $agentId,'PosId'=> $posId)) ===
						 false)
					{
						throw new \PDOException('更新历史记录表订单状态失败');
					}
					$rs = $adminApi->sendCmd('finance/charge', 
						array('enameId'=> $enameId,'domain'=> $domainData->DomainName,'remark'=> '推广店铺扣款',
								'price'=> $income,'type'=> 99));
					$rs = json_decode($rs);
					if($rs->code != 100000 || ! $rs->flag)
					{
						throw new \PDOException('扣款失败');
					}
					$model->commit();
				}
				catch(\PDOException $e)
				{
					\core\Logger::write('crontab_add_order_shop', array('add_order_shop_error',$e->getMessage()));
					\core\Logger::write('crontab_add_order_shop', 
						array('add_order_shop_error',array($buyer,$price,$crypt,$domainName,$TransType)));
					$model->rollback(); // 执行失败，事务回滚
					continue;
				}
				Redis::getInstance()->rPush('agent_shop_trans_success', array($agentEnameId,$domainName,$price,$income));
				Common::addScore($agentEnameId, 3, '分销域名：' . $domainName . '成功');
				if($isAgentDomain)
				{
					if($backupM->count(array('DomainAgentId'=> $domainData->DomainAgentId), 'AgentBackupId'))
					{
						if(! $backupM->update(array('BackupType'=> 3), 
							array('DomainAgentId'=> $domainData->DomainAgentId)))
						{
							\core\Logger::write('crontab_add_order', 
								array('crontab_add_order','update_status_fail:agentId' . $domainData->DomainAgentId));
						}
					}
				}
				else
				{
					if(! $backupM->insert(
						array('DomainAgentId'=> $domainData->DomainAgentId,'EnameId'=> $domainData->EnameId,
								'DomainName'=> $domainData->DomainName,'Price'=> $price,
								'TransType'=> $domainData->TransType,'Percent'=> $percent,'TLD'=> $domainData->TLD,
								'GroupOne'=> $domainData->GroupOne,'GroupTwo'=> $domainData->GroupTwo,
								'DomainLen'=> $domainData->DomainLen,'ClickNum'=> $domainData->ClickNum,
								'CreateTime'=> time(),'BackupType'=> 3,'SimpleDec'=> $domainData->SimpleDec,
								'GroupThree'=> $domainData->GroupThree)))
					{
						\core\Logger::write('crontab_add_order', 
							array('crontab_add_order','insert_status_fail:agentId' . $agentId));
					}
				}
			}
		}
		\core\Logger::write('crontab_add_order_shop', array('add_order_shop','sucess',date('Y-m-d H:i:s')));
	}

	/**
	 * 发送店铺交易成功订单消息
	 */
	public function SendByShopTransSucessAction()
	{
		\core\Logger::write('crontab_send_shop_trans_sucess', 
			array('send_shop_trans_sucess','start',date('Y-m-d H:i:s')));
		if($count = Redis::getInstance()->lSize('agent_shop_trans_success'))
		{
			for($i = 0; $i < $count && $i < self::MAX_NUM; $i++)
			{
				if(! $data = Redis::getInstance()->lPop('agent_shop_trans_success'))
				{
					continue;
				}
				list($agentEnameId, $domainName, $price, $income) = $data;
				$adminApi = new EnameApi(\core\Config::item('apiTrans'));
				$adminApi->sendCmd('member/addsitemessage', 
					array(
							'data'=> array('enameid'=> $agentEnameId,'title'=> $domainName . '售出通知',
									'content'=> '亲爱的' . $agentEnameId .
										 '用户:</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！您所推广的域名：' . $domainName .
										 '已经售出,佣金' . $income .
										 '元已经飞到您的账户里，请在<a href=http://www.ename.com.cn/user/finance>财务中心</a>查收。感谢您对域名联盟平台的支持！'),
							'enameId'=> $agentEnameId,'templateId'=> '','type'=> 99));
				// 发送邮箱
				// 发送消息
				$adminApi->sendCmd('member/sendemail', 
					array(
							'tplData'=> array('enameid'=> $agentEnameId,'title'=> $domainName . '售出通知',
									'content'=> '亲爱的' . $agentEnameId .
										 '用户:</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！您所推广的域名：' . $domainName .
										 '已经售出,佣金' . $income .
										 '元已经飞到您的账户里，请在<a href=http://www.ename.com.cn/user/finance>财务中心</a>查收。感谢您对域名联盟平台的支持！'),
							'enameId'=> $agentEnameId,'templateId'=> '','type'=> 99,'email'=> ''));
			}
		}
		\core\Logger::write('crontab_send_shop_trans_sucess', 
			array('send_shop_trans_sucess','sucess',date('Y-m-d H:i:s')));
	}

	/**
	 * 发送域名交易成功消息
	 */
	public function SendByDomainTransSucessAction()
	{
		\core\Logger::write('crontab_send_domain_trans_sucess', 
			array('crontab_send_domain_trans_sucess','start',date('Y-m-d H:i:s')));
		if($count = Redis::getInstance()->lSize('agent_domain_trans_success'))
		{
			for($i = 0; $i < $count && $i < self::MAX_NUM; $i++)
			{
				if(! $data = Redis::getInstance()->lPop('agent_domain_trans_success'))
				{
					continue;
				}
				list($agentEnameId, $domainName, $price, $income, $topic) = $data;
				if($topic == 8)
				{
					$content = '亲爱的' . $agentEnameId . '用户:</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！您所推广的域名：' .
						 $domainName . '已经售出,恭喜您获得佣金' . $income . '元，拍卖会结束后佣金会发放到您的易名管理平台账户中，请注意查收。感谢您对域名联盟平台的支持！';
				}
				else
				{
					$content = '亲爱的' . $agentEnameId . '用户:</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！您所推广的域名：' .
						 $domainName . '已经售出,佣金' . $income .
						 '元已经飞到您的账户里，请在<a href=http://www.ename.com.cn/user/finance>财务中心</a>查收。感谢您对域名联盟平台的支持！';
				}
				$adminApi = new EnameApi(\core\Config::item('apiTrans'));
				$rs = $adminApi->sendCmd('member/addsitemessage', 
					array('data'=> array('enameid'=> $agentEnameId,'title'=> $domainName . '售出通知','content'=> $content),
							'enameId'=> $agentEnameId,'templateId'=> '','type'=> 99));
				\core\Logger::write('crontab_send_domain_trans_sucess', array($rs));
				// 发送邮箱
				// 发送消息
				$adminApi->sendCmd('member/sendemail', 
					array(
							'tplData'=> array('enameid'=> $agentEnameId,'title'=> $domainName . '售出通知',
									'content'=> $content),'enameId'=> $agentEnameId,'templateId'=> '','type'=> 99,
							'email'=> ''));
			}
		}
		\core\Logger::write('crontab_send_domain_trans_sucess', 
			array('crontab_send_domain_trans_sucess','sucess',date('Y-m-d H:i:s')));
	}
}