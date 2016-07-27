<?php
use \core\ModelBase;
use \solr\DomainAuctionSolr;
use \core\driver\Redis;
use logic\task\TaskLogic;
use core\EnameApi;
use Phalcon\Cache\Frontend\Data;
use Phalcon\Cache\Backend\File;
use es\DomainElasticSearch;

class AgentTask extends \Phalcon\CLI\Task
{

	const UPDATE_NUM = 100; // 每次更新数量
	const JINJIA_UPDATE_TIME = 300; // 域名竞价更新时间 5分钟
	const SHOP_UPDATE_TIME = 3600; // 店铺更新时间 1一个小时
	const YIKOU_UPDATE_TIME = 3600; // 域名一口价更新时间 1个小时
	const SHOP_CLOSE_KEY = 'agent_shop_close';

	const MAX_NUM = 100;

	public function mainAction()
	{
		echo '一个定时任务';
	}

	/**
	 * 分销域名价格更新任务(竞价)
	 */
	public function domainJinJiaAction() // 定时更新域名
	{
		$this->updateAgentDomain(1);
		exit();
	}

	/**
	 * 分销域名价格更新任务(一口价)
	 */
	public function domainYiKouAction() // 定时更新域名
	{
		$this->updateAgentDomain(4);
		exit();
	}

	/**
	 * 店鋪數據更新任務
	 */
	public function shopAction()
	{
		\core\Logger::write('crontab_update_shop', array('update_shop','start',date('Y-m-d H:i:s')));
		$shopModel = new ModelBase('shop_agent');
		$spreadShopR = new ModelBase('spread_shop_record');
		$where = array();
		$updateTime = time() - self::SHOP_UPDATE_TIME;
		$where['UpdateTime'] = array('<=',$updateTime);
		// $where['FinishTime'] = array('>=',time()); // 更新店铺没过期的
		$count = $shopModel->count($where, 'ShopAgeId');
		$size = ceil($count / self::UPDATE_NUM);
		$api = new \core\EnameApi();
		for($i = 0; $i < $size; $i++)
		{
			$enameIdArr = $shopModel->getData('ShopAgeId,EnameId,Status,FinishTime', $where, $shopModel::FETCH_ALL, 
				false, array($i * self::UPDATE_NUM,self::UPDATE_NUM));
			if(! $enameIdArr)
			{
				\core\Logger::write('crontab_update_shop', array('update_shop',$shopModel->getLastSql()));
				// echo 'update domian_agent sucess';
				break;
			}
			foreach($enameIdArr as $val)
			{
				switch($val->Status)
				{
					case 1:
						$rs = $api->sendCmd('agent/shopinfo', array('EnameId'=> $val->EnameId));
						$rs = json_decode($rs);
						if(! $rs->flag && $rs->code == 120002)
						{
							$spreadShopR->update(array('Status'=> - 1), array('ShopAgentId'=> $val->ShopAgeId));
							\core\Logger::write('crontab_update_shop', 
								array('update_shop','shop_colse',date('Y-m-d H:i:s')));
							if($shopModel->update(array('Status'=> 2), array('EnameId'=> $val->EnameId)) === false)
							{
								\core\Logger::write('crontab_update_shop', 
									array('update_shop','shop_colse_fail',date('Y-m-d H:i:s')));
							}
							Redis::getInstance()->rPush(self::SHOP_CLOSE_KEY, $val->EnameId);
							continue;
						}
						if($rs->flag)
						{
							$DomainNum = $rs->msg->DomainCount;
							$GoodRating = $rs->msg->sellerGoodRate * 100;
							$Credit = $rs->msg->sellerLevel;
							$Logo = $rs->msg->Avatar;
							$Notice = $rs->msg->ShopAnnounce;
							$Name = $rs->msg->ShopName;
							$Recommands = implode(',', $rs->msg->shopRecommands);
							
							$shopModel->update(
								array('DomainNum'=> $DomainNum,'GoodRating'=> $GoodRating,'Credit'=> $Credit,
										'UpdateTime'=> time(),'Logo'=> $Logo,'Notice'=> $Notice,'Name'=> $Name,
										'Recommands'=> $Recommands), array('EnameId'=> $val->EnameId));
						}
						break;
					case 2:
						$rs = $api->sendCmd('agent/shopinfo', array('EnameId'=> $val->EnameId));
						$rs = json_decode($rs);
						if($rs->flag)
						{
							$status = $val->FinishTime > time()? 3: 1;
							$DomainNum = $rs->msg->DomainCount;
							$GoodRating = $rs->msg->sellerGoodRate * 100;
							$Credit = $rs->msg->sellerLevel;
							$Logo = $rs->msg->Avatar;
							$Notice = $rs->msg->ShopAnnounce;
							$Name = $rs->msg->ShopName;
							$Recommands = implode(',', $rs->msg->shopRecommands);
							$shopModel->update(
								array('DomainNum'=> $DomainNum,'GoodRating'=> $GoodRating,'Credit'=> $Credit,
										'UpdateTime'=> time(),'Status'=> $status,'Logo'=> $Logo,'Notice'=> $Notice,
										'Name'=> $Name,'Recommands'=> $Recommands), array('EnameId'=> $val->EnameId));
						}
						break;
				}
			}
		}
		\core\Logger::write('crontab_update_shop', array('update_shop','update sho_agent sucess',date('Y-m-d H:i:s')));
		exit();
	}

