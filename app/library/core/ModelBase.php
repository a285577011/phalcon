<?php
namespace core;
use \core\driver\PdoMysql;

class ModelBase
{

	/**
	 * 释放所有
	 *
	 * @var string
	 */
	const FETCH_ALL = 'fetchAll';

	/**
	 * 释放一行
	 *
	 * @var string
	 */
	const FETCH_ROW = 'fetch';

	/**
	 * 释放一列
	 *
	 * @var string
	 */
	const FETCH_COLUMN = 'fetchColumn';

	/**
	 * 预处理对象
	 *
	 * @var object
	 */
	private $stmt;

	/**
	 * 表名
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * 数据库对象
	 *
	 * @var \PDO
	 */
	protected $db;

	protected $sql;

	/**
	 * pdoSQL语句
	 *
	 * @var unknown
	 */
	protected $params = array();

	protected static $methods = array('count','sum','min','max','avg'); // 数据库常用的统计方法
	/**
	 * pdo绑定的参数
	 */
	/**
	 * 创建模型
	 */
	public function __construct($table = "", $key = 'default')
	{
		$this->db = PdoMysql::getInstance($key);
		$table && $this->table = $table;
	}

	/**
	 * 对象销毁
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->db = null;
	}

	/**
	 * 执行sql查询
	 *
	 * @param string sql语句
	 * @param array 参数数组
	 * @param string 返回结果绑定到的对象
	 * @param boolean 是否输出调试语句
	 * @return void
	 */
	public function query($sql, $params = array(), $class = 'stdClass', $debug = FALSE)
	{
		// 预处理绑定语句
		try
		{
			$this->sql = $sql;
			$this->params = $params;
			$this->stmt = $this->db->prepare($this->sql);
			// 参数绑定
			! $params or $this->bindValue();
			// 输出调试
			if($debug)
			{
				$this->getLastSql();
			}
			// 执行一条sql语句
			if($this->stmt->execute())
			{
				// 设置解析模式
				$this->stmt->setFetchMode(\PDO::FETCH_CLASS, $class);
			}
			else
			{
				// 获取数据库错误信息
				throw new \Exception(print_r($this->stmt->errorInfo(), TRUE));
			}
		}
		catch(\Exception $e)
		{
			 error_log(date('Y-m-d H:i:s').PHP_EOL.var_export(array('code'=>$e->getCode(), 'msg'=>$e->getMessage()), TRUE).PHP_EOL, 3, '/tmp/sql_error' . date('Y-m-d') . ".log");
			 throw new \Exception('system error', '49902');
		}
	}

	/**
	 * 参数与数据类型绑定
	 *
	 * @param array
	 */
	private function bindValue()
	{
		foreach($this->params as $key => $value)
		{
			// 数据类型选择
			switch(TRUE)
			{
				case is_int($value):
					$type = \PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = \PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = \PDO::PARAM_NULL;
					break;
				default:
					$type = \PDO::PARAM_STR;
			}
			// 参数绑定
			$this->stmt->bindValue($key, $value, $type);
		}
	}

	/**
	 * 获取所有记录集合
	 *
	 * @return \driver\mixed
	 */
	public function getAll()
	{
		return $this->fetch();
	}

	/**
	 * 获取一行记录
	 *
	 * @return \driver\mixed
	 */
	public function getRow()
	{
		return $this->fetch(self::FETCH_ROW);
	}

	/**
	 * 获取一个字段
	 *
	 * @return \driver\mixed
	 */
	public function getOne()
	{
		return $this->fetch(self::FETCH_COLUMN);
	}

	/**
	 * 解析数据库查询资源
	 *
	 * @param string fetchAll | fetch | fetchColumn
	 * @return mixed 查询得到返回数组或字符串,否则返回false或空数组
	 */
	private function fetch($func = self::FETCH_ALL)
	{
		// 执行释放
		$result = $this->stmt->$func();
		// 删除资源
		unset($this->stmt);
		// 返回结果
		return $result;
	}

	/**
	 * 获取上次插入的id
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->db->lastInsertId();
	}

	/**
	 * 返回插入|更新|删除影响的行数
	 *
	 * @return int
	 */
	public function affectRow()
	{
		return $this->stmt->rowCount();
	}

	/**
	 * 返回结果集中的行数
	 *
	 * @return int
	 */
	public function resourceCount()
	{
		return $this->stmt->columnCount();
	}

	/**
	 * 输出预绑定sql和参数列表
	 *
	 * @return void
	 */
	public function getLastSql()
	{
		$sql = $this->sql;
		foreach($this->params as $key => $placeholder)
		{
			// 字符串加上引号
			! is_string($placeholder) or ($placeholder = "'{$placeholder}'");
			// 替换
			$start = strpos($sql, $key);
			$end = strlen($key);
			$sql = substr_replace($sql, $placeholder, $start, $end);
		}
		
		echo  $sql;
	}

