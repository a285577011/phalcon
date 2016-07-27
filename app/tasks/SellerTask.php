<?php
use Phalcon\CLI\Task;
use core\driver\Redis;
use core\ModelBase;
use core\EnameApi;
use core\Logger;
use logic\task\TaskLogic;

class SellerTask extends Task
{

	/**
	 * 域名下架
	 *
	 * @var string
	 */
	const CANCEL_SALE_KEY = 'agent:cancelsale';

	/**
	 * 下架发送通知
	 *
	 * @var unknown
	 */
	const SEND_MSG_KEY = 'agent:sendmsg';

	const LOG_NAME = 'crontab_seller';

	const MAX_NUM = 500;

	public function mainAction()
	{
		echo '一个定时任务';
	}

	/**
	 * 定时删除下架域名的分销
	 */
	public function deleteOffAgentAction()
	{
		$logName = self::LOG_NAME . '_deleteoffagent';
		Logger::write($logName, ': Start.');
		$agent = new DomainAgent();
		$backup = new AgentBackup();
		$temp = new TemporarySolr();
		$spreadDomain = new ModelBase('spread_domain_record');
		$redis = Redis::getInstance();
		Logger::write($logName, ': Initialization data success.');
		if($count = $redis->lSize(self::CANCEL_SALE_KEY))
		{
			for($i = 0; $i < $count && $i < self::MAX_NUM; $i++)
			{
				$transOff = $redis->lPop(self::CANCEL_SALE_KEY);
				Logger::write($logName, ': Get redis "' . self::CANCEL_SALE_KEY . '" key success.');
				if(! $transOff['EnameId'] || ! $transOff['DomainName'] || ! $transOff['AuditListId'])
				{
					continue;
				}
				$condition['EnameId'] = $transOff['EnameId'];
				$condition['DomainName'] = $transOff['DomainName'];
				
				$fields = 'DomainAgentId,EnameId,DomainName,Price,TransType,TLD,GroupOne,GroupTwo,DomainLen,Percent,ClickNum,SimpleDec,Topic,GroupThree';
				$agentData = $agent->getData($fields, 
					array_merge(array('TransId'=> $transOff['AuditListId']), $condition), $agent::FETCH_ROW); // 是否存在分销表中
				Logger::write($logName, 
					": Get domain_agent(EnameId:{$transOff['EnameId']}, DomainName:{$transOff['DomainName']}) data success.");
				if(! empty($agentData))
				{
					if(! $agentData->DomainAgentId)
					{
						continue;
					}
					// 插入到备份表
					$type = isset($transOff['type']) && $transOff['type']? $transOff['type']: 4;
					$type == 3 && $type = 5;
					$isExit = $backup->count(
						array_merge($condition, array('DomainAgentId'=> $agentData->DomainAgentId)));
					if($isExit > 0)
					{
						$update['BackupType'] = $type;
						$affRow = $backup->update($update, $condition);
						Logger::write($logName, ": Update agent_backup set type={$type} success(AffectRow:{$affRow}).");
					}
					else
					{
						$condition['DomainAgentId'] = $agentData->DomainAgentId;
						$condition['Price'] = $agentData->Price;
						$condition['TransType'] = $agentData->TransType;
						$condition['Percent'] = $agentData->Percent;
						$condition['ClickNum'] = $agentData->ClickNum;
						$condition['TLD'] = $agentData->TLD;
						$condition['GroupOne'] = $agentData->GroupOne;
						$condition['GroupTwo'] = $agentData->GroupTwo;
						$condition['GroupThree'] = $agentData->GroupThree;
						$condition['DomainLen'] = $agentData->DomainLen;
						$condition['SimpleDec'] = $agentData->SimpleDec;
						$condition['BackupType'] = $type; // 下架域名
						$condition['CreateTime'] = time();
						$id = $backup->insert($condition);
						Logger::write($logName, ': Insert data into agent_backup success(ID:' . $id . ').');
					}
					
					// 删除分销表
					$agentRow = $agent->delete(array('DomainAgentId'=> $agentData->DomainAgentId));
					Logger::write($logName, 
						": Delete data from domain_agent(EnameId:{$agentData->EnameId}, DomainName:{$agentData->DomainName}) success($agentRow rows).");
					
					// 更新推广历史记录表
					$row = $spreadDomain->update(array('Status'=> - 1), 
						array('DomainAgentId'=> $agentData->DomainAgentId));
					Logger::write($logName, 
						": Update spread_domain_record {$row} row(s) data(DomainAgentId:{$agentData->DomainAgentId}) success.");
					
					// 拍卖会不写入消息队列
					if(0 == $agentData->Topic)
					{
						$redis->rPush(self::SEND_MSG_KEY, 
							array('DomainAgentId'=> $agentData->DomainAgentId,'DomainName'=> $agentData->DomainName));
						Logger::write($logName, ": Push the key '" . self::SEND_MSG_KEY . "' to redis success.");
					}
				}
				else
				{
					$deleteRow = $temp->delete($condition); // 删除未设置分销表中的数据
					Logger::write($logName, ": Delete temporary_solr data success(AffectRow:{$deleteRow}).");
				}
				unset($condition);
			}
		}
		
		Logger::write($logName, ': Delete off trans domain\'s agent success.');
	}

