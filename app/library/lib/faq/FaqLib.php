<?php
namespace lib\faq;
use core\ModelBase;

class FaqLib
{

	protected $model;

	function __construct($table = '')
	{
		$this->model = new ModelBase($table);
	}

	/**
	 * 查找faq列表
	 *
	 * @param number $type
	 * @param string $keyWord
	 * @param number $offset
	 * @param number $pageSize
	 * @return multitype:
	 */
	public function getList($type = 0, $keyWord = '', $offset = 0, $pageSize = 20)
	{
		$where = '';
		$fields = 'Id,Title,Content,IsRecommend';
		$keyWord && ($where .= ! $where? "WHERE ": " AND") &&
			 ($where .= " (Content LIKE :keyWord1 OR Title LIKE :keyWord2)") && ($param[':keyWord1'] = "%$keyWord%") &&
			 ($param[':keyWord2'] = "%$keyWord%");
		if($type > 0 && $type < 6)
		{
			$where .= ! $where? "WHERE": " AND";
			$where .= " Type=:type";
			$param[':type'] = $type;
		}
		else
		{
			$where .= ! $where? "WHERE": " AND";
			$where .= " Type<6";
		}
		$limit = "LIMIT :offset,:pageSize";
		$orderBy = "ORDER BY Level DESC, Id DESC";
		$sql = "SELECT {$fields} FROM faq {$where} {$orderBy} {$limit}";
		$param[':offset'] = $offset;
		$param[':pageSize'] = $pageSize;
		
		$this->model->query($sql, $param);
		$faqList = $this->model->getAll();
		
		return array_filter($faqList);
	}

	/**
	 *
	 * @param number $type
	 * @param string $keyWord
	 * @return number
	 */
	public function faqNum($type = 0, $keyWord = '')
	{
		$where = '';
		$param = array();
		$keyWord && ($where .= ! $where? "WHERE ": " AND") &&
			 ($where .= " (Content LIKE :keyWord1 OR Title LIKE :keyWord2)") && ($param[':keyWord1'] = "%$keyWord%") &&
			 ($param[':keyWord2'] = "%$keyWord%");
		if($type > 0 && $type < 6)
		{
			$where .= ! $where? "WHERE": " AND";
			$where .= " Type=:type";
			$param[':type'] = $type;
		}
		else
		{
			$where .= ! $where? "WHERE": " AND";
			$where .= " Type<6";
		}
		$sql = "SELECT count(*) FROM faq {$where}";
		
		$this->model->query($sql, $param);
		$count = $this->model->getOne();
		
		return $count;
	}
}