<?php
namespace logic\user;
use core\ModelBase;
use lib\faq\FaqLib;

class Faq
{


	protected $model;

	public function __construct()
	{
		$this->model = new ModelBase('faq');
	}

	public function getFaq($type, $keyWord, $offset = 0, $pageSize = 20)
	{
		$lib = new FaqLib();
		$count = $lib->faqNum($type, $keyWord);
		if($count <= 0)
		{
			return array(TRUE, array(), 0);
		}
		$faqList = $lib->getList($type, $keyWord, $offset, $pageSize); // 常见问题
		$isEmpty = empty($faqList)? :FALSE;
		
		return array($isEmpty, $faqList, $count);
	}
	
	public function detail($id)
	{
		if(!$id)
		{
			throw new \Exception('请选择FAQ');
		}
		
		$fields = 'Id,Type,Title,Content,IsRecommend';
		$condition['Id'] = $id;
		$details = $this->model->getData($fields, $condition, ModelBase::FETCH_ROW);
		if(!$details)
		{
			throw new \Exception('该问题尚未编辑');
		}
		
		return $details;
	}
	
	public function recommendList($offset = 0, $pageSize = 5)
	{
		$fields = 'Id,Title,Content,IsRecommend';
		$condition['IsRecommend'] = 1;
		$limit = "{$offset},{$pageSize}";
		$orderBy = 'UpdateTime DESC';
		$list = $this->model->getData($fields, $condition, ModelBase::FETCH_ALL, $orderBy, $limit);
		
		return $list;
	}
}