	/**
	 * 域名下架定时发送站内信给分销客
	 */
	public function sendMsgAction()
	{
		$off = array();
		$logName = self::LOG_NAME . '_sendmsg';
		Logger::write($logName, ': Start.');
		$api = new EnameApi();
		$logic = new TaskLogic();
		$redis = Redis::getInstance();
		Logger::write($logName, ': Initialization data success');
		while($redis->lSize(self::SEND_MSG_KEY))
		{
			$sendMsg = $redis->lPop(self::SEND_MSG_KEY);
			Logger::write($logName, ': Get redis key "' . self::SEND_MSG_KEY . '" success.');
			if(! $sendMsg['DomainName'] || ! $sendMsg['DomainAgentId'])
			{
				continue;
			}
			$enameId = $logic->agentEnameId($sendMsg['DomainAgentId']);
			if(empty($enameId))
			{
				continue;
			}
			
			Logger::write($logName, ': Get enameId of domain Agent "' . $sendMsg['DomainAgentId'] . '" success.');
			foreach($enameId as $userId)
			{
				$off[$userId][] = $sendMsg['DomainName'];
			}
			Logger::write($logName, ': Rebuild new array.');
		}
		if(empty($off))
		{
			exit();
		}
		foreach($off as $id => $val)
		{
			$message = implode(', ', $val);
			Logger::write($logName, ": Begin to send msg to users.");
			$title = "域名下架通知";
			$content = "亲爱的{$id}用户：<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！您所推广的域名{$message}已经下架，导致推广链接失效，请您及时<a href='http://www.ename.com.cn/agentguests/domainagent'>更换推广链接</a>。感谢您对域名联盟平台的支持！";
			$content .= "</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;如不想再接收此类邮件，请<a href='http://www.ename.com.cn/user/unsubscribe?type=2' target='_blank'>点击这里</a>退订。";
			
			// 发送消息
			$msgJson = $api->sendCmd('member/addsitemessage', 
				array('enameId'=> $id,'data'=> array('enameid'=> $id,'title'=> $title,'content'=> $content)));
			$msgData = json_decode($msgJson, TRUE);
			$msg = $msgData['code'] == 100000 && $msgData['flag'] == true? 'Send msg to user "' . $id . '" success.': $msgData;
			Logger::write($logName, ": {$msg}");
			
			// 发送邮箱
			$msgEmail = $api->sendCmd('member/sendemail', 
				array('tplData'=> array('enameid'=> $id,'title'=> $title,'content'=> $content),'enameId'=> $id,
						'templateId'=> '','type'=> 99,'email'=> ''));
			$emailData = json_decode($msgEmail, TRUE);
			$email = $emailData['code'] == 100000 && $emailData['flag'] == true? 'Send the email to ' . $id . ' success.': $emailData;
			Logger::write($logName, ": {$email}");
		}
		Logger::write($logName, ': Send message success');
	}

	/**
	 * 店铺分销时间到期定时任务修改状态为3“过期”
	 */
	public function shopExpiredAction()
	{
		$logName = self::LOG_NAME . '_shopexpired';
		Logger::write($logName, ': Start.');
		$shop = new ShopAgent();
		$spreadDomain = new ModelBase('spread_domain_record');
		$sql = "UPDATE shop_agent SET status=3 WHERE Status=1 AND FinishTime<=" . time();
		$sqlSpread = "UPDATE spread_shop_record SET Status=-1 WHERE Status=1 AND FinishTime<=" . time();
		try
		{
			$shop->query($sql);
			$row = $shop->affectRow();
			Logger::write($logName, ": Update shop_agent {$row} row(s) data success.");
			
			$spreadDomain->query($sqlSpread);
			$spreadRow = $spreadDomain->affectRow();
			Logger::write($logName, ": Update spread_shop_record {$spreadRow} row(s) data success.");
		}
		catch(\PDOException $e)
		{
			Logger::write($logName, $e->getMessage());
		}
	}
}