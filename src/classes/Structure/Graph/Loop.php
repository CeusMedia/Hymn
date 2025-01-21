<?php
declare(strict_types=1);

class Hymn_Structure_Graph_Loop
{
	/** @var Hymn_Structure_Graph_Node $node */
	public Hymn_Structure_Graph_Node $node;

	/** @var Hymn_Structure_Graph_Node[] $modules */
	public array $steps		= [];

	/**
	 *	@param		Hymn_Structure_Graph_Node		$node
	 *	@param		Hymn_Structure_Graph_Node[]		$steps
	 */
	public function __construct( Hymn_Structure_Graph_Node $node, array $steps = [] )
	{
		$this->node = $node;
		$this->steps = $steps;
	}
}
