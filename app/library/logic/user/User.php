<?php
namespace logic\user;
use core\ModelBase;
use core\Page;
use lib\user\UserLib;
use lib\common\CsvExport;
use core\EnameApi;
use core\Config;

class User
{

	protected $enameId;

	public function __construct($enameId = '')
	{
		$this->enameId = $enameId;
	}

	/**
	 * 获取财务记录数据
	 *
	 * @param unknown $start
	 * @return multitype:Ambigous <\logic\user\unknown, unknown> \driver\mixed
	 * Ambigous <\driver\mixed, \core\mixed> string
	 */
	public function getFinance($start)
	{
		$data = array();
		$uModal = new ModelBase('user_list');
		$orderM = new ModelBase('order_record');
		$field = 'IncomeMoney,PayMoney,Money';
		$data['fina'] = $uModal->getData($field, array('EnameId'=> $this->enameId), $uModal::FETCH_ROW);
		$orderM->query(
			"select sum(Income) from order_record where AgentEnameId={$this->enameId} AND Status=1 AND Topic=0");
		$data['sucessMoney'] = $orderM->getOne();
		$fModal = new ModelBase('finance_record');
		$data['record'] = $fModal->getData('Money,Remark,CreateTime,MoneyType,FinanceType', 
			array('EnameId'=> $this->enameId), $fModal::FETCH_ALL, 'CreateTime DESC', 
			array($start,\core\Config::item('pagesize')));
		$data['record'] = $this->formatFinance($data['record']);
		$count = $fModal->count(array('EnameId'=> $this->enameId), 'FinanceRId');
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		return $data;
	}

	/**
	 * 格式化财务记录数据
	 *
	 * @param unknown $record
	 * @return unknown
	 */
	public function formatFinance($record)
	{
		if($record)
		{
			foreach($record as $key => $val)
			{
				$record[$key]->MoneyTypeCn = \core\Config::item('MoneyType')->toArray()[$record[$key]->MoneyType];
				$record[$key]->FinanceTypeCn = \core\Config::item('Finncetype')->toArray()[$record[$key]->FinanceType];
				$record[$key]->CreateTime = date('Y-m-d', $record[$key]->CreateTime);
				$record[$key]->Remark = $record[$key]->Remark? : '-';
			}
		}
		return $record;
	}

	/**
	 * 获取订单详情数据
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $orderType
	 * @param unknown $start
	 * @return multitype:Ambigous <\logic\user\unknown, unknown> string Ambigous
	 * <\driver\mixed, \core\mixed>
	 */
	public function getOrderDetail($startDate, $endDate, $orderType, $start, $topic)
	{
		$data = array();
		$where = $this->setWhere($startDate, $endDate, $orderType, $topic);
		$model = new ModelBase('order_record');
		$data['list'] = $model->getData('Percent,CreateTime,CheckoutTime,Status,Price,TransType,DomainName,OrderId', 
			$where, $model::FETCH_ALL, 'CreateTime DESC', array($start,\core\Config::item('pagesize')));
		$data['list'] = $this->formatOrderDetail($data['list']);
		$count = $model->count($where, 'OrderId');
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		return $data;
	}

	/**
	 * 设置订单详情数据条件
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $orderType
	 * @return multitype:unknown string Ambigous <\lib\user\multitype:string,
	 * boolean, multitype:string number , multitype:string unknown ,
	 * multitype:string multitype:number unknown , multitype:string
	 * multitype:number >
	 */
	public function setWhere($startDate, $endDate, $orderType, $topic)
	{
		$where = array();
		$date = UserLib::setTimeRange($startDate, $endDate);
		$where['CreateTime'] = $date;
		$orderType && $where['Status'] = $orderType;
		$where['AgentEnameId'] = $this->enameId;
		$where['Topic'] = $topic;
		return $where;
	}

