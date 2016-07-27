<?php
namespace lib\user;
use core\ModelBase;
class UserLib
{

	/**
	 * 设置时间范围
	 * 
	 * @param unknown $startDate
	 * @param unknown $endDate
	 * @return multitype:string number |multitype:string multitype:number
	 * |boolean
	 */
	public static function setTimeRange($startDate, $endDate)
	{
		$startDate = strtotime($startDate);
		$endDate = strtotime($endDate);
		if($startDate && $endDate)
		{
			if($endDate < $startDate)
			{
				return array('>=',$startDate);
			}
			return array('between',array($startDate,$endDate + 86399));
		}
		elseif($endDate)
		{
			return array('<',$endDate + 86400);
		}
		elseif($startDate)
		{
			return array('>=',$startDate);
		}
		else
		{
			return array('between',array(strtotime(date('Y-m-d')) - \core\Config::item('OrderDetailTime'),time()));
		}
		return false;
	}
	public  static function getUserStatus($enameid, $status)
	{
		$umod = new ModelBase('user_list');
		$uinfo = $umod->getData('Status',array('EnameId'=>$enameid),$umod::FETCH_ROW);
		if($uinfo)
		{
			$oldstatus = $uinfo->Status;
			if($oldstatus == 7)
			{
				return true;
			}
			if($status == 1)
			{
				if($oldstatus == 4 || $oldstatus == 5 || $oldstatus == 1)
				{
					return true;
				}
			}
			elseif($status == 2)
			{
				if($oldstatus == 4 || $oldstatus == 6 || $oldstatus == 2)
				{
					return true;
				}
			}
			elseif($status == 3)
			{
				if($oldstatus == 5 || $oldstatus == 6 || $oldstatus == 3)
				{
					return true;
				}
			}
			return false;
		}
		return true;
	}
	public static function setUserGuideStatus($enameid ,$status = 1)
	{
		$newstatus = 0;
		$umod = new ModelBase('user_list');
		$uinfo = $umod->getData('Status',array('EnameId'=>$enameid),$umod::FETCH_ROW);
		if($uinfo)
		{
			$oldstatus = intval($uinfo->Status);
			if($oldstatus == 7)
			{
				return true;
			}
			if($status == 1)
			{
				if($oldstatus == 4 || $oldstatus == 5 || $oldstatus == 1)
				{
					return true;
				}
				elseif($oldstatus == 2)
				{
					$newstatus = 4 ;
				}
				elseif($oldstatus == 3)
				{
					$newstatus = 5 ;
				}
				elseif($oldstatus == 6)
				{
					$newstatus = 7 ;
				}
				else
				{
					$newstatus = 1 ;
				}
				
			}
			elseif ($status == 2)
			{
				if($oldstatus == 4 || $oldstatus == 6 || $oldstatus == 2)
				{
					return true;
				}
				elseif($oldstatus == 1)
				{
					$newstatus = 4 ;
				}
				elseif($oldstatus == 3)
				{
					$newstatus = 6 ;
				}
				elseif($oldstatus == 5)
				{
					$newstatus = 7 ;
				}
				else{
					$newstatus = 2 ;
				}
			}
			elseif ($status == 3)
			{
				if($oldstatus == 5 || $oldstatus == 6 || $oldstatus == 3)
				{
					return true;
				}
				elseif($oldstatus == 2)
				{
					$newstatus = 6 ;
				}
				elseif($oldstatus == 1)
				{
					$newstatus = 5 ;
				}
				elseif($oldstatus == 4)
				{
					$newstatus = 7 ;
				}
				else{
					$newstatus = 3 ;
				}
			}
			return $umod->update(array('Status'=>$newstatus ),array('EnameId'=>$enameid) );
		}
		return false;
	}
}