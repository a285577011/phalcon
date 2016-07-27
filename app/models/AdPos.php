<?php
use \core\ModelBase;

class AdPos extends ModelBase
{

	protected $table = "ad_pos";

	public function getPosIdByHaving($AgentId,$AgentType)
	{
		$num = count($AgentId);
		list($key, $parm) = self::arrayBind($AgentId, 'AgentId');
		$AgentId = implode(',', $key);
		$parm[':AgentType'] = $AgentType;
		$sql = 'select PosId from agent_pos where AgentId IN (' . $AgentId .
			 ') and AgentType=:AgentType group by PosId having count(PosId)=' . $num;
		$this->query($sql, $parm);
		return $this->getAll();
	}
}
	