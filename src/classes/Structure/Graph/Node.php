<?php
declare(strict_types=1);

class Hymn_Structure_Graph_Node
{
	public Hymn_Structure_Module $module;
//	public Hymn_Structure_Graph $graph;
	public int $level;//	= 0;

	/** @var array<string,Hymn_Structure_Graph_Node> $in */
	public array $in;//		= [];

	/** @var array<string,Hymn_Structure_Graph_Node> $out */
	public array $out;//	= [];

	public function __construct( Hymn_Structure_Module $module, int $level = 0 )
	{
		$this->module	= $module;
		$this->level	= $level;
		$this->in		= [];
		$this->out		= [];
	}

	public function addIn( Hymn_Structure_Graph_Node $node ): self
	{
		$this->in[]		= $node;
		return $this;
	}

	public function addOut( Hymn_Structure_Graph_Node $node ): self
	{
		$this->out[]		= $node;
		return $this;
	}

}