	/**
	 * 根据交易类型更新分销域名表价格
	 *
	 * @param unknown $transType
	 */
	public function updateAgentDomain($transType)
	{
		\core\Logger::write('crontab_update_domain', 
			array('updateAgent_transtype:' . $transType,'start',date('Y-m-d H:i:s')));
		$domainModel = new ModelBase('domain_agent');
		$lib = new lib\agent\AgentManagerLib();
		$where = array();
		$updateTime = $transType == 1? time() - self::JINJIA_UPDATE_TIME: time() - self::YIKOU_UPDATE_TIME;
		$where['UpdateTime'] = array('<=',$updateTime);
		$where['TransType'] = $transType;
		$count = $domainModel->count($where, 'TransId');
		$size = ceil($count / self::UPDATE_NUM);
		//$solr = new DomainAuctionSolr();
		$domainEs = new DomainElasticSearch();
		for($i = 0; $i < $size; $i++)
		{
			$transIdArr = $domainModel->getData('DomainAgentId,TransId', $where, $domainModel::FETCH_ALL, false, 
				array($i * self::UPDATE_NUM,self::UPDATE_NUM));
			if(! $transIdArr)
			{
				\core\Logger::write('crontab_update_domain', 
					array('updateAgent_start_transtype:' . $transType,$domainModel->getLastSql()));
				echo 'update domian_agent sucess';
			}
			foreach($transIdArr as $val)
			{
// 				$data = $solr->getById($val->TransId);
				$data = $domainEs->getInfoById($val->TransId);
				if(! $data)
				{
					continue;
				}
// 				if($data['numFound'])
// 				{
// 					$price = $data->docs[0]['BidPrice'];
// 					$FinishTime = strtotime(str_replace(array('T','Z'), ' ', $data->docs[0]['FinishDate']));
// 					$domainModel->update(array('Price'=> $price,'FinishTime'=> $FinishTime,'UpdateTime'=> time()), 
// 						array('DomainAgentId'=> $val->DomainAgentId));
// 				}
				if($data['total'])
				{
					$price = $data['data'][0]['_source']['t_now_price'];
					$FinishTime = $data['data'][0]['_source']['t_complate_time'];
					$domainModel->update(array('Price'=> $price,'FinishTime'=> $FinishTime,'UpdateTime'=> time()),
						array('DomainAgentId'=> $val->DomainAgentId));
				}
			}
		}
		\core\Logger::write('crontab_update_domain', 
			array('updateAgent_transtype:' . $transType,'update domian_agent sucess',date('Y-m-d H:i:s')));
	}

	/**
	 * 无数据则删除店铺数据
	 *
	 * @param unknown $TransId
	 * @param unknown $domainModel
	 */
	private function delShop($enameId)
	{
		$model = new ModelBase('shop_agent');
		if(! $model->delete(array('EnameId'=> $enameId)))
		{
			// log
		}
	}