	/**
	 * 格式化订单详情数据
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	public function formatOrderDetail($data)
	{
		if($data)
		{
			foreach($data as $key => $val)
			{
				$data[$key]->TransTypeCn = \core\Config::item('TransType')->toArray()[$data[$key]->TransType];
				$data[$key]->StatusCn = \core\Config::item('OrderType')->toArray()[$data[$key]->Status];
				$data[$key]->CreateTime = date('Y-m-d', $data[$key]->CreateTime);
				$data[$key]->commission = $data[$key]->Price * ($data[$key]->Percent / 100);
			}
		}
		return $data;
	}

	/**
	 * 获取财务历史数据
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $start
	 * @return multitype:Ambigous <\logic\user\unknown, unknown> \driver\mixed
	 * Ambigous <\driver\mixed, \core\mixed> string
	 */
	public function getFinanceRecord($startDate, $endDate, $start)
	{
		$data = array();
		$uModal = new ModelBase('user_list');
		$orderM = new ModelBase('order_record');
		$financeM = new ModelBase('finance_record');
		$orderM->query(
			"select sum(Income) from order_record where AgentEnameId={$this->enameId} AND Status=1 AND Topic=0");
		$data['sucessMoney'] = $orderM->getOne();
		$data['Money'] = $uModal->getData('Money', array('EnameId'=> $this->enameId), $uModal::FETCH_COLUMN);
		$where = array('EnameId'=> $this->enameId,'MoneyType'=> 1,'FinanceType'=> 2);
		$where['CreateTime'] = UserLib::setTimeRange($startDate, $endDate);
		$data['list'] = $financeM->getData('Money,Remark,CreateTime', $where, $financeM::FETCH_ALL, 'CreateTime DESC', 
			array($start,\core\Config::item('pagesize')));
		$data['list'] = $this->formatFinanceRecord($data['list']);
		$count = $financeM->count($where, 'FinanceRId');
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		return $data;
	}

