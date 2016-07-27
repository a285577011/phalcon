<?php
namespace logic\datastatic;
use lib\agent\AgentGuestsLib;
use lib\common\CsvExport;

use core\ModelBase;

class DataStatic
{


	protected $enameId;

	public function __construct($enameId = '')
	{
		$this->enameId = $enameId;
	}
	public function farmerStatic($dgroup,$ttype,$starttime,$endtime)
	{
		$group = \core\Config::item('domaingroup')->toArray();
		$transtype = \core\Config::item('transtype')->toArray();
		$starttime = empty($starttime) ? date('Y-m-d',strtotime('-6 days')) : $starttime ;
		$endtime = empty($endtime) ?  date('Y-m-d') : $endtime;
		
 		return array('group'=>$group,
 				'transtype'=>$transtype,
 				'endtime'=>$endtime,
 				'starttime'=>$starttime,
 				'dgroup'=>$dgroup,
 				'ttype'=>$ttype
 				);
	}
	public function ajaxFarmStatic($dgroup,$ttype,$starttime,$endtime)
	{
		$omod = new ModelBase('order_record');
		$vmod = new ModelBase('visit_record');
		$where2 = array('SellerId'=>$this->enameId);
		$where1 = array('EnameId'=>$this->enameId);
		$data = array();
		if($dgroup)
		{
			list($where1['GroupTwo'], $GroupOne, $where1['DomainLen']) = AgentGuestsLib::getDomainGroup($dgroup);
			$where1['GroupOne'] = is_array($GroupOne)? array('BETWEEN',$GroupOne): $GroupOne;
			$where2['GroupOne'] = $where1['GroupOne'];
			$where2['GroupTwo'] = $where1['GroupTwo'];
			$where2['DomainLen'] = $where1['DomainLen'];
		}
		if($ttype)
		{
			$where1['TransType'] = $where2['TransType'] = $ttype;
		}
		if($starttime && $endtime)
		{
			if(strtotime($endtime) < strtotime($starttime))
			{
				return  array('flag'=>false,'error'=>'请选择正确时间段');
			}
		}
		elseif($endtime)
		{
			$endtime = strtotime($endtime);
			$starttime = date('Y-m-d',strtotime('-6 days',$endtime));
		}
		elseif($starttime)
		{
			if(strtotime($starttime . '00:00:00') > time())
			{
				return  array('flag'=>false,'error'=>'请选择正确时间段');
			}
			$endtime = date('Y-m-d');
		}
		$dtstart = strtotime($starttime);
		$dtend = strtotime($endtime);
		$stardate = $enddate = $detailacc = $undercc = $ccnum = $clickcc = $clickrev = $avagecc = 0;
		$k = 0;
		while ($dtstart <= $dtend)
		{
			$n1 = $n2 = $n3 = $n4 = 0;
			$dates = date('Y-m-d',$dtstart);
			$stardate = strtotime($dates . '00:00:00' );
			$edndate = strtotime($dates . '23:59:59' );
			$where1['CreateTime'] = $where2['CreateTime'] = array('BETWEEN',array($stardate,$edndate));
			$data[$k]['linkdate'] = date('m-d',strtotime($dates)) ;
			//成交金额
			$detailacc += $n3 = $data[$k]['detailacc'] = doubleval($omod->sum($where1,'Price'));
			//结算佣金
			$undercc += $n4 =  $data[$k]['undercc'] = doubleval($omod->sum($where1,'Income'));			
			//付款比数
			$ccnum += $n1 = $data[$k]['ccnum'] = intval($omod->count($where1,'OrderId'));
			//点击数
			$clickcc += $n2 = $data[$k]['clickcc'] = intval($vmod->count($where2,'VisitRecId'));
			//点击转化率
			if($n1 > 0 && $n2 > 0)
			{
				$data[$k]['clickrev'] = round($n1/$n2,2);
			}			
			else
			{
				$data[$k]['clickrev'] = 0;
			}
			//平均佣金比例
			if($n3 > 0 && $n4 > 0)
			{
				$data[$k]['avagecc'] = round($n4/$n3,2);
			}
			else
			{
				$data[$k]['avagecc'] = 0;
			}
			$k ++;
			$dtstart = strtotime('+1 day',$dtstart);
		}
		$clickrev = $ccnum >0 && $clickcc >0 ? round($ccnum/$clickcc,2) : 0;
		$avagecc = $undercc >0 && $detailacc >0 ? round($undercc/$detailacc,2) : 0;
		$return = array('flag'=>true,'error'=>'','data'=>array('detailacc'=>$detailacc,
				'undercc'=>$undercc,'ccnum'=>$ccnum,'clickcc'=>$clickcc,'clickrev'=>$clickrev,
				'avagecc'=>$avagecc,'data'=>$data));
		return $return;
	}
	public function exprotFarm($ctrl ,$dgroup , $ttype , $starttime , $endtime)
	{
		$omod = new ModelBase('order_record');
		$vmod = new ModelBase('visit_record');
		$where1 = array('SellerId'=>$this->enameId);
		$where2 = array('EnameId'=>$this->enameId);
		$data = array();
		if($dgroup)
		{
			$dlib = new AgentGuestsLib();
			list($where1['GroupTwo'], $GroupOne, $where1['DomainLen']) = $dlib->getDomainGroup($dgroup);
			$where1['GroupOne'] = is_array($GroupOne)? array('BETWEEN',$GroupOne): $GroupOne;
			$where2['GroupOne'] = $where1['GroupOne'];
			$where2['GroupTwo'] = $where1['GroupTwo'];
			$where2['DomainLen'] = $where1['DomainLen'];
		}
		if($ttype)
		{
			$where1['TransType'] = $where2['TransType'] = $ttype;
		}
		if($starttime && $endtime)
		{
			if(strtotime($endtime) < strtotime($starttime))
			{
				echo '<script language="javascript">alert("请选择正确时间段");parent.location.href = "'.$ctrl->url->get('static/farmerstatic').'";</script>';
				exit();
			}
		}
		elseif($endtime)
		{
			$endtime = strtotime($endtime);
			$starttime = date('Y-m-d',strtotime('-6 days',$endtime));
		}
		elseif($starttime)
		{
			if(strtotime($starttime . '00:00:00') > time())
			{
				echo '<script language="javascript">alert("请选择正确时间段");parent.location.href = "'.$ctrl->url->get('static/farmerstatic').'";</script>';
				exit();
				//throw new \Exception('请选择正确时间段');
			}
			$endtime = date('Y-m-d');
		}
		$dtstart = strtotime($starttime);
		$dtend = strtotime($endtime);
		$stardate = $enddate = $detailacc = $undercc = $ccnum = $clickcc = $clickrev = 0;
		$k = 0;
		while ($dtstart <= $dtend)
		{
			$n1 = $n2 = $n3 = $n4 = 0;
			$dates = date('Y-m-d',$dtstart);
			$stardate = strtotime($dates . '00:00:00' );
			$edndate = strtotime($dates . '23:59:59' );
			$where2['CreateTime'] = $where1['CreateTime'] = array('BETWEEN',array($stardate,$edndate));
			$data[$k][] = date('Y-m-d',strtotime($dates)) ;
			//成交金额
			$detailacc += $n3 =  $data[$k][] = $omod->sum($where2,'Price');
			//结算佣金
			$undercc += $n4 = $data[$k][] = $omod->sum($where2,'Income');
			//付款比数
			$ccnum += $n1 =  $data[$k][] = $omod->count($where2,'OrderId');
			//点击数
			 $clickcc += $n2 = $data[$k][] = $vmod->count($where1,'VisitRecId');
			//点击转化率
			$data[$k][] =  $n1 >0 && $n2 >0 ? $n1/$n2 : 0;
			
			$data[$k][] = $n3 >0 && $n4 >0 ? $n4/$n3 : 0; 
			$k ++;
			$dtstart = strtotime('+1 day',$dtstart);
		}
		$clickrev = $ccnum >0 && $ccnum >0 ? $ccnum/$clickcc : 0 ;
		$avagecc = $undercc >0 && $detailacc >0 ?$undercc/$detailacc : 0;
		$tableName = "米掌柜数据统计" .$starttime.'_'.$endtime;
		$head = array('日期','成交金额','结算佣金','付款笔数','点击数','点击转化率','平均佣金比例');
		$data[$k+1] = array('总和', $detailacc , $undercc, $ccnum , $clickcc , $clickrev, $avagecc);
		if(empty($detailacc) && empty($ccnum) && empty($clickcc) && empty($clickrev) && empty($avagecc) && empty($undercc))
		{
			echo '<script language="javascript">alert("没有数据");parent.location.href = "'.$ctrl->url->get('static/farmerstatic?starttime='.$starttime.'&endtime='.$endtime.'&dgroup='.$dgroup.'&ttype='.$ttype).'";</script>';
				exit();
			//return  array('flag'=>false,'error'=>'没有数据');
		}
		$cvslib = new CsvExport();
		$cvslib::outcsv($tableName, $head, $data);
	}
	public function guestStatic($starttime ,$endtime,$ptype ,$stype)
	{
		$plattype = \core\Config::item('plattype')->toArray();
		$spreadtype = \core\Config::item('spreadtype')->toArray();
		$starttime = empty($starttime) ? date('Y-m-d',strtotime('-6 days')) : $starttime ;
		$endtime = empty($endtime) ?  date('Y-m-d') : $endtime;
	
		return array('plattype'=>$plattype,
				'spreadtype'=>$spreadtype,
				'endtime'=>$endtime,
				'starttime'=>$starttime,
				'ptype'=>$ptype,
				'stype'=>$stype
		);
	}
	public function ajaxGuestStatic($stype,$ptype,$starttime,$endtime)
	{
		$omod = new ModelBase('order_record');
		$vmod = new ModelBase('visit_record');
		$where1 = array('EnameId'=>$this->enameId);
		$where2 = array('AgentEnameId'=>$this->enameId);
		$data = array();
		if($stype)
		{
			$where1['AgentType'] = $where2['AgentType'] = $stype;
		}
		if($ptype)
		{
			$where1['PlatformType'] = $where2['PlatformType'] = $ptype;
		}
		if($starttime && $endtime)
		{
			if(strtotime($endtime) < strtotime($starttime))
			{
				return  array('flag'=>false,'error'=>'请选择正确时间段');
			}
		}
		elseif($endtime)
		{
			$endtime = strtotime($endtime);
			$starttime = date('Y-m-d',strtotime('-6 days',$endtime));
		}
		elseif($starttime)
		{
			if(strtotime($starttime . '00:00:00') > time())
			{
				return  array('flag'=>false,'error'=>'请选择正确时间段');
			}
			$endtime = date('Y-m-d');
		}
		$dtstart = strtotime($starttime);
		$dtend = strtotime($endtime);
		$stardate = $enddate = $detailacc = $undercc = $ccnum = $clickcc = $clickrev = $estincome = 0;
		$k = 0;
		while ($dtstart <= $dtend)
		{
			$n1 = $n2 = 0;
			$dates = date('Y-m-d',$dtstart);
			$stardate = strtotime($dates . '00:00:00' );
			$edndate = strtotime($dates . '23:59:59' );
			$where1['CreateTime'] = $where2['CreateTime'] = array('BETWEEN',array($stardate,$edndate));
			$data[$k]['linkdate'] = date('m-d',strtotime($dates)) ;
			//成交金额
			$detailacc +=  $data[$k]['detailacc'] = doubleval($omod->sum($where2,'Price'));
			//结算佣金
			$undercc += $data[$k]['undercc'] = doubleval($omod->sum($where2,'Income'));
			//付款比数
			$ccnum += $n1 = $data[$k]['ccnum'] = intval($omod->count($where2,'OrderId'));
			//点击数
			$clickcc += $n2 =  $data[$k]['clickcc'] = intval($vmod->count($where1,'VisitRecId'));
			//点击转化率
			$data[$k]['clickrev'] = $n1 >0 && $n2 >0 ? round($n1/$n2,2) : 0;
			//预估收入
			$where2['Status'] = 1;
			$estincome += $data[$k]['estincome'] = doubleval($omod->sum($where2,'Income'));
			$k ++;
			unset($where2['Status']);
			$dtstart = strtotime('+1 day',$dtstart);
		}
		$clickrev = $ccnum >0 && $clickcc >0 ? round($ccnum/$clickcc,2) : 0; 
		$return = array('flag'=>true,'error'=>'','data'=>array('detailacc'=>$detailacc,
				'undercc'=>$undercc,'ccnum'=>$ccnum,'clickcc'=>$clickcc,'clickrev'=>$clickrev,
				'estincome'=>$estincome,'data'=>$data));
		return $return;
	}
	public function exprotGuest($ctrl , $stype , $dgroup , $ptype , $starttime , $endtime)
	{
		$omod = new ModelBase('order_record');
		$vmod = new ModelBase('visit_record');
		$where1 = array('EnameId'=>$this->enameId);
		$where2 = array('AgentEnameId'=>$this->enameId);
		$data = array();
		if($stype)
		{
			$where1['AgentType'] = $where2['AgentType'] = $stype;
		}
		if($ptype)
		{
			$where1['PlatformType'] = $where2['PlatformType'] = $ptype;
		}
		$data = array();
		if($starttime && $endtime)
		{
			if(strtotime($endtime) < strtotime($starttime))
			{
				echo '<script language="javascript">alert("请选择正确时间段");parent.location.href = "'.$ctrl->url->get('static/gueststatic').'";</script>';
				exit();
			}
		}
		elseif($endtime)
		{
			$endtime = strtotime($endtime);
			$starttime = date('Y-m-d',strtotime('-6 days',$endtime));
		}
		elseif($starttime)
		{
			if(strtotime($starttime . '00:00:00') > time())
			{
				echo '<script language="javascript">alert("请选择正确时间段");parent.location.href = "'.$ctrl->url->get('static/gueststatic').'";</script>';
				exit();
			}
			$endtime = date('Y-m-d');
		}
		$dtstart = strtotime($starttime);
		$dtend = strtotime($endtime);
		$stardate = $enddate = $detailacc = $undercc = $ccnum = $clickcc = $clickrev = $estincome = 0;
		$k = 0;
		while ($dtstart <= $dtend)
		{
			$n1 = $n2 = 0;
			$dates = date('Y-m-d',$dtstart);
			$stardate = strtotime($dates . '00:00:00' );
			$edndate = strtotime($dates . '23:59:59' );
			$where1['CreateTime'] = $where2['CreateTime'] = array('BETWEEN',array($stardate,$edndate));
			$data[$k][] = date('Y-m-d',strtotime($dates)) ;
			//成交金额
			$detailacc += $data[$k][] = $omod->sum($where2,'Price');
			//结算佣金
			$undercc += $data[$k][] = $omod->sum($where2,'Income');
			//付款比数
			$ccnum += $n1 = $data[$k][] = $omod->count($where2,'OrderId');
			//点击数
			$clickcc +=  $n2 = $data[$k][] = $vmod->count($where1,'VisitRecId');
			//点击转化率
			$data[$k][] = $n1 >0 && $n2 >0 ? $n1/$n2 : 0;
				
			//预估收入
			$where2['Status'] = 1;
			$estincome += $data[$k][] = $omod->sum($where2,'Income');;
			$k ++;
			unset($where2['Status']);
			$dtstart = strtotime('+1 day',$dtstart);
		}
		$clickrev = $ccnum >0 && $clickcc >0 ? $ccnum/$clickcc : 0; 
		if(empty($detailacc) && empty($ccnum) && empty($clickcc) && empty($clickrev) && empty($estincome) && empty($undercc))
		{
			echo '<script language="javascript">alert("没有数据");parent.location.href = "'.$ctrl->url->get('static/gueststatic?starttime='.$starttime.'&endtime='.$endtime.'&ptype='.$ptype.'&stype='.$stype).'";</script>';
			exit();
		}
		$tableName = "米客数据统计" . $starttime.'_'.$endtime;
		$head = array('日期','成交金额','结算佣金','付款笔数','点击数','点击转化率','预估收入');
		$data[$k+1] = array('总和', $detailacc , $undercc, $ccnum , $clickcc , $clickrev, $estincome);
		$cvslib = new CsvExport();
		$cvslib::outcsv($tableName, $head, $data);
	}
}