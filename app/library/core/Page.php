<?php
namespace core;
use Phalcon\Mvc\Url;
class Page
{

	public $pageSize; // 页码大小
	public $nowPage = 1; // 当前页
	public $pageLink = ''; // 页码连接
	public $pageCount; // 总页数
	public $total; // 数据总数量
	public $parameter = array(); // URL参数数组
	public $rollPage = 5; // 分页栏每页显示的页数
	public function __construct($count,$pageSize)
	{
		unset($_GET['_url']);
		$this->pageSize=$pageSize;
		$this->total=$count;
		$this->parameter = ! empty($this->parameter)? $this->parameter: $_GET;
		$this->nowPage = isset($_GET['limit_start']) && $_GET['limit_start']? floor(intval($_GET['limit_start']) / $this->pageSize + 1): 1;
		$this->pageCount = ceil($this->total / $this->pageSize);
	}

	/**
	 *
	 * @param string $name
	 * @param unknown $value
	 */
	 public function __set($name, $value)
	{
		$this->$name = $value;
	}

	/**
	 * 设置url链接
	 *
	 * @param int $page
	 * @return string
	 */
	public function setUrl($page)
	{
		$url =new Url();
		$this->parameter['limit_start'] = ($page - 1) * $this->pageSize;
		return $url->get(CONTROLLER_NAME . '/' . ACTION_NAME,$this->parameter);
	}

	/**
	 * 拼接分页链接
	 *
	 * @return string
	 */
	public function show()
	{
		if($this->total == 0)
		{
			return '';
		}
		$this->nowPage > $this->pageCount && $this->nowPage = $this->pageCount;
		$this->nowPage < 1 && $this->nowPage = 1;
		
		/* 计算分页临时变量 */
		$now_cool_page = $this->rollPage / 2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->pageLink='<div class="art_page">';
		//($this->pageCount > $this->rollPage && $this->nowPage > $now_cool_page_ceil) &&
			// $this->pageLink .= "<a href='{$this->setUrl(1)}'>首页</a>&nbsp;";
		$this->nowPage>1&&$this->pageLink .= "<a class='pre_btn' href='{$this->setUrl($this->nowPage-1)}'>上一页</a>";
		if($this->pageCount > 1)
		{
			for($i = 1; $i <= $this->rollPage; $i++)
			{
				if($this->nowPage <= $now_cool_page)
				{
					$page = $i;
				}
				else
				{
					if($this->nowPage + $now_cool_page > $this->pageCount)
					{
						$page = $this->pageCount - $this->rollPage + $i;
					}
					else
					{
						$page = $this->nowPage - $now_cool_page_ceil + $i;
					}
				}
				if($page > 0 && $page != $this->nowPage)
				{
					if($page <= $this->pageCount)
					{
						$this->pageLink .= "<a href='{$this->setUrl($page)}'>{$page}</a>";
					}
					else
					{
						break;
					}
				}
				else
				{
					if($page > 0)
					{
						$this->pageLink .= "<a class='cur'>{$page}</a>";
					}
				}
			}
			$this->nowPage<$this->pageCount&&$this->pageLink .= "<a class='next_btn' href='{$this->setUrl($this->nowPage+1)}'>下一页</a>";
			//($this->pageCount > $this->rollPage && $this->nowPage + $now_cool_page < $this->pageCount) &&
				 //$this->nowPage > $now_cool_page &&
				// $this->pageLink .= "<a href='{$this->setUrl($this->pageCount)}'>末页</a>";
		}
		$this->pageLink .= "<span class='rows ml10'>共 {$this->total} 条记录&nbsp;" . $this->pageSize . "条/页&nbsp;</span>";
		$this->pageLink .= $this->pageCount > $this->rollPage? "
							<span>到第</span>
							<input type='text' name='limit_start' class='com_input page_txt pl-text w_30' value='' onKeyDown=\"bindEnter(event,this.value," .
									$this->pageSize .
									")\" />
							<span>页</span>
							<input type='button' class='page_btn' onclick=\"gotoPage(this," .
									$this->pageSize . ")\" value='确定' class='btnpage'/>
						": '';
		$this->pageLink.='</div>';
		return $this->pageLink;
	}
}