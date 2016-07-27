<?php
namespace logic\agent;
use \core\ModelBase;
use \core\Page;

class Platform
{

	protected $lib;

	protected $enameId;

	public function __construct($enameId = '')
	{
		$this->enameId = $enameId;
	}

	/**
	 * 添加自有网站平台
	 * 
	 * @param unknown $siteName
	 * @param unknown $site
	 * @param unknown $siteType
	 * @param unknown $decr
	 * @param unknown $PlatformType
	 * @return Ambigous <number, string>
	 */
	public function addPlatform($siteName, $site, $siteType, $decr, $PlatformType)
	{
		$model = new ModelBase('platform');
		$decr = $decr == '可以针对网站人群、网站作用、网站流量等介绍'? '': $decr;
		$insert = array('EnameId'=> $this->enameId,'Name'=> $siteName,'Url'=> $site,'ClassType'=> $siteType,
				'Description'=> $decr,'Message'=> '','PlatformType'=> $PlatformType);
		return $model->insert($insert);
	}

	/**
	 * 获取平台列表
	 * 
	 * @param unknown $siteName
	 * @param unknown $start
	 * @param unknown $type
	 * @return string
	 */
	public function getPlatformList($siteName, $start, $type)
	{
		$where = array();
		$siteName && $where['Name'] = '%' . $siteName . '%';
		$where['PlatformType'] = $type;
		$where['EnameId'] = $this->enameId;
		$model = new ModelBase('platform');
		$limit = array($start,\core\Config::item('pagesize'));
		$field = 'PlatformId,Name,Url,ClassType,Description,PlatformType';
		$data['list'] = $model->getData($field, $where, $model::FETCH_ALL, false, $limit);
		$type==1&&$data['list'] = $this->formatSiteList($data['list']);
		$count = $model->count($where, 'PlatformId');
		$page = new Page($count, \core\Config::item('pagesize'));
		$data['page'] = $page->show();
		$data['isLastPage']=$page->pageCount==$page->nowPage?true:false;
		return $data;
	}

	/**
	 * 更新平台信息
	 * 
	 * @param unknown $siteName
	 * @param unknown $site
	 * @param unknown $siteType
	 * @param unknown $decr
	 * @param unknown $PlatformId
	 * @return boolean
	 */
	public function updatePlatform($siteName, $site, $siteType, $decr, $PlatformId)
	{
		$model = new ModelBase('platform');
		$value = array('Name'=> $siteName,'Url'=> $site,'ClassType'=> $siteType,'Description'=> $decr,'Message'=> '');
		$res = $model->update($value, array('PlatformId'=> $PlatformId,'EnameId'=> $this->enameId));
		if($res !== false)
		{
			return true;
		}
		return false;
	}

	/**
	 * 根据ID获取平台信息
	 * 
	 * @param unknown $PlatformId
	 * @return Ambigous <\driver\mixed, \core\mixed>|boolean
	 */
	public function getSiteById($PlatformId)
	{
		if($PlatformId)
		{
			$model = new ModelBase('platform');
			$field = 'Name,Url,ClassType,Description,PlatformType';
			$where = array('PlatformId'=> $PlatformId,'EnameId'=> $this->enameId);
			return $model->getData($field, $where, $model::FETCH_ROW);
		}
		return false;
	}

	/**
	 * 删除平台
	 * 
	 * @param unknown $PlatformId
	 * @return multitype:string |boolean|number
	 */
	public function deletePlatformById($PlatformId)
	{
		$model = new ModelBase('platform');
		$visitRecordM=new ModelBase('visit_record');
		$error = array();
		$PlatformId=(array)$PlatformId;
		if(is_array($PlatformId) && ! empty($PlatformId))
		{
			foreach($PlatformId as $id)
			{
				
				if(! intval($id))
				{
					continue;
				}
				$res = $model->delete(array('PlatformId'=> intval($id),'EnameId'=> $this->enameId));
				if(! $res)
				{
					$name = $model->getData('Name', array(array('PlatformId'=> intval($id),'EnameId'=> $this->enameId)), 
						$model::FETCH_COLUMN);
					$error[] = $name . ' 删除失败，请重试！';
				}
				if(!$visitRecordM->update(array('Status'=>-1),array('PlatformId'=>intval($id)))){
					\core\Logger::write('PLATFORM',array(__CLASS__ . '::' . __FUNCTION__,'PlatformId:'.$id.'更新记录表失败'));
				}
			}
			if(! empty($error))
			{
				return $error;
			}
			return true;
		}
		return false;
	}

	/**
	 * 根据类型获取【平台数据
	 * 
	 * @param unknown $type
	 * @throws \Exception
	 * @return Ambigous <\driver\mixed, \core\mixed>
	 */
	public function getSite($type)
	{
		if(! $type)
		{
			throw new \Exception('平台类型错误');
		}
		$model = new ModelBase('platform');
		$data = $model->getData('Name,PlatformId', array('EnameId'=> $this->enameId,'PlatformType'=> $type));
		return $data;
	}

	/**
	 * 检查平台名字
	 * 
	 * @param unknown $name
	 * @return Ambigous <\driver\mixed, \core\mixed>|boolean
	 */
	public function checkName($name,$type)
	{
		if($name)
		{
			$model = new ModelBase('platform');
			return $model->getData('PlatformId', array('Name'=> $name,'EnameId'=> $this->enameId,'PlatformType'=>$type), $model::FETCH_COLUMN);
		}
		return false;
	}

	/**
	 * 格式化平台列表
	 * 
	 * @param unknown $data
	 * @return unknown boolean
	 */
	public function formatSiteList($data)
	{
		if($data)
		{
			foreach($data as $key => $val)
			{
				$data[$key]->ClassTypeCn = \core\Config::item('site_type')->toArray()[$data[$key]->ClassType];
			}
			return $data;
		}
		return false;
	}
}