	/**
	 * 执行插入
	 *
	 * @param array 插入键值对数组
	 * @param boolean 是否输出调试语句
	 * @return int 上一次插入的id
	 */
	public final function insert(array $insert, $debug = FALSE, $class = 'stdClass')
	{
		// 所有key
		$keys = array_keys($insert);
		// 所有value
		$vals = array_values($insert);
		foreach($keys as $k => $v)
		{
			$values[":{$v}"] = $vals[$k];
		}
		
		$keys = implode(',', $keys);
		$placeholder = implode(',', array_keys($values));
		
		// sql语句
		$sql = "INSERT INTO {$this->table}({$keys}) VALUES ({$placeholder})";
		// 执行sql语句
		$this->query($sql, $values, $class, $debug);
		// 插入的id
		return $this->lastInsertId();
	}

	/**
	 * 执行更新
	 *
	 * @param array $update 键值对数组
	 * @param array | string $where where查询条件
	 * @param boolean $debug 是否输出调试语句
	 * @return int 影响行数
	 */
	public final function update(array $update, $where = array(), $debug = FALSE, $class = 'stdClass')
	{
		$i=0;
		foreach($update as $key => $val)
		{
			$set[] = "{$key}=:{$key}_{$i}";
			$values[":{$key}_{$i}"] = $val;
			$i++;
		}
		// set语句
		$set = implode(',', $set);
		// 获取sql子语句
		list($where, $values) = $this->where($where, $values);
		// sql语句
		$sql = "UPDATE {$this->table} SET {$set} {$where}";
		// 执行更新
		$this->query($sql, $values, $class, $debug);
		// 返回影响行数
		return $this->affectRow();
	}

	/**
	 * 拼接where子句 只支持and,or拼接
	 * $where['字段']=array('操作符号',值(多个值以数组),连接符(默认不填为and))或者$where['字段']=值;
	 *
	 * @param array $condition 键值对数组
	 * @param array $values 需要合并的数组
	 * @return array
	 */
	public final function where($condition, $values = array())
	{
		$data = array();
		$where = $op = '';
		foreach($condition as $key => $option)
		{
			if(! $option && ! is_int($option)) // false null array() ""的时候全部过滤
			{
				continue;
			}
			switch(true)
			{
				case is_array($option): // 数组值分析绑定
					list($whereStr, $dataBind) = $this->parseWhereByArray($key, $option, $op);
					$where .= $whereStr;
					$data = array_merge($data, $dataBind);
					break;
				case strpos($option, "%") !== FALSE: // like绑定
					$where .= " {$op} {$key} LIKE :{$key}";
					$data[":{$key}"] = $option;
					break;
				default: // 等于绑定
					$where .= " {$op} {$key} =:{$key}";
					$data[":{$key}"] = $option;
					break;
			}
			if(count($option) == 3)
			{
				$op = (strtoupper($option[2]) == 'AND' || strtoupper($option[2]) == 'OR')? strtoupper(trim($option[2])): 'AND';
			}
			else
			{
				$op = 'AND';
			}
		}
		$where = $where? " WHERE{$where} ": '';
		$values = array_merge($values, $data);
		return array($where,$values);
	}

	/**
	 * 数组绑定
	 *
	 * @param unknown $array 数组
	 * @param unknown $key 字段名
	 * @return array 绑定语句 绑定值
	 */
	public static function arrayBind($array = array(), $key)
	{
		$data = $temp = array();
		if(! empty($array))
		{
			foreach($array as $k => $val)
			{
				$temp[] = ":{$key}{$k}";
				$data[":{$key}{$k}"] = is_numeric($val)? $val: "'$val'";
			}
		}
		return array($temp,$data);
	}