	/**
	 * 店铺关闭发送消息
	 */
	public function SendMsgByShopCloseAction()
	{
		\core\Logger::write('crontab_send_msg_shop_close', array('send_msg_shop_close','start',date('Y-m-d H:i:s')));
		$msg = array();
		while(Redis::getInstance()->lSize(self::SHOP_CLOSE_KEY))
		{
			$adminApi = new EnameApi(\core\Config::item('apiTrans'));
			$logic = new TaskLogic();
			if(! $enameId = Redis::getInstance()->lPop(self::SHOP_CLOSE_KEY))
			{
				continue;
			}
			$agentEnameId = $logic->getAgentEnameId($enameId);
			
			if(empty($agentEnameId))
			{
				\core\Logger::write('crontab_send_msg_shop_close', array('enameId:' . $enameId,'no body agent this'));
				continue;
			}
			foreach($agentEnameId as $agentor)
			{
				$msg[$agentor][] = $enameId;
			}
		}
		if(empty($msg))
		{
			exit();
		}
		foreach($msg as $key => $val)
		{
			$title = "店铺关闭通知";
			$content = "亲爱的{$key}用户:</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;您好！你所推广店铺" . implode(',', $val) .
				 "因卖家关闭店铺的原因，导致链接暂时无法访问，请您及时<a href=http://www.ename.com.cn/agentguests/shopagent>更换店铺推广链接</a>或耐心等待店铺重新开启。感谢您对域名联盟平台的支持！";
			$content .= "</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;如不想再接收此类邮件，请<a href='http://www.ename.com.cn/user/unsubscribe?type=1' target='_blank'>点击这里</a>退订。";
			
			// 发送消息
			$adminApi->sendCmd('member/addsitemessage', 
				array('data'=> array('enameid'=> $key,'title'=> $title,'content'=> $content),'enameId'=> $key,
						'templateId'=> '','type'=> 99));
			// 发送邮箱
			$adminApi->sendCmd('member/sendemail', 
				array('tplData'=> array('enameid'=> $key,'title'=> $title,'content'=> $content),'enameId'=> $key,
						'templateId'=> '','type'=> 99,'email'=> ''));
		}
		\core\Logger::write('crontab_send_msg_shop_close', array('send_msg_shop_close','sucess',date('Y-m-d H:i:s')));
	}

	/**
	 * 获取专题拍卖的域名直接进入米市
	 */
	public function getTopciTransAction()
	{
		\core\Logger::write('crontab_getTopciTrans', array('getTopciTrans','start',date('Y-m-d H:i:s')));
		// $frontCache = new Data(array("lifetime"=> 86400 * 365));
		// $cache = new File($frontCache, array("cacheDir"=> ROOT_PATH .
		// "app/cache/"));
		// $cacheKey = "getTopciTrans";
		// $time = $cache->get($cacheKey)? : 1;
		$logic = new TaskLogic();
		$model = new ModelBase('domain_agent');
		//$solr = new DomainAuctionSolr();
		$domainEs = new DomainElasticSearch();
// 		if(! $solr->ping())
// 		{
// 			exit();
// 		}
		$time = time() - 600;
// 		$data = $solr->getTransByTopic(8, $time);
		$data = $domainEs->getInfoByTopic(1, $time);
		if($data['total'])
		{
			foreach($data['data'] as $k => $v)
			{
				$v = $v['_source'];
				$domain = $v['t_dn'];
				$seller = $v['t_enameId'];
				$transId = $v['t_id'];
				$price = $v['t_now_price'];
				$finishTime = $v['t_complate_time'];
				// $creatTime=strtotime(str_replace(array('T','Z'), ' ',
				// $v['CreateDate']));
				if($agentData = $logic->checkIsSell($model, $domain, $seller))
				{
					\core\Logger::write('crontab_getTopciTrans', 
						array('getTopciTrans_get_Data',json_encode($agentData)));
					$logic->updateAgent($model, $domain, $seller, $finishTime, 0.5, $transId, $price);
				}
				else
				{
					$tld = $v['t_tld'];
					$domainSysOne = $v['t_class_name'];
					$domainSysTwo = $v['t_two_class'];
					$domainLength = $v['t_len'];
					$domainSysThree = $v['t_three_class'];
// 					$tld = \common\domain\Domain::getDomainLtd($domain);
// 					extract(\common\domain\Domain::getDomainGroup($domain));
					if(! $model->insert(
						array('TransId'=> $transId,'DomainName'=> $domain,'EnameId'=> $seller,
								'TransType'=> $v['t_type'],'TLD'=> $tld,'FinishTime'=> $finishTime,'Percent'=> 0.5,
								'GroupOne'=> $domainSysOne,'GroupTwo'=> $domainSysTwo,'DomainLen'=> $domainLength,
								'CreateTime'=> time() - \core\Config::item('edittime'),'UpdateTime'=> time(),
								'ClickNum'=> 0,'Price'=> $price,'SimpleDec'=> $v['t_desc'],'Topic'=> 8,'GroupThree'=>$domainSysThree)))
					{
						\core\Logger::write('crontab_getTopciTrans', 
							array('getTopciTrans_insert_false','AuditListId',$v['t_id']));
					}
					else
					{
						\core\Logger::write('crontab_getTopciTrans', 
							array('getTopciTrans_insert_sucess','AuditListId',$v['t_id'],$domain));
					}
				}
				// $cache->save($cacheKey, $creatTime);
			}
		}
		\core\Logger::write('crontab_getTopciTrans', array('getTopciTrans','sucess',date('Y-m-d H:i:s')));
	}
}