	/**
	 * 格式化财务历史数据
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	public function formatFinanceRecord($data)
	{
		if($data)
		{
			foreach($data as $key => $val)
			{
				$data[$key]->StatusCn = '已经转出';
				$data[$key]->CreateTime = date('Y-m-d H:i:s', $data[$key]->CreateTime);
			}
		}
		return $data;
	}

	/**
	 * 分销转出
	 *
	 * @param unknown $price
	 * @throws \PDOException
	 * @return multitype:number
	 */
	public function doTurnOut($price)
	{
		if($price)
		{
			$model = new ModelBase('user_list');
			try
			{
				$model->begin();
				if(! $money = $this->checkMoney($price))
				{
					throw new \PDOException('价格错误');
				}
				$newMoney = $money - $price;
				$model->exec(
					"INSERT INTO finance_record (EnameId,Money,Remark,CreateTime,MoneyType,FinanceType,Balance)
					VALUES({$this->enameId},{$price},''," . time() . ",1,2,{$newMoney})");
				$model->exec(
					"UPDATE user_list SET Money=Money-{$price},PayMoney=PayMoney+{$price} WHERE EnameId={$this->enameId}");
				$adminApi = new EnameApi(\core\Config::item('apiTrans'));
				$rs = $adminApi->sendCmd('finance/addmoney', 
					array('moneyType'=> '3','enameId'=> $this->enameId,'domain'=> '','remark'=> '余额转出','price'=> $price,
							'inType'=> 99));
				$rs = json_decode($rs);
				if($rs->code != 100000 || ! $rs->flag)
				{
					 throw new \PDOException('入款失败');
				}
				$model->commit();
			}
			catch(\PDOException $e)
			{
				$model->rollback(); // 执行失败，事务回滚
				return array('status'=> 0);
			}
			return array('status'=> 1);
		}
		return array('status'=> 0);
	}

	/**
	 * 检查转出的金额
	 *
	 * @param unknown $price
	 * @return boolean
	 */
	public function checkMoney($price)
	{
		$model = new ModelBase('user_list');
		$oldPrice = $model->getData('Money', array('EnameId'=> $this->enameId), $model::FETCH_COLUMN);
		if($price > $oldPrice)
		{
			return false;
		}
		return $oldPrice;
	}

	/**
	 * 导出订单详情数据
	 *
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @param unknown $orderType
	 */
	public function exportOrderDetail($ctrl, $startDate, $endDate, $orderType, $topic)
	
	{
		$data = array();
		$where = $this->setWhere($startDate, $endDate, $orderType, $topic);
		$model = new ModelBase('order_record');
		$data = $model->getData('OrderId,CreateTime,DomainName,Status,TransType,Price,Percent', $where);
		$query = http_build_query(
			array('startDate'=> $startDate,'endDate'=> $endDate,'OrderType'=> $orderType,'topic'=> $topic));
		if(empty($data))
		{
			echo '<script language="javascript">alert("没有数据");parent.location.href = "' . $ctrl->url->get(
				'user/orderdetail?' . $query) . '";</script>';
			exit();
		}
		$cvslib = new CsvExport();
		$head = array('订单编号','时间','域名','订单状态','交易类型','成交价格','佣金比例','佣金金额');
		$data = $this->formatCsv($data);
		$cvslib::outcsv(date('Y-m-d', time()) . 'order', $head, $data);
	}

	/**
	 * 格式化数据为CSV可用数组
	 *
	 * @param unknown $data
	 * @return Ambigous <multitype:, unknown>
	 */
	public function formatCsv($data)
	{
		$csv = array();
		if($data)
		{
			foreach($data as $key => $val)
			{
				$data[$key]->CreateTime = date('Y-m-d', $data[$key]->CreateTime);
				$data[$key]->Status = \core\Config::item('OrderType')->toArray()[$data[$key]->Status];
				$data[$key]->TransType = \core\Config::item('TransType')->toArray()[$data[$key]->TransType];
				$data[$key]->Percent = $data[$key]->Percent . '%';
				$data[$key]->commission = $data[$key]->Price * ($data[$key]->Percent / 100);
			}
			foreach($data as $key => $val)
			{
				foreach($val as $cVal)
				{
					$csv[$key][] = $cVal;
				}
			}
		}
		return $csv;
	}

	/**
	 * 获取用户信息
	 *
	 * @param unknown $status
	 * @param number $page
	 * @return multitype:boolean unknown |multitype:boolean string
	 */
	public function getUserMessage($status = 3, $offset = 0, $pageSize = 10)
	{
		$offset = $offset? $offset: 0;
		$page = $offset / $pageSize + 1;
		$status = $status? $status: 3;
		$config = Config::item('apiTrans');
		$api = new EnameApi($config);
		$params = array('enameId'=> $this->enameId,'pagenum'=> $page,'pageSize'=> $pageSize,'messageType'=> '99',
				'status'=> $status);
		$data = json_decode($api->sendCmd('member/distrimsglist', $params), TRUE);
		if($data['code'] == 100000)
		{
			$messageList = $data['msg']['data'];
			$count = $data['msg']['count'];
			$isEmpty = empty($messageList)? : FALSE;
			return array($isEmpty,$messageList,$count);
		}
		else
		{
			\core\Logger::write('user_member_setdistrimsg', $data);
			return array(TRUE,'',0);
		}
	}

	/**
	 * 將站內信的状态设置为5“删除”|4已读
	 *
	 * @param unknown $messageId
	 */
	public function setMsgStatus($messageId, $status)
	{
		try
		{
			$api = new EnameApi();
			foreach($messageId as $id)
			{
				$params = array('messageId'=> $id,'status'=> $status,'enameId'=> $this->enameId);
				$data = json_decode($api->sendCmd('member/setdistrimsg', $params), TRUE);
				if($data['code'] != 100000 && ! $data['flag'])
				{
					\core\Logger::write('user_member_setdistrimsg', $data);
					continue;
				}
			}
			return TRUE;
		}
		catch(Exception $e)
		{
			return FALSE;
		}
	}

	public function changeIsAgree($status)
	{
		$uModal = new ModelBase('user_list');
		\core\Logger::write('changeIsAgree', 
			array('EnameId:' . $this->enameId,'ip:' . \common\Client::getClientIp(0),'time:' . date('Y-m-d H:i:s')));
		return $uModal->update(array('isAgree'=> $status), array('EnameId'=> $this->enameId));
	}

	public function setUserGuideStatus($status)
	{
		$return = \lib\user\UserLib::setUserGuideStatus($this->enameId, $status);
		return $return;
	}
	
	/**
	 * 退订店铺关闭或域名下架邮件通知
	 * 
	 * @param int $unsubscribe
	 * @param int $type
	 */
	public function unsubscribeMsg($unsubscribe, $type)
	{
		if(empty($unsubscribe))
		{
			echo "<script type='text/javascript'>alert('请选择您退订此邮件的原因');parent.location.href='/user/unsubscribe?type={$type}';</script>";
			exit();
		}
		else 
		{
			$user = new ModelBase('user_list');
			$userInfo = $user->getData('isAgree,Email_Type', array('EnameId'=>$this->enameId), $user::FETCH_ROW);
			if($userInfo->isAgree == $type || 3 == $userInfo->isAgree)
			{
				$url = '/';
				$msg = '已退订成功，无需重复操作';
			}
			else
			{
				$type = intval($type);
				$unsubscribe = \core\Config::item('unsubscribe_email')[$type][$unsubscribe][0];
				$unsubscribe = $userInfo->Email_Type? "{$userInfo->Email_Type},{$unsubscribe}": $unsubscribe;
				$user->query("UPDATE `user_list` SET `isAgree`=`isAgree` | :Type,`Email_Type`=:Unsubscribe WHERE `EnameId`=:EnameId", array(':Type'=>$type, ':Unsubscribe'=>$unsubscribe, ':EnameId'=>$this->enameId));
				$row = $user->affectRow();
				$msg = $row? '您已经取消此类邮件的通知，如需再次接收到此类邮件，请联系客服开通。': '退订失败请联系客服确认是否已经退订。';
				$url = $row? '/': "/user/unsubscribe?type={$type}";
			}
			
			echo "<script type='text/javascript'>alert('{$msg}');parent.location.href='{$url}';</script>";
			exit();
		}
	}
}