	/**
	 * where值为数组分析
	 *
	 * @param unknown $key
	 * @param unknown $option
	 * @param unknown $op
	 */
	protected function parseWhereByArray($key, $option, $op)
	{
		$data = array();
		$whereStr='';
		if(preg_match('/^(IN|NOTIN|BETWEEN|>|<|>=|<=|<>|LIKE|NOTLIKE|=|!=|\^|\||&)$/i', @self::trimAll($option[0]))) // 数组第一个值是操作符
		{
			if((! $option[1] && ! is_int($option[1])))
			{
				return array('',array());
			}
			if(is_array($option[1])) // 值是数组
			{
				if(preg_match('/BETWEEN/i', self::trimAll($option[0]))) // between绑定
				{
					$whereStr = " {$op} {$key} BETWEEN :{$key}_1 AND :{$key}_2";
					$data[":{$key}_1"] = $option[1][0];
					$data[":{$key}_2"] = $option[1][1];
				}
				elseif(preg_match('/^(IN|NOTIN)$/i', self::trimAll($option[0]))) // 数组绑定(IN,NOTINT)
				{
					list($temp, $data) = self::arrayBind($option[1], $key);
					$whereStr = ' ' . $op . ' ' . $key . ' ' . strtoupper($option[0]) . ' (' . implode(',', $temp) . ')';
				}
			}
			else // 非数组或者数组只有一个值(操作符绑定)
			{
				$whereStr = ' ' . $op . ' ' . $key . ' ' . strtoupper($option[0]) . ' :' . $key;
				$data[":{$key}"] = $option[1];
			}
		}
		else // 不是操作符 IN绑定
		{
			list($temp, $data) = self::arrayBind($option, $key);
			$whereStr = ' ' . $op . ' ' . $key . ' IN ' . '(' . implode(',', $temp) . ')';
		}
		return array($whereStr,$data);
	}

	public static function trimAll($str) // 删除所有空格换行
	{
		$blank = array(" ","　","\t","\n","\r");
		$replace = '';
		return str_replace($blank, $replace, $str);
	}

	/**
	 * 分页
	 *
	 * @param string|array $limit 偏移量,数量，偏移量可以省略
	 * @param array $values
	 * @return multitype:string:array limit语句以及参数值
	 */
	public final function limit($limit, $values = array())
	{
		is_array($limit) or ($limit = explode(',', $limit));
		
		$offset = "";
		if((count($limit) == 2))
		{
			$offset = ":offset,";
			$values[':offset'] = ($limit[0] < 0? 0: (int)$limit[0]);
		}
		
		$number = ":number";
		$values[':number'] = (int)array_pop($limit);
		
		return array(" LIMIT {$offset}{$number}",$values);
	}

	/**
	 * 执行删除
	 *
	 * @param array $where
	 * @param string 默认只删除一条,设置为null表示删除所有匹配到的行
	 * @return int 影响行数
	 */
	public function delete($where, $limit = "LIMIT 1", $debug = FALSE, $class = 'stdClass')
	{
		list($where, $values) = $this->where($where);
		
		$sql = "DELETE FROM {$this->table} {$where} {$limit}";
		
		$this->query($sql, $values, $class, $debug);
		
		return $this->affectRow();
	}

	/**
	 * mysql 系统统计方法
	 */
	public function __call($method, $args)
	{
		if(in_array(strtolower($method), self::$methods))
		{
			$where = isset($args[0])? $args[0]: array();
			$field = isset($args[1])? $args[1]: '*';
			$class = isset($args[2])? $args[2]: 'stdClass';
			$debug = isset($args[3])? $args[3]: false;
			list($where, $values) = $this->where($where);
			$sql = 'SELECT ' . strtoupper($method) . '(' . $field . ') FROM ' . $this->table . ' ' . $where;
			$this->query($sql, $values, $class, $debug);
			return $this->getOne();
		}
	}

	/**
	 * 数据查询
	 *
	 * @param string $fields
	 * @param array $condition
	 * @param array $value
	 * @param string $class
	 * @param string $orderBy
	 * @param string $limit
	 * @param boolean $lock 是否加锁
	 * @return \driver\mixed
	 */
	public function getData($fields = '*', $condition = array(), $getType = self::FETCH_ALL, $orderBy = false, $limit = false, $groupBy = false, $lock = false, 
		$class = 'stdClass')
	{
		$where = '';
		$values = array();
		list($where, $values) = $this->where($condition);
		if(trim($groupBy))
		{
			$where .= ' GROUP BY ' . $groupBy;
		}
		if(trim($orderBy))
		{
			$where .= ' ORDER BY ' . $orderBy;
		}
		if($limit)
		{
			list($limitSql, $values) = $this->limit($limit, $values);
			$where .= $limitSql;
		}
		
		$sql = 'SELECT ' . $fields . ' FROM ' . $this->table . $where;
		if($lock)
		{
			$sql .= ' FOR UPDATE';
		}
		$this->query($sql, $values, $class);
		return $this->fetch($getType);
	}

	/**
	 * 启动事务
	 */
	public final function begin()
	{
		$this->db->beginTransaction();
	}

	/**
	 * 提交事务
	 */
	public final function commit()
	{
		$this->db->commit();
	}

	/**
	 * 回滚事务
	 */
	public final function rollback()
	{
		$this->db->rollBack();
	}

	/**
	 * 事务执行
	 *
	 * @param unknown $sql
	 */
	public final function exec($sql)
	{
		$this->db->exec($sql);
	}

	public function errorInfo()
	{
		$this->db->errorInfo();
	}